# Optimización aplicada

- Imagen principal convertida a AVIF y WebP con fallback PNG.
- Leaflet se carga bajo demanda mediante IntersectionObserver.
- Mapa cambiado de tema oscuro a CartoDB Positron claro.
- Chart.js usa defer.
- Preconnect y dns-prefetch para CDN externos.
- CSS/JS minificados y versionados.
- Caché anual para assets versionados y gzip en Nginx.
- Caché desactivada para la API y buffering activado.
- `content-visibility` para contenido fuera del viewport.

## Ejecución

```bash
docker compose down
docker compose build --no-cache frontend
docker compose up -d
```
