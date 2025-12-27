# Script para baixar e instalar Ollama no Windows
Write-Host "=== Instalador Ollama para SafeNode ===" -ForegroundColor Cyan
Write-Host ""

# Verificar se já está instalado
if (Get-Command ollama -ErrorAction SilentlyContinue) {
    Write-Host "Ollama já está instalado!" -ForegroundColor Green
    ollama --version
    exit 0
}

# URL de download
$downloadUrl = "https://ollama.com/download/windows"
$downloadPath = "$env:USERPROFILE\Downloads\OllamaSetup.exe"

Write-Host "Baixando Ollama..." -ForegroundColor Yellow
try {
    # Baixar o instalador
    Invoke-WebRequest -Uri $downloadUrl -OutFile $downloadPath -UseBasicParsing
    
    $fileSize = (Get-Item $downloadPath).Length / 1MB
    Write-Host "Download concluído! ($([math]::Round($fileSize, 2)) MB)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Localização: $downloadPath" -ForegroundColor Gray
    Write-Host ""
    
    # Perguntar se quer instalar agora
    Write-Host "Deseja instalar agora? (S/N)" -ForegroundColor Yellow
    $response = Read-Host
    
    if ($response -eq "S" -or $response -eq "s" -or $response -eq "") {
        Write-Host "Iniciando instalação..." -ForegroundColor Yellow
        Start-Process -FilePath $downloadPath -Wait
        Write-Host ""
        Write-Host "Instalação concluída!" -ForegroundColor Green
        Write-Host ""
        
        # Verificar se foi instalado
        Start-Sleep -Seconds 2
        if (Get-Command ollama -ErrorAction SilentlyContinue) {
            Write-Host "Ollama instalado com sucesso!" -ForegroundColor Green
            Write-Host ""
            Write-Host "Agora baixe um modelo com:" -ForegroundColor Cyan
            Write-Host "  ollama pull llama3.2:1b" -ForegroundColor White
        } else {
            Write-Host "Por favor, reinicie o terminal e execute novamente." -ForegroundColor Yellow
        }
    } else {
        Write-Host ""
        Write-Host "Arquivo salvo em: $downloadPath" -ForegroundColor Gray
        Write-Host "Execute-o manualmente para instalar." -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "Erro ao baixar: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Tente baixar manualmente em:" -ForegroundColor Yellow
    Write-Host "https://ollama.com/download/windows" -ForegroundColor Cyan
}





