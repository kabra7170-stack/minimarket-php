@echo off
echo Iniciando MiniMarket G2 API...
cd /d C:\Users\Deurys\Downloads\xampp\htdocs\minimarket\api_fastapi
py -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload
pause
