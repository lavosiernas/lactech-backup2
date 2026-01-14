/**
 * Extension Service
 * Gerencia extensões reais do Open VSX Registry
 * Sistema seguro e compatível com VS Code extensions
 */

export interface VSXExtension {
  namespace: string;
  name: string;
  displayName: string;
  description: string;
  publisher: {
    displayName: string;
    loginName: string;
  };
  version: string;
  downloadUrl: string;
  iconUrl?: string;
  downloadCount: number;
  averageRating?: number;
  reviewCount?: number;
  files: {
    download: string;
    manifest: string;
    readme?: string;
  };
  metadata: {
    id: string;
    namespace: string;
    name: string;
    version: string;
    publisher: string;
    displayName: string;
    description: string;
    categories: string[];
    tags: string[];
    license: string;
    homepage?: string;
    repository?: string;
    bugs?: string;
    engines: {
      vscode: string;
    };
    activationEvents?: string[];
    contributes?: {
      commands?: Array<{
        command: string;
        title: string;
        category?: string;
      }>;
      languages?: Array<{
        id: string;
        aliases?: string[];
        extensions?: string[];
      }>;
      themes?: Array<{
        label: string;
        uiTheme: string;
        path: string;
      }>;
      grammars?: Array<{
        language: string;
        scopeName: string;
        path: string;
      }>;
    };
  };
}

export interface InstalledExtension {
  id: string;
  namespace: string;
  name: string;
  displayName: string;
  version: string;
  publisher: string;
  description: string;
  iconUrl?: string;
  enabled: boolean;
  installedAt: Date;
  manifest: any;
}

const OPEN_VSX_API = 'https://open-vsx.org/api';
const STORAGE_KEY = 'safecode-installed-extensions';

/**
 * Busca extensões no Open VSX Registry
 */
export async function searchExtensions(query: string, limit: number = 50): Promise<VSXExtension[]> {
  try {
    const response = await fetch(
      `${OPEN_VSX_API}/-/search?query=${encodeURIComponent(query)}&size=${limit}`
    );
    
    if (!response.ok) {
      throw new Error(`Failed to search extensions: ${response.statusText}`);
    }
    
    const data = await response.json();
    return data.extensions || [];
  } catch (error) {
    console.error('Error searching extensions:', error);
    return [];
  }
}

/**
 * Busca extensões populares
 */
export async function getPopularExtensions(limit: number = 20): Promise<VSXExtension[]> {
  try {
    const response = await fetch(
      `${OPEN_VSX_API}/-/search?sortBy=downloadCount&sortOrder=desc&size=${limit}`,
      {
        headers: {
          'Accept': 'application/json',
        },
      }
    );
    
    if (!response.ok) {
      throw new Error(`Failed to fetch popular extensions: ${response.statusText}`);
    }
    
    const data = await response.json();
    
    if (data.extensions && Array.isArray(data.extensions)) {
      return data.extensions
        .filter((ext: any) => ext && ext.name) // Filtrar extensões inválidas
        .map((ext: any) => {
          const publisher = ext.publisher || ext.namespace || 'unknown';
    // Tentar diferentes URLs para o ícone
    let iconUrl = ext.files?.icon || ext.iconUrl;
    if (!iconUrl && ext.namespace && ext.name) {
      // Tentar URL padrão do Open VSX
      iconUrl = `https://open-vsx.org/api/${ext.namespace}/${ext.name}/file/icon`;
    }

    return {
      namespace: ext.namespace || publisher,
      name: ext.name,
      displayName: ext.displayName || ext.name || 'Unknown Extension',
      description: ext.description || '',
      publisher: {
        displayName: typeof publisher === 'string' ? publisher : (publisher?.displayName || publisher?.loginName || 'Unknown'),
        loginName: typeof publisher === 'string' ? publisher : (publisher?.loginName || publisher || 'unknown'),
      },
      version: ext.version || '1.0.0',
      downloadUrl: ext.files?.download || '',
      iconUrl: iconUrl,
      downloadCount: ext.downloadCount || 0,
      averageRating: ext.averageRating,
      reviewCount: ext.reviewCount,
      files: {
        download: ext.files?.download || '',
        manifest: ext.files?.manifest || '',
        readme: ext.files?.readme,
      },
      metadata: ext,
    };
        });
    }
    
    return [];
  } catch (error) {
    console.error('Error fetching popular extensions:', error);
    throw error;
  }
}

/**
 * Busca detalhes de uma extensão específica
 */
