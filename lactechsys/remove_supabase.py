#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para remover TODAS as chamadas Supabase do gerente.php
e substituir por código MySQL/localStorage
"""

import re

# Ler o arquivo
with open('gerente.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Substituir "const supabase = await getSupabaseClient();" por comentário
content = re.sub(
    r'const supabase = await getSupabaseClient\(\);',
    '// const supabase = await getSupabaseClient(); // MySQL',
    content
)

# 2. Substituir chamadas .from() por retorno vazio
content = re.sub(
    r'const \{ data: (\w+), error: \w+ \} = await supabase\.from\([^)]+\)\.select\([^)]+\)[^;]*;',
    r'const \1 = []; // MySQL stub',
    content
)

# 3. Substituir .auth.getUser() por localStorage
content = re.sub(
    r'const \{ data: \{ user \} \} = await supabase\.auth\.getUser\(\);',
    'const user = JSON.parse(localStorage.getItem("user_data") || "null"); // MySQL',
    content
)

# 4. Substituir .rpc() por retorno vazio
content = re.sub(
    r'const \{ data: (\w+), error: \w+ \} = await supabase\.rpc\([^)]+\)[^;]*;',
    r'const \1 = null; // MySQL stub',
    content
)

# Salvar
with open('gerente.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Supabase removido com sucesso!")
print(f"📝 Arquivo atualizado: {len(content)} caracteres")




