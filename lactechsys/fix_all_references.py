#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir TODAS as referências incorretas nos arquivos HTML
"""

import os
import re

def fix_file(file_path):
    """Corrige todas as referências incorretas em um arquivo"""
    print(f"🔧 Corrigindo: {file_path}")
    
    # Ler o arquivo
    with open(file_path, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # Substituições necessárias
    replacements = [
        # Corrigir tabelas
        (r"\.from\('milk_production'\)", ".from('volume_records')"),
        (r"\.from\('lactech_users'\)", ".from('users')"),
        
        # Corrigir queries de email para id
        (r"\.eq\('email', user\.email\)", ".eq('id', user.id)"),
        (r"\.eq\('email', authUser\.email\)", ".eq('id', authUser.id)"),
        
        # Corrigir campos
        (r"'shift'", "'milking_type'"),
        (r"record\.shift", "record.milking_type"),
        (r"'receita'", "'income'"),
        (r"'despesa'", "'expense'"),
        
        # Corrigir valores de turno
        (r"'manha'", "'morning'"),
        (r"'tarde'", "'afternoon'"),
        (r"'noite'", "'evening'"),
        (r"'madrugada'", "'night'"),
        
        # Corrigir referências em joins
        (r"lactech_users\(name, email\)", "users(name, email)"),
        (r"lactech_users\(name\)", "users(name)"),
    ]
    
    # Aplicar todas as substituições
    for pattern, replacement in replacements:
        content = re.sub(pattern, replacement, content)
    
    # Salvar o arquivo
    with open(file_path, 'w', encoding='utf-8') as file:
        file.write(content)
    
    print(f"✅ Corrigido: {file_path}")

def main():
    """Corrige todos os arquivos HTML"""
    print("🚀 INICIANDO CORREÇÃO DE TODOS OS ARQUIVOS...")
    
    # Lista de arquivos para corrigir
    files_to_fix = [
        'lactechsys/gerente.html',
        'lactechsys/funcionario.html', 
        'lactechsys/proprietario.html',
        'lactechsys/PrimeiroAcesso.html',
        'lactechsys/login.html'
    ]
    
    # Corrigir cada arquivo
    for file_path in files_to_fix:
        if os.path.exists(file_path):
            fix_file(file_path)
        else:
            print(f"⚠️ Arquivo não encontrado: {file_path}")
    
    print("\n🎯 CORREÇÕES APLICADAS:")
    print("📊 milk_production → volume_records")
    print("👥 lactech_users → users")
    print("📧 user.email → user.id")
    print("🔄 shift → milking_type")
    print("💰 receita → income")
    print("⏰ manha/tarde/noite → morning/afternoon/evening")
    print("\n✅ TODOS OS ARQUIVOS CORRIGIDOS!")

if __name__ == "__main__":
    main()
