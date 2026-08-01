@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul
cd /d "%~dp0"
title GeoIncidencias - Instalador y Lanzador

set "SITE_URL=http://localhost:8080/login.html"
set "API_URL=http://localhost:8080/api/health"

echo ======================================================
echo      GEOINCIDENCIAS - LEVANTAR TODO AUTOMATICO
echo ======================================================
echo.

where docker >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Docker no esta instalado o no esta en el PATH.
  echo Instala o abre Docker Desktop y vuelve a ejecutar este archivo.
  goto :FAIL
)

docker info >nul 2>&1
if errorlevel 1 (
  echo [INFO] Docker Desktop no esta listo. Intentando abrirlo...
  if exist "%ProgramFiles%\Docker\Docker\Docker Desktop.exe" start "" "%ProgramFiles%\Docker\Docker\Docker Desktop.exe"
  echo Esperando a Docker Desktop...
  for /L %%I in (1,1,60) do (
    timeout /t 2 /nobreak >nul
    docker info >nul 2>&1 && goto :DOCKER_READY
  )
  echo [ERROR] Docker Desktop no inicio dentro del tiempo esperado.
  goto :FAIL
)

:DOCKER_READY
echo [OK] Docker esta funcionando.

echo.
echo [1/7] Deteniendo contenedores anteriores sin borrar los datos...
docker compose down --remove-orphans
if errorlevel 1 goto :FAIL

echo.
echo [2/7] Construyendo backend y frontend con las correcciones...
docker compose build --pull
if errorlevel 1 goto :FAIL

echo.
echo [3/7] Levantando los servicios...
docker compose up -d --force-recreate
if errorlevel 1 goto :FAIL

echo.
echo [4/7] Esperando a que el backend este saludable...
set "READY=0"
for /L %%I in (1,1,60) do (
  docker compose ps --format json 2>nul | findstr /I "healthy" >nul && (
    set "READY=1"
    goto :BACKEND_READY
  )
  timeout /t 2 /nobreak >nul
)

:BACKEND_READY
if "%READY%"=="0" (
  echo [ERROR] El backend no alcanzo el estado healthy.
  docker compose ps
  docker compose logs --tail=200 backend
  goto :FAIL
)
echo [OK] Backend saludable.

echo.
echo [5/7] Aplicando migraciones, limpiando cache y preparando storage...
docker compose exec -T backend sh -lc "mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache && touch storage/logs/security.log storage/logs/laravel.log && chmod -R 777 storage bootstrap/cache"
if errorlevel 1 goto :FAIL

docker compose exec -T backend php artisan optimize:clear
if errorlevel 1 goto :FAIL

docker compose exec -T backend php artisan migrate --force
if errorlevel 1 goto :FAIL

docker compose exec -T backend php artisan storage:link 2>nul

echo.
echo [6/7] Verificando comandos, rutas y API...
docker compose exec -T backend php artisan list | findstr /R /C:" test " /C:"test  Run the application tests" >nul
if errorlevel 1 (
  echo [AVISO] El comando artisan test no fue detectado. Revisa los logs de construccion.
) else (
  echo [OK] El comando php artisan test esta disponible.
)

curl -fsS "%API_URL%" >nul
if errorlevel 1 (
  echo [ERROR] La API no respondio en %API_URL%
  docker compose logs --tail=200
  goto :FAIL
)
echo [OK] API disponible.

echo.
echo [7/7] Estado final de los contenedores:
docker compose ps

echo.
echo ======================================================
echo     PROYECTO LISTO
echo ======================================================
echo Frontend: http://localhost:8080
echo Backend:  http://localhost:8000
echo Log seguridad: storage/logs/security.log
echo.
echo IMPORTANTE: No se ejecutan pruebas automaticamente porque
 echo IncidenciasTest usa RefreshDatabase y podria reiniciar la BD Aiven.
echo.
start "" "%SITE_URL%"
pause
exit /b 0

:FAIL
echo.
echo ======================================================
echo ERROR AL LEVANTAR GEOINCIDENCIAS
echo ======================================================
docker compose ps 2>nul
docker compose logs --tail=200 2>nul
pause
exit /b 1
