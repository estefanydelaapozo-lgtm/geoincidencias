@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo ================================================
echo  GEOINCIDENCIAS - VERSION FINAL PARA PRESENTAR
echo ================================================
echo.

echo [1/8] Cerrando proyectos anteriores...
docker compose down --remove-orphans --volumes 2>nul
docker rm -f geo_backend geo_frontend geo_backend_final geo_frontend_final 2>nul

echo [2/8] Eliminando imagenes antiguas del proyecto...
docker image rm proyecto-completo-backend proyecto-completo-frontend 2>nul

echo [3/8] Construyendo backend y frontend SIN CACHE...
docker compose build --no-cache --pull
if errorlevel 1 goto ERROR

echo [4/8] Levantando contenedores...
docker compose up -d --force-recreate
if errorlevel 1 goto ERROR

echo [5/8] Esperando servicios...
timeout /t 15 /nobreak >nul

echo [6/8] Verificando y creando estados requeridos...
docker compose exec -T backend php artisan migrate --force
if errorlevel 1 goto ERROR

echo [7/8] Verificando API y archivo JavaScript servido...
docker compose ps
curl -fsS http://localhost:8080/api/health
if errorlevel 1 goto ERROR
echo.
curl -fsS http://localhost:8080/js/auth-guard.js | findstr /C:"const API = '/api';" >nul
if errorlevel 1 goto ERROR
curl -fsS http://localhost:8080/js/auth-guard.js | findstr /C:"localhost:8000" >nul
if not errorlevel 1 goto ERROR_OLD_JS

echo [8/8] Verificando ruta POST...
docker compose exec -T backend php artisan route:list --path=incidencias | findstr /C:"POST" >nul
if errorlevel 1 goto ERROR

echo.
echo ================================================
echo  PROYECTO LISTO - API Y FRONTEND CORRECTOS
echo ================================================
echo.
echo La peticion de registro usara:
echo http://localhost:8080/api/incidencias
echo.
start "" "http://localhost:8080/login.html?v=20260720-final-real"
pause
exit /b 0

:ERROR_OLD_JS
echo.
echo ERROR: El contenedor sigue sirviendo JavaScript antiguo.
docker compose logs --tail=150
pause
exit /b 1

:ERROR
echo.
echo ERROR AL LEVANTAR O VERIFICAR EL PROYECTO
docker compose logs --tail=150
pause
exit /b 1
