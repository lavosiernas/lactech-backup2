<?php
/**
 * SafeNode Mail - Exemplos de Uso
 * Este arquivo contém exemplos de código para usar a API de envio de e-mails
 */

// Exemplos em PHP, JavaScript, Python, etc.

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode Mail - Exemplos</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #0a0a0a;
            color: #fff;
            padding: 20px;
            line-height: 1.6;
        }
        .example {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        h2 {
            color: #fff;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        code {
            background: #000;
            padding: 2px 6px;
            border-radius: 4px;
            color: #0f0;
        }
        pre {
            background: #000;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            border: 1px solid #333;
        }
    </style>
</head>
<body>
    <h1>SafeNode Mail - Exemplos de Uso</h1>
    
    <div class="example">
        <h2>PHP - cURL</h2>
        <pre><?php echo htmlspecialchars('<?php
$token = "seu-token-aqui";
$url = "https://safenode.cloud/api/mail/send";

$data = [
    "to" => "destinatario@email.com",
    "subject" => "Assunto do e-mail",
    "html" => "<h1>Olá!</h1><p>Este é um e-mail de teste.</p>"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
if ($result["success"]) {
    echo "E-mail enviado com sucesso!";
} else {
    echo "Erro: " . $result["error"];
}
?>'); ?></pre>
    </div>
    
    <div class="example">
        <h2>JavaScript - Fetch API</h2>
        <pre><?php echo htmlspecialchars('const token = "seu-token-aqui";
const url = "https://safenode.cloud/api/mail/send";

const data = {
    to: "destinatario@email.com",
    subject: "Assunto do e-mail",
    html: "<h1>Olá!</h1><p>Este é um e-mail de teste.</p>"
};

fetch(url, {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "Authorization": `Bearer ${token}`
    },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => {
    if (result.success) {
        console.log("E-mail enviado com sucesso!");
    } else {
        console.error("Erro:", result.error);
    }
})
.catch(error => {
    console.error("Erro na requisição:", error);
});'); ?></pre>
    </div>
    
    <div class="example">
        <h2>Usando Template</h2>
        <pre><?php echo htmlspecialchars('// PHP
$data = [
    "to" => "usuario@email.com",
    "template" => "verificacao-conta",
    "variables" => [
        "nome" => "João",
        "codigo" => "123456",
        "link" => "https://exemplo.com/verificar?code=123456"
    ]
];

// JavaScript
const data = {
    to: "usuario@email.com",
    template: "verificacao-conta",
    variables: {
        nome: "João",
        codigo: "123456",
        link: "https://exemplo.com/verificar?code=123456"
    }
};'); ?></pre>
    </div>
    
    <div class="example">
        <h2>Python - requests</h2>
        <pre><?php echo htmlspecialchars('import requests
import json

token = "seu-token-aqui"
url = "https://safenode.cloud/api/mail/send"

data = {
    "to": "destinatario@email.com",
    "subject": "Assunto do e-mail",
    "html": "<h1>Olá!</h1><p>Este é um e-mail de teste.</p>"
}

headers = {
    "Content-Type": "application/json",
    "Authorization": f"Bearer {token}"
}

response = requests.post(url, json=data, headers=headers)
result = response.json()

if result["success"]:
    print("E-mail enviado com sucesso!")
else:
    print(f"Erro: {result[\'error\']}")'); ?></pre>
    </div>
    
    <div class="example">
        <h2>Node.js - axios</h2>
        <pre><?php echo htmlspecialchars('const axios = require("axios");

const token = "seu-token-aqui";
const url = "https://safenode.cloud/api/mail/send";

const data = {
    to: "destinatario@email.com",
    subject: "Assunto do e-mail",
    html: "<h1>Olá!</h1><p>Este é um e-mail de teste.</p>"
};

axios.post(url, data, {
    headers: {
        "Content-Type": "application/json",
        "Authorization": `Bearer ${token}`
    }
})
.then(response => {
    if (response.data.success) {
        console.log("E-mail enviado com sucesso!");
    } else {
        console.error("Erro:", response.data.error);
    }
})
.catch(error => {
    console.error("Erro na requisição:", error);
});'); ?></pre>
    </div>
</body>
</html>















