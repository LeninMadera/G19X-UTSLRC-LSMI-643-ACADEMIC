@echo off
rem ============================================================
rem  Monitoreo continuo - ejecutor para el Programador de tareas
rem  de Windows (o ejecucion manual desde consola).
rem
rem  Deja las opciones que quieras pasar a cli\monitor.php:
rem    (sin opciones) ejecuta las configuraciones vencidas
rem    --run-id=ID   fuerza la configuracion ID
rem    --list        muestra el estado de las configuraciones
rem ============================================================
cd /d "%~dp0\.."

where php >nul 2>nul
if errorlevel 1 (
    echo [ERROR] PHP no esta en el PATH.
    echo Si usas XAMPP, anade: set PATH=%%PATH%%;C:\xampp\php
    exit /b 1
)

php cli\monitor.php %*
exit /b %errorlevel%