export async function getExtensionDetails(namespace: string, name: string, version?: string): Promise<VSXExtension | null> {
  try {
    const url = version 
      ? `${OPEN_VSX_API}/${namespace}/${name}/${version}`
      : `${OPEN_VSX_API}/${namespace}/${name}`;
    
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
      },
    });
    
    if (!response.ok) {
      throw new Error(`Failed to fetch extension: ${response.statusText}`);
    }
    
    const ext = await response.json();
    
    return {
      namespace: ext.namespace || ext.publisher,
      name: ext.name,
      displayName: ext.displayName || ext.name,
      description: ext.description || '',
      publisher: {
        displayName: ext.publisher?.displayName || ext.publisher || 'Unknown',
        loginName: ext.publisher?.loginName || ext.publisher || 'unknown',
      },
      version: ext.version || '1.0.0',
      downloadUrl: ext.files?.download || '',
      iconUrl: ext.files?.icon || ext.iconUrl,
      downloadCount: ext.downloadCount || 0,
      averageRating: ext.averageRating,
      reviewCount: ext.reviewCount,
      files: {
        download: ext.files?.download || '',
        manifest: ext.files?.manifest || '',
        readme: ext.files?.readme,
      },
      metadata: ext,
    };
  } catch (error) {
    console.error('Error fetching extension details:', error);
    return null;
  }
}

/**
 * Obtém extensões instaladas do localStorage
 */
export function getInstalledExtensions(): InstalledExtension[] {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (!stored) return [];
    
    const extensions = JSON.parse(stored);
    return extensions.map((ext: any) => ({
      ...ext,
      installedAt: new Date(ext.installedAt),
    }));
  } catch (error) {
    console.error('Error loading installed extensions:', error);
    return [];
  }
}

/**
 * Salva extensões instaladas no localStorage
 */
export function saveInstalledExtensions(extensions: InstalledExtension[]): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(extensions));
  } catch (error) {
    console.error('Error saving installed extensions:', error);
  }
}

/**
 * Instala uma extensão (simulado - em produção seria necessário baixar e extrair o .vsix)
 */
export async function installExtension(extension: VSXExtension): Promise<{ success: boolean; error?: string }> {
  try {
    const installed = getInstalledExtensions();
    
    // Verificar se já está instalada
    const extensionId = `${extension.namespace}.${extension.name}`;
    if (installed.some(ext => ext.id === extensionId)) {
      return { success: false, error: 'Extension already installed' };
    }
    
    // Tentar buscar manifest completo, mas usar metadata como fallback
    let manifest = extension.metadata || {};
    
    if (extension.files?.manifest) {
      try {
        const manifestResponse = await fetch(extension.files.manifest, {
          headers: {
            'Accept': 'application/json',
          },
        });
        
        if (manifestResponse.ok) {
          const contentType = manifestResponse.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            const manifestData = await manifestResponse.json();
            if (manifestData && typeof manifestData === 'object') {
              manifest = manifestData;
            }
          }
        }
      } catch (manifestError) {
        // Se falhar ao buscar manifest, usar metadata como fallback
        console.warn(`Could not fetch manifest for ${extensionId}, using metadata:`, manifestError);
      }
    }
    
    // Adicionar à lista de instaladas
    const newExtension: InstalledExtension = {
      id: extensionId,
      namespace: extension.namespace,
      name: extension.name,
      displayName: extension.displayName || extension.metadata?.displayName || extension.name,
      version: extension.version,
      publisher: extension.publisher?.displayName || extension.publisher?.loginName || extension.metadata?.publisher || 'Unknown',
      description: extension.description || extension.metadata?.description || '',
      iconUrl: extension.iconUrl,
      enabled: true,
      installedAt: new Date(),
      manifest,
    };
    
    installed.push(newExtension);
    saveInstalledExtensions(installed);
    
    // Ativar extensão
    await activateExtension(newExtension);
    
    return { success: true };
  } catch (error) {
    console.error('Error installing extension:', error);
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Failed to install extension' 
    };
  }
}

/**
 * Desinstala uma extensão
 */
export async function uninstallExtension(extensionId: string): Promise<{ success: boolean; error?: string }> {
  try {
    const installed = getInstalledExtensions();
    const extension = installed.find(ext => ext.id === extensionId);
    
    if (!extension) {
      return { success: false, error: 'Extension not found' };
    }
    
    // Desativar extensão
    await deactivateExtension(extension);
    
    // Remover da lista
    const updated = installed.filter(ext => ext.id !== extensionId);
    saveInstalledExtensions(updated);
    
    return { success: true };
  } catch (error) {
    console.error('Error uninstalling extension:', error);
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Failed to uninstall extension' 
    };
  }
}

/**
 * Ativa uma extensão (carrega e executa)
 */
