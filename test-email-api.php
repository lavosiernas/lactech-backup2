<?php
/**
 * Script de teste para APIs de validação de email temporário
 * Use este script para testar diferentes APIs antes de integrar
 */

// Email de teste conhecido como temporário
$testEmail = 'teste@fftube.com';
$testDomain = 'fftube.com';

echo "=== TESTANDO APIs DE EMAIL TEMPORÁRIO ===\n\n";
echo "Email de teste: $testEmail\n";
echo "Domínio: $testDomain\n\n";

// Lista de APIs para testar
$apis = [
    [
        'name' => 'DeBounce Free API',
        'url' => "https://disposable.debounce.io/?email=" . urlencode($testEmail),
        'method' => 'GET',
        'check' => function($response, $data) {
            // DeBounce retorna texto simples: "true" ou "false"
            return trim($response) === 'true' || (isset($data['disposable']) && $data['disposable'] === true);
        }
    ],
    [
        'name' => 'EmailVerify.io',
        'url' => "https://api.emailverify.io/v2/info?email=" . urlencode($testEmail),
        'method' => 'GET',
        'check' => function($response, $data) {
            return isset($data['disposable']) && $data['disposable'] === true;
        }
    ],
    [
        'name' => 'Kickbox (já testada - retornou false)',
        'url' => "https://open.kickbox.com/v1/disposable/" . urlencode($testDomain),
        'method' => 'GET',
        'check' => function($response, $data) {
            return isset($data['disposable']) && $data['disposable'] === true;
        }
    ],
    // Adicione mais APIs aqui conforme encontrar
];

foreach ($apis as $api) {
    echo "Testando: {$api['name']}\n";
    echo "URL: {$api['url']}\n";
    
    try {
        $context = stream_context_create([
            'http' => [
                'method' => $api['method'],
                'timeout' => 5,
                'ignore_errors' => true,
                'user_agent' => 'SafeNode-Test/1.0'
            ]
        ]);
        
        $response = @file_get_contents($api['url'], false, $context);
        
        if ($response === false) {
            echo "❌ ERRO: Não conseguiu conectar\n";
        } else {
            echo "Response: " . substr($response, 0, 200) . "\n";
            
            $data = json_decode($response, true);
            $isDisposable = $api['check']($response, $data ?: []);
            
            if ($isDisposable) {
                echo "✅ SUCESSO: Detectou como TEMPORÁRIO (correto!)\n";
            } else {
                echo "❌ FALHOU: NÃO detectou como temporário (errado para fftube.com)\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    }
    
    echo str_repeat("-", 60) . "\n\n";
}

echo "\n=== DICAS ===\n";
echo "1. Se uma API retornar TRUE para fftube.com = BOM\n";
echo "2. Se retornar FALSE para fftube.com = RUIM (não usar)\n";
echo "3. Teste com outros emails temporários conhecidos:\n";
echo "   - teste@tempmail.com\n";
echo "   - teste@guerrillamail.com\n";
echo "   - teste@10minutemail.com\n";
echo "\n4. Procure por APIs que:\n";
echo "   - Funcionam SEM API key (ou key gratuita)\n";
echo "   - Têm documentação clara\n";
echo "   - Respondem rápido (< 2 segundos)\n";
echo "   - Detectam corretamente fftube.com\n";



