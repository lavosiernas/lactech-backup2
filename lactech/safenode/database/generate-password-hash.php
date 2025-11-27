<?php
/**
 * Script para gerar hash de senha para o novo usuário admin
 * 
 * Como usar:
 * 1. Altere a variável $senha abaixo com a senha desejada
 * 2. Execute este arquivo no navegador ou via linha de comando: php generate-password-hash.php
 * 3. Copie o hash gerado e cole no script REPLACE_ADMIN_USER.sql
 */

// ============================================
// CONFIGURE AQUI A SENHA DO NOVO ADMIN
// ============================================
$senha = 'SuaSenhaSegura123!@#'; // ALTERE AQUI COM A SENHA DESEJADA

// ============================================
// NÃO ALTERE NADA ABAIXO
// ============================================

if (php_sapi_name() === 'cli') {
    // Executando via linha de comando
    echo "\n";
    echo "========================================\n";
    echo "Gerador de Hash de Senha - SafeNode\n";
    echo "========================================\n\n";
    
    if ($senha === 'SuaSenhaSegura123!@#') {
        echo "ERRO: Por favor, altere a variável \$senha no arquivo antes de executar!\n";
        exit(1);
    }
    
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    echo "Senha informada: " . $senha . "\n";
    echo "Hash gerado: " . $hash . "\n\n";
    echo "========================================\n";
    echo "Copie o hash acima e cole no script REPLACE_ADMIN_USER.sql\n";
    echo "========================================\n\n";
} else {
    // Executando via navegador
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gerador de Hash de Senha - SafeNode</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background: #0a0a0a;
                color: #fff;
            }
            .container {
                background: #1a1a1a;
                padding: 30px;
                border-radius: 10px;
                border: 1px solid #333;
            }
            h1 {
                color: #4ade80;
                margin-bottom: 20px;
            }
            .warning {
                background: #fbbf24;
                color: #000;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                font-weight: bold;
            }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 8px;
                color: #ccc;
                font-weight: bold;
            }
            input[type="password"], input[type="text"] {
                width: 100%;
                padding: 12px;
                border: 1px solid #444;
                border-radius: 5px;
                background: #2a2a2a;
                color: #fff;
                font-size: 16px;
            }
            input[type="password"]:focus, input[type="text"]:focus {
                outline: none;
                border-color: #4ade80;
            }
            button {
                background: #4ade80;
                color: #000;
                padding: 12px 30px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.3s;
            }
            button:hover {
                background: #22c55e;
            }
            .result {
                margin-top: 30px;
                padding: 20px;
                background: #2a2a2a;
                border-radius: 5px;
                border: 1px solid #444;
            }
            .hash {
                background: #1a1a1a;
                padding: 15px;
                border-radius: 5px;
                font-family: 'Courier New', monospace;
                word-break: break-all;
                color: #4ade80;
                margin-top: 10px;
                border: 1px solid #333;
            }
            .copy-btn {
                background: #3b82f6;
                color: #fff;
                padding: 8px 15px;
                font-size: 14px;
                margin-top: 10px;
            }
            .copy-btn:hover {
                background: #2563eb;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Gerador de Hash de Senha</h1>
            
            <div class="warning">
                ⚠️ ATENÇÃO: Este script gera o hash da senha para o novo usuário admin. 
                Use uma senha forte e segura!
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="senha">Digite a senha do novo admin:</label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="Digite uma senha forte (mín. 8 caracteres, maiúsculas, minúsculas, números e símbolos)">
                </div>
                
                <button type="submit">Gerar Hash</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
                $senhaInput = $_POST['senha'];
                
                // Validar senha forte
                $errors = [];
                if (strlen($senhaInput) < 8) {
                    $errors[] = "A senha deve ter no mínimo 8 caracteres";
                }
                if (!preg_match('/[A-Z]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos uma letra maiúscula";
                }
                if (!preg_match('/[a-z]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos uma letra minúscula";
                }
                if (!preg_match('/[0-9]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos um número";
                }
                if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos um caractere especial (!@#$%^&*)";
                }
                
                if (!empty($errors)) {
                    echo '<div class="result" style="border-color: #ef4444;">';
                    echo '<h3 style="color: #ef4444;">❌ Erros na senha:</h3>';
                    echo '<ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                } else {
                    $hash = password_hash($senhaInput, PASSWORD_DEFAULT);
                    
                    echo '<div class="result">';
                    echo '<h3 style="color: #4ade80;">✅ Hash gerado com sucesso!</h3>';
                    echo '<p><strong>Copie o hash abaixo e cole no script REPLACE_ADMIN_USER.sql:</strong></p>';
                    echo '<div class="hash" id="hashOutput">' . htmlspecialchars($hash) . '</div>';
                    echo '<button class="copy-btn" onclick="copyHash()">📋 Copiar Hash</button>';
                    echo '</div>';
                    ?>
                    <script>
                        function copyHash() {
                            const hash = document.getElementById('hashOutput').textContent;
                            navigator.clipboard.writeText(hash).then(function() {
                                alert('Hash copiado para a área de transferência!');
                            });
                        }
                    </script>
                    <?php
                }
            }
            ?>
        </div>
    </body>
    </html>
    <?php
}
?>


/**
 * Script para gerar hash de senha para o novo usuário admin
 * 
 * Como usar:
 * 1. Altere a variável $senha abaixo com a senha desejada
 * 2. Execute este arquivo no navegador ou via linha de comando: php generate-password-hash.php
 * 3. Copie o hash gerado e cole no script REPLACE_ADMIN_USER.sql
 */

// ============================================
// CONFIGURE AQUI A SENHA DO NOVO ADMIN
// ============================================
$senha = 'SuaSenhaSegura123!@#'; // ALTERE AQUI COM A SENHA DESEJADA

// ============================================
// NÃO ALTERE NADA ABAIXO
// ============================================

if (php_sapi_name() === 'cli') {
    // Executando via linha de comando
    echo "\n";
    echo "========================================\n";
    echo "Gerador de Hash de Senha - SafeNode\n";
    echo "========================================\n\n";
    
    if ($senha === 'SuaSenhaSegura123!@#') {
        echo "ERRO: Por favor, altere a variável \$senha no arquivo antes de executar!\n";
        exit(1);
    }
    
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    echo "Senha informada: " . $senha . "\n";
    echo "Hash gerado: " . $hash . "\n\n";
    echo "========================================\n";
    echo "Copie o hash acima e cole no script REPLACE_ADMIN_USER.sql\n";
    echo "========================================\n\n";
} else {
    // Executando via navegador
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gerador de Hash de Senha - SafeNode</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background: #0a0a0a;
                color: #fff;
            }
            .container {
                background: #1a1a1a;
                padding: 30px;
                border-radius: 10px;
                border: 1px solid #333;
            }
            h1 {
                color: #4ade80;
                margin-bottom: 20px;
            }
            .warning {
                background: #fbbf24;
                color: #000;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                font-weight: bold;
            }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 8px;
                color: #ccc;
                font-weight: bold;
            }
            input[type="password"], input[type="text"] {
                width: 100%;
                padding: 12px;
                border: 1px solid #444;
                border-radius: 5px;
                background: #2a2a2a;
                color: #fff;
                font-size: 16px;
            }
            input[type="password"]:focus, input[type="text"]:focus {
                outline: none;
                border-color: #4ade80;
            }
            button {
                background: #4ade80;
                color: #000;
                padding: 12px 30px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.3s;
            }
            button:hover {
                background: #22c55e;
            }
            .result {
                margin-top: 30px;
                padding: 20px;
                background: #2a2a2a;
                border-radius: 5px;
                border: 1px solid #444;
            }
            .hash {
                background: #1a1a1a;
                padding: 15px;
                border-radius: 5px;
                font-family: 'Courier New', monospace;
                word-break: break-all;
                color: #4ade80;
                margin-top: 10px;
                border: 1px solid #333;
            }
            .copy-btn {
                background: #3b82f6;
                color: #fff;
                padding: 8px 15px;
                font-size: 14px;
                margin-top: 10px;
            }
            .copy-btn:hover {
                background: #2563eb;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Gerador de Hash de Senha</h1>
            
            <div class="warning">
                ⚠️ ATENÇÃO: Este script gera o hash da senha para o novo usuário admin. 
                Use uma senha forte e segura!
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="senha">Digite a senha do novo admin:</label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="Digite uma senha forte (mín. 8 caracteres, maiúsculas, minúsculas, números e símbolos)">
                </div>
                
                <button type="submit">Gerar Hash</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
                $senhaInput = $_POST['senha'];
                
                // Validar senha forte
                $errors = [];
                if (strlen($senhaInput) < 8) {
                    $errors[] = "A senha deve ter no mínimo 8 caracteres";
                }
                if (!preg_match('/[A-Z]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos uma letra maiúscula";
                }
                if (!preg_match('/[a-z]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos uma letra minúscula";
                }
                if (!preg_match('/[0-9]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos um número";
                }
                if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $senhaInput)) {
                    $errors[] = "A senha deve conter pelo menos um caractere especial (!@#$%^&*)";
                }
                
                if (!empty($errors)) {
                    echo '<div class="result" style="border-color: #ef4444;">';
                    echo '<h3 style="color: #ef4444;">❌ Erros na senha:</h3>';
                    echo '<ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                } else {
                    $hash = password_hash($senhaInput, PASSWORD_DEFAULT);
                    
                    echo '<div class="result">';
                    echo '<h3 style="color: #4ade80;">✅ Hash gerado com sucesso!</h3>';
                    echo '<p><strong>Copie o hash abaixo e cole no script REPLACE_ADMIN_USER.sql:</strong></p>';
                    echo '<div class="hash" id="hashOutput">' . htmlspecialchars($hash) . '</div>';
                    echo '<button class="copy-btn" onclick="copyHash()">📋 Copiar Hash</button>';
                    echo '</div>';
                    ?>
                    <script>
                        function copyHash() {
                            const hash = document.getElementById('hashOutput').textContent;
                            navigator.clipboard.writeText(hash).then(function() {
                                alert('Hash copiado para a área de transferência!');
                            });
                        }
                    </script>
                    <?php
                }
            }
            ?>
        </div>
    </body>
    </html>
    <?php
}
?>


