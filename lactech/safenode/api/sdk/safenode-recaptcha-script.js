/**
 * SafeNode reCAPTCHA SDK - 100% SafeNode (sem Google)
 * 
 * Sistema próprio de verificação humana do SafeNode
 * 
 * Como usar:
 * 1. Inclua este script
 * 2. Configure apenas com sua API Key do SafeNode
 * 3. Chame SafeNodeReCAPTCHA.init() e SafeNodeReCAPTCHA.execute()
 */

(function() {
    'use strict';
    
    /**
     * SafeNode reCAPTCHA SDK
     */
    window.SafeNodeReCAPTCHA = {
        config: {
            apiKey: '', // API Key do SafeNode (obrigatória)
            apiUrl: '', // URL base do SafeNode (ex: https://safenode.example.com/api/sdk)
            version: 'v2', // v2 (checkbox) ou v3 (invisível)
            action: 'submit', // Para v3
            theme: 'dark', // dark ou light
            size: 'normal' // normal ou compact
        },
        challenge: null,
        token: null,
        behaviorData: {
            mouse_movements: 0,
            clicks: 0,
            scroll_events: 0,
            key_events: 0,
            time_on_page: 0,
            start_time: null
        },
        widgetId: null,
        callback: null,
        
        /**
         * Inicializa o SDK
         */
        init: function(config) {
            this.config = Object.assign(this.config, config || {});
            
            // Validar API Key
            if (!this.config.apiKey) {
                console.error('SafeNode reCAPTCHA: API Key é obrigatória');
                return this;
            }
            
            // Se não tiver API URL, tentar detectar
            if (!this.config.apiUrl) {
                const scripts = document.getElementsByTagName('script');
                for (let i = 0; i < scripts.length; i++) {
                    const src = scripts[i].src || '';
                    if (src.includes('safenode-recaptcha-script.js')) {
                        const match = src.match(/^(https?:\/\/[^\/]+)/);
                        if (match) {
                            this.config.apiUrl = match[1] + '/api/sdk';
                            break;
                        }
                    }
                }
            }
            
            if (!this.config.apiUrl) {
                this.config.apiUrl = '/api/sdk';
            }
            
            // Iniciar coleta de dados comportamentais
            this.startBehaviorTracking();
            
            return this;
        },
        
        /**
         * Inicia rastreamento de comportamento
         */
        startBehaviorTracking: function() {
            this.behaviorData.start_time = Date.now();
            
            // Mouse movements
            let mouseMoveCount = 0;
            document.addEventListener('mousemove', function() {
                mouseMoveCount++;
                this.behaviorData.mouse_movements = mouseMoveCount;
            }.bind(this), { passive: true });
            
            // Clicks
            let clickCount = 0;
            document.addEventListener('click', function() {
                clickCount++;
                this.behaviorData.clicks = clickCount;
            }.bind(this), { passive: true });
            
            // Scroll
            let scrollCount = 0;
            document.addEventListener('scroll', function() {
                scrollCount++;
                this.behaviorData.scroll_events = scrollCount;
            }.bind(this), { passive: true });
            
            // Key events
            let keyCount = 0;
            document.addEventListener('keydown', function() {
                keyCount++;
                this.behaviorData.key_events = keyCount;
            }.bind(this), { passive: true });
        },
        
        /**
         * Gera challenge do SafeNode
         */
        generateChallenge: function() {
            const self = this;
            
            return fetch(this.config.apiUrl + '/safenode-recaptcha-init.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.config.apiKey
                },
                body: JSON.stringify({
                    api_key: this.config.apiKey
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    self.challenge = data;
                    self.token = data.token;
                    return data;
                } else {
                    throw new Error(data.error || 'Erro ao gerar challenge');
                }
            });
        },
        
        /**
         * Renderiza widget v2 (checkbox)
         */
        render: function(containerId, callback) {
            if (this.config.version !== 'v2') {
                console.error('SafeNode reCAPTCHA: render() só funciona com v2');
                return;
            }
            
            this.callback = callback;
            const container = typeof containerId === 'string' 
                ? document.getElementById(containerId.replace('#', ''))
                : containerId;
            
            if (!container) {
                console.error('SafeNode reCAPTCHA: Container não encontrado');
                return;
            }
            
            // Gerar challenge primeiro
            this.generateChallenge().then(function() {
                this.renderWidget(container);
            }.bind(this)).catch(function(error) {
                console.error('SafeNode reCAPTCHA: Erro ao gerar challenge:', error);
            });
        },
        
        /**
         * Renderiza o widget visual
         */
        renderWidget: function(container) {
            const widget = document.createElement('div');
            widget.className = 'safenode-recaptcha-widget';
            widget.id = 'safenode-recaptcha-' + Date.now();
            this.widgetId = widget.id;
            
            // Estilos inline
            widget.style.cssText = `
                width: 304px;
                height: 78px;
                background: ${this.config.theme === 'dark' ? '#1a1a1a' : '#f9f9f9'};
                border: 1px solid ${this.config.theme === 'dark' ? '#3c3c3c' : '#d3d3d3'};
                border-radius: 3px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                user-select: none;
                font-family: Roboto, Arial, sans-serif;
                position: relative;
                overflow: hidden;
            `;
            
            // Checkbox
            const checkbox = document.createElement('div');
            checkbox.className = 'safenode-recaptcha-checkbox';
            checkbox.style.cssText = `
                width: 24px;
                height: 24px;
                border: 2px solid ${this.config.theme === 'dark' ? '#888' : '#666'};
                border-radius: 2px;
                margin-right: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: ${this.config.theme === 'dark' ? '#2a2a2a' : '#fff'};
                transition: all 0.2s;
            `;
            
            // Texto
            const text = document.createElement('span');
            text.textContent = 'Não sou um robô';
            text.style.cssText = `
                color: ${this.config.theme === 'dark' ? '#fff' : '#000'};
                font-size: 14px;
                font-weight: 400;
            `;
            
            widget.appendChild(checkbox);
            widget.appendChild(text);
            
            // Click handler
            let checked = false;
            widget.addEventListener('click', function() {
                if (checked) return;
                
                checked = true;
                checkbox.style.background = '#4CAF50';
                checkbox.style.borderColor = '#4CAF50';
                checkbox.innerHTML = '✓';
                checkbox.style.color = '#fff';
                checkbox.style.fontSize = '16px';
                checkbox.style.fontWeight = 'bold';
                
                // Executar validação
                this.execute().then(function(result) {
                    if (this.callback) {
                        this.callback(result);
                    }
                }.bind(this));
            }.bind(this));
            
            // Hover effect
            widget.addEventListener('mouseenter', function() {
                if (!checked) {
                    widget.style.borderColor = this.config.theme === 'dark' ? '#555' : '#999';
                }
            }.bind(this));
            
            widget.addEventListener('mouseleave', function() {
                if (!checked) {
                    widget.style.borderColor = this.config.theme === 'dark' ? '#3c3c3c' : '#d3d3d3';
                }
            }.bind(this));
            
            container.innerHTML = '';
            container.appendChild(widget);
        },
        
        /**
         * Executa reCAPTCHA (v2 ou v3)
         */
        execute: function(action) {
            const self = this;
            
            // Se não tiver challenge, gerar
            if (!this.challenge) {
                return this.generateChallenge().then(function() {
                    return self.execute(action);
                });
            }
            
            // Atualizar tempo na página
            if (this.behaviorData.start_time) {
                this.behaviorData.time_on_page = (Date.now() - this.behaviorData.start_time) / 1000;
            }
            
            // Validar com SafeNode
            return fetch(this.config.apiUrl + '/safenode-recaptcha-validate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.config.apiKey
                },
                body: JSON.stringify({
                    response: this.token,
                    api_key: this.config.apiKey,
                    behavior: this.behaviorData
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Colocar token em campo hidden se existir
                    let input = document.querySelector('input[name="safenode-recaptcha-token"]');
                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'safenode-recaptcha-token';
                        document.body.appendChild(input);
                    }
                    input.value = self.token;
                    
                    return {
                        success: true,
                        token: self.token,
                        score: data.score || null
                    };
                } else {
                    throw new Error(data.error || 'Verificação falhou');
                }
            });
        },
        
        /**
         * Reseta o widget
         */
        reset: function() {
            this.challenge = null;
            this.token = null;
            this.behaviorData = {
                mouse_movements: 0,
                clicks: 0,
                scroll_events: 0,
                key_events: 0,
                time_on_page: 0,
                start_time: Date.now()
            };
            
            if (this.widgetId) {
                const widget = document.getElementById(this.widgetId);
                if (widget) {
                    widget.remove();
                }
            }
        }
    };
    
    // Auto-inicializar se tiver atributos data-*
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeFromDataAttributes();
        });
    } else {
        initializeFromDataAttributes();
    }
    
    function initializeFromDataAttributes() {
        const scripts = document.getElementsByTagName('script');
        for (let i = 0; i < scripts.length; i++) {
            const script = scripts[i];
            if (script.src && script.src.includes('safenode-recaptcha-script.js')) {
                const apiKey = script.getAttribute('data-api-key');
                const apiUrl = script.getAttribute('data-api-url');
                const version = script.getAttribute('data-version') || 'v2';
                
                if (apiKey) {
                    window.SafeNodeReCAPTCHA.init({
                        apiKey: apiKey,
                        apiUrl: apiUrl,
                        version: version
                    });
                }
                break;
            }
        }
    }
})();

