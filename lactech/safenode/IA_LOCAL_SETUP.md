# 🤖 Configuração de IA Local (100% Gratuita!)

Não quer gastar com APIs pagas? Configure uma IA local! Totalmente grátis e roda na sua máquina.

---

## 🚀 Opção 1: Ollama (Recomendado - Mais Fácil)

Ollama é a forma mais simples de rodar modelos de IA localmente.

### 📥 Instalação

#### Windows:
1. Baixe em: https://ollama.com/download/windows
2. Execute o instalador
3. Pronto! Ollama já está rodando

#### Linux/Mac:
```bash
curl -fsSL https://ollama.com/install.sh | sh
```

### 🎯 Configuração no SafeNode

1. **Baixe um modelo pequeno e rápido** (recomendado para começar):
```bash
ollama pull llama3.2:1b
```
ou modelos maiores (melhor qualidade, mais lento):
```bash
ollama pull llama3.2:3b
ollama pull mistral:7b
```

2. **Configure no SafeNode** (já está configurado por padrão!):
   - O sistema já detecta Ollama em `http://localhost:11434`
   - Configure `AI_PROVIDER=local` (ou deixe padrão)

3. **Pronto!** Use o assistente de IA normalmente.

### 📋 Comandos Úteis do Ollama

```bash
# Listar modelos instalados
ollama list

# Rodar um modelo manualmente (teste)
ollama run llama3.2:1b "Como criar um template HTML?"

# Remover um modelo
ollama rm nome-do-modelo
```

---

## 🎨 Opção 2: LM Studio

Interface gráfica bonita para gerenciar modelos de IA.

### 📥 Instalação:
1. Baixe em: https://lmstudio.ai/
2. Instale e abra
3. Baixe um modelo (ex: Llama 3, Mistral)
4. Configure o servidor local

### ⚙️ Configuração:
- Abra LM Studio → Local Server
- Inicie o servidor na porta 1234
- Configure no SafeNode:
  ```
  LOCAL_AI_URL=http://localhost:1234/v1/chat/completions
  AI_PROVIDER=local
  ```

---

## 🔧 Opção 3: Text Generation WebUI (Oobabooga)

Mais opções e controle, mas mais complexo.

1. Instale seguindo: https://github.com/oobabooga/text-generation-webui
2. Configure para rodar na porta padrão
3. Use a API local

---

## ⚙️ Configuração Avançada

### Variáveis de Ambiente Opcionais:

```bash
# URL da API local (padrão: http://localhost:11434/api/generate para Ollama)
LOCAL_AI_URL=http://localhost:11434/api/generate

# Modelo a usar (padrão: llama3.2:1b)
LOCAL_AI_MODEL=llama3.2:1b

# Provedor de IA (local usa IA local)
AI_PROVIDER=local
```

### Windows (PowerShell):
```powershell
$env:AI_PROVIDER = "local"
$env:LOCAL_AI_MODEL = "llama3.2:1b"
```

### Linux/Mac:
```bash
export AI_PROVIDER=local
export LOCAL_AI_MODEL=llama3.2:1b
```

---

## 🎯 Modelos Recomendados

### Para Começar (Pequenos e Rápidos):
- **llama3.2:1b** - Muito rápido, qualidade básica (~700MB)
- **llama3.2:3b** - Bom equilíbrio (~2GB)
- **mistral:7b** - Melhor qualidade (~4GB)

### Para Produção (Melhor Qualidade):
- **llama3:8b** - Excelente qualidade (~4.7GB)
- **mistral:7b-instruct** - Otimizado para instruções (~4GB)
- **codellama:7b** - Especializado em código (~3.8GB)

---

## ✅ Testando

1. **Teste se Ollama está rodando:**
```bash
curl http://localhost:11434/api/tags
```

2. **Teste um modelo:**
```bash
ollama run llama3.2:1b "Olá, você está funcionando?"
```

3. **No SafeNode:**
   - Abra a IDE de código
   - Use o assistente de IA
   - Deve funcionar sem APIs pagas!

---

## 🐛 Solução de Problemas

### Ollama não está rodando:
```bash
# Windows: Procure "Ollama" no menu iniciar e execute
# Linux/Mac:
ollama serve
```

### Porta ocupada:
- Ollama usa porta 11434 por padrão
- Altere se necessário: `LOCAL_AI_URL=http://localhost:PORTA/api/generate`

### Modelo não encontrado:
```bash
ollama pull nome-do-modelo
```

### Respostas muito lentas:
- Use modelos menores (1b ou 3b)
- Ou aumente o timeout no código

---

## 💡 Dicas

1. **Comece pequeno**: Use `llama3.2:1b` primeiro para testar
2. **Upgrade depois**: Se precisar de melhor qualidade, baixe modelos maiores
3. **Sem internet**: IA local funciona 100% offline!
4. **Privacidade**: Seus dados nunca saem da sua máquina
5. **Gratuito**: Zero custos, zero limites

---

## 🎉 Vantagens da IA Local

✅ **100% Gratuito** - Sem custos  
✅ **Offline** - Funciona sem internet  
✅ **Privacidade** - Dados não saem da sua máquina  
✅ **Sem limites** - Use quanto quiser  
✅ **Rápido** - Sem latência de rede (dependendo do hardware)  
✅ **Controle total** - Escolha o modelo que quiser  

---

**Pronto! Agora você tem IA gratuita rodando localmente! 🚀**





