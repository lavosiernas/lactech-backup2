@echo off
TITLE SafeCode IDE - Precision Studio Launcher
SETLOCAL EnableDelayedExpansion

:: --- CONFIGURATION ---
SET "REQ_FILE=package.json"

echo ===================================================
echo    SAFECODE IDE - THE PRECISION STUDIO
echo ===================================================
echo.

:: 1. Check for Node.js
echo [1/4] Verificando Node.js...
node -v >nul 2>&1
if !errorlevel! neq 0 (
    echo.
    echo [ERRO] O Node.js nao esta instalado ou nao foi encontrado no PATH.
    echo Por favor, instale o Node.js em: https://nodejs.org
    echo.
    echo Se voce ja instalou, tente reiniciar o computador.
    echo.
    pause
    exit /b
)
echo OK: Node.js detectado.

:: 2. Check for Project Files (The most common error)
echo [2/4] Verificando integridade do projeto...
if not exist "%REQ_FILE%" (
    echo.
    echo [ERRO] O arquivo %REQ_FILE% nao foi encontrado nesta pasta.
    echo.
    echo ATENCAO:
    echo Voce provavelmente baixou este arquivo na pasta "Downloads".
    echo Para funcionar, voce PRECISA mover este arquivo (START_SAFECODE.bat)
    echo para dentro da pasta raiz do seu projeto SafeCode.
    echo.
    echo Pasta atual: %CD%
    echo.
    pause
    exit /b
)
echo OK: Arquivos do projeto encontrados.

:: 3. Install Dependencies
echo [3/4] Inicializando Precision Engine (npm install)...
echo Isso pode levar um minuto na primeira execucao...
call npm install --no-audit --no-fund
if !errorlevel! neq 0 (
    echo.
    echo [ERRO] Falha ao instalar as dependencias.
    echo Verifique sua conexao com a internet.
    echo.
    pause
    exit /b
)

:: 4. Launch IDE
echo [4/4] Iniciando SafeCode Studio...
echo.
echo Abrindo...
call npm start
if !errorlevel! neq 0 (
    echo.
    echo [AVISO] 'npm start' falhou. Tentando inicio direto via Electron...
    npx electron .
    if !errorlevel! neq 0 (
        echo.
        echo [ERRO] Nao foi possivel iniciar a IDE.
        echo Tente rodar 'npm start' manualmente no terminal para ver o erro real.
        echo.
        pause
    )
)

echo.
echo Sessao SafeCode Studio encerrada.
pause
ENDLOCAL
