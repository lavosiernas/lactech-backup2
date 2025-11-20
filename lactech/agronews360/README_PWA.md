# PWA - Progressive Web App - AgroNews360

O AgroNews360 agora é um Progressive Web App (PWA), permitindo que os usuários instalem o app no celular ou computador.

## 📱 Funcionalidades

- ✅ Instalação como app nativo
- ✅ Funcionamento offline (cache de recursos)
- ✅ Ícone na tela inicial
- ✅ Tela de splash personalizada
- ✅ Atalhos rápidos (Notícias, Perfil)

## 🚀 Como Instalar

### No Mobile (Android/iPhone):
1. Acesse o site no navegador
2. Aparecerá um botão "Instalar App" no canto inferior direito
3. Ou use o menu do navegador: "Adicionar à tela inicial"

### No Desktop:
1. Acesse o site no Chrome/Edge
2. Clique no ícone de instalação na barra de endereço
3. Ou use o botão "Instalar App" que aparece

## 📁 Arquivos do PWA

- `manifest.json` - Configuração do app (nome, ícones, cores)
- `sw.js` - Service Worker (cache offline)
- Meta tags no `index.php` - Configuração PWA

## 🎨 Personalização

Para alterar:
- **Nome do app**: Edite `manifest.json` → `name` e `short_name`
- **Cores**: Edite `manifest.json` → `theme_color` e `background_color`
- **Ícones**: Substitua `assets/img/agro360.png` (recomendado: 512x512px)

## ⚙️ Requisitos

- HTTPS (obrigatório para PWA em produção)
- Service Worker registrado
- Manifest.json válido
- Ícones de 192x192 e 512x512 pixels

## 🔧 Troubleshooting

Se o botão de instalação não aparecer:
- Verifique se está usando HTTPS
- Limpe o cache do navegador
- Verifique o console para erros do Service Worker








