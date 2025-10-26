/**
 * Console Guard - Lactech
 * Proteção e otimização do console para produção
 */

class ConsoleGuard {
    constructor() {
        this.isProduction = this.detectProduction();
        this.originalConsole = {
            log: console.log,
            warn: console.warn,
            error: console.error,
            info: console.info,
            debug: console.debug
        };
        
        // Sistema de limite de console
        this.logCache = new Map();
        this.logCounts = new Map();
        this.rateLimits = new Map();
        this.maxRepeats = 3; // Máximo de repetições
        this.rateLimitWindow = 5000; // 5 segundos
        this.maxLogsPerWindow = 10; // Máximo de logs por janela de tempo
        
        this.init();
    }

    init() {
        if (this.isProduction) {
            this.setupProductionMode();
        } else {
            this.setupDevelopmentMode();
        }
        
        this.setupErrorHandling();
        this.setupPerformanceLogging();
        console.log('🛡️ Console Guard inicializado');
    }

    /**
     * Detectar se está em produção
     */
    detectProduction() {
        return window.location.hostname !== 'localhost' && 
               window.location.hostname !== '127.0.0.1' &&
               !window.location.hostname.includes('localhost');
    }

    /**
     * Configurar modo de produção
     */
    setupProductionMode() {
        // Desabilitar console em produção
        console.log = () => {};
        console.warn = () => {};
        console.info = () => {};
        console.debug = () => {};
        
        // Manter apenas erros críticos
        console.error = (...args) => {
            this.logError(args);
        };
    }

    /**
     * Configurar modo de desenvolvimento
     */
    setupDevelopmentMode() {
        // Adicionar timestamps e controle de limite aos logs
        const addTimestampAndLimit = (originalMethod, methodName) => {
            return (...args) => {
                if (this.shouldLog(methodName, args)) {
                    const timestamp = new Date().toLocaleTimeString();
                    originalMethod(`[${timestamp}]`, ...args);
                }
            };
        };

        console.log = addTimestampAndLimit(this.originalConsole.log, 'log');
        console.warn = addTimestampAndLimit(this.originalConsole.warn, 'warn');
        console.error = addTimestampAndLimit(this.originalConsole.error, 'error');
        console.info = addTimestampAndLimit(this.originalConsole.info, 'info');
        console.debug = addTimestampAndLimit(this.originalConsole.debug, 'debug');
    }

    /**
     * Verificar se deve fazer log (controle de limite)
     */
    shouldLog(methodName, args) {
        const logKey = this.generateLogKey(methodName, args);
        const now = Date.now();
        
        // Verificar rate limiting
        if (!this.checkRateLimit(methodName, now)) {
            return false;
        }
        
        // Verificar repetições
        if (!this.checkRepeats(logKey)) {
            return false;
        }
        
        // Atualizar cache
        this.updateLogCache(logKey, now);
        
        return true;
    }

    /**
     * Gerar chave única para o log
     */
    generateLogKey(methodName, args) {
        const message = args.map(arg => 
            typeof arg === 'string' ? arg : 
            typeof arg === 'object' ? JSON.stringify(arg) : 
            String(arg)
        ).join(' ');
        
        // Normalizar mensagem para agrupar logs similares
        const normalizedMessage = message
            .replace(/\d+/g, 'N') // Substituir números por N
            .replace(/\[.*?\]/g, '[TIME]') // Substituir timestamps
            .replace(/https?:\/\/[^\s]+/g, 'URL') // Substituir URLs
            .replace(/\b\w+@\w+\.\w+\b/g, 'EMAIL') // Substituir emails
            .substring(0, 100); // Limitar tamanho
        
        return `${methodName}:${normalizedMessage}`;
    }

    /**
     * Verificar rate limiting
     */
    checkRateLimit(methodName, now) {
        const key = `rate_${methodName}`;
        const windowStart = now - this.rateLimitWindow;
        
        if (!this.rateLimits.has(key)) {
            this.rateLimits.set(key, []);
        }
        
        const logs = this.rateLimits.get(key);
        
        // Remover logs antigos
        const recentLogs = logs.filter(timestamp => timestamp > windowStart);
        this.rateLimits.set(key, recentLogs);
        
        // Verificar se excedeu o limite
        if (recentLogs.length >= this.maxLogsPerWindow) {
            // Mostrar aviso de rate limit apenas uma vez
            if (!this.logCache.has(`rate_limit_${key}`)) {
                this.originalConsole.warn(`⚠️ Rate limit atingido para ${methodName}. Logs suprimidos por ${this.rateLimitWindow/1000}s`);
                this.logCache.set(`rate_limit_${key}`, now);
            }
            return false;
        }
        
        // Adicionar log atual
        recentLogs.push(now);
        this.rateLimits.set(key, recentLogs);
        
        return true;
    }

