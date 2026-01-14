# SafeCode IDE Professional Installer for Windows
# Usage: irm https://safenode.cloud/safecode/install.ps1 | iex

$ErrorActionPreference = 'Stop'

# Configuration
$installDir = Join-Path $HOME ".local\bin"
$exeName = "safecode.exe"
$targetPath = Join-Path $installDir $exeName
$downloadUrl = "https://github.com/safenode/safecode/releases/latest/download/safecode.exe"

function Write-Step($msg) {
    Write-Host "`n[🚀] $msg" -ForegroundColor Cyan
}

function Write-Success($msg) {
    Write-Host "[✅] $msg" -ForegroundColor Green
}

function Write-Error-Custom($msg) {
    Write-Host "[❌] $msg" -ForegroundColor Red
}

try {
    Write-Host "`n--- SafeCode IDE Installer ---" -ForegroundColor White -BackgroundColor DarkBlue
    Write-Host "Elevating your workspace...`n" -ForegroundColor Gray

    # 1. Create Directory
    if (!(Test-Path $installDir)) {
        Write-Step "Creating installation directory: $installDir"
        New-Item -ItemType Directory -Path $installDir -Force | Out-Null
    }

    # 2. Download Binary
    Write-Step "Downloading SafeCode IDE..."
    # Note: Use -UseBasicParsing if inside a constrained environment
    Invoke-WebRequest -Uri $downloadUrl -OutFile $targetPath -MaximumRetryCount 3

    # 3. Add to PATH
    Write-Step "Configuring Environment Variables..."
    $currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
    if ($currentPath -notlike "*$installDir*") {
        $newPath = "$currentPath;$installDir"
        [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
        Write-Success "SafeCode added to User PATH."
    }
    else {
        Write-Success "SafeCode already in PATH."
    }

    # 4. Finalizing
    Write-Success "Installation Complete!"
    Write-Host "`n------------------------------------------------" -ForegroundColor Gray
    Write-Host "You can now run SafeCode by typing 'safecode' in any terminal." -ForegroundColor White
    Write-Host "Restart your terminal for PATH changes to take effect." -ForegroundColor Yellow
    Write-Host "------------------------------------------------`n" -ForegroundColor Gray

}
catch {
    Write-Error-Custom "An error occurred during installation: $($_.Exception.Message)"
    exit 1
}
