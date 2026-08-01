# Script de pruebas de carga básicas para GeoIncidencias (PowerShell)
# Este script realiza pruebas de carga sobre los endpoints principales de la API

Write-Host "=== Pruebas de Carga - GeoIncidencias ===" -ForegroundColor Cyan
Write-Host "Fecha: $(Get-Date)" -ForegroundColor Cyan
Write-Host ""

# Configuración
$API_URL = "http://localhost:8000/api"

# Función para ejecutar prueba de carga
function Run-LoadTest {
    param(
        [string]$endpoint,
        [string]$method,
        [string]$data,
        [string]$description
    )

    Write-Host "Prueba: $description" -ForegroundColor Yellow
    Write-Host "Endpoint: $method $endpoint"
    Write-Host ""

    try {
        if ([string]::IsNullOrEmpty($data)) {
            $response = Invoke-RestMethod -Uri "$API_URL$endpoint" -Method $method -TimeoutSec 30
            Write-Host "✓ Exitoso" -ForegroundColor Green
        } else {
            $response = Invoke-RestMethod -Uri "$API_URL$endpoint" -Method $method -Body $data -ContentType "application/json" -TimeoutSec 30
            Write-Host "✓ Exitoso" -ForegroundColor Green
        }
    } catch {
        Write-Host "✗ Error: $($_.Exception.Message)" -ForegroundColor Red
    }

    Write-Host ""
    Write-Host "---"
    Write-Host ""
}

# Verificar si el servidor está activo
Write-Host "Verificando conexión con el servidor..."
try {
    $health = Invoke-RestMethod -Uri "$API_URL/health" -Method GET -TimeoutSec 5
    Write-Host "✓ Servidor activo" -ForegroundColor Green
} catch {
    Write-Host "✗ Servidor no disponible. Inicia el servidor primero." -ForegroundColor Red
    exit 1
}
Write-Host ""

# Prueba 1: Health Check
Run-LoadTest "/health" "GET" "" "Health Check"

# Prueba 2: Login (POST)
Run-LoadTest "/auth/login" "POST" '{"correo":"admin@geoincidencias.com","password":"123456"}' "Login de Usuario"

# Prueba 3: Obtener catálogos (GET)
Run-LoadTest "/catalogos/estados" "GET" "" "Obtener Catálogo de Estados"

# Prueba 4: Obtener tipos de incidencia (GET)
Run-LoadTest "/catalogos/tipos" "GET" "" "Obtener Tipos de Incidencia"

# Prueba 5: Obtener zonas (GET)
Run-LoadTest "/catalogos/zonas" "GET" "" "Obtener Zonas"

Write-Host "=== Pruebas de carga completadas ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para pruebas más detalladas, usar herramientas como:" -ForegroundColor White
Write-Host "- Apache Bench: ab -n 1000 -c 10 http://localhost:8000/api/health" -ForegroundColor Gray
Write-Host "- JMeter" -ForegroundColor Gray
Write-Host "- k6" -ForegroundColor Gray
Write-Host "- Locust" -ForegroundColor Gray
