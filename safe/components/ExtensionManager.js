/**
 * SafeCode Extension Manager
 * Handles extension loading, activation, and API exposure.
 */
class ExtensionManager {
    constructor(ide) {
        this.ide = ide;
        this.extensions = new Map();
        this.commands = new Map();
        this.eventListeners = new Map();
    }

    async init() {
        console.log('[ExtensionManager] Initializing extension system...');
        const result = await window.electronAPI.extensions.list();

        if (result.success) {
            for (const ext of result.extensions) {
                await this.loadExtension(ext);
            }
        } else {
            console.error('[ExtensionManager] Failed to list extensions:', result.error);
        }
    }

    async loadExtension(ext) {
        console.log(`[ExtensionManager] Loading extension: ${ext.manifest.displayName || ext.id}`);

        // Prepare API for this specific extension
        const api = this.createExtensionAPI(ext);

        try {
            // Fetch main script content
            const scriptFile = ext.manifest.main || 'extension.js';
            const scriptResult = await window.electronAPI.extensions.readFile(ext.id, scriptFile);

            if (scriptResult.success) {
                // Execute script in a sandbox-like environment (wrapped in a function)
                const extensionFunc = new Function('safecode', scriptResult.content);

                const extensionContext = {
                    api,
                    manifest: ext.manifest,
                    active: false
                };

                // Run the extension's activate function
                const exports = extensionFunc(api);

                // If the extension returns an object with activate/deactivate
                if (exports && typeof exports.activate === 'function') {
                    exports.activate(extensionContext);
                }

                this.extensions.set(ext.id, {
                    ...ext,
                    context: extensionContext,
                    exports
                });

                console.log(`[ExtensionManager] Extension activated: ${ext.id}`);
            }
        } catch (error) {
            console.error(`[ExtensionManager] Error loading extension ${ext.id}:`, error);
        }
    }

    createExtensionAPI(ext) {
        const self = this;
        return {
            // Command Registry
            commands: {
                registerCommand: (commandId, callback, metadata = {}) => {
                    console.log(`[ExtensionAPI] ${ext.id} registered command: ${commandId}`);
                    self.commands.set(commandId, {
                        id: commandId,
                        callback,
                        title: metadata.title || commandId,
                        description: metadata.description || '',
                        icon: metadata.icon || 'terminal',
                        extensionId: ext.id
                    });
                }
            },

            // UI Components
            window: {
                showInformationMessage: (message) => {
                    alert(`SafeCode (Info): ${message}`);
                },
                showWarningMessage: (message) => {
                    alert(`SafeCode (Warning): ${message}`);
                },
                showErrorMessage: (message) => {
                    alert(`SafeCode (Error): ${message}`);
                }
            },

            // Workspace access
            workspace: {
                onDidSaveFile: (callback) => self.addEventListener('file-saved', callback),
                onDidOpenFile: (callback) => self.addEventListener('file-opened', callback),
            },

            // Language Intelligence API
            languages: {
                registerCompletionItemProvider: (language, provider) => {
                    return self.ide.editorManager.registerCompletionProvider(language, provider);
                },
                registerDefinitionProvider: (language, provider) => {
                    return self.ide.editorManager.registerDefinitionProvider(language, provider);
                },
                registerHoverProvider: (language, provider) => {
                    return self.ide.editorManager.registerHoverProvider(language, provider);
                }
            },

            // Source Control Management API
            scm: {
                getStatus: () => window.electronAPI.git.status(self.ide.sidebarManager.workspacePath),
                stage: (filePath) => window.electronAPI.git.stage(self.ide.sidebarManager.workspacePath, filePath),
                commit: (message) => window.electronAPI.git.commit(self.ide.sidebarManager.workspacePath, message)
            }
        };
    }

    // Command Execution
    executeCommand(commandId, ...args) {
        const cmd = this.commands.get(commandId);
        if (cmd) {
            try {
                return cmd.callback(...args);
            } catch (error) {
                console.error(`[ExtensionManager] Error executing command ${commandId}:`, error);
            }
        } else {
            console.warn(`[ExtensionManager] Command not found: ${commandId}`);
        }
    }

    // Event Bus
    addEventListener(event, callback) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push(callback);
    }

    emitEvent(event, data) {
        if (this.eventListeners.has(event)) {
            this.eventListeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`[ExtensionManager] Error in event listener for ${event}:`, error);
                }
            });
        }
    }
}

// Export for use in main.js
window.ExtensionManager = ExtensionManager;
