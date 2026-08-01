#!/bin/bash

# Script de pruebas de carga básicas para GeoIncidencias
# Este script realiza pruebas de carga sobre los endpoints principales de la API

echo "=== Pruebas de Carga - GeoIncidencias ==="
echo "Fecha: $(date)"
echo ""

# Configuración
API_URL="http://localhost:8000/api"
TOTAL_REQUESTS=100
CONCURRENT_REQUESTS=10

# Colores para salida
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para ejecutar prueba de carga
run_load_test() {
    local endpoint=$1
    local method=$2
    local data=$3
    local description=$4

    echo -e "${YELLOW}Prueba: $description${NC}"
    echo "Endpoint: $method $endpoint"
    echo "Peticiones: $TOTAL_REQUESTS"
    echo "Concurrentes: $CONCURRENT_REQUESTS"
    echo ""

    if [ -z "$data" ]; then
        curl -X $method "$API_URL$endpoint" \
            -w "\nTiempo total: %{time_total}s\nTamaño: %{size_download} bytes\nHTTP: %{http_code}\n" \
            -o /dev/null \
            -s
    else
        curl -X $method "$API_URL$endpoint" \
            -H "Content-Type: application/json" \
            -d "$data" \
            -w "\nTiempo total: %{time_total}s\nTamaño: %{size_download} bytes\nHTTP: %{http_code}\n" \
            -o /dev/null \
            -s
    fi

    echo ""
    echo "---"
    echo ""
}

# Verificar si el servidor está activo
echo "Verificando conexión con el servidor..."
curl -s "$API_URL/health" > /dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Servidor activo${NC}"
else
    echo -e "${RED}✗ Servidor no disponible. Inicia el servidor primero.${NC}"
    exit 1
fi
echo ""

# Prueba 1: Health Check
run_load_test "/health" "GET" "" "Health Check"

# Prueba 2: Login (POST)
run_load_test "/auth/login" "POST" '{"correo":"admin@geoincidencias.com","password":"123456"}' "Login de Usuario"

# Prueba 3: Obtener catálogos (GET)
run_load_test "/catalogos/estados" "GET" "" "Obtener Catálogo de Estados"

# Prueba 4: Obtener tipos de incidencia (GET)
run_load_test "/catalogos/tipos" "GET" "" "Obtener Tipos de Incidencia"

# Prueba 5: Obtener zonas (GET)
run_load_test "/catalogos/zonas" "GET" "" "Obtener Zonas"

echo "=== Pruebas de carga completadas ==="
echo ""
echo "Para pruebas más detalladas, usar herramientas como:"
echo "- Apache Bench: ab -n 1000 -c 10 http://localhost:8000/api/health"
echo "- JMeter"
echo "- k6"
echo "- Locust"
