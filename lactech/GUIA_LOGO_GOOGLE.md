# 🎨 Como Configurar Logo do Sistema no Google OAuth

## 📋 O que é isso?

Quando o usuário faz login com Google, o Google mostra um seletor de contas. Por padrão, aparece uma foto de perfil genérica (ícone azul com silhueta de pessoa). Você pode configurar para mostrar a logo do LacTech no lugar dessa foto genérica.

## 🚀 Como Configurar

### Passo 1: Acessar Google Cloud Console

1. Acesse: https://console.cloud.google.com/
2. Selecione o projeto que contém suas credenciais OAuth
3. Vá em **APIs & Services** > **OAuth consent screen**

### Passo 2: Configurar Logo da Aplicação

1. Na seção **Application information**:
   - **Application name**: `LacTech - Sistema de Gestão Leiteira`
   - **Application logo**: Clique em **Upload** e faça upload da logo do sistema
   - **Application home page**: `https://lactechsys.com`
   - **Application privacy policy link**: (opcional)
   - **Application terms of service link**: (opcional)
   - **Authorized domains**: Adicione `lactechsys.com`

2. **Logo recomendada**:
   - URL da logo: `https://i.postimg.cc/vmrkgDcB/lactech.png`
   - Ou baixe a logo e faça upload no Google Console
   - Tamanho recomendado: 120x120 pixels (mínimo)
   - Formato: PNG ou JPG
   - Fundo: Transparente ou branco (recomendado)

### Passo 3: Configurar Branding (Opcional)

Para personalizar ainda mais:

1. Vá em **APIs & Services** > **Branding**
2. Configure:
   - **Logo**: Upload da logo do LacTech
   - **Background color**: Cor do sistema (verde: #16a34a)
   - **Text color**: Branco ou preto (dependendo do contraste)

### Passo 4: Salvar e Publicar

1. Clique em **Save**
2. Se necessário, publique as alterações
3. Aguarde alguns minutos para as mudanças serem propagadas

## 📝 Notas Importantes

- ⚠️ As alterações podem levar alguns minutos para aparecer
- ⚠️ O logo precisa ter pelo menos 120x120 pixels
- ⚠️ O formato deve ser PNG ou JPG
- ⚠️ O logo aparece apenas para usuários que ainda não autorizaram o app
- ⚠️ Usuários que já autorizaram podem continuar vendo a foto antiga até limpar o cache

## 🔄 Forçar Atualização

Se o logo não aparecer imediatamente:

1. Limpe o cache do navegador
2. Ou use modo anônimo/privado
3. Ou aguarde alguns minutos (propagação do Google)

## 📸 Logo do Sistema

**URL da Logo**: `https://i.postimg.cc/vmrkgDcB/lactech.png`

Você pode usar essa URL diretamente ou baixar a imagem e fazer upload no Google Console.

---

**Dica**: Se você quiser que a logo apareça também no perfil dos usuários dentro do sistema, isso já está configurado no código. A logo do Google será usada como foto de perfil quando o usuário fizer login com Google.


