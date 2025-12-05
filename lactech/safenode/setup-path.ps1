# Script para adicionar Composer e PHP ao PATH permanentemente
# Execute como Administrador: PowerShell -ExecutionPolicy Bypass -File setup-path.ps1

$composerPath = "C:\ProgramData\ComposerSetup\bin"
$phpPath = "C:\xampp1\php"

# Verificar se já está no PATH do sistema
$systemPath = [Environment]::GetEnvironmentVariable("Path", "Machine")

if ($systemPath -notlike "*$composerPath*") {
    Write-Host "Adicionando Composer ao PATH do sistema..." -ForegroundColor Yellow
    [Environment]::SetEnvironmentVariable("Path", "$systemPath;$composerPath", "Machine")
    Write-Host "✓ Composer adicionado ao PATH" -ForegroundColor Green
} else {
    Write-Host "✓ Composer já está no PATH" -ForegroundColor Green
}

if ($systemPath -notlike "*$phpPath*") {
    Write-Host "Adicionando PHP ao PATH do sistema..." -ForegroundColor Yellow
    $systemPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
    [Environment]::SetEnvironmentVariable("Path", "$systemPath;$phpPath", "Machine")
    Write-Host "✓ PHP adicionado ao PATH" -ForegroundColor Green
} else {
    Write-Host "✓ PHP já está no PATH" -ForegroundColor Green
}

Write-Host "`nReinicie o terminal para aplicar as mudanças!" -ForegroundColor Cyan

