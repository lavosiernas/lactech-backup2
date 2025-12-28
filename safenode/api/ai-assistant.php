<?php
/**
 * API para Assistente de IA
 * Integração com serviços de IA para assistência na criação de templates
 */

session_start();
header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter dados do JSON
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$code = $input['code'] ?? '';
$projectData = $input['project_data'] ?? null;

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Mensagem vazia']);
    exit;
}

// Preparar contexto do projeto
$context = "Você é um assistente de IA especializado em criação de templates de e-mail HTML.\n\n";
if ($projectData) {
    $context .= "Informações do projeto:\n";
    $context .= "- Nome: " . ($projectData['project_name'] ?? 'Não especificado') . "\n";
    $context .= "- E-mail remetente: " . ($projectData['sender_email'] ?? 'Não especificado') . "\n";
    $context .= "- Função do e-mail: " . ($projectData['email_function'] ?? 'Não especificada') . "\n\n";
}

$context .= "Código atual do template:\n```html\n" . substr($code, 0, 2000) . "\n```\n\n";
$context .= "Pergunta do usuário: " . $message . "\n\n";
$context .= "Forneça uma resposta útil e específica sobre como criar ou melhorar o template de e-mail.";

// ============================================================
// CONFIGURAÇÃO DA API DE IA
// ============================================================
// OPÇÃO 1: Via variável de ambiente (recomendado)
//   - Windows: Defina AI_API_KEY nas Variáveis de Ambiente do Sistema
//   - Linux/Mac: export AI_API_KEY="sua-chave-aqui"
//
// OPÇÃO 2: Configure diretamente abaixo (se não usar variáveis de ambiente)
//   - Descomente a linha abaixo e cole sua API Key
//   - ⚠️ CUIDADO: Não commite sua API Key no Git!
// ============================================================

$aiProvider = getenv('AI_PROVIDER') ?: ($_ENV['AI_PROVIDER'] ?? ($_SERVER['AI_PROVIDER'] ?? 'openai')); // 'openai', 'claude', 'local'

// Tenta pegar da variável de ambiente de múltiplas formas (compatibilidade Windows/Linux)
$apiKey = getenv('AI_API_KEY') ?: ($_ENV['AI_API_KEY'] ?? ($_SERVER['AI_API_KEY'] ?? ''));
// Se não configurou via ambiente, descomente e configure aqui:
// $apiKey = 'sk-sua-openai-api-key-aqui'; // OpenAI
// $apiKey = 'sk-ant-sua-claude-api-key-aqui'; // Claude (Anthropic)

// Se não configurou nada, usará modo local (sem API Key)

// Função para chamar OpenAI
function callOpenAI($message, $apiKey) {
    if (empty($apiKey)) {
        return null; // Retorna null para permitir fallback automático
    }
    
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Você é um assistente especializado em criação de templates de e-mail HTML. Forneça respostas úteis e práticas, sempre focando em código HTML limpo e responsivo.'
            ],
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return "❌ Erro ao conectar com a API: Código HTTP $httpCode";
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }
    
    return "❌ Erro ao processar resposta da IA";
}

// Função para chamar Claude (Anthropic)
function callClaude($message, $apiKey) {
    if (empty($apiKey)) {
        return null; // Retorna null para permitir fallback automático
    }
    
    $url = 'https://api.anthropic.com/v1/messages';
    
    $data = [
        'model' => 'claude-3-haiku-20240307',
        'max_tokens' => 1000,
        'messages' => [
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
        'system' => 'Você é um assistente especializado em criação de templates de e-mail HTML. Forneça respostas úteis e práticas, sempre focando em código HTML limpo e responsivo.'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return "❌ Erro ao conectar com a API: Código HTTP $httpCode";
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['content'][0]['text'])) {
        return $result['content'][0]['text'];
    }
    
    return "❌ Erro ao processar resposta da IA";
}

// Função para chamar IA local (Ollama ou outra API local)
function callLocalAI($message, $apiKey = '') {
    // URL da API local (padrão: Ollama)
    $localApiUrl = getenv('LOCAL_AI_URL') ?: 'http://localhost:11434/api/generate';
    $model = getenv('LOCAL_AI_MODEL') ?: 'llama3.2:1b'; // Modelo pequeno e rápido
    
    // Ollama usa formato diferente
    $data = [
        'model' => $model,
        'prompt' => $message,
        'stream' => false,
        'options' => [
            'temperature' => 0.7,
            'num_predict' => 500 // Limitar tokens para resposta rápida
        ]
    ];
    
    $ch = curl_init($localApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout de conexão
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Se não conseguir conectar, retorna null para usar fallback
    if ($curlError || $httpCode !== 200) {
        error_log("Local AI Error: HTTP $httpCode - $curlError");
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['response'])) {
        return $result['response'];
    }
    
    return null;
}