    /**
     * Verificar repetições
     */
    checkRepeats(logKey) {
        const now = Date.now();
        const windowStart = now - this.rateLimitWindow;
        
        if (!this.logCounts.has(logKey)) {
            this.logCounts.set(logKey, []);
        }
        
        const timestamps = this.logCounts.get(logKey);
        
        // Remover timestamps antigos
        const recentTimestamps = timestamps.filter(timestamp => timestamp > windowStart);
        this.logCounts.set(logKey, recentTimestamps);
        
        // Verificar se excedeu o limite de repetições
        if (recentTimestamps.length >= this.maxRepeats) {
            // Mostrar aviso de repetição apenas uma vez
            if (!this.logCache.has(`repeat_${logKey}`)) {
                this.originalConsole.warn(`🔄 Log repetido ${recentTimestamps.length} vezes. Suprimindo por ${this.rateLimitWindow/1000}s`);
                this.logCache.set(`repeat_${logKey}`, now);
            }
            return false;
        }
        
        // Adicionar timestamp atual
        recentTimestamps.push(now);
        this.logCounts.set(logKey, recentTimestamps);
        
        return true;
    }

    /**
     * Atualizar cache de logs
     */
    updateLogCache(logKey, timestamp) {
        this.logCache.set(logKey, timestamp);
        
        // Limpar cache antigo periodicamente
        if (this.logCache.size > 1000) {
            const now = Date.now();
            for (const [key, time] of this.logCache.entries()) {
                if (now - time > this.rateLimitWindow * 2) {
                    this.logCache.delete(key);
                }
            }
        }
    }

