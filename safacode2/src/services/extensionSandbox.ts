/**
 * Extension Sandbox
 * Sistema seguro para executar extensões isoladas
 */

import type { InstalledExtension } from './extensionService';

/**
 * Sandbox seguro para executar código de extensões
 * Usa Web Workers ou iframes para isolamento
 */
export class ExtensionSandbox {
  private workers: Map<string, Worker> = new Map();
  private iframes: Map<string, HTMLIFrameElement> = new Map();

  /**
   * Cria um sandbox seguro para uma extensão
   */
  async createSandbox(extension: InstalledExtension): Promise<{ success: boolean; error?: string }> {
    try {
      // Em produção, você usaria uma das seguintes abordagens:
      // 1. Web Worker (para código JavaScript puro)
      // 2. iframe com sandbox (para HTML/CSS)
      // 3. WebAssembly (para código compilado)
      
      // Por enquanto, apenas simulamos
      console.log(`[ExtensionSandbox] Creating sandbox for: ${extension.id}`);
      
      return { success: true };
    } catch (error) {
      console.error(`Error creating sandbox for ${extension.id}:`, error);
      return { 
        success: false, 
        error: error instanceof Error ? error.message : 'Failed to create sandbox' 
      };
    }
  }

  /**
   * Executa código da extensão no sandbox
   */
  async executeInSandbox(
    extensionId: string, 
    code: string, 
    api: any
  ): Promise<{ success: boolean; result?: any; error?: string }> {
    try {
      // Validação de segurança básica
      if (this.containsDangerousCode(code)) {
        return { 
          success: false, 
          error: 'Extension contains potentially dangerous code' 
        };
      }

      // Em produção, executaria o código em um Worker isolado
      // Por enquanto, apenas logamos
      console.log(`[ExtensionSandbox] Executing code for: ${extensionId}`);
      
      return { success: true };
    } catch (error) {
      console.error(`Error executing code in sandbox:`, error);
      return { 
        success: false, 
        error: error instanceof Error ? error.message : 'Execution failed' 
      };
    }
  }

  /**
   * Verifica se o código contém padrões perigosos
   */
  private containsDangerousCode(code: string): boolean {
    const dangerousPatterns = [
      /eval\s*\(/,
      /Function\s*\(/,
      /document\.cookie/,
      /localStorage\.clear/,
      /sessionStorage\.clear/,
      /XMLHttpRequest/,
      /fetch\s*\(/,
      /importScripts/,
      /postMessage/,
    ];

    return dangerousPatterns.some(pattern => pattern.test(code));
  }

  /**
   * Destrói o sandbox de uma extensão
   */
  destroySandbox(extensionId: string): void {
    const worker = this.workers.get(extensionId);
    if (worker) {
      worker.terminate();
      this.workers.delete(extensionId);
    }

    const iframe = this.iframes.get(extensionId);
    if (iframe) {
      iframe.remove();
      this.iframes.delete(extensionId);
    }
  }

  /**
   * Limpa todos os sandboxes
   */
  destroyAll(): void {
    this.workers.forEach((worker, id) => {
      worker.terminate();
    });
    this.workers.clear();

    this.iframes.forEach((iframe) => {
      iframe.remove();
    });
    this.iframes.clear();
  }
}

/**
 * API segura exposta para extensões
 */
export function createExtensionAPI(extension: InstalledExtension) {
  return {
    // Window API
    window: {
      showInformationMessage: (message: string) => {
        console.log(`[Extension ${extension.id}] Info: ${message}`);
        // Em produção, mostraria uma notificação
      },
      showWarningMessage: (message: string) => {
        console.log(`[Extension ${extension.id}] Warning: ${message}`);
      },
      showErrorMessage: (message: string) => {
        console.log(`[Extension ${extension.id}] Error: ${message}`);
      },
    },

    // Commands API
    commands: {
      registerCommand: (command: string, callback: (...args: any[]) => void) => {
        console.log(`[Extension ${extension.id}] Registering command: ${command}`);
        // Registrar comando no sistema global
        (window as any).extensionCommands = (window as any).extensionCommands || {};
        (window as any).extensionCommands[command] = callback;
      },
      executeCommand: (command: string, ...args: any[]) => {
        const commands = (window as any).extensionCommands || {};
        if (commands[command]) {
          return commands[command](...args);
        }
      },
    },

    // Languages API
    languages: {
      registerCompletionItemProvider: (
        language: string, 
        provider: {
          provideCompletionItems: (document: any, position: any) => any[];
        }
      ) => {
        console.log(`[Extension ${extension.id}] Registering completion provider for: ${language}`);
        // Registrar provider no Monaco Editor
      },
      registerHoverProvider: (language: string, provider: any) => {
        console.log(`[Extension ${extension.id}] Registering hover provider for: ${language}`);
      },
    },

    // Workspace API
    workspace: {
      getConfiguration: (section?: string) => {
        // Retornar configurações da extensão
        return {};
      },
      onDidChangeConfiguration: (callback: () => void) => {
        // Registrar listener de mudanças de configuração
        return { dispose: () => {} };
      },
    },

    // Extension context
    extension: {
      extensionPath: `/extensions/${extension.id}`,
      extensionUri: `/extensions/${extension.id}`,
    },
  };
}

