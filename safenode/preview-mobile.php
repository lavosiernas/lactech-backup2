<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Preview - SafeNode Relay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }
        
        .preview-container {
            max-width: 100%;
            margin: 0 auto;
            background: #ffffff;
            min-height: 100vh;
        }
        
        .expired-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            text-align: center;
            background: #f5f5f5;
        }
        
        .expired-message h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 12px;
        }
        
        .expired-message p {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .error-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            text-align: center;
            background: #f5f5f5;
        }
        
        .error-message h1 {
            font-size: 24px;
            color: #d32f2f;
            margin-bottom: 12px;
        }
        
        .error-message p {
            font-size: 16px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="preview-container" id="previewContent">
        <!-- Conteúdo será inserido aqui -->
    </div>

    <script>
        // Obter token da URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        
        if (!token) {
            document.getElementById('previewContent').innerHTML = `
                <div class="error-message">
                    <h1>Token não fornecido</h1>
                    <p>O link de preview não contém um token válido.</p>
                </div>
            `;
        } else {
            let lastUpdateTime = 0;
            let pollingInterval = null;
            
            // Função para carregar preview
            async function loadPreview() {
                try {
                    const currentPath = window.location.pathname;
                    const basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
                    const apiPath = basePath + 'api/get-mobile-preview.php';
                    
                    const response = await fetch(apiPath + '?token=' + token + '&t=' + Date.now());
                    const data = await response.json();
                    
                    if (data.success) {
                        // Verificar se houve atualização
                        const updateTime = data.updated_at || data.created_at || 0;
                        if (updateTime > lastUpdateTime) {
                            lastUpdateTime = updateTime;
                            
                            // Limpar e inserir HTML do template
                            let html = data.html;
                            
                            // Remover estilos inline problemáticos e classes de edição que possam ter sobrado
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;
                            
                            // Remover qualquer elemento com classes de edição
                            tempDiv.querySelectorAll('[class*="component-"], [class*="draggable"], [class*="cursor-"]').forEach(el => {
                                el.className = el.className.split(' ').filter(c => 
                                    !c.includes('component-') && 
                                    !c.includes('draggable') && 
                                    !c.includes('cursor-') &&
                                    !c.includes('border-')
                                ).join(' ');
                            });
                            
                            // Remover atributos de edição
                            tempDiv.querySelectorAll('[data-type], [data-x], [data-y]').forEach(el => {
                                el.removeAttribute('data-type');
                                el.removeAttribute('data-x');
                                el.removeAttribute('data-y');
                            });
                            
                            // Inserir HTML limpo
                            document.getElementById('previewContent').innerHTML = tempDiv.innerHTML;
                        }
                        return true;
                    } else {
                        if (data.expired) {
                            document.getElementById('previewContent').innerHTML = `
                                <div class="expired-message">
                                    <h1>Link Expirado</h1>
                                    <p>${data.message || 'Este link não está mais disponível.'}</p>
                                    <p style="font-size: 14px; color: #999; margin-top: 8px;">Os links de preview expiram após 1 hora.</p>
                                </div>
                            `;
                            // Parar polling se expirou
                            if (pollingInterval) {
                                clearInterval(pollingInterval);
                                pollingInterval = null;
                            }
                            return false;
                        }
                        return true;
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    return true; // Continuar tentando
                }
            }
            
            // Carregar preview inicial
            loadPreview().then(continuePolling => {
                if (continuePolling !== false) {
                    // Atualizar a cada 2 segundos para preview em tempo real
                    pollingInterval = setInterval(() => {
                        loadPreview();
                    }, 2000);
                }
            });
        }
    </script>
</body>
</html>

