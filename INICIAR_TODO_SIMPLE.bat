@echo off
chcp 65001 >nul
cd /d "%~dp0"
title GeoIncidencias - Iniciar

echo ================================================
echo   GEOINCIDENCIAS - Iniciar todo
echo ================================================
echo.

echo [1/5] Apagando lo que estuviera corriendo antes...
docker compose down

echo.
echo [2/5] Reconstruyendo el proyecto (puede tardar varios minutos la primera vez)...
docker compose build
if errorlevel 1 goto ERROR

echo.
echo [3/5] Levantando los servicios...
docker compose up -d
if errorlevel 1 goto ERROR

echo.
echo [4/5] Esperando a que el backend arranque...
timeout /t 20 /nobreak >nul

echo.
echo [5/5] Aplicando cambios en la base de datos...
docker compose exec -T backend php artisan migrate --force
if errorlevel 1 goto ERROR

echo.
echo ================================================
echo   LISTO. Abriendo el sistema en el navegador...
echo ================================================
start "" "http://localhost:8080/login.html"
pause
exit /b 0

:ERROR
echo.
echo ================================================
echo   ALGO FALLO. Copia TODO este mensaje (desde
echo   aqui hacia arriba) y enviamelo para revisarlo.
echo ================================================
docker compose logs --tail=150
pause
exit /b 1