// Função para resposta local (fallback quando não há API configurada)
function getLocalResponse($message, $code) {
    $messageLower = strtolower($message);
    
    // Respostas pré-definidas baseadas em palavras-chave
    if (strpos($messageLower, 'variável') !== false || strpos($messageLower, 'variable') !== false) {
        return "Você pode usar variáveis no template usando a sintaxe {{nome_variavel}}. Exemplos:\n\n" .
               "- {{nome}} - Nome do destinatário\n" .
               "- {{codigo}} - Código de verificação\n" .
               "- {{link}} - Link de ação\n" .
               "- {{email}} - E-mail do usuário\n\n" .
               "Essas variáveis serão substituídas automaticamente quando o e-mail for enviado.";
    }
    
    if (strpos($messageLower, 'responsivo') !== false || strpos($messageLower, 'mobile') !== false || strpos($messageLower, 'responsive') !== false) {
        return "Para criar um template responsivo:\n\n" .
               "1. Use max-width: 600px no container principal\n" .
               "2. Adicione meta viewport: <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n" .
               "3. Use unidades relativas (%, em, rem) em vez de pixels fixos\n" .
               "4. Teste em diferentes tamanhos de tela\n\n" .
               "Exemplo:\n" .
               "```html\n" .
               "<div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">\n" .
               "  <!-- Conteúdo aqui -->\n" .
               "</div>\n" .
               "```";
    }
    
    if (strpos($messageLower, 'estilo') !== false || strpos($messageLower, 'css') !== false || strpos($messageLower, 'style') !== false) {
        return "Para estilizar seu template:\n\n" .
               "1. Use CSS inline (recomendado para e-mails)\n" .
               "2. Evite CSS externo (muitos clientes bloqueiam)\n" .
               "3. Use tabelas para layout (melhor compatibilidade)\n" .
               "4. Teste em diferentes clientes de e-mail\n\n" .
               "Exemplo de estilo inline:\n" .
               "```html\n" .
               "<div style=\"font-family: Arial, sans-serif; color: #333; padding: 20px;\">\n" .
               "  Conteúdo estilizado\n" .
               "</div>\n" .
               "```";
    }
    
    // Resposta genérica
    return "Olá! Sou seu assistente de IA. Posso ajudá-lo com:\n\n" .
           "✅ Criação de templates HTML responsivos\n" .
           "✅ Uso de variáveis no template\n" .
           "✅ Estilização e design\n" .
           "✅ Compatibilidade com clientes de e-mail\n" .
           "✅ Boas práticas de e-mail marketing\n\n" .
           "💡 **Dica**: Para respostas mais avançadas e personalizadas, configure uma API Key de IA (OpenAI ou Claude) nas variáveis de ambiente. Veja o arquivo `AI_API_CONFIG.md` para instruções detalhadas.\n\n" .
           "Como posso ajudar você especificamente?";
}

// Processar requisição
try {
    $response = '';
    
    switch ($aiProvider) {
        case 'openai':
            $response = callOpenAI($context, $apiKey);
            // Se não há API key ou falhou, fazer fallback para modo local
            if ($response === null || (is_string($response) && strpos($response, '❌') === 0)) {
                $localResponse = callLocalAI($context);
                $response = $localResponse !== null ? $localResponse : getLocalResponse($message, $code);
            }
            break;
            
        case 'claude':
            $response = callClaude($context, $apiKey);
            // Se não há API key ou falhou, fazer fallback para modo local
            if ($response === null || (is_string($response) && strpos($response, '❌') === 0)) {
                $localResponse = callLocalAI($context);
                $response = $localResponse !== null ? $localResponse : getLocalResponse($message, $code);
            }
            break;
            
        case 'local':
            // Tentar usar IA local (Ollama, etc)
            $localResponse = callLocalAI($context);
            if ($localResponse !== null) {
                $response = $localResponse;
            } else {
                // Fallback para respostas pré-definidas
                $response = getLocalResponse($message, $code);
            }
            break;
            
        default:
            // Tentar IA local primeiro, depois fallback
            $localResponse = callLocalAI($context);
            if ($localResponse !== null) {
                $response = $localResponse;
            } else {
                $response = getLocalResponse($message, $code);
            }
            break;
    }
    
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

