#!/usr/bin/env python3
"""
Script para remover TODAS as referências ao Supabase do gerente.php
e substituir por stubs MySQL
"""

import re

# Ler o arquivo
with open('gerente.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Lista de padrões para remover/substituir
replacements = [
    # Remover verificações de window.supabase
    (r'if \(!window\.supabase\) \{[^}]+\}', ''),
    (r'if \(!window\.supabase\) return;', ''),
    
    # Remover await new Promise com supabase
    (r'await new Promise\(resolve => setTimeout\(resolve, \d+\)\);\s*if \(!window\.supabase\) \{[^}]+\}', ''),
    
    # Remover console.logs excessivos
    (r'console\.log\(\'[🔄📊✅❌⚠️🔍📋🔐🗑️🚀📝🎯📄🔧🐛]\s*[^\']+\'\);', ''),
    (r'console\.error\(\'[🔄📊✅❌⚠️🔍📋🔐🗑️🚀📝🎯📄🔧🐛]\s*[^\']+\'\);', ''),
    
    # Remover comentários sobre Supabase
    (r'// .*[Ss]upabase.*\n', '\n'),
    (r'console\.log\(.*Supabase.*\);', ''),
]

# Aplicar substituições
for pattern, replacement in replacements:
    content = re.sub(pattern, replacement, content, flags=re.MULTILINE | re.DOTALL)

# Salvar arquivo limpo
with open('gerente_limpo.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Arquivo limpo criado: gerente_limpo.php")
print(f"📊 Tamanho original: {len(open('gerente.php', 'r', encoding='utf-8').read())} chars")
print(f"📊 Tamanho limpo: {len(content)} chars")
print(f"📊 Redução: {len(open('gerente.php', 'r', encoding='utf-8').read()) - len(content)} chars")

