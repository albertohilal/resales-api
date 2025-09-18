# Resales API

## Instalación y activación
1. Copia la carpeta `resales` en `wp-content/plugins/`.
2. Activa el plugin desde el panel de WordPress.

## Configuración de credenciales
- Ve a **Ajustes → Resales API**.
- Ingresa tu Contact/Client ID (P1) y API Key (P2).
- Guarda los cambios. Si faltan credenciales, se mostrará un aviso.

## Diagnóstico
- En el submenú **Diagnóstico API** puedes ver la Outbound IP del servidor y variables de red.
- Usa el botón “Probar conexión” para verificar acceso a la WebAPI V6. Se mostrará el código HTTP, tiempo de respuesta, tamaño y un extracto del JSON.

## Ejemplos de shortcode
```
[resales_developments agency_id="4" per_page="6"]
[resales_developments agency_id="1" per_page="12"]
[resales_developments agency_id="2"]
[resales_developments agency_id="3"]
```

## Notas de seguridad
- La API Key (P2) nunca se expone en el frontend, solo se usa server-side.
- Evita cachés agresivas en la página del shortcode para asegurar datos frescos y evitar errores de autenticación.

## Soporte y contribuciones
Para reportar bugs o sugerir mejoras, abre un issue en este repositorio.

---