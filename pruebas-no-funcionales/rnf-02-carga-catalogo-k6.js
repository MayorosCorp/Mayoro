import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

/**
 * RNF-02 — Capacidad de carga del catálogo público de Mayoro.
 *
 * Este script define una carga progresiva de 1 a 100 VUs. No se ejecuta al
 * guardarlo. Por defecto apunta a localhost:8000; verifica TARGET antes de
 * ejecutarlo y nunca uses producción sin autorización expresa.
 */
const BASE_URL = (__ENV.TARGET || 'http://localhost:8000').replace(/\/$/, '');
const CATEGORY = __ENV.CATEGORY || 'Abarrotes';
const SEARCH_TERM = __ENV.SEARCH_TERM || 'arroz';

const failedRequests = new Rate('failed_requests');
const catalogLatency = new Trend('catalog_latency', true);
const searchLatency = new Trend('search_latency', true);
const categoryLatency = new Trend('category_latency', true);

export const options = {
  stages: [
    { duration: __ENV.RAMP_DURATION || '2m', target: 25 },
    { duration: __ENV.RAMP_DURATION || '2m', target: 50 },
    { duration: __ENV.RAMP_DURATION || '2m', target: 75 },
    { duration: __ENV.RAMP_DURATION || '2m', target: 100 },
    { duration: __ENV.HOLD_DURATION || '5m', target: 100 },
    { duration: __ENV.RAMP_DOWN_DURATION || '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    failed_requests: ['rate<0.01'],
    checks: ['rate>0.99'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

export default function () {
  const catalog = http.get(`${BASE_URL}/products`, {
    tags: { scenario: 'catalog', endpoint: 'products.index' },
  });
  catalogLatency.add(catalog.timings.duration);
  check(catalog, {
    'catálogo responde HTTP 200': (response) => response.status === 200,
  });
  failedRequests.add(catalog.status !== 200);

  const search = http.get(`${BASE_URL}/products`, {
    params: { q: SEARCH_TERM },
    tags: { scenario: 'search', endpoint: 'products.search' },
  });
  searchLatency.add(search.timings.duration);
  check(search, {
    'búsqueda responde HTTP 200': (response) => response.status === 200,
  });
  failedRequests.add(search.status !== 200);

  const category = http.get(`${BASE_URL}/products`, {
    params: { categoria: CATEGORY },
    tags: { scenario: 'category', endpoint: 'products.category' },
  });
  categoryLatency.add(category.timings.duration);
  check(category, {
    'filtro por categoría responde HTTP 200': (response) => response.status === 200,
  });
  failedRequests.add(category.status !== 200);

  // Pausa breve entre iteraciones para modelar navegación y limitar el ritmo
  // por usuario. El total real de solicitudes depende de tiempos y thresholds.
  sleep(1);
}

export function handleSummary(data) {
  const report = JSON.stringify(data, null, 2);
  return {
    'rnf-02-k6-summary.json': report,
    stdout: `RNF-02 finalizado. Resumen JSON: rnf-02-k6-summary.json\n`,
  };
}
