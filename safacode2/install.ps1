# SafeCode IDE Professional Installer for Windows
# Usage: irm https://safenode.cloud/safecode/install.ps1 | iex
# Or: powershell -ExecutionPolicy ByPass -c "irm https://safenode.cloud/safecode/install.ps1 | iex"

$ErrorActionPreference = 'Stop'

# Configuration
$installDir = Join-Path $env:USERPROFILE "SafeCode"
$repoUrl = "https://github.com/safenode/safecode.git"
$repoName = "safecode"

# Colors
function Write-Step($msg) {
    Write-Host "`n[🚀] $msg" -ForegroundColor Cyan
}

function Write-Success($msg) {
    Write-Host "[✅] $msg" -ForegroundColor Green
}

function Write-Error-Custom($msg) {
    Write-Host "[❌] $msg" -ForegroundColor Red
}

function Write-Info($msg) {
    Write-Host "[ℹ️] $msg" -ForegroundColor Yellow
}

try {
    Write-Host "`n╔════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║   SafeCode IDE Installer v1.0.0        ║" -ForegroundColor Cyan
    Write-Host "╚════════════════════════════════════════╝" -ForegroundColor Cyan
    Write-Host "`nElevating your workspace...`n" -ForegroundColor Gray

    # Check prerequisites
    Write-Step "Checking prerequisites..."
    
    # Check Node.js
    try {
        $nodeVersion = node --version
        Write-Success "Node.js found: $nodeVersion"
    } catch {
        Write-Error-Custom "Node.js is not installed. Please install Node.js 18+ from https://nodejs.org"
        exit 1
    }

    # Check npm
    try {
        $npmVersion = npm --version
        Write-Success "npm found: v$npmVersion"
    } catch {
        Write-Error-Custom "npm is not installed. Please install npm."
        exit 1
    }

    # Check Git
    try {
        $gitVersion = git --version
        Write-Success "Git found: $gitVersion"
    } catch {
        Write-Error-Custom "Git is not installed. Please install Git from https://git-scm.com"
        exit 1
    }

    # 1. Create Installation Directory
    Write-Step "Setting up installation directory..."
    if (Test-Path $installDir) {
        Write-Info "Installation directory already exists: $installDir"
        $overwrite = Read-Host "Do you want to reinstall? This will remove the existing installation. (y/N)"
        if ($overwrite -eq "y" -or $overwrite -eq "Y") {
            Remove-Item -Path $installDir -Recurse -Force
            Write-Success "Removed existing installation"
        } else {
            Write-Info "Installation cancelled."
            exit 0
        }
    }
    
    New-Item -ItemType Directory -Path $installDir -Force | Out-Null
    Write-Success "Installation directory created: $installDir"

    # 2. Clone Repository
    Write-Step "Downloading SafeCode IDE from repository..."
    $repoPath = Join-Path $installDir $repoName
    
    try {
        git clone $repoUrl $repoPath
        Write-Success "Repository cloned successfully"
    } catch {
        Write-Error-Custom "Failed to clone repository. Please check your internet connection and Git installation."
        exit 1
    }

    # Navigate to project directory
    # Check if safacode2 subdirectory exists (for monorepo structure)
    $projectDir = Join-Path $repoPath "safacode2"
    if (!(Test-Path $projectDir)) {
        # If safacode2 doesn't exist, check if package.json is in root
        if (Test-Path (Join-Path $repoPath "package.json")) {
            $projectDir = $repoPath
        } else {
            Write-Error-Custom "Could not find project directory. Please check the repository structure."
            exit 1
        }
    }
    
    Push-Location $projectDir

    # 3. Install Dependencies
    Write-Step "Installing dependencies (this may take a few minutes)..."
    try {
        npm install
        Write-Success "Dependencies installed successfully"
    } catch {
        Write-Error-Custom "Failed to install dependencies. Please check your internet connection."
        Pop-Location
        exit 1
    }

    # 4. Create Startup Script
    Write-Step "Creating startup script..."
    $startScript = Join-Path $installDir "start-safecode.ps1"
    $startScriptContent = @"
# SafeCode IDE Startup Script
`$ErrorActionPreference = 'Stop'

`$projectDir = "$projectDir"
Push-Location `$projectDir

Write-Host "Starting SafeCode IDE..." -ForegroundColor Cyan
npm run dev

Pop-Location
"@
    
    Set-Content -Path $startScript -Value $startScriptContent
    Write-Success "Startup script created: $startScript"

    # 5. Create Desktop Shortcut (Optional)
    Write-Step "Creating desktop shortcut..."
    try {
        $desktopPath = [Environment]::GetFolderPath("Desktop")
        $shortcutPath = Join-Path $desktopPath "SafeCode IDE.lnk"
        
        $WshShell = New-Object -ComObject WScript.Shell
        $Shortcut = $WshShell.CreateShortcut($shortcutPath)
        $Shortcut.TargetPath = "powershell.exe"
        $Shortcut.Arguments = "-NoExit -ExecutionPolicy Bypass -File `"$startScript`""
        $Shortcut.WorkingDirectory = $projectDir
        $Shortcut.IconLocation = Join-Path $projectDir "public\logos (6).png"
        $Shortcut.Description = "SafeCode IDE - Professional Development Environment"
        $Shortcut.Save()
        
        Write-Success "Desktop shortcut created"
    } catch {
        Write-Info "Could not create desktop shortcut (this is optional)"
    }

    # 6. Create Batch File for Easy Access
    Write-Step "Creating batch file for easy access..."
    $batchFile = Join-Path $installDir "SafeCode.bat"
    $batchContent = @"
@echo off
cd /d "$projectDir"
echo Starting SafeCode IDE...
start "" powershell.exe -NoExit -ExecutionPolicy Bypass -File "$startScript"
"@
    
    Set-Content -Path $batchFile -Value $batchContent
    Write-Success "Batch file created: $batchFile"

    Pop-Location

    # 7. Finalizing
    Write-Host "`n╔════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "║   Installation Complete! ✅             ║" -ForegroundColor Green
    Write-Host "╚════════════════════════════════════════╝" -ForegroundColor Green
    
    Write-Host "`n📁 Installation Location: $projectDir" -ForegroundColor White
    Write-Host "`n🚀 To start SafeCode IDE:" -ForegroundColor Cyan
    Write-Host "   1. Double-click 'SafeCode.bat' in: $installDir" -ForegroundColor Yellow
    Write-Host "   2. Or run: cd `"$projectDir`" && npm run dev" -ForegroundColor Yellow
    Write-Host "   3. Or use the desktop shortcut (if created)" -ForegroundColor Yellow
    
    Write-Host "`n📖 Documentation: https://safenode.cloud/safecode/docs.html" -ForegroundColor White
    Write-Host "🐛 Issues: https://github.com/safenode/safecode/issues" -ForegroundColor White
    
    Write-Host "`nPress any key to exit..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

} catch {
    Write-Error-Custom "Installation failed: $_"
    Write-Host "`nIf you encounter issues, please report them at:" -ForegroundColor Yellow
    Write-Host "https://github.com/safenode/safecode/issues" -ForegroundColor Yellow
    exit 1
}