async function activateExtension(extension: InstalledExtension): Promise<void> {
  try {
    console.log(`[ExtensionService] Activating extension: ${extension.id}`);
    
    // Importar sandbox dinamicamente
    const { ExtensionSandbox, createExtensionAPI } = await import('./extensionSandbox');
    const sandbox = new ExtensionSandbox();
    
    // Criar API segura para a extensão
    const extensionAPI = createExtensionAPI(extension);
    
    // Verificar se a extensão tem código para executar
    if (extension.manifest?.main) {
      // Em produção, você baixaria e executaria o código da extensão
      // Por enquanto, apenas registramos os recursos que a extensão contribui
      
      // Registrar comandos
      if (extension.manifest.contributes?.commands) {
        extension.manifest.contributes.commands.forEach((cmd: any) => {
          console.log(`[Extension ${extension.id}] Registering command: ${cmd.command}`);
          // Registrar comando no sistema global
          (window as any).extensionCommands = (window as any).extensionCommands || {};
          (window as any).extensionCommands[cmd.command] = () => {
            console.log(`[Extension ${extension.id}] Command executed: ${cmd.command}`);
          };
        });
      }
      
      // Registrar temas
      if (extension.manifest.contributes?.themes) {
        console.log(`[Extension ${extension.id}] Registering ${extension.manifest.contributes.themes.length} theme(s)`);
        // Carregar e aplicar temas
      }
      
      // Registrar gramáticas (syntax highlighting)
      if (extension.manifest.contributes?.grammars) {
        console.log(`[Extension ${extension.id}] Registering ${extension.manifest.contributes.grammars.length} grammar(s)`);
        // Carregar gramáticas para o Monaco Editor
      }
    }
    
    // Marcar extensão como ativa no storage
    const installed = getInstalledExtensions();
    const updated = installed.map(ext => 
      ext.id === extension.id ? { ...ext, enabled: true } : ext
    );
    saveInstalledExtensions(updated);
    
  } catch (error) {
    console.error(`Error activating extension ${extension.id}:`, error);
    throw error;
  }
}

/**
 * Desativa uma extensão
 */
async function deactivateExtension(extension: InstalledExtension): Promise<void> {
  try {
    console.log(`[ExtensionService] Deactivating extension: ${extension.id}`);
    
    // Limpar comandos registrados
    if (extension.manifest?.contributes?.commands) {
      extension.manifest.contributes.commands.forEach((cmd: any) => {
        if ((window as any).extensionCommands) {
          delete (window as any).extensionCommands[cmd.command];
        }
      });
    }
    
    // Limpar outros recursos (temas, gramáticas, etc.)
    // Em produção, você limparia todos os recursos registrados
    
    // Marcar extensão como desativada no storage
    const installed = getInstalledExtensions();
    const updated = installed.map(ext => 
      ext.id === extension.id ? { ...ext, enabled: false } : ext
    );
    saveInstalledExtensions(updated);
    
  } catch (error) {
    console.error(`Error deactivating extension ${extension.id}:`, error);
    throw error;
  }
}

/**
 * Alterna o estado enabled/disabled de uma extensão
 */
export async function toggleExtension(extensionId: string): Promise<{ success: boolean; error?: string }> {
  try {
    const installed = getInstalledExtensions();
    const extension = installed.find(ext => ext.id === extensionId);
    
    if (!extension) {
      return { success: false, error: 'Extension not found' };
    }
    
    extension.enabled = !extension.enabled;
    
    if (extension.enabled) {
      await activateExtension(extension);
    } else {
      await deactivateExtension(extension);
    }
    
    saveInstalledExtensions(installed);
    return { success: true };
  } catch (error) {
    console.error('Error toggling extension:', error);
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Failed to toggle extension' 
    };
  }
}

/**
 * Verifica se uma extensão está instalada
 */
export function isExtensionInstalled(extensionId: string): boolean {
  const installed = getInstalledExtensions();
  return installed.some(ext => ext.id === extensionId);
}

/**
 * Obtém uma extensão instalada
 */
export function getInstalledExtension(extensionId: string): InstalledExtension | undefined {
  const installed = getInstalledExtensions();
  return installed.find(ext => ext.id === extensionId);
}

/**
 * Obtém todos os temas disponíveis das extensões instaladas
 */
export interface ExtensionTheme {
  id: string;
  label: string;
  uiTheme: string;
  path: string;
  extensionId: string;
  extensionName: string;
}

export function getAvailableThemes(): ExtensionTheme[] {
  const installed = getInstalledExtensions();
  const themes: ExtensionTheme[] = [];

  installed.forEach(ext => {
    if (ext.manifest?.contributes?.themes && Array.isArray(ext.manifest.contributes.themes)) {
      ext.manifest.contributes.themes.forEach((theme: any) => {
        themes.push({
          id: `${ext.id}-${theme.label || 'default'}`,
          label: theme.label || 'Default Theme',
          uiTheme: theme.uiTheme || 'vs-dark',
          path: theme.path || '',
          extensionId: ext.id,
          extensionName: ext.displayName,
        });
      });
    }
  });

  return themes;
}

