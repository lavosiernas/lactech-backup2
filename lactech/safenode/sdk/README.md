# SafeNode SDKs

SDKs oficiais do SafeNode para diferentes linguagens de programação.

## SDKs Disponíveis

### 🌐 JavaScript (Browser)
**Arquivo:** `safenode-hv.js`  
**Uso:** Para integração em sites web

```html
<script src="https://safenode.cloud/sdk/safenode-hv.js"></script>
<script>
const safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key');
await safenode.init();
const result = await safenode.validate();
</script>
```

### 🐘 PHP
**Arquivo:** `php/SafeNodeHV.php`  
**Uso:** Para integração em aplicações PHP

```php
require_once 'sdk/php/SafeNodeHV.php';

$safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key');
$safenode->init();

// Antes de processar formulário
$result = $safenode->validate();
if ($result['valid']) {
    // Processar formulário
}
```

### 🐍 Python
**Arquivo:** `python/safenode_hv.py`  
**Uso:** Para integração em aplicações Python

```python
from safenode_hv import SafeNodeHV

safenode = SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key')
safenode.init()

# Antes de processar requisição
result = safenode.validate()
if result['valid']:
    # Processar requisição
```

### 📦 Node.js
**Arquivo:** `nodejs/safenode-hv.js`  
**Uso:** Para integração em aplicações Node.js

```javascript
const SafeNodeHV = require('./sdk/nodejs/safenode-hv.js');

const safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key');
await safenode.init();

// Antes de processar requisição
const result = await safenode.validate();
if (result.valid) {
    // Processar requisição
}
```

## Instalação

### PHP
```bash
# Copiar o arquivo para seu projeto
cp sdk/php/SafeNodeHV.php /caminho/do/seu/projeto/
```

### Python
```bash
# Copiar o arquivo para seu projeto
cp sdk/python/safenode_hv.py /caminho/do/seu/projeto/

# Ou instalar via pip (futuro)
pip install safenode-hv
```

### Node.js
```bash
# Copiar o arquivo para seu projeto
cp sdk/nodejs/safenode-hv.js /caminho/do/seu/projeto/

# Ou instalar via npm (futuro)
npm install @safenode/hv
```

## Exemplos Completos

### PHP - Proteção de Formulário

```php
<?php
require_once 'sdk/php/SafeNodeHV.php';

session_start();

// Inicializar SDK
$safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar antes de processar
    try {
        $result = $safenode->validate();
        if ($result['valid']) {
            // Processar formulário
            echo "Formulário processado com sucesso!";
        }
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage();
    }
} else {
    // Inicializar na página
    $safenode->init();
}
?>
```

### Python - Proteção de API

```python
from flask import Flask, request, jsonify
from safenode_hv import SafeNodeHV

app = Flask(__name__)
safenode = SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key')

@app.route('/api/form', methods=['POST'])
def submit_form():
    try:
        result = safenode.validate()
        if result['valid']:
            # Processar formulário
            return jsonify({'success': True})
        else:
            return jsonify({'error': 'Validação falhou'}), 400
    except Exception as e:
        return jsonify({'error': str(e)}), 400
```

### Node.js - Proteção de API

```javascript
const express = require('express');
const SafeNodeHV = require('./sdk/nodejs/safenode-hv.js');

const app = express();
const safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key');

app.post('/api/form', async (req, res) => {
    try {
        const result = await safenode.validate();
        if (result.valid) {
            // Processar formulário
            res.json({ success: true });
        } else {
            res.status(400).json({ error: 'Validação falhou' });
        }
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});
```

## Documentação

Para mais informações, consulte a [documentação completa](../docs.php).

## Suporte

Em caso de dúvidas ou problemas, entre em contato através do sistema de ajuda do SafeNode.



