# Badges para GitHub README

## Badge "Dev SAFECODE"

### Opção 1: Badge Simples (Shields.io)
```markdown
![Dev SAFECODE](https://img.shields.io/badge/Dev-SAFECODE-000000?style=for-the-badge&logo=visual-studio-code&logoColor=white)
```

### Opção 2: Badge com Logo Customizado
```markdown
![Dev SAFECODE](https://img.shields.io/badge/Dev%20SAFECODE-000000?style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==)
```

### Opção 3: Badge Estilizado (Recomendado)
```markdown
[![Dev SAFECODE](https://img.shields.io/badge/Dev-SAFECODE-000000?style=for-the-badge&logo=github&logoColor=white)](https://safenode.cloud/safecode)
```

### Opção 4: Badge com Link e Estilo Customizado
```markdown
<a href="https://safenode.cloud/safecode">
  <img src="https://img.shields.io/badge/Dev%20SAFECODE-000000?style=for-the-badge&logo=visual-studio-code&logoColor=white" alt="Dev SAFECODE" />
</a>
```

### Opção 5: Badge Minimalista Preto e Branco
```markdown
![Dev SAFECODE](https://img.shields.io/badge/Dev%20SAFECODE-000000?style=flat-square&labelColor=000000&color=ffffff)
```

### Opção 6: Badge com Gradiente
```markdown
![Dev SAFECODE](https://img.shields.io/badge/Dev%20SAFECODE-000000?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDJMMTMuMDkgOC4yNkwyMCA5TDEzLjA5IDE1Ljc0TDEyIDIyTDEwLjkxIDE1Ljc0TDQgOUwxMC45MSA4LjI2TDEyIDJaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K&logoColor=white)
```

## Badge SVG Customizado com Logo SAFECODE (Recomendado)

O arquivo `public/badge-dev-safecode.svg` já está criado e inclui a logo do PostImg.

Use no README:
```markdown
<a href="https://safenode.cloud/safecode">
  <img src="./public/badge-dev-safecode.svg" alt="Dev SAFECODE" />
</a>
```

O SVG inclui:
- Logo da SAFECODE do PostImg: `https://i.postimg.cc/9fMqbs8k/logos-(6).png`
- Texto "Dev SAFECODE"
- Gradiente preto elegante

## Exemplo de Uso no README.md

### Com Badge SVG (Recomendado - inclui logo SAFECODE):

```markdown
# SafeCode IDE

<a href="https://safenode.cloud/safecode">
  <img src="./public/badge-dev-safecode.svg" alt="Dev SAFECODE" />
</a>

A powerful, modern code editor built with React, TypeScript, and Vite.
```

### Com Badge Shields.io:

```markdown
# SafeCode IDE

[![Dev SAFECODE](https://img.shields.io/badge/Dev-SAFECODE-000000?style=for-the-badge&logo=visual-studio-code&logoColor=white)](https://safenode.cloud/safecode)

A powerful, modern code editor built with React, TypeScript, and Vite.
```

