# Troubleshooting Resales API

- **403 “Missing Authentication Token”**: Se usó GET en vez de POST, o el body/URL es incorrecto.
- **401/403 con POST**: Credenciales P1/P2 erróneas o la IP del servidor no está whitelisteada en Resales Online.
- **502/504**: Latencia, timeout o endpoint incorrecto (verifica que la URL incluya `/V6/Search`).
- **cURL error 6**: Problema de DNS en el hosting. Prueba la conexión desde la terminal del servidor y contacta soporte si persiste.
- **Aumentar timeout**: Edita el parámetro `timeout` en el cliente API (`wp_remote_get`).
- **Loguear respuesta**: Usa `error_log($response)` o plugins de debug para registrar el cuerpo de la respuesta y analizar errores.
