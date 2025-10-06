# Script PowerShell para limpar TODO o Supabase do gerente.php

Write-Host "Limpando Supabase do gerente.php..." -ForegroundColor Yellow

$content = Get-Content -Path "gerente.php" -Raw

# Contar linhas originais
$linhasOriginais = ($content -split "`n").Count

# 1. Remover blocos de verificação do window.supabase
$content = $content -replace '(?s)if \(!window\.supabase\) \{.*?return;?\s*\}', ''
$content = $content -replace 'if \(!window\.supabase\) return;', ''

# 2. Remover await new Promise com supabase
$content = $content -replace '(?s)await new Promise\(resolve => setTimeout\(resolve, \d+\)\);\s*if \(!window\.supabase\).*?\}', ''

# 3. Remover comentários sobre Supabase/supabase
$content = $content -replace '//.*[Ss]upabase.*', ''

# 4. Remover console.logs com emojis
$content = $content -replace 'console\.log\(''[🔄📊✅❌⚠️🔍📋🔐🗑️🚀📝🎯📄🔧🐛].*?''\);', ''
$content = $content -replace 'console\.error\(''[🔄📊✅❌⚠️🔍📋🔐🗑️🚀📝🎯📄🔧🐛].*?''\);', ''

# 5. Limpar linhas vazias múltiplas
$content = $content -replace '(\r?\n){3,}', "`n`n"

# Salvar arquivo limpo
$content | Set-Content -Path "gerente_limpo_temp.php" -Encoding UTF8

$linhasLimpas = ($content -split "`n").Count

Write-Host "✅ Limpeza concluída!" -ForegroundColor Green
Write-Host "📊 Linhas originais: $linhasOriginais" -ForegroundColor Cyan
Write-Host "📊 Linhas limpas: $linhasLimpas" -ForegroundColor Cyan
Write-Host "📊 Linhas removidas: $($linhasOriginais - $linhasLimpas)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Arquivo salvo em: gerente_limpo_temp.php" -ForegroundColor Green

