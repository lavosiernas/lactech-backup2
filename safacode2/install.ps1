# SafeCode IDE by SafeNode - Online Launcher
# Usage: irm https://safenode.cloud/safecode/install.ps1 | iex

$ErrorActionPreference = 'Stop'

# Configuration
$ideUrl = "https://safenode.cloud/safecode"

# Clear screen
Clear-Host

# Clean Design Functions
function Write-Banner {
    Write-Host ""
    Write-Host "  " -NoNewline
    Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor DarkGray
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor DarkGray
    Write-Host "                                                           " -NoNewline
    Write-Host "║" -ForegroundColor DarkGray
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor DarkGray
    Write-Host "         " -NoNewline
    Write-Host "SAFECODE" -NoNewline -ForegroundColor Cyan
    Write-Host " by SafeNode" -NoNewline -ForegroundColor DarkGray
    Write-Host " | KRON Technologies" -NoNewline -ForegroundColor Gray
    Write-Host "         ║" -ForegroundColor DarkGray
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor DarkGray
    Write-Host "                                                           " -NoNewline
    Write-Host "║" -ForegroundColor DarkGray
    Write-Host "  " -NoNewline
    Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor DarkGray
    Write-Host ""
}

function Write-Step($msg) {
    Write-Host "  " -NoNewline
    Write-Host "→" -NoNewline -ForegroundColor Cyan
    Write-Host " $msg" -ForegroundColor White
}

function Write-Success($msg) {
    Write-Host "  " -NoNewline
    Write-Host "✓" -NoNewline -ForegroundColor Green
    Write-Host " $msg" -ForegroundColor Green
}

function Write-Info($msg) {
    Write-Host "  " -NoNewline
    Write-Host "ℹ" -NoNewline -ForegroundColor Yellow
    Write-Host " $msg" -ForegroundColor Yellow
}

function Write-Section($title) {
    Write-Host ""
    Write-Host "  " -NoNewline
    Write-Host "$title" -ForegroundColor Cyan
    Write-Host "  " -NoNewline
    Write-Host ("─" * 59) -ForegroundColor DarkGray
    Write-Host ""
}

try {
    # Set console encoding to UTF-8 for proper character display
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    
    # Banner
    Write-Banner
    
    Write-Host "  " -NoNewline
    Write-Host "Abrindo SafeCode IDE no navegador..." -ForegroundColor DarkGray
    Start-Sleep -Milliseconds 500
    Write-Host ""

    # Open IDE in Browser
    Write-Section "Iniciando SafeCode IDE"
    
    Write-Step "Abrindo navegador..."
    Start-Sleep -Milliseconds 300
    
    try {
        Start-Process $ideUrl
        Write-Success "Navegador aberto com sucesso"
    } catch {
        Write-Host "  " -NoNewline
        Write-Host "⚠" -NoNewline -ForegroundColor Yellow
        Write-Host " Não foi possível abrir o navegador automaticamente." -ForegroundColor Yellow
        Write-Host ""
        Write-Info "Abra manualmente no navegador: $ideUrl"
    }

    Write-Host ""
    Write-Section "Informações"
    
    Write-Host "  " -NoNewline
    Write-Host "🌐" -NoNewline -ForegroundColor Cyan
    Write-Host " URL: " -NoNewline -ForegroundColor Gray
    Write-Host "$ideUrl" -ForegroundColor White
    
    Write-Host ""
    Write-Section "Recursos"
    
    Write-Host "  " -NoNewline
    Write-Host "📖" -NoNewline -ForegroundColor Cyan
    Write-Host " Documentação: " -NoNewline -ForegroundColor Gray
    Write-Host "https://safenode.cloud/safecode/lp/docs.html" -ForegroundColor White
    
    Write-Host ""
    Write-Host "  " -NoNewline
    Write-Host "🐛" -NoNewline -ForegroundColor Cyan
    Write-Host " Suporte: " -NoNewline -ForegroundColor Gray
    Write-Host "https://github.com/safenode/safecode/issues" -ForegroundColor White
    
    Write-Host ""
    Write-Host "  " -NoNewline
    Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor Green
    Write-Host "                                                           " -NoNewline
    Write-Host "║" -ForegroundColor Green
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor Green
    Write-Host "              " -NoNewline
    Write-Host "SafeCode IDE Aberto com Sucesso!" -NoNewline -ForegroundColor White
    Write-Host "              ║" -ForegroundColor Green
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor Green
    Write-Host "                                                           " -NoNewline
    Write-Host "║" -ForegroundColor Green
    Write-Host "  " -NoNewline
    Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "  " -NoNewline
    Write-Host "Pressione qualquer tecla para sair..." -ForegroundColor DarkGray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

} catch {
    Write-Host ""
    Write-Host "  " -NoNewline
    Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor Red
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor Red
    Write-Host "                                                           " -NoNewline
    Write-Host "║" -ForegroundColor Red
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor Red
    Write-Host "                    " -NoNewline
    Write-Host "Erro ao Abrir IDE" -NoNewline -ForegroundColor White
    Write-Host "                    ║" -ForegroundColor Red
    Write-Host "  " -NoNewline
    Write-Host "║" -NoNewline -ForegroundColor Red
    Write-Host "                                                           " -NoNewline
    Write-Host "║" -ForegroundColor Red
    Write-Host "  " -NoNewline
    Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor Red
    
    Write-Host ""
    Write-Host "  " -NoNewline
    Write-Host "✗" -NoNewline -ForegroundColor Red
    Write-Host " Erro: $_" -ForegroundColor Red
    Write-Host ""
    Write-Info "Abra manualmente no navegador: $ideUrl"
    Write-Host ""
    Write-Info "Reporte problemas em: https://github.com/safenode/safecode/issues"
    Write-Host ""
    exit 1
}
