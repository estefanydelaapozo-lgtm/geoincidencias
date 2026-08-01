@echo off
cd /d "%~dp0"
docker compose restart
start "" "http://localhost:8080/login.html"
pause
