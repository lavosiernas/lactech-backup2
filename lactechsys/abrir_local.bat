@echo off
title LacTech - Sistema Local
echo.
echo ============================================
echo   LACTECH - ABRINDO SISTEMA LOCAL
echo ============================================
echo.
echo Abrindo sistema em HTTP (desenvolvimento local)...
echo.

REM Abrir em HTTP (não HTTPS)
start "" "http://localhost/GitHub/lactech-backup2/lactechsys/login.php"

echo Sistema aberto no navegador!
echo.
echo Se der erro de privacidade:
echo 1. Clique em "Avançadas" 
echo 2. Clique em "Prosseguir para localhost (não seguro)"
echo.
echo Ou pressione Ctrl + F5 para recarregar
echo.
pause

