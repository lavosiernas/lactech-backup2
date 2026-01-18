/**
 * SafeCode IDE - Settings API Service
 */

const API_BASE = import.meta.env.VITE_API_BASE || '/safecode/api';

export interface UserSettings {
  user_id: number;
  editor_settings: {
    fontSize?: number;
    fontFamily?: string;
    theme?: 'dark' | 'light';
    wordWrap?: boolean;
    lineNumbers?: boolean;
    tabSize?: number;
    minimap?: boolean;
    autoSave?: boolean;
  };
  ide_settings: {
    sidebarOpen?: boolean;
    terminalOpen?: boolean;
    previewOpen?: boolean;
  };
  keybindings: Record<string, string>;
  extensions: any[];
}

/**
 * Obter token de autenticação
 */
function getAuthToken(): string | null {
  const authStorage = localStorage.getItem('safecode-auth-storage');
  if (!authStorage) return null;
  
  try {
    const parsed = JSON.parse(authStorage);
    return parsed.state?.token || null;
  } catch {
    return null;
  }
}

/**
 * Fazer requisição autenticada
 */
async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getAuthToken();
  
  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    },
  });

  const data = await response.json();

  if (!response.ok || !data.success) {
    throw new Error(data.error || 'Erro na requisição');
  }

  return data;
}

/**
 * Buscar configurações do usuário
 */
export async function getSettings(): Promise<UserSettings> {
  const data = await apiRequest<{ settings: UserSettings }>('/settings.php?action=get');
  return data.settings;
}

/**
 * Atualizar configurações do usuário
 */
export async function updateSettings(settings: Partial<UserSettings>): Promise<UserSettings> {
  const data = await apiRequest<{ settings: UserSettings }>('/settings.php?action=update', {
    method: 'PUT',
    body: JSON.stringify(settings),
  });
  return data.settings;
}

