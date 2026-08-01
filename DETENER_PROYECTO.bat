@echo off
cd /d "%~dp0"
docker compose down --remove-orphans
pause
