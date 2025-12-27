# Configuração da API de IA para Assistente

O assistente de IA da IDE suporta três modos de operação:

## 🔧 Modos Disponíveis

### 1. **OpenAI (GPT-4o-mini)** - Padrão
Usa a API da OpenAI para respostas avançadas de IA.

### 2. **Claude (Anthropic)**
Usa a API da Anthropic (Claude).

### 3. **Local (Fallback)**
Modo sem API Key - respostas pré-definidas baseadas em palavras-chave.

---

## ⚙️ Como Configurar

### Opção 1: Variáveis de Ambiente do Sistema (Recomendado)

#### No Windows (XAMPP):
1. Abra as **Propriedades do Sistema**
2. Vá em **Configurações avançadas do sistema** → **Variáveis de ambiente**
3. Clique em **Novo** na seção "Variáveis do sistema"
4. Adicione:
   - **Nome**: `AI_API_KEY`
   - **Valor**: Sua API Key (veja como obter abaixo)
5. Clique em **OK** e reinicie o servidor Apache/XAMPP

#### No Linux/Mac:
```bash
export AI_API_KEY="sua-api-key-aqui"
```

Ou adicione ao `~/.bashrc` ou `~/.zshrc`:
```bash
echo 'export AI_API_KEY="sua-api-key-aqui"' >> ~/.bashrc
source ~/.bashrc
```

### Opção 2: Arquivo .env (Se tiver suporte)

Crie um arquivo `.env` na raiz do projeto:
```env
AI_API_KEY=sua-api-key-aqui
AI_PROVIDER=openai
```

### Opção 3: Modificar diretamente no código (Não recomendado para produção)

Edite `api/ai-assistant.php` linha 48:
```php
$apiKey = getenv('AI_API_KEY') ?: 'sua-api-key-aqui';
```

---

## 🔑 Como Obter API Keys

### Para OpenAI:
1. Acesse: https://platform.openai.com/api-keys
2. Faça login na sua conta OpenAI
3. Clique em **"Create new secret key"**
4. Copie a chave gerada (ela só aparece uma vez!)
5. Cole como valor da variável `AI_API_KEY`

**Modelo usado**: `gpt-4o-mini` (barato e rápido)

### Para Claude (Anthropic):
1. Acesse: https://console.anthropic.com/
2. Faça login na sua conta
3. Vá em **API Keys**
4. Clique em **"Create Key"**
5. Copie a chave gerada
6. Configure também: `AI_PROVIDER=claude`

**Modelo usado**: `claude-3-haiku-20240307`

---

## 🎯 Escolhendo o Provedor

Para escolher qual API usar, configure a variável `AI_PROVIDER`:

- `AI_PROVIDER=openai` (padrão) - Usa OpenAI
- `AI_PROVIDER=claude` - Usa Claude/Anthropic  
- `AI_PROVIDER=local` - Modo local (sem API Key necessária)

---

## ✅ Testando a Configuração

Após configurar, teste no assistente de IA da IDE:
1. Abra a IDE de código
2. Abra o painel de IA Assistente
3. Digite uma pergunta qualquer
4. Se funcionar, verá resposta da IA
5. Se não funcionar, verá o modo local (respostas pré-definidas)

---

## 💡 Modo Local (Sem API Key)

Se não configurar nenhuma API Key, o sistema funciona em modo local com respostas pré-definidas para:
- Perguntas sobre variáveis (`{{nome}}`, `{{codigo}}`, etc)
- Perguntas sobre responsividade/mobile
- Perguntas sobre CSS/estilização
- Outras perguntas recebem resposta genérica

---

## 🚨 Segurança

⚠️ **NUNCA** commite sua API Key no Git!
- Use variáveis de ambiente
- Adicione `.env` ao `.gitignore`
- Não compartilhe suas chaves

---

## 📝 Exemplo de Uso

### No Windows PowerShell (temporário):
```powershell
$env:AI_API_KEY = "sk-sua-chave-aqui"
```

### No Windows CMD (temporário):
```cmd
set AI_API_KEY=sk-sua-chave-aqui
```

### No Linux/Mac (temporário):
```bash
export AI_API_KEY="sk-sua-chave-aqui"
```

**Nota**: Configurações temporárias só duram enquanto a sessão do terminal/servidor estiver ativa.



