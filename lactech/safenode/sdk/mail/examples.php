<?php
/**
 * SafeNode Mail SDK - Exemplos de Uso (PHP)
 */

require_once __DIR__ . '/SafeNodeMail.php';

// Configuração
$apiBaseUrl = 'https://safenode.cloud/api/mail';
$token = 'seu-token-aqui';

// Inicializar SDK
$mail = new SafeNodeMail($apiBaseUrl, $token);

try {
    // Exemplo 1: Enviar e-mail simples
    $result = $mail->send(
        'usuario@email.com',
        'Bem-vindo!',
        '<h1>Olá!</h1><p>Bem-vindo ao nosso sistema.</p>',
        'Olá! Bem-vindo ao nosso sistema.'
    );
    
    if ($result['success']) {
        echo "E-mail enviado com sucesso!\n";
    }
    
    // Exemplo 2: Enviar usando template
    $result = $mail->sendTemplate(
        'usuario@email.com',
        'verificacao-conta',
        [
            'nome' => 'João',
            'codigo' => '123456',
            'link' => 'https://exemplo.com/verificar?code=123456'
        ]
    );
    
    if ($result['success']) {
        echo "E-mail de verificação enviado!\n";
    }
    
    // Exemplo 3: Com retry customizado
    $mail = new SafeNodeMail($apiBaseUrl, $token, [
        'max_retries' => 5,
        'retry_delay' => 2000
    ]);
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}



