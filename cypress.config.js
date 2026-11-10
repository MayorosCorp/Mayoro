import { defineConfig } from 'cypress';

/**
 * Configuración E2E de Mayoro.
 *
 * El destino se controla con la variable de entorno `CYPRESS_baseUrl`:
 *   - Local:  http://localhost:8000 (valor por defecto)
 *   - CI:     http://mayoro-app:8080 (contenedor desplegado por Jenkins)
 *
 * En Jenkins los tests corren con `xvfb-run` porque el agente es headless.
 */
export default defineConfig({
    e2e: {
        baseUrl: process.env.CYPRESS_baseUrl || 'http://localhost:8000',
        specPattern: 'cypress/e2e/**/*.cy.js',
        supportFile: 'cypress/support/e2e.js',
        viewportWidth: 1280,
        viewportHeight: 800,
        video: false,
        screenshotOnRunFailure: true,
        // Un reintento absorbe fallos de red puntuales contra el contenedor recién desplegado.
        retries: {
            runMode: 1,
            openMode: 0,
        },
    },
});
