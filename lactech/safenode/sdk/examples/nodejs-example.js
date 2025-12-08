/**
 * Exemplo de uso do SDK Node.js do SafeNode
 */

const express = require('express');
const SafeNodeHV = require('../nodejs/safenode-hv.js');

const app = express();
const safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key-aqui');

app.use(express.json());

// Inicializar SDK na página
app.get('/', async (req, res) => {
    try {
        await safenode.init();
        res.json({
            success: true,
            message: 'SDK inicializado',
            token: safenode.getToken() ? safenode.getToken().substring(0, 16) + '...' : null
        });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Validar e processar formulário
app.post('/submit', async (req, res) => {
    try {
        const result = await safenode.validate();
        
        if (result.valid) {
            // Processar formulário com segurança
            res.json({
                success: true,
                message: 'Formulário validado e processado com sucesso!'
            });
        } else {
            res.status(400).json({
                success: false,
                error: result.message || 'Validação falhou'
            });
        }
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

app.listen(3000, () => {
    console.log('Servidor rodando na porta 3000');
});




