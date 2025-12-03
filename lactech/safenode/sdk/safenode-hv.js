/**
 * SafeNode Human Verification SDK
 * 
 * SDK JavaScript para verificação humana em sites de terceiros
 * 
 * Uso:
 *   <script src="https://seudominio.com/safenode/sdk/safenode-hv.js"></script>
 *   <script>
 *     const hv = new SafeNodeHV('https://seudominio.com/safenode/api/sdk');
 *     hv.init().then(() => {
 *       // SDK pronto para uso
 *     });
 *   </script>
 */

(function(window) {
    'use strict';

    /**
     * SafeNode Human Verification SDK
     */
    class SafeNodeHV {
        constructor(apiBaseUrl, apiKey, options = {}) {
            this.apiBaseUrl = apiBaseUrl || '';
            this.apiKey = apiKey || '';
            this.token = null;
            this.nonce = null;
            this.initialized = false;
            this.jsEnabled = true;
            this.maxRetries = options.maxRetries || 3;
            this.retryDelay = options.retryDelay || 1000;
            this.tokenMaxAge = options.tokenMaxAge || 3600000; // 1 hora em ms
            this.initTime = null;
        }

        /**
         * Inicializa o SDK e obtém o token com retry automático
         */
        async init(retryCount = 0) {
            if (!this.apiKey) {
                throw new Error('API key é obrigatória');
            }

            try {
                const url = new URL(`${this.apiBaseUrl}/init.php`);
                url.searchParams.set('api_key', this.apiKey);

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-Key': this.apiKey
                    }
                });

                // Verificar rate limit
                if (response.status === 429) {
                    const resetTime = response.headers.get('X-RateLimit-Reset');
                    const waitTime = resetTime ? (parseInt(resetTime) * 1000 - Date.now()) : this.retryDelay;
                    throw new Error(`Rate limit excedido. Tente novamente em ${Math.ceil(waitTime / 1000)} segundos.`);
                }

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || 'Erro ao inicializar verificação');
                }

                const data = await response.json();
                
                if (data.success && data.token) {
                    this.token = data.token;
                    this.nonce = data.nonce || '';
                    this.tokenMaxAge = (data.max_age || 3600) * 1000; // Converter para ms
                    this.initTime = Date.now();
                    this.initialized = true;
                    return true;
                } else {
                    throw new Error('Token não recebido');
                }
            } catch (error) {
                // Retry automático em caso de erro de rede
                if (retryCount < this.maxRetries && (
                    error.message.includes('Failed to fetch') || 
                    error.message.includes('NetworkError') ||
                    error.message.includes('timeout')
                )) {
                    await this._delay(this.retryDelay * (retryCount + 1));
                    return this.init(retryCount + 1);
                }
                
                console.error('SafeNode HV: Erro ao inicializar', error);
                this.initialized = false;
                throw error;
            }
        }

        /**
         * Helper para delay
         */
        _delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        /**
         * Verifica se o token ainda é válido
         */
        _isTokenValid() {
            if (!this.initTime || !this.token) {
                return false;
            }
            const age = Date.now() - this.initTime;
            return age < this.tokenMaxAge;
        }

        /**
         * Valida a verificação humana
         */
        async validate(retryCount = 0) {
            if (!this.initialized || !this.token) {
                // Tentar reinicializar se token expirou
                if (this.token && !this._isTokenValid()) {
                    try {
                        await this.init();
                    } catch (e) {
                        throw new Error('SDK não inicializado. Chame init() primeiro.');
                    }
                } else {
                    throw new Error('SDK não inicializado. Chame init() primeiro.');
                }
            }

            if (!this.apiKey) {
                throw new Error('API key é obrigatória');
            }

            // Verificar se token ainda é válido
            if (!this._isTokenValid()) {
                // Reinicializar automaticamente
                await this.init();
            }

            try {
                const response = await fetch(`${this.apiBaseUrl}/validate.php`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-API-Key': this.apiKey
                    },
                    body: JSON.stringify({
                        token: this.token,
                        nonce: this.nonce || '',
                        js_enabled: '1',
                        api_key: this.apiKey
                    })
                });

                // Verificar rate limit
                if (response.status === 429) {
                    const resetTime = response.headers.get('X-RateLimit-Reset');
                    const waitTime = resetTime ? (parseInt(resetTime) * 1000 - Date.now()) : this.retryDelay;
                    throw new Error(`Rate limit excedido. Tente novamente em ${Math.ceil(waitTime / 1000)} segundos.`);
                }

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    // Se token inválido, tentar reinicializar uma vez
                    if (response.status === 400 && retryCount === 0 && errorData.error?.includes('Token inválido')) {
                        await this.init();
                        return this.validate(1);
                    }
                    throw new Error(errorData.error || 'Erro ao validar verificação');
                }

                const data = await response.json();
                return data.success === true && data.valid === true;
            } catch (error) {
                // Retry automático em caso de erro de rede
                if (retryCount < this.maxRetries && (
                    error.message.includes('Failed to fetch') || 
                    error.message.includes('NetworkError') ||
                    error.message.includes('timeout')
                )) {
                    await this._delay(this.retryDelay * (retryCount + 1));
                    return this.validate(retryCount + 1);
                }
                
                console.error('SafeNode HV: Erro ao validar', error);
                throw error;
            }
        }

        /**
         * Adiciona campos hidden ao formulário
         */
        attachToForm(formSelector) {
            const form = document.querySelector(formSelector);
            if (!form) {
                throw new Error('Formulário não encontrado');
            }

            // Remover campos anteriores se existirem
            const existingToken = form.querySelector('input[name="safenode_hv_token"]');
            const existingJs = form.querySelector('input[name="safenode_hv_js"]');
            
            if (existingToken) existingToken.remove();
            if (existingJs) existingJs.remove();

            // Adicionar token
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'safenode_hv_token';
            tokenInput.value = this.token || '';
            form.appendChild(tokenInput);

            // Adicionar flag de JavaScript
            const jsInput = document.createElement('input');
            jsInput.type = 'hidden';
            jsInput.name = 'safenode_hv_js';
            jsInput.value = '1';
            form.appendChild(jsInput);

            // Adicionar API key
            if (this.apiKey) {
                const apiKeyInput = document.createElement('input');
                apiKeyInput.type = 'hidden';
                apiKeyInput.name = 'safenode_api_key';
                apiKeyInput.value = this.apiKey;
                form.appendChild(apiKeyInput);
            }

            return true;
        }

        /**
         * Valida antes de enviar o formulário
         */
        async validateForm(formSelector) {
            try {
                const isValid = await this.validate();
                if (!isValid) {
                    throw new Error('Verificação humana falhou');
                }
                return true;
            } catch (error) {
                console.error('SafeNode HV: Validação falhou', error);
                throw error;
            }
        }

        /**
         * Retorna o token atual
         */
        getToken() {
            return this.token;
        }

        /**
         * Verifica se está inicializado
         */
        isInitialized() {
            return this.initialized;
        }
    }

    // Exportar para window
    window.SafeNodeHV = SafeNodeHV;

    // Auto-inicializar se configurado
    if (window.SafeNodeHVConfig) {
        const config = window.SafeNodeHVConfig;
        const hv = new SafeNodeHV(config.apiBaseUrl, config.apiKey);
        hv.init().then(() => {
            if (config.onReady) {
                config.onReady(hv);
            }
        }).catch((error) => {
            console.error('SafeNode HV: Erro na inicialização automática', error);
        });
    }

})(window);

