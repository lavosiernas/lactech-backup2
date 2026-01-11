# Script de Deploy - lactech-backup2
# Gera um ZIP pronto para subir na hospedagem EXCLUINDO node_modules, vendor e .git

$zipName = "deploy_$(Get-Date -Format 'yyyyMMdd_HHmm').zip"
$excludeList = @(
    "node_modules",
    "vendor",
    ".git",
    ".github",
    "dist",
    "build",
    "cache",
    "tmp",
    "*.sql",
    ".env*",
    "create-deploy-zip.ps1"
)

Write-Host "Iniciando criação do pacote de deploy..." -ForegroundColor Cyan

# Remove ZIP antigo se existir
if (Test-Path $zipName) { Remove-Item $zipName }

# Cria o ZIP filtrando os arquivos
Get-ChildItem -Path . -Exclude $excludeList -Recurse | Where-Object { 
    $path = $_.FullName
    $shouldExclude = $false
    foreach ($exclude in $excludeList) {
        if ($path -like "*\$exclude*" -or $path -like "*\$exclude") {
            $shouldExclude = $true
            break
        }
    }
    !$shouldExclude
} | Compress-Archive -DestinationPath $zipName -Force

Write-Host "Sucesso! Arquivo pronto: $zipName" -ForegroundColor Green
Write-Host "Agora você pode subir apenas este arquivo ZIP na sua hospedagem e extraí-lo lá." -ForegroundColor Yellow
