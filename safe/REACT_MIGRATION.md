# Migração para React + TypeScript

## Estrutura Planejada

```
src/
├── main.tsx                 # Entry point
├── App.tsx                  # Componente principal
├── index.html              # HTML base
├── components/
│   ├── Layout/
│   │   ├── Header.tsx
│   │   ├── Sidebar.tsx
│   │   ├── EditorArea.tsx
│   │   └── Terminal.tsx
│   ├── Editor/
│   │   ├── MonacoEditor.tsx
│   │   └── Tabs.tsx
│   ├── Sidebar/
│   │   ├── Explorer.tsx
│   │   ├── Git.tsx
│   │   └── Search.tsx
│   └── Preview/
│       └── LivePreview.tsx
├── contexts/
│   ├── IDEContext.tsx
│   └── FileSystemContext.tsx
├── hooks/
│   ├── useFileSystem.ts
│   ├── useTerminal.ts
│   └── useGit.ts
├── types/
│   └── index.ts
├── utils/
│   └── fileSystem.ts
└── styles/
    └── main.css
```

## Plano de Implementação

1. ✅ Configurar React + TypeScript
2. ⏳ Criar estrutura base
3. ⏳ Componentes principais
4. ⏳ Editor com Monaco
5. ⏳ Terminal funcional
6. ⏳ Git integration
7. ⏳ Live Preview



