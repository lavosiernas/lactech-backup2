# SafeNode - React App (Monitoramento em Tempo Real)

## 📋 Estrutura

```
app/
├── src/
│   ├── pages/
│   │   └── SecurityMonitor.tsx    # Página principal
│   ├── components/
│   │   ├── ThreatStats.tsx        # Cards de estatísticas
│   │   ├── ThreatTimeline.tsx     # Gráfico de timeline
│   │   └── RealTimeAlerts.tsx     # Lista de alertas
│   ├── api/
│   │   └── client.ts              # Cliente API TypeScript
│   ├── types/
│   │   └── security.ts            # Tipos TypeScript
│   ├── App.tsx
│   ├── main.tsx
│   └── index.css
├── package.json
├── tsconfig.json
├── vite.config.ts
└── tailwind.config.js
```

## 🚀 Instalação

```bash
cd safenode/app
npm install
```

## 🔨 Build

```bash
npm run build
```

O build será gerado em `dist/` e será carregado automaticamente pelo `security-monitor.php`.

## 🛠️ Desenvolvimento

```bash
npm run dev
```

Isso iniciará o servidor de desenvolvimento na porta 5173.

## 📝 Notas

- O React App consome as APIs PHP existentes (`/api/threat-detection.php`)
- O wrapper PHP (`security-monitor.php`) carrega o build do React
- Tudo está separado e organizado - não mistura com código PHP existente

