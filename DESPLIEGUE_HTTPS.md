# Desplegar en tu VPS con HTTPS

## 1. Antes de nada, en tu proveedor de dominio (GoDaddy, Namecheap, etc.)
Creá un registro DNS tipo **A** que apunte tu dominio (o subdominio, ej.
`geoincidencias.tudominio.com`) a la **IP pública de tu VPS**. Esto puede
tardar unos minutos en propagarse.

## 2. En este proyecto, antes de subirlo
Reemplazá `tudominio.com` por tu dominio real en estos 2 archivos:
- `Caddyfile` (la línea que dice `tudominio.com {`)
- `backend-laravel/.env.docker` (la línea `APP_URL=https://tudominio.com`)

## 3. Subir el proyecto al VPS
Copiá toda la carpeta `geofinalbuild` a tu servidor (por SCP, SFTP, git, lo
que uses normalmente) y entrá por SSH.

## 4. Abrir los puertos necesarios
En tu VPS, asegurate de que el firewall deje pasar **80** y **443** (además
del 22 de SSH). Por ejemplo, con `ufw`:
```
sudo ufw allow 80
sudo ufw allow 443
```

## 5. Levantar todo
Parado en la carpeta `geofinalbuild`:
```
docker compose up -d --build
```

Caddy va a detectar automáticamente que `tudominio.com` no es `localhost`,
va a pedir el certificado HTTPS a Let's Encrypt solo, y en un par de minutos
tu sitio va a estar disponible en:

```
https://tudominio.com
```

No hace falta abrir el puerto 8080 ni el 8000 al público — esos quedan solo
para acceso interno/depuración; todo el tráfico real entra por Caddy en
80/443 y se renueva el certificado automáticamente cada vez que hace falta.

## Si algo falla
Mirá los logs de Caddy específicamente:
```
docker compose logs caddy
```
El error más común es que el DNS todavía no haya propagado, o que el
firewall del VPS (o del proveedor, si es un firewall aparte como el de
DigitalOcean/AWS) esté bloqueando el 80/443 desde afuera.
