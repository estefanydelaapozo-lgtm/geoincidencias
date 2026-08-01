@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo ================================================
echo  GEOINCIDENCIAS - EVIDENCIAS DE CALIDAD
echo ================================================
echo.

echo [1/4] Estado de los contenedores...
docker compose ps
if errorlevel 1 goto ERROR

echo.
echo [2/4] Rutas funcionales de incidencias...
docker compose exec -T backend php artisan route:list --path=incidencias
if errorlevel 1 goto ERROR

echo.
echo [3/4] Pruebas automatizadas Laravel...
docker compose exec -T backend php artisan test
if errorlevel 1 echo ATENCION: alguna prueba fallo; toma captura y revisa el detalle.

echo.
echo [4/4] Prueba basica de 50 solicitudes al healthcheck...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$ok=0;$err=0;$times=@();1..50|%%{$sw=[Diagnostics.Stopwatch]::StartNew();try{$r=Invoke-WebRequest -UseBasicParsing http://localhost:8080/api/health -TimeoutSec 15;if($r.StatusCode -eq 200){$ok++}else{$err++}}catch{$err++};$sw.Stop();$times+=$sw.ElapsedMilliseconds};$avg=[math]::Round(($times|Measure-Object -Average).Average,2);$max=($times|Measure-Object -Maximum).Maximum;Write-Host ('Exitosas: '+$ok);Write-Host ('Errores: '+$err);Write-Host ('Promedio ms: '+$avg);Write-Host ('Maximo ms: '+$max)"

echo.
echo Guarda una captura de esta ventana como evidencia.
pause
exit /b 0

:ERROR
echo Error: primero ejecuta LEVANTAR_PROYECTO.bat
pause
exit /b 1
