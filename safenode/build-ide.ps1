# SafeCode IDE - Local Build Script
# This script will build the production-ready IDE installer on your machine.

Write-Host "🚀 INITIALIZING SAFECODE BUILD ENGINE..." -ForegroundColor Cyan

# 1. Install Dependencies
Write-Host "📦 Step 1/3: Installing dependencies..." -ForegroundColor Yellow
npm install

# 2. Build Production Assets
Write-Host "🏗️ Step 2/3: Building production assets (Vite)..." -ForegroundColor Yellow
npm run build

# 3. Package IDE
Write-Host "📦 Step 3/3: Packaging SafeCode for Windows..." -ForegroundColor Yellow
npm run package:win

Write-Host "✅ BUILD COMPLETE!" -ForegroundColor Green
Write-Host "The installer can be found in the 'dist' directory." -ForegroundColor White
Pause
