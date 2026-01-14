/**
 * Helper para obter caminhos de assets corretos baseado no base path
 */
export function getAssetPath(path: string): string {
  // Remove leading slash se existir
  const cleanPath = path.startsWith('/') ? path.slice(1) : path;
  // Retorna com o base path (BASE_URL já inclui a barra final)
  const baseUrl = import.meta.env.BASE_URL || '/safecode/';
  // Codificar espaços e caracteres especiais no nome do arquivo
  const encodedPath = cleanPath.split('/').map(part => 
    encodeURIComponent(part).replace(/%20/g, ' ')
  ).join('/');
  return `${baseUrl}${encodedPath}`;
}

/**
 * Helper específico para a logo
 */
export function getLogoPath(): string {
  return getAssetPath('logos (6).png');
}

