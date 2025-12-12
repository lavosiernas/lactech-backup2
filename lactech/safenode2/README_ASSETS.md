# Configuração de Assets

Para que as imagens funcionem corretamente, você precisa copiar os arquivos da pasta `assets/img` para `public/assets/img`.

## Opção 1: Script Automático

Execute o comando:
```bash
npm run copy-assets
```

Ou ele será executado automaticamente ao rodar `npm run dev` ou `npm run build`.

## Opção 2: Cópia Manual

1. Crie a pasta `public/assets/img` se não existir
2. Copie todos os arquivos de `assets/img/` para `public/assets/img/`

## Estrutura Esperada

```
safenode2/
├── assets/
│   └── img/
│       ├── logos (4).png
│       ├── logos (5).png
│       ├── logos (6).png
│       └── ...
└── public/
    └── assets/
        └── img/
            ├── logos (4).png
            ├── logos (5).png
            ├── logos (6).png
            └── ...
```

## Nota

O Next.js serve arquivos estáticos da pasta `public/`. Por isso, as imagens precisam estar lá para serem acessíveis via `/assets/img/...`.





