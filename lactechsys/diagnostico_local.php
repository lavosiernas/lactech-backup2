<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - LacTech Local</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .check-item {
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 2px solid transparent;
        }
        .check-item.success {
            border-color: #10b981;
            background: #ecfdf5;
        }
        .check-item.error {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .check-item.warning {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        .icon {
            font-size: 24px;
            min-width: 24px;
        }
        .info {
            flex: 1;
        }
        .label {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        .value {
            color: #666;
            font-size: 14px;
        }
        .code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .summary {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .summary-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .summary-text {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .summary-sub {
            color: #666;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico do Sistema Local</h1>
        <p class="subtitle">Verificando configuração do LacTech no XAMPP</p>

        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $checks = [];
        $errors = 0;
        $warnings = 0;
        $success = 0;

        // 1. Verificar PHP
        echo '<div class="section">';
        echo '<div class="section-title">🐘 PHP</div>';
        
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.4', '>=');
        $checks[] = [
            'status' => $phpOk ? 'success' : 'warning',
            'label' => 'Versão do PHP',
            'value' => $phpVersion . ($phpOk ? ' ✅' : ' ⚠️ Recomendado 7.4+')
        ];
        $phpOk ? $success++ : $warnings++;
        
        $pdoEnabled = extension_loaded('pdo_mysql');
        $checks[] = [
            'status' => $pdoEnabled ? 'success' : 'error',
            'label' => 'Extensão PDO MySQL',
            'value' => $pdoEnabled ? 'Habilitada ✅' : 'NÃO habilitada ❌'
        ];
        $pdoEnabled ? $success++ : $errors++;
        
        foreach ($checks as $check) {
            echo '<div class="check-item ' . $check['status'] . '">';
            echo '<div class="icon">' . ($check['status'] == 'success' ? '✅' : ($check['status'] == 'error' ? '❌' : '⚠️')) . '</div>';
            echo '<div class="info">';
            echo '<div class="label">' . $check['label'] . '</div>';
            echo '<div class="value">' . $check['value'] . '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // 2. Verificar Arquivos de Configuração
        $checks = [];
        echo '<div class="section">';
        echo '<div class="section-title">📁 Arquivos de Configuração</div>';
        
        $configFile = 'includes/config_mysql.php';
        $configExists = file_exists($configFile);
        $checks[] = [
            'status' => $configExists ? 'success' : 'error',
            'label' => 'config_mysql.php',
            'value' => $configExists ? 'Encontrado ✅' : 'NÃO encontrado ❌'
        ];
        $configExists ? $success++ : $errors++;
        
        $loginFile = 'includes/config_login.php';
        $loginExists = file_exists($loginFile);
        $checks[] = [
            'status' => $loginExists ? 'success' : 'error',
            'label' => 'config_login.php',
            'value' => $loginExists ? 'Encontrado ✅' : 'NÃO encontrado ❌'
        ];
        $loginExists ? $success++ : $errors++;
        
        foreach ($checks as $check) {
            echo '<div class="check-item ' . $check['status'] . '">';
            echo '<div class="icon">' . ($check['status'] == 'success' ? '✅' : '❌') . '</div>';
            echo '<div class="info">';
            echo '<div class="label">' . $check['label'] . '</div>';
            echo '<div class="value">' . $check['value'] . '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // 3. Verificar Configurações do Banco
        if ($configExists) {
            require_once $configFile;
            
            $checks = [];
            echo '<div class="section">';
            echo '<div class="section-title">⚙️ Configurações do Banco</div>';
            
            $checks[] = [
                'status' => defined('ENVIRONMENT') ? 'success' : 'warning',
                'label' => 'Ambiente',
                'value' => defined('ENVIRONMENT') ? ENVIRONMENT : 'Não definido'
            ];
            
            $checks[] = [
                'status' => 'success',
                'label' => 'Host',
                'value' => DB_HOST
            ];
            
            $checks[] = [
                'status' => 'success',
                'label' => 'Banco de Dados',
                'value' => DB_NAME
            ];
            
            $checks[] = [
                'status' => 'success',
                'label' => 'Usuário',
                'value' => DB_USER
            ];
            
            $checks[] = [
                'status' => 'success',
                'label' => 'Senha',
                'value' => DB_PASS ? str_repeat('•', 8) : '(vazia)'
            ];
            
            foreach ($checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<div class="icon">' . ($check['status'] == 'success' ? '✅' : '⚠️') . '</div>';
                echo '<div class="info">';
                echo '<div class="label">' . $check['label'] . '</div>';
                echo '<div class="value">' . $check['value'] . '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
            
            // 4. Testar Conexão
            $checks = [];
            echo '<div class="section">';
            echo '<div class="section-title">🔌 Conexão com MySQL</div>';
            
            try {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
                
                if ($conn->connect_error) {
                    throw new Exception($conn->connect_error);
                }
                
                $checks[] = [
                    'status' => 'success',
                    'label' => 'Conexão MySQL',
                    'value' => 'Conectado com sucesso! ✅'
                ];
                $success++;
                
                // Verificar se o banco existe
                $result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
                $dbExists = $result && $result->num_rows > 0;
                
                $checks[] = [
                    'status' => $dbExists ? 'success' : 'error',
                    'label' => 'Banco "' . DB_NAME . '"',
                    'value' => $dbExists ? 'Existe ✅' : 'NÃO existe ❌'
                ];
                $dbExists ? $success++ : $errors++;
                
                if ($dbExists) {
                    $conn->select_db(DB_NAME);
                    
                    // Verificar tabelas
                    $result = $conn->query("SHOW TABLES");
                    $tableCount = $result ? $result->num_rows : 0;
                    
                    $checks[] = [
                        'status' => $tableCount > 0 ? 'success' : 'error',
                        'label' => 'Tabelas no banco',
                        'value' => $tableCount > 0 ? "$tableCount tabelas encontradas ✅" : 'Nenhuma tabela ❌'
                    ];
                    $tableCount > 0 ? $success++ : $errors++;
                    
                    // Verificar tabela users
                    $result = $conn->query("SHOW TABLES LIKE 'users'");
                    $usersTableExists = $result && $result->num_rows > 0;
                    
                    $checks[] = [
                        'status' => $usersTableExists ? 'success' : 'error',
                        'label' => 'Tabela "users"',
                        'value' => $usersTableExists ? 'Existe ✅' : 'NÃO existe ❌'
                    ];
                    $usersTableExists ? $success++ : $errors++;
                    
                    // Contar usuários
                    if ($usersTableExists) {
                        $result = $conn->query("SELECT COUNT(*) as total FROM users");
                        $count = $result->fetch_assoc();
                        
                        $checks[] = [
                            'status' => $count['total'] > 0 ? 'success' : 'warning',
                            'label' => 'Usuários cadastrados',
                            'value' => $count['total'] . ' usuário(s)'
                        ];
                        $count['total'] > 0 ? $success++ : $warnings++;
                    }
                }
                
                $conn->close();
                
            } catch (Exception $e) {
                $checks[] = [
                    'status' => 'error',
                    'label' => 'Conexão MySQL',
                    'value' => 'ERRO: ' . $e->getMessage() . ' ❌'
                ];
                $errors++;
            }
            
            foreach ($checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<div class="icon">' . ($check['status'] == 'success' ? '✅' : ($check['status'] == 'error' ? '❌' : '⚠️')) . '</div>';
                echo '<div class="info">';
                echo '<div class="label">' . $check['label'] . '</div>';
                echo '<div class="value">' . $check['value'] . '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Resumo final
        $allOk = $errors == 0;
        echo '<div class="summary ' . ($allOk ? 'success' : 'error') . '">';
        echo '<div class="summary-icon">' . ($allOk ? '✅' : ($errors > 0 ? '❌' : '⚠️')) . '</div>';
        
        if ($allOk && $warnings == 0) {
            echo '<div class="summary-text" style="color: #10b981;">Sistema Configurado Corretamente!</div>';
            echo '<div class="summary-sub">Tudo pronto para desenvolvimento local</div>';
        } else if ($errors == 0 && $warnings > 0) {
            echo '<div class="summary-text" style="color: #f59e0b;">Sistema Funcionando com Avisos</div>';
            echo '<div class="summary-sub">' . $warnings . ' aviso(s) encontrado(s)</div>';
        } else {
            echo '<div class="summary-text" style="color: #ef4444;">Problemas Encontrados!</div>';
            echo '<div class="summary-sub">' . $errors . ' erro(s) e ' . $warnings . ' aviso(s)</div>';
        }
        
        echo '</div>';
        
        // Próximos passos
        echo '<div class="section">';
        echo '<div class="section-title">📋 Próximos Passos</div>';
        
        if ($errors > 0) {
            echo '<div class="check-item error">';
            echo '<div class="icon">❌</div>';
            echo '<div class="info">';
            echo '<div class="label">Há erros que precisam ser corrigidos</div>';
            echo '<div class="value">Veja as instruções abaixo</div>';
            echo '</div>';
            echo '</div>';
            
            if (!$dbExists ?? false) {
                echo '<div class="code">-- Criar banco de dados no phpMyAdmin<br>';
                echo 'CREATE DATABASE IF NOT EXISTS ' . (DB_NAME ?? 'lactech_lagoa_mato') . '<br>';
                echo '  CHARACTER SET utf8mb4<br>';
                echo '  COLLATE utf8mb4_unicode_ci;</div>';
            }
            
            if (($tableCount ?? 0) == 0) {
                echo '<div class="check-item warning">';
                echo '<div class="icon">💡</div>';
                echo '<div class="info">';
                echo '<div class="label">Importar estrutura do banco</div>';
                echo '<div class="value">Use o phpMyAdmin para importar o arquivo banco_mysql_completo.sql</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="check-item success">';
            echo '<div class="icon">✅</div>';
            echo '<div class="info">';
            echo '<div class="label">Sistema pronto!</div>';
            echo '<div class="value">Você pode começar a desenvolver</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Botões de ação
        echo '<div style="text-align: center; margin-top: 30px;">';
        echo '<a href="testar_conexao.php" class="btn">🔍 Teste Detalhado</a>';
        echo '<a href="resetar_senhas.php" class="btn btn-success">🔑 Resetar Senhas</a>';
        echo '<a href="login.php" class="btn">🚀 Ir para Login</a>';
        echo '<a href="CONFIGURAR_LOCAL.md" class="btn">📖 Ver Guia Completo</a>';
        echo '</div>';
        
        ?>
    </div>
</body>
</html>

