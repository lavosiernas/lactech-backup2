/**
 * SafeNode Mail SDK - JavaScript
 * 
 * SDK oficial do SafeNode Mail para integração em aplicações JavaScript/Node.js
 * 
 * @package SafeNode
 * @version 1.0.0
 * @license MIT
 */

class SafeNodeMail {
    constructor(apiBaseUrl, token, options = {}) {
        this.apiBaseUrl = apiBaseUrl.replace(/\/$/, '');
        this.token = token;
        this.maxRetries = options.maxRetries || 3;
        this.retryDelay = options.retryDelay || 1000;
    }
    
    /**
     * Envia um e-mail
     * 
     * @param {string} to E-mail destinatário
     * @param {string} subject Assunto do e-mail
     * @param {string|null} html Conteúdo HTML
     * @param {string|null} text Conteúdo texto alternativo
     * @param {object} options Opções adicionais (template, variables, etc)
     * @returns {Promise<object>} Resultado do envio
     */
    async send(to, subject, html = null, text = null, options = {}) {
        if (!this.token) {
            throw new Error('Token de autenticação é obrigatório');
        }
        
        if (!to || !this.isValidEmail(to)) {
            throw new Error('E-mail destinatário inválido');
        }
        
        if (!subject) {
            throw new Error('Assunto é obrigatório');
        }
        
        const payload = {
            to: to,
            subject: subject
        };
        
        // Se usar template
        if (options.template) {
            payload.template = options.template;
            if (options.variables) {
                payload.variables = options.variables;
            }
        } else {
            // Conteúdo direto
            if (html) {
                payload.html = html;
            }
            if (text) {
                payload.text = text;
            }
        }
        
        return await this.makeRequest('/send', payload);
    }
    
    /**
     * Envia e-mail usando template
     * 
     * @param {string} to E-mail destinatário
     * @param {string} templateName Nome do template
     * @param {object} variables Variáveis para o template
     * @returns {Promise<object>} Resultado do envio
     */
    async sendTemplate(to, templateName, variables = {}) {
        return await this.send(to, '', null, null, {
            template: templateName,
            variables: variables
        });
    }
    
    /**
     * Faz requisição à API
     * 
     * @param {string} endpoint Endpoint da API
     * @param {object} data Dados a enviar
     * @param {number} retryCount Contador de retry
     * @returns {Promise<object>} Resposta da API
     */
    async makeRequest(endpoint, data, retryCount = 0) {
        try {
            const url = this.apiBaseUrl + endpoint;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.status === 429) {
                throw new Error('Rate limit excedido. Tente novamente em alguns instantes.');
            }
            
            if (response.status === 401) {
                throw new Error('Token inválido ou expirado');
            }
            
            if (response.status !== 200) {
                throw new Error(result.error || 'Erro ao enviar e-mail');
            }
            
            if (result.success) {
                return {
                    success: true,
                    message: result.message || 'E-mail enviado com sucesso',
                    data: result
                };
            } else {
                throw new Error(result.error || 'Erro ao enviar e-mail');
            }
        } catch (error) {
            // Retry automático em caso de erro de rede
            if (retryCount < this.maxRetries && (
                error.message.includes('Failed to fetch') ||
                error.message.includes('NetworkError') ||
                error.message.includes('Rate limit')
            )) {
                await this.sleep(this.retryDelay * (retryCount + 1));
                return await this.makeRequest(endpoint, data, retryCount + 1);
            }
            
            throw error;
        }
    }
    
    /**
     * Valida formato de e-mail
     * 
     * @param {string} email 
     * @returns {boolean}
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Sleep helper
     * 
     * @param {number} ms Milissegundos
     * @returns {Promise}
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Verifica se o token é válido
     * 
     * @returns {Promise<boolean>}
     */
    async validateToken() {
        return !!this.token;
    }
}

// Export para Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SafeNodeMail;
}