    /**
     * Configurar tratamento de erros
     */
    setupErrorHandling() {
        // Capturar erros globais
        window.addEventListener('error', (event) => {
            this.handleError('JavaScript Error', event.error, event.filename, event.lineno);
        });

        // Capturar promessas rejeitadas
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError('Unhandled Promise Rejection', event.reason);
        });

        // Capturar erros de recursos
        window.addEventListener('error', (event) => {
            if (event.target !== window) {
                this.handleError('Resource Error', event.target.src || event.target.href);
            }
        }, true);
    }

    /**
     * Configurar logging de performance
     */
    setupPerformanceLogging() {
        // Monitorar performance
        if (performance && performance.observer) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'measure') {
                        console.log(`⏱️ Performance: ${entry.name} - ${entry.duration.toFixed(2)}ms`);
                    }
                });
            });
            
            observer.observe({ entryTypes: ['measure'] });
        }

        // Monitorar memória
        if (performance.memory) {
            setInterval(() => {
                const memory = performance.memory;
                const used = Math.round(memory.usedJSHeapSize / 1024 / 1024);
                const total = Math.round(memory.totalJSHeapSize / 1024 / 1024);
                const limit = Math.round(memory.jsHeapSizeLimit / 1024 / 1024);
                
                if (used > limit * 0.8) {
                    console.warn(`⚠️ Alto uso de memória: ${used}MB / ${limit}MB`);
                }
            }, 30000); // A cada 30 segundos
        }
    }

    /**
     * Tratar erros
     */
    handleError(type, error, filename = null, lineno = null) {
        const errorInfo = {
            type: type,
            message: error?.message || error,
            stack: error?.stack,
            filename: filename,
            lineno: lineno,
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent
        };

        // Log do erro
        this.originalConsole.error('🚨 Erro capturado:', errorInfo);

        // Enviar para servidor em produção
        if (this.isProduction) {
            this.sendErrorToServer(errorInfo);
        }
    }

    /**
     * Log de erro
     */
    logError(args) {
        const errorInfo = {
            message: args.join(' '),
            timestamp: new Date().toISOString(),
            url: window.location.href,
            stack: new Error().stack
        };

        this.originalConsole.error('🚨 Erro crítico:', errorInfo);

        // Enviar para servidor
        this.sendErrorToServer(errorInfo);
    }

    /**
     * Enviar erro para servidor
     */
    async sendErrorToServer(errorInfo) {
        try {
            await fetch('api/error-log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(errorInfo)
            });
        } catch (error) {
            // Falha ao enviar erro para servidor
            this.originalConsole.error('Falha ao enviar erro para servidor:', error);
        }
    }

    /**
     * Log personalizado
     */
    log(level, message, data = null) {
        const timestamp = new Date().toISOString();
        const logEntry = {
            level,
            message,
            data,
            timestamp,
            url: window.location.href
        };

        switch (level) {
            case 'info':
                this.originalConsole.info('ℹ️', message, data);
                break;
            case 'warn':
                this.originalConsole.warn('⚠️', message, data);
                break;
            case 'error':
                this.originalConsole.error('❌', message, data);
                break;
            case 'success':
                this.originalConsole.log('✅', message, data);
                break;
            default:
                this.originalConsole.log('📝', message, data);
        }

        // Enviar para servidor em produção
        if (this.isProduction && level === 'error') {
            this.sendErrorToServer(logEntry);
        }
    }

    /**
     * Limpar console
     */
    clear() {
        if (!this.isProduction) {
            console.clear();
        }
    }

    /**
     * Agrupar logs
     */
    group(label) {
        if (!this.isProduction) {
            console.group(label);
        }
    }

    /**
     * Fechar grupo de logs
     */
    groupEnd() {
        if (!this.isProduction) {
            console.groupEnd();
        }
    }

    /**
     * Medir tempo
     */
    time(label) {
        if (!this.isProduction) {
            console.time(label);
        }
    }

    /**
     * Parar medição de tempo
     */
    timeEnd(label) {
        if (!this.isProduction) {
            console.timeEnd(label);
        }
    }

    /**
     * Configurar limites de console
     */
    setLimits(options = {}) {
        this.maxRepeats = options.maxRepeats || this.maxRepeats;
        this.rateLimitWindow = options.rateLimitWindow || this.rateLimitWindow;
        this.maxLogsPerWindow = options.maxLogsPerWindow || this.maxLogsPerWindow;
        
        console.log(`🛡️ Limites configurados: ${this.maxRepeats} repetições, ${this.maxLogsPerWindow} logs/${this.rateLimitWindow/1000}s`);
    }

    /**
     * Resetar contadores de limite
     */
    resetLimits() {
        this.logCache.clear();
        this.logCounts.clear();
        this.rateLimits.clear();
        console.log('🔄 Contadores de limite resetados');
    }

    /**
     * Obter estatísticas de logs
     */
    getLogStats() {
        const stats = {
            totalLogs: this.logCounts.size,
            rateLimits: this.rateLimits.size,
            cacheSize: this.logCache.size,
            maxRepeats: this.maxRepeats,
            rateLimitWindow: this.rateLimitWindow,
            maxLogsPerWindow: this.maxLogsPerWindow
        };
        
        console.table(stats);
        return stats;
    }

    /**
     * Habilitar/desabilitar controle de limite
     */
    setLimitControl(enabled) {
        this.limitControlEnabled = enabled;
        console.log(`🛡️ Controle de limite ${enabled ? 'habilitado' : 'desabilitado'}`);
    }

    /**
     * Modificar método shouldLog para considerar o controle
     */
    shouldLog(methodName, args) {
        // Se controle de limite desabilitado, sempre permitir
        if (this.limitControlEnabled === false) {
            return true;
        }
        
        const logKey = this.generateLogKey(methodName, args);
        const now = Date.now();
        
        // Verificar rate limiting
        if (!this.checkRateLimit(methodName, now)) {
            return false;
        }
        
        // Verificar repetições
        if (!this.checkRepeats(logKey)) {
            return false;
        }
        
        // Atualizar cache
        this.updateLogCache(logKey, now);
        
        return true;
    }

    /**
     * Restaurar console original
     */
    restore() {
        console.log = this.originalConsole.log;
        console.warn = this.originalConsole.warn;
        console.error = this.originalConsole.error;
        console.info = this.originalConsole.info;
        console.debug = this.originalConsole.debug;
    }
}

// Inicializar Console Guard
window.consoleGuard = new ConsoleGuard();

// Exportar para uso global
window.ConsoleGuard = ConsoleGuard;

// Exemplo de uso (comentado para produção):
/*
// Configurar limites personalizados
window.consoleGuard.setLimits({
    maxRepeats: 5,           // Máximo 5 repetições
    rateLimitWindow: 10000,  // Janela de 10 segundos
    maxLogsPerWindow: 20     // Máximo 20 logs por janela
});

// Ver estatísticas
window.consoleGuard.getLogStats();

// Resetar contadores
window.consoleGuard.resetLimits();

// Desabilitar controle de limite temporariamente
window.consoleGuard.setLimitControl(false);

// Reabilitar controle de limite
window.consoleGuard.setLimitControl(true);
*/

