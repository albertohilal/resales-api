# Pruebas E2E con Playwright

## Cómo ejecutar localmente

1. Configura las variables de entorno:
   ```bash
   export BASIC_USER="b3t0h"
   export BASIC_PASS="elgeneral2018"
   export BASE_URL="https://lussogroup.es"
   ```
2. Ejecuta las pruebas:
   ```bash
   npm run test:real
   ```
3. Para ver el reporte HTML:
   ```bash
   npx playwright show-report
   ```

## Scripts útiles
- `npm run test:real`: Ejecuta las pruebas E2E en Chromium.
- `npm run test:ui`: Abre el UI de Playwright para seleccionar y correr tests manualmente.

## Notas
- Las credenciales de Basic Auth se leen de las variables de entorno.
- Las trazas y screenshots se guardan automáticamente en caso de fallo.
