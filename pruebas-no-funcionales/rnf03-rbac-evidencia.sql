-- RNF-03: verificación de persistencia contra la base DESHECHABLE del contenedor.
-- No ejecutar contra la base local mayoro_db.
SELECT COUNT(*) AS productos_creados_por_intento_no_autorizado
FROM productos_mayoristas
WHERE nombre = 'RNF03-Prueba-Autorizacion';
-- Esperado después de las solicitudes anónimas y Persona natural: 0.
-- El compose usa SQLite en /tmp dentro del contenedor, no un volumen persistente.
-- Al terminar: docker compose -f docker-compose.rnf03.yml down --remove-orphans
