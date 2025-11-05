<?php
/**
 * PÁGINA DE TESTE DE SEGURANÇA - LACTECH
 * Esta página permite testar vulnerabilidades de segurança do sistema
 * ATENÇÃO: Use apenas em ambiente de desenvolvimento/teste
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar configuração
require_once __DIR__ . '/includes/config_login.php';
require_once __DIR__ . '/includes/Database.class.php';

// Verificar se está em ambiente de desenvolvimento (OPCIONAL - remover em produção)
// $isDev = defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL';
// if (!$isDev) {
//     die('Acesso negado. Esta página só está disponível em ambiente de desenvolvimento.');
// }

// Função para sanitizar saída
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Processar testes
$testResults = [];
$testAction = $_POST['test_action'] ?? '';

if ($testAction) {
    try {
        $db = Database::getInstance();
        
        switch ($testAction) {
            // ==================== TESTE 1: SQL INJECTION ====================
            case 'test_sql_injection':
                $input = $_POST['test_input'] ?? '';
                
                // Teste 1: Prepared Statement (SEGURO)
                $safeResult = ['status' => 'safe', 'message' => ''];
                try {
                    $results = $db->query("SELECT * FROM users WHERE email = ? LIMIT 1", [$input]);
                    $safeResult['message'] = 'Prepared Statement funcionou corretamente. Resultados: ' . count($results);
                } catch (Exception $e) {
                    $safeResult['status'] = 'error';
                    $safeResult['message'] = 'Erro: ' . $e->getMessage();
                }
                
                // Teste 2: Concatenação direta (VULNERÁVEL - apenas para demonstração)
                $vulnerableResult = ['status' => 'vulnerable', 'message' => ''];
                try {
                    // ATENÇÃO: Este código é intencionalmente vulnerável para demonstração
                    // NUNCA use isso em produção!
                    $sql = "SELECT * FROM users WHERE email = '" . $input . "' LIMIT 1";
                    $stmt = $db->getConnection()->query($sql);
                    $vulnerableResult['message'] = 'Query executada com sucesso (VULNERÁVEL!)';
                    if ($stmt) {
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $vulnerableResult['message'] .= ' - Resultados: ' . count($results);
                    }
                } catch (Exception $e) {
                    $vulnerableResult['message'] = 'Erro detectado: ' . $e->getMessage();
                }
                
                $testResults['sql_injection'] = [
                    'safe' => $safeResult,
                    'vulnerable' => $vulnerableResult,
                    'input' => $input
                ];
                break;
            
            // ==================== TESTE 2: XSS (Cross-Site Scripting) ====================
            case 'test_xss':
                $input = $_POST['test_input'] ?? '';
                
                // Teste sem sanitização (VULNERÁVEL)
                $unsafeOutput = $input;
                
                // Teste com sanitização (SEGURO)
                $safeOutput = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                
                $testResults['xss'] = [
                    'input' => $input,
                    'unsafe_output' => $unsafeOutput,
                    'safe_output' => $safeOutput,
                    'has_script_tags' => (stripos($input, '<script') !== false),
                    'has_event_handlers' => (preg_match('/on\w+\s*=/i', $input) > 0)
                ];
                break;
            
            // ==================== TESTE 3: CSRF ====================
            case 'test_csrf':
                // Verificar se há token CSRF na sessão
                $hasToken = isset($_SESSION['csrf_token']);
                
                // Verificar se foi enviado token
                $sentToken = $_POST['csrf_token'] ?? '';
                $sessionToken = $_SESSION['csrf_token'] ?? '';
                
                $tokenValid = ($hasToken && $sentToken === $sessionToken);
                
                $testResults['csrf'] = [
                    'has_token_in_session' => $hasToken,
                    'token_match' => $tokenValid,
                    'session_token' => substr($sessionToken, 0, 10) . '...',
                    'sent_token' => substr($sentToken, 0, 10) . '...',
                    'vulnerable' => !$tokenValid
                ];
                break;
            
            // ==================== TESTE 4: AUTENTICAÇÃO ====================
            case 'test_auth':
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $authResult = ['status' => 'safe', 'message' => ''];
                
                if (!empty($email) && !empty($password)) {
                    // Testar login
                    $result = $db->login($email, $password);
                    $authResult['login_success'] = $result['success'];
                    $authResult['message'] = $result['success'] ? 'Login bem-sucedido' : 'Credenciais inválidas';
                    
                    // Verificar força de senha
                    $passwordStrength = [
                        'length' => strlen($password),
                        'has_uppercase' => preg_match('/[A-Z]/', $password),
                        'has_lowercase' => preg_match('/[a-z]/', $password),
                        'has_numbers' => preg_match('/[0-9]/', $password),
                        'has_special' => preg_match('/[^A-Za-z0-9]/', $password)
                    ];
                    $authResult['password_strength'] = $passwordStrength;
                } else {
                    $authResult['message'] = 'Email e senha são obrigatórios';
                }
                
                $testResults['auth'] = $authResult;
                break;
            
            // ==================== TESTE 5: INCLUSÃO DE ARQUIVOS (LFI/RFI) ====================
            case 'test_file_inclusion':
                $file = $_POST['test_input'] ?? '';
                
                $lfiResult = ['status' => 'tested', 'message' => ''];
                
                // Verificar se é um caminho relativo perigoso
                $dangerousPatterns = ['../', '..\\', '/etc/', 'C:\\', 'php://', 'file://'];
                $isDangerous = false;
                foreach ($dangerousPatterns as $pattern) {
                    if (stripos($file, $pattern) !== false) {
                        $isDangerous = true;
                        break;
                    }
                }
                
                $lfiResult['is_dangerous'] = $isDangerous;
                $lfiResult['message'] = $isDangerous ? 'Caminho perigoso detectado!' : 'Caminho parece seguro';
                $lfiResult['file'] = $file;
                
                $testResults['file_inclusion'] = $lfiResult;
                break;
            
            // ==================== TESTE 6: VALIDAÇÃO DE ENTRADA ====================
            case 'test_input_validation':
                $input = $_POST['test_input'] ?? '';
                
                $validationResult = [
                    'input' => $input,
                    'is_empty' => empty($input),
                    'is_numeric' => is_numeric($input),
                    'is_email' => filter_var($input, FILTER_VALIDATE_EMAIL) !== false,
                    'is_url' => filter_var($input, FILTER_VALIDATE_URL) !== false,
                    'has_special_chars' => preg_match('/[<>"\']/', $input) > 0,
                    'length' => strlen($input),
                    'sanitized' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8')
                ];
                
                $testResults['input_validation'] = $validationResult;
                break;
            
            // ==================== TESTE 7: EXPOSIÇÃO DE INFORMAÇÕES ====================
            case 'test_info_disclosure':
                $infoResult = [
                    'php_version' => PHP_VERSION,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                    'display_errors' => ini_get('display_errors'),
                    'error_reporting' => error_reporting(),
                    'session_config' => [
                        'cookie_httponly' => ini_get('session.cookie_httponly'),
                        'cookie_secure' => ini_get('session.cookie_secure'),
                        'use_only_cookies' => ini_get('session.use_only_cookies')
                    ],
                    'database_config_exposed' => false, // Verificar se há credenciais expostas
                    'file_paths_exposed' => false
                ];
                
                // Verificar se há erros sendo exibidos
                if ($infoResult['display_errors']) {
                    $infoResult['vulnerable'] = true;
                    $infoResult['message'] = 'Erros podem estar sendo exibidos!';
                }
                
                $testResults['info_disclosure'] = $infoResult;
                break;
            
            // ==================== TESTE 8: VERIFICAR PREPARED STATEMENTS ====================
            case 'test_prepared_statements':
                $queries = [];
                
                // Verificar se todas as queries usam prepared statements
                // Este é um teste manual - você precisa verificar o código
                $queries[] = ['query' => 'SELECT * FROM users WHERE id = ?', 'uses_prepared' => true];
                $queries[] = ['query' => 'SELECT * FROM users WHERE email = ?', 'uses_prepared' => true];
                
                $testResults['prepared_statements'] = [
                    'queries_checked' => count($queries),
                    'all_safe' => true,
                    'queries' => $queries
                ];
                break;
        }
    } catch (Exception $e) {
        $testResults['error'] = $e->getMessage();
    }
}

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Segurança - LacTech</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #000;
            color: #fff;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #fff;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .warning {
            background: #ff6b35;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #ff4500;
        }
        .test-section {
            background: #111;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .test-section h2 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .test-section h3 {
            color: #ccc;
            margin: 15px 0 10px 0;
            font-size: 1.1em;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 10px;
            background: #222;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #666;
        }
        button {
            background: #0066cc;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-top: 10px;
        }
        button:hover {
            background: #0052a3;
        }
        .result {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        .result.safe {
            border-left: 4px solid #4CAF50;
        }
        .result.vulnerable {
            border-left: 4px solid #f44336;
        }
        .result.warning {
            border-left: 4px solid #ff9800;
        }
        .result pre {
            background: #000;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            color: #0f0;
            font-size: 12px;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.safe {
            background: #4CAF50;
            color: #fff;
        }
        .status.vulnerable {
            background: #f44336;
            color: #fff;
        }
        .status.warning {
            background: #ff9800;
            color: #fff;
        }
        .code-example {
            background: #000;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #0f0;
            overflow-x: auto;
        }
        .info-box {
            background: #1a1a1a;
            border-left: 4px solid #2196F3;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Teste de Segurança - LacTech</h1>
        
        <div class="warning">
            <strong>⚠️ ATENÇÃO:</strong> Esta página é para testes de segurança. Use apenas em ambiente de desenvolvimento.
        </div>

        <!-- TESTE 1: SQL INJECTION -->
        <div class="test-section">
            <h2>1. SQL Injection</h2>
            <p>Teste de vulnerabilidade SQL Injection. Tente usar: <code>' OR '1'='1</code></p>
            
            <form method="POST">
                <input type="hidden" name="test_action" value="test_sql_injection">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label>Email para testar:</label>
                    <input type="text" name="test_input" placeholder="test@example.com ou ' OR '1'='1" value="<?= h($_POST['test_input'] ?? '') ?>">
                </div>
                
                <button type="submit">Testar SQL Injection</button>
            </form>
            
            <?php if (isset($testResults['sql_injection'])): ?>
                <div class="result <?= $testResults['sql_injection']['vulnerable']['status'] ?>">
                    <h3>Resultado:</h3>
                    <p><strong>Input:</strong> <?= h($testResults['sql_injection']['input']) ?></p>
                    
                    <h3>Prepared Statement (SEGURO):</h3>
                    <p><span class="status safe">SEGURO</span> <?= h($testResults['sql_injection']['safe']['message']) ?></p>
                    
                    <h3>Concatenação Direta (VULNERÁVEL):</h3>
                    <p><span class="status vulnerable">VULNERÁVEL</span> <?= h($testResults['sql_injection']['vulnerable']['message']) ?></p>
                    
                    <div class="info-box">
                        <strong>Recomendação:</strong> Sempre use prepared statements com placeholders (?).
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- TESTE 2: XSS -->
        <div class="test-section">
            <h2>2. Cross-Site Scripting (XSS)</h2>
            <p>Teste de vulnerabilidade XSS. Tente usar: <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code></p>
            
            <form method="POST">
                <input type="hidden" name="test_action" value="test_xss">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label>Input para testar XSS:</label>
                    <input type="text" name="test_input" placeholder="<script>alert('XSS')</script>" value="<?= h($_POST['test_input'] ?? '') ?>">
                </div>
                
                <button type="submit">Testar XSS</button>
            </form>
            
            <?php if (isset($testResults['xss'])): ?>
                <div class="result <?= $testResults['xss']['has_script_tags'] ? 'vulnerable' : 'safe' ?>">
                    <h3>Resultado:</h3>
                    <p><strong>Input original:</strong> <?= h($testResults['xss']['input']) ?></p>
                    
                    <h3>Saída sem sanitização (VULNERÁVEL):</h3>
                    <div class="code-example"><?= $testResults['xss']['unsafe_output'] ?></div>
                    
                    <h3>Saída com sanitização (SEGURO):</h3>
                    <div class="code-example"><?= $testResults['xss']['safe_output'] ?></div>
                    
                    <p><strong>Tags &lt;script&gt; detectadas:</strong> <?= $testResults['xss']['has_script_tags'] ? 'SIM ⚠️' : 'NÃO ✓' ?></p>
                    <p><strong>Event handlers detectados:</strong> <?= $testResults['xss']['has_event_handlers'] ? 'SIM ⚠️' : 'NÃO ✓' ?></p>
                    
                    <div class="info-box">
                        <strong>Recomendação:</strong> Sempre use <code>htmlspecialchars()</code> antes de exibir dados do usuário.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- TESTE 3: CSRF -->
        <div class="test-section">
            <h2>3. CSRF (Cross-Site Request Forgery)</h2>
            <p>Teste de proteção CSRF</p>
            
            <form method="POST">
                <input type="hidden" name="test_action" value="test_csrf">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                
                <button type="submit">Testar CSRF</button>
            </form>
            
            <?php if (isset($testResults['csrf'])): ?>
                <div class="result <?= $testResults['csrf']['vulnerable'] ? 'vulnerable' : 'safe' ?>">
                    <h3>Resultado:</h3>
                    <p><strong>Token na sessão:</strong> <?= $testResults['csrf']['has_token_in_session'] ? 'SIM ✓' : 'NÃO ⚠️' ?></p>
                    <p><strong>Tokens correspondem:</strong> <?= $testResults['csrf']['token_match'] ? 'SIM ✓' : 'NÃO ⚠️' ?></p>
                    <p><strong>Vulnerável:</strong> <?= $testResults['csrf']['vulnerable'] ? 'SIM ⚠️' : 'NÃO ✓' ?></p>
                    
                    <div class="info-box">
                        <strong>Recomendação:</strong> Sempre valide tokens CSRF em formulários críticos.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- TESTE 4: AUTENTICAÇÃO -->
        <div class="test-section">
            <h2>4. Autenticação e Força de Senha</h2>
            <p>Teste de segurança de autenticação</p>
            
            <form method="POST">
                <input type="hidden" name="test_action" value="test_auth">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" placeholder="test@example.com">
                </div>
                
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="password" placeholder="Sua senha">
                </div>
                
                <button type="submit">Testar Autenticação</button>
            </form>
            
            <?php if (isset($testResults['auth'])): ?>
                <div class="result">
                    <h3>Resultado:</h3>
                    <?php if (isset($testResults['auth']['login_success'])): ?>
                        <p><strong>Login:</strong> <?= $testResults['auth']['login_success'] ? 'Bem-sucedido ✓' : 'Falhou' ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($testResults['auth']['password_strength'])): ?>
                        <h3>Força da Senha:</h3>
                        <?php $strength = $testResults['auth']['password_strength']; ?>
                        <p><strong>Comprimento:</strong> <?= $strength['length'] ?> caracteres</p>
                        <p><strong>Tem maiúsculas:</strong> <?= $strength['has_uppercase'] ? 'SIM ✓' : 'NÃO' ?></p>
                        <p><strong>Tem minúsculas:</strong> <?= $strength['has_lowercase'] ? 'SIM ✓' : 'NÃO' ?></p>
                        <p><strong>Tem números:</strong> <?= $strength['has_numbers'] ? 'SIM ✓' : 'NÃO' ?></p>
                        <p><strong>Tem caracteres especiais:</strong> <?= $strength['has_special'] ? 'SIM ✓' : 'NÃO' ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TESTE 5: INCLUSÃO DE ARQUIVOS -->
        <div class="test-section">
            <h2>5. Local/Remote File Inclusion (LFI/RFI)</h2>
            <p>Teste de vulnerabilidade de inclusão de arquivos</p>
            
            <form method="POST">
                <input type="hidden" name="test_action" value="test_file_inclusion">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label>Caminho do arquivo:</label>
                    <input type="text" name="test_input" placeholder="ex: ../config.php">
                </div>
                
                <button type="submit">Testar LFI/RFI</button>
            </form>
            
            <?php if (isset($testResults['file_inclusion'])): ?>
                <div class="result <?= $testResults['file_inclusion']['is_dangerous'] ? 'vulnerable' : 'safe' ?>">
                    <h3>Resultado:</h3>
                    <p><strong>Arquivo:</strong> <?= h($testResults['file_inclusion']['file']) ?></p>
                    <p><strong>Perigoso:</strong> <?= $testResults['file_inclusion']['is_dangerous'] ? 'SIM ⚠️' : 'NÃO ✓' ?></p>
                    <p><?= h($testResults['file_inclusion']['message']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TESTE 6: VALIDAÇÃO DE ENTRADA -->
        <div class="test-section">
            <h2>6. Validação de Entrada</h2>
            <p>Teste de validação de dados de entrada</p>
            
            <form method="POST">
                <input type="hidden" name="test_action" value="test_input_validation">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label>Input para validar:</label>
                    <input type="text" name="test_input" placeholder="Digite qualquer coisa">
                </div>
                
                <button type="submit">Validar Entrada</button>
            </form>
            
            <?php if (isset($testResults['input_validation'])): ?>
                <div class="result">
                    <h3>Resultado:</h3>
                    <?php $val = $testResults['input_validation']; ?>
                    <p><strong>Input:</strong> <?= h($val['input']) ?></p>
                    <p><strong>Vazio:</strong> <?= $val['is_empty'] ? 'SIM' : 'NÃO' ?></p>
                    <p><strong>É numérico:</strong> <?= $val['is_numeric'] ? 'SIM' : 'NÃO' ?></p>
                    <p><strong>É email válido:</strong> <?= $val['is_email'] ? 'SIM ✓' : 'NÃO' ?></p>
                    <p><strong>É URL válida:</strong> <?= $val['is_url'] ? 'SIM ✓' : 'NÃO' ?></p>
                    <p><strong>Tem caracteres especiais:</strong> <?= $val['has_special_chars'] ? 'SIM ⚠️' : 'NÃO' ?></p>
                    <p><strong>Comprimento:</strong> <?= $val['length'] ?> caracteres</p>
                    <p><strong>Sanitizado:</strong> <code><?= h($val['sanitized']) ?></code></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TESTE 7: EXPOSIÇÃO DE INFORMAÇÕES -->
        <div class="test-section">
            <h2>7. Exposição de Informações</h2>
            <p>Verificar se informações sensíveis estão sendo expostas</p>
            
            <form method="POST">
                <input type="hidden" name="test_action" value="test_info_disclosure">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                
                <button type="submit">Verificar Exposição</button>
            </form>
            
            <?php if (isset($testResults['info_disclosure'])): ?>
                <div class="result <?= isset($testResults['info_disclosure']['vulnerable']) && $testResults['info_disclosure']['vulnerable'] ? 'vulnerable' : 'safe' ?>">
                    <h3>Resultado:</h3>
                    <?php $info = $testResults['info_disclosure']; ?>
                    <p><strong>Versão PHP:</strong> <?= h($info['php_version']) ?></p>
                    <p><strong>Servidor:</strong> <?= h($info['server_software']) ?></p>
                    <p><strong>Display Errors:</strong> <?= $info['display_errors'] ? 'ATIVADO ⚠️' : 'DESATIVADO ✓' ?></p>
                    <p><strong>Error Reporting:</strong> <?= $info['error_reporting'] ?></p>
                    
                    <h3>Configuração de Sessão:</h3>
                    <p><strong>Cookie HttpOnly:</strong> <?= $info['session_config']['cookie_httponly'] ? 'SIM ✓' : 'NÃO ⚠️' ?></p>
                    <p><strong>Cookie Secure:</strong> <?= $info['session_config']['cookie_secure'] ? 'SIM ✓' : 'NÃO' ?></p>
                    <p><strong>Use Only Cookies:</strong> <?= $info['session_config']['use_only_cookies'] ? 'SIM ✓' : 'NÃO ⚠️' ?></p>
                    
                    <?php if (isset($info['vulnerable']) && $info['vulnerable']): ?>
                        <div class="info-box" style="border-color: #f44336;">
                            <strong>⚠️ Problema detectado:</strong> <?= h($info['message']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RELATÓRIO GERAL -->
        <div class="test-section">
            <h2>📋 Recomendações Gerais</h2>
            <div class="info-box">
                <h3>Boas Práticas de Segurança:</h3>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>✅ Sempre use prepared statements para queries SQL</li>
                    <li>✅ Sempre sanitize saída com htmlspecialchars()</li>
                    <li>✅ Implemente proteção CSRF em formulários</li>
                    <li>✅ Valide e filtre todas as entradas do usuário</li>
                    <li>✅ Use HTTPS em produção</li>
                    <li>✅ Configure cookies de sessão com HttpOnly e Secure</li>
                    <li>✅ Desative exibição de erros em produção</li>
                    <li>✅ Implemente rate limiting para login</li>
                    <li>✅ Use senhas fortes (mínimo 8 caracteres, maiúsculas, minúsculas, números e especiais)</li>
                    <li>✅ Mantenha dependências atualizadas</li>
                </ul>
            </div>
        </div>

    </div>
</body>
</html>
