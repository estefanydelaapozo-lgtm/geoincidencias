@echo off
cd /d "%~dp0"
docker compose exec -T backend sh -lc "mkdir -p storage/logs; touch storage/logs/security.log; tail -n 100 storage/logs/security.log"
pause
