@echo off
title Gobierno de Datos IA - Inicio rapido
cd /d "%~dp0"

echo ============================================================
echo  Plataforma de Gobierno de Datos con Inteligencia Artificial
echo ============================================================
echo.

rem -- Verifica que PHP este disponible en el PATH --
where php >nul 2>nul
if errorlevel 1 (
    echo [ERROR] PHP no esta en el PATH.
    echo Si usas XAMPP, abre la consola y anade: C:\xampp\php
    echo o ejecuta: set PATH=%%PATH%%;C:\xampp\php
    echo.
    pause
    exit /b 1
)

rem -- Recordatorio: MySQL debe estar corriendo --
echo [INFO] Asegurate de que MySQL 8 este en ejecucion (servicio "MySQL80").
echo [INFO] La base "gobierno_datos_ia" ya debe existir con los datos seed.
echo.

echo [OK] Levantando el servidor en: http://127.0.0.1:8000
start "GDIA - PHP Server" cmd /k "php -S 127.0.0.1:8000 -t ."

rem -- Espera un momento y abre el navegador --
timeout /t 2 /nobreak >nul
start "" "http://127.0.0.1:8000"

echo [OK] Navegador abierto. Usuarios de prueba:
echo      admin@example.com  /  Admin123!
echo      steward@example.com  /  Steward123!
echo.
echo [INFO] Monitoreo continuo: programa cli\monitor.bat en el
echo        Programador de tareas de Windows para ejecuciones automaticas.
echo.
echo Para detener el servidor, cierra la ventana "GDIA - PHP Server".
echo.
timeout /t 8 /nobreak >nul