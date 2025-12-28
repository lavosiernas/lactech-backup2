/**
 * SafeNode Human Verification SDK - Node.js
 * 
 * SDK oficial do SafeNode para integração em aplicações Node.js
 * 
 * @package SafeNode
 * @version 1.0.0
 * @license MIT
 */

const https = require('https');
const http = require('http');
const { URL } = require('url');

class SafeNodeHV {
    /**
     * Construtor
     * 
     * @param {string} apiBaseUrl URL base da API (ex: https://safenode.cloud/api/sdk)
     * @param {string} apiKey Chave de API do SafeNode
     * @param {Object} options Opções adicionais
     */
    constructor(apiBaseUrl, apiKey, options = {}) {
        this.apiBaseUrl = apiBaseUrl.replace(/\/$/, '');
        this.apiKey = apiKey;
        this.token = null;
        this.nonce = null;
        this.initialized = false;
        this.maxRetries = options.maxRetries || 3;
        this.retryDelay = options.retryDelay || 1000; // ms
        this.tokenMaxAge = options.tokenMaxAge || 3600; // segundos
        this.initTime = null;
    }
    
    /**
     * Helper para fazer requisições HTTP
     */
    async _makeRequest(method, path, data = null, headers = {}) {
        return new Promise((resolve, reject) => {
            const url = new URL(path, this.apiBaseUrl);
            const isHttps = url.protocol === 'https:';
            const client = isHttps ? https : http;
            
            const options = {
                hostname: url.hostname,
                port: url.port || (isHttps ? 443 : 80),
                path: url.pathname + url.search,
                method: method,
                headers: {
                    'Accept': 'application/json',
                    ...headers
                }
            };
            
            if (data) {
                const jsonData = JSON.stringify(data);
                options.headers['Content-Type'] = 'application/json';
                options.headers['Content-Length'] = Buffer.byteLength(jsonData);
            }
            
            const req = client.request(options, (res) => {
                let body = '';
                
                res.on('data', (chunk) => {
                    body += chunk;
                });
                
                res.on('end', () => {
                    try {
                        const response = {
                            statusCode: res.statusCode,
                            headers: res.headers,
                            data: JSON.parse(body)
                        };
                        resolve(response);
                    } catch (e) {
                        resolve({
                            statusCode: res.statusCode,
                            headers: res.headers,
                            data: body
                        });
                    }
                });
            });
            
            req.on('error', (error) => {
                reject(error);
            });
            
            if (data) {
                req.write(JSON.stringify(data));
            }
            
            req.end();
        });
    }
    
    /**
     * Helper para delay
     */
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Inicializa o SDK e obtém o token de verificação
     * 
     * @param {number} retryCount Contador interno para retry
     * @returns {Promise<boolean>} True se inicializado com sucesso
     */
    async init(retryCount = 0) {
        if (!this.apiKey) {
            throw new Error('API key é obrigatória');
        }
        
        try {
            const url = `${this.apiBaseUrl}/init.php?api_key=${encodeURIComponent(this.apiKey)}`;
            const response = await this._makeRequest('GET', url, null, {
                'X-API-Key': this.apiKey
            });
            
            if (response.statusCode === 429) {
                throw new Error('Rate limit excedido. Tente novamente em alguns instantes.');
            }
            
            if (response.statusCode !== 200) {
                throw new Error(response.data.error || 'Erro ao inicializar verificação');
            }
            
            const data = response.data;
            
            if (data.success && data.token) {
                this.token = data.token;
                this.nonce = data.nonce || '';
                this.tokenMaxAge = data.max_age || 3600;
                this.initTime = Math.floor(Date.now() / 1000);
                this.initialized = true;
                
                return true;
            } else {
                throw new Error('Token não recebido');
            }
        } catch (error) {
            // Retry automático em caso de erro de rede
            if (retryCount < this.maxRetries && (
                error.code === 'ECONNREFUSED' ||
                error.code === 'ETIMEDOUT' ||
                error.message.includes('timeout')
            )) {
                await this._delay(this.retryDelay * (retryCount + 1));
                return this.init(retryCount + 1);
            }
            
            this.initialized = false;
            throw error;
        }
    }
    
    /**
     * Verifica se o token ainda é válido
     * 
     * @returns {boolean}
     */
    _isTokenValid() {
        if (!this.initTime || !this.token) {
            return false;
        }
        
        const age = Math.floor(Date.now() / 1000) - this.initTime;
        return age < this.tokenMaxAge;
    }
    
    /**
     * Valida a verificação humana
     * 
     * @param {number} retryCount Contador interno para retry
     * @returns {Promise<Object>} Resultado da validação
     */
    async validate(retryCount = 0) {
        if (!this.initialized || !this.token) {
            if (this.token && !this._isTokenValid()) {
                await this.init();
            } else {
                throw new Error('SDK não inicializado. Chame init() primeiro.');
            }
        }
        
        if (!this._isTokenValid()) {
            await this.init();
        }
        
        try {
            const payload = {
                token: this.token,
                nonce: this.nonce || '',
                js_enabled: '1',
                api_key: this.apiKey
            };
            
            const response = await this._makeRequest('POST', `${this.apiBaseUrl}/validate.php`, payload, {
                'X-API-Key': this.apiKey
            });
            
            if (response.statusCode === 200 && response.data.success) {
                return {
                    valid: true,
                    message: response.data.message || 'Verificação válida'
                };
            } else {
                throw new Error(response.data.error || 'Validação falhou');
            }
        } catch (error) {
            if (retryCount < this.maxRetries) {
                await this._delay(this.retryDelay * (retryCount + 1));
                return this.validate(retryCount + 1);
            }
            
            throw error;
        }
    }
    
    /**
     * Verifica se o SDK está inicializado
     * 
     * @returns {boolean}
     */
    isInitialized() {
        return this.initialized;
    }
    
    /**
     * Obtém o token atual
     * 
     * @returns {string|null}
     */
    getToken() {
        return this.token;
    }
}

module.exports = SafeNodeHV;


