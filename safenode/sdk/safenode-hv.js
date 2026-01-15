(function(window) {
    'use strict';


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
            this.tokenMaxAge = options.tokenMaxAge || 3600000; 
            this.initTime = null;
        }

        async init(retryCount = 0) {
            if (!this.apiKey) {
                throw new Error('API key é obrigatória');
            }

            try {
                const url = new URL(`${this.apiBaseUrl}/init.php`);
                url.searchParams.set('api_key', this.apiKey);

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-Key': this.apiKey
                    }
                });

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
                    
                    // Sempre mostrar indicador visual de verificação ativa
                    try {
                        this.showVerificationIndicator();
                    } catch (e) {
                        console.warn('SafeNode HV: Erro ao mostrar indicador', e);
                    }
                    
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
            console.log('SafeNode HV: validate() chamado, retryCount:', retryCount);
            
            if (!this.initialized || !this.token) {
                console.warn('SafeNode HV: SDK não inicializado ou sem token, tentando reinicializar...');
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
                console.warn('SafeNode HV: Token expirado, reinicializando...');
                await this.init();
            }

            try {
                const payload = {
                    token: this.token,
                    nonce: this.nonce || '',
                    js_enabled: '1',
                    api_key: this.apiKey
                };
                
                console.log('SafeNode HV: Enviando validação para:', `${this.apiBaseUrl}/validate.php`);
                console.log('SafeNode HV: Payload (sem token):', { ...payload, token: '***' });
                
                const response = await fetch(`${this.apiBaseUrl}/validate.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-API-Key': this.apiKey
                    },
                    body: JSON.stringify(payload)
                });

                console.log('SafeNode HV: Resposta recebida, status:', response.status);

                // Verificar rate limit
                if (response.status === 429) {
                    const resetTime = response.headers.get('X-RateLimit-Reset');
                    const waitTime = resetTime ? (parseInt(resetTime) * 1000 - Date.now()) : this.retryDelay;
                    throw new Error(`Rate limit excedido. Tente novamente em ${Math.ceil(waitTime / 1000)} segundos.`);
                }

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    console.error('SafeNode HV: Erro na resposta:', errorData);
                    
                    // Se token inválido, tentar reinicializar uma vez
                    if (response.status === 400 && retryCount === 0 && (
                        errorData.error?.includes('Token inválido') || 
                        errorData.error?.includes('Recarregue a página')
                    )) {
                        console.warn('SafeNode HV: Token inválido, reinicializando e tentando novamente...');
                        await this.init();
                        return this.validate(1);
                    }
                    throw new Error(errorData.error || 'Erro ao validar verificação');
                }

                const data = await response.json();
                console.log('SafeNode HV: Dados da validação:', data);
                return data.success === true && data.valid === true;
            } catch (error) {
                console.error('SafeNode HV: Erro ao validar', error);
                
                // Retry automático em caso de erro de rede
                if (retryCount < this.maxRetries && (
                    error.message.includes('Failed to fetch') || 
                    error.message.includes('NetworkError') ||
                    error.message.includes('timeout')
                )) {
                    console.log('SafeNode HV: Tentando novamente após erro de rede...');
                    await this._delay(this.retryDelay * (retryCount + 1));
                    return this.validate(retryCount + 1);
                }
                
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
                console.log('SafeNode HV: Iniciando validação do formulário...');
                console.log('SafeNode HV: Token atual:', this.token ? 'existe' : 'não existe');
                console.log('SafeNode HV: SDK inicializado?', this.initialized);
                
                if (!this.initialized || !this.token) {
                    console.warn('SafeNode HV: SDK não inicializado, tentando reinicializar...');
                    await this.init();
                }
                
                const isValid = await this.validate();
                console.log('SafeNode HV: Resultado da validação:', isValid);
                
                if (!isValid) {
                    throw new Error('Verificação humana falhou');
                }
                
                // Atualizar campos do formulário com o token atual
                if (formSelector) {
                    this.attachToForm(formSelector);
                }
                
                return true;
            } catch (error) {
                console.error('SafeNode HV: Validação falhou', error);
                console.error('SafeNode HV: Detalhes do erro:', {
                    message: error.message,
                    stack: error.stack,
                    token: this.token ? 'existe' : 'não existe',
                    initialized: this.initialized
                });
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

        /**
         * Mostra indicador visual de verificação ativa (mesma interface do login SafeNode)
         * Sempre insere ANTES do botão de submit, no mesmo local do login SafeNode
         */
        showVerificationIndicator() {
            console.log('SafeNode HV: Mostrando indicador de verificação...');
            
            // Função para tentar inserir (com retry)
            const tryInsert = (attempt = 0) => {
                // Buscar formulários na página
                const forms = document.querySelectorAll('form');
                console.log('SafeNode HV: Tentativa', attempt + 1, '- Formulários encontrados:', forms.length);
                
                if (forms.length === 0 && attempt < 10) {
                    console.warn('SafeNode HV: Nenhum formulário encontrado, tentando novamente em 500ms...');
                    setTimeout(() => tryInsert(attempt + 1), 500);
                    return;
                }
                
                if (forms.length === 0) {
                    console.warn('SafeNode HV: Nenhum formulário encontrado após 10 tentativas. A caixa aparecerá quando um formulário for adicionado à página.');
                    // Criar um observer para detectar quando formulários forem adicionados
                    const observer = new MutationObserver((mutations) => {
                        const newForms = document.querySelectorAll('form');
                        if (newForms.length > 0) {
                            console.log('SafeNode HV: Formulário detectado, inserindo caixa de verificação...');
                            this._insertVerificationBox(newForms);
                            observer.disconnect();
                        }
                    });
                    observer.observe(document.body, { childList: true, subtree: true });
                    return;
                }
                
                this._insertVerificationBox(forms);
            };
            
            // Aguardar DOM estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => tryInsert(0), 100);
                });
            } else {
                // DOM já está pronto, aguardar um pouco para garantir
                setTimeout(() => tryInsert(0), 100);
            }
        }
        
        /**
         * Insere a interface de verificação nos formulários
         */
        _insertVerificationBox(forms) {

            // Adicionar CSS para animação se não existir
            if (!document.getElementById('safenode-hv-styles')) {
                const style = document.createElement('style');
                style.id = 'safenode-hv-styles';
                style.textContent = `
                    @keyframes safenode-spin {
                        to { transform: rotate(360deg); }
                    }
                    .safenode-hv-spinner {
                        animation: safenode-spin 0.8s linear infinite;
                    }
                `;
                document.head.appendChild(style);
            }

            // Processar cada formulário
            const boxes = [];
            Array.from(forms).forEach((form, index) => {
                // Remover box anterior se existir para este formulário
                const formId = form.id || 'safenode-form-' + index;
                const existingBox = form.querySelector('#safenode-hv-box-' + formId);
                if (existingBox) {
                    existingBox.remove();
                }

                // Detectar URL base do SafeNode (produção ou desenvolvimento)
                const getSafeNodeBaseUrl = () => {
                    // Sempre usar produção para a logo
                    return 'https://safenode.cloud';
                };
                
                const baseUrl = getSafeNodeBaseUrl();
                const logoUrl = baseUrl + '/assets/img/logos%20(6).png';
                
                // Criar a interface de verificação (igual ao login SafeNode)
                const hvBox = document.createElement('div');
                hvBox.id = 'safenode-hv-box-' + formId;
                hvBox.setAttribute('style', 'margin-top: 12px; padding: 12px; border-radius: 16px; border: 1px solid #e2e8f0; background-color: #f8fafc; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);');
                hvBox.innerHTML = `
                    <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px;">
                        <div class="safenode-hv-spinner" id="safenode-hv-spinner-${formId}" style="position: absolute; inset: 0; border-radius: 16px; border: 2px solid #e2e8f0; border-top-color: #000000;"></div>
                        <div style="position: relative; z-index: 10; width: 28px; height: 28px; border-radius: 16px; background-color: #000000; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img src="${logoUrl}" alt="SafeNode" style="width: 16px; height: 16px; object-fit: contain; filter: brightness(0) invert(1);" onerror="this.onerror=null; this.style.display='none'; const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); svg.setAttribute('width', '16'); svg.setAttribute('height', '16'); svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'white'); svg.setAttribute('stroke-width', '2'); const path1 = document.createElementNS('http://www.w3.org/2000/svg', 'path'); path1.setAttribute('d', 'M12 2L2 7l10 5 10-5-10-5z'); svg.appendChild(path1); const path2 = document.createElementNS('http://www.w3.org/2000/svg', 'path'); path2.setAttribute('d', 'M2 17l10 5 10-5'); svg.appendChild(path2); const path3 = document.createElementNS('http://www.w3.org/2000/svg', 'path'); path3.setAttribute('d', 'M2 12l10 5 10-5'); svg.appendChild(path3); this.parentElement.appendChild(svg);">
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <p style="font-size: 12px; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 4px; margin: 0 0 4px 0;">
                            SafeNode <span style="font-size: 10px; font-weight: 400; color: #64748b;">verificação humana</span>
                        </p>
                        <p style="font-size: 11px; color: #64748b; margin: 0;" id="safenode-hv-text-${formId}">Validando interação do navegador…</p>
                    </div>
                    <svg id="safenode-hv-check-${formId}" style="width: 16px; height: 16px; color: #10b981; display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                `;

                // Buscar botão de submit - tentar diferentes seletores
                let submitBtn = form.querySelector('button[type="submit"]');
                if (!submitBtn) {
                    submitBtn = form.querySelector('input[type="submit"]');
                }
                if (!submitBtn) {
                    // Se não houver botão explícito, buscar qualquer botão no formulário
                    submitBtn = form.querySelector('button');
                }

                // Inserir ANTES do botão de submit (mesmo local do login SafeNode)
                if (submitBtn && submitBtn.parentNode) {
                    submitBtn.parentNode.insertBefore(hvBox, submitBtn);
                    console.log('SafeNode HV: Interface inserida antes do botão de submit no formulário', formId);
                    console.log('SafeNode HV: Elemento inserido:', hvBox);
                    console.log('SafeNode HV: Elemento visível?', hvBox.offsetParent !== null);
                } else {
                    // Se não houver botão, adicionar no final do formulário
                    form.appendChild(hvBox);
                    console.log('SafeNode HV: Interface inserida no final do formulário', formId);
                    console.log('SafeNode HV: Elemento inserido:', hvBox);
                    console.log('SafeNode HV: Elemento visível?', hvBox.offsetParent !== null);
                }

                // Forçar visibilidade
                hvBox.style.display = 'flex';
                hvBox.style.visibility = 'visible';
                hvBox.style.opacity = '1';
                
                boxes.push({ formId: formId });
            });
            
            console.log('SafeNode HV: Total de interfaces criadas:', boxes.length);
            
            // Teste: verificar se os elementos estão no DOM
            setTimeout(() => {
                boxes.forEach(({ formId }) => {
                    const box = document.getElementById('safenode-hv-box-' + formId);
                    if (box) {
                        console.log('SafeNode HV: Box encontrado no DOM:', box);
                        console.log('SafeNode HV: Box estilos:', window.getComputedStyle(box).display);
                    } else {
                        console.error('SafeNode HV: Box NÃO encontrado no DOM:', 'safenode-hv-box-' + formId);
                    }
                });
            }, 100);

            // Após um pequeno atraso, mostrar visual de verificado (igual ao login)
            setTimeout(() => {
                boxes.forEach(({ formId }) => {
                    const spinner = document.getElementById('safenode-hv-spinner-' + formId);
                    const check = document.getElementById('safenode-hv-check-' + formId);
                    const text = document.getElementById('safenode-hv-text-' + formId);
                    
                    if (spinner) spinner.style.display = 'none';
                    if (check) check.style.display = 'block';
                    if (text) {
                        text.innerHTML = 'Verificado com <a href="https://safenode.cloud" target="_blank" rel="noopener noreferrer" style="color: #3b82f6; text-decoration: none; font-weight: 600;">SafeNode</a>';
                    }
                });
            }, 800);
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

