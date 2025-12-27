/**
 * SafeNode Mail SDK - Exemplos de Uso (JavaScript)
 */

// Browser
const SafeNodeMail = window.SafeNodeMail || require('./safenode-mail.js');

// Configuração
const apiBaseUrl = 'https://safenode.cloud/api/mail';
const token = 'seu-token-aqui';

// Inicializar SDK
const mail = new SafeNodeMail(apiBaseUrl, token);

// Exemplo 1: Enviar e-mail simples
async function exemplo1() {
    try {
        const result = await mail.send(
            'usuario@email.com',
            'Bem-vindo!',
            '<h1>Olá!</h1><p>Bem-vindo ao nosso sistema.</p>',
            'Olá! Bem-vindo ao nosso sistema.'
        );
        
        if (result.success) {
            console.log('E-mail enviado com sucesso!');
        }
    } catch (error) {
        console.error('Erro:', error.message);
    }
}

// Exemplo 2: Enviar usando template
async function exemplo2() {
    try {
        const result = await mail.sendTemplate(
            'usuario@email.com',
            'verificacao-conta',
            {
                nome: 'João',
                codigo: '123456',
                link: 'https://exemplo.com/verificar?code=123456'
            }
        );
        
        if (result.success) {
            console.log('E-mail de verificação enviado!');
        }
    } catch (error) {
        console.error('Erro:', error.message);
    }
}

// Exemplo 3: Com retry customizado
const mailCustom = new SafeNodeMail(apiBaseUrl, token, {
    maxRetries: 5,
    retryDelay: 2000
});

// Executar exemplos
exemplo1();
exemplo2();












