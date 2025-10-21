# 🐄 INSTALAÇÃO DO SISTEMA DE CONTROLE DE NOVILHAS

## ✅ **COMPATÍVEL COM SEU BANCO ATUAL** `lactech_lgmato`

---

## 📋 **O QUE ESSE SQL FAZ:**

### **1. MANTÉM SUA TABELA `heifer_costs` EXISTENTE** ✅
- **NÃO apaga** seus dados atuais
- **ADICIONA** novos campos opcionais
- **MANTÉM** compatibilidade com registros antigos

### **2. ADICIONA 4 NOVAS TABELAS:**
- `heifer_phases` - Fases de criação (Aleitamento → Pré-parto)
- `heifer_cost_categories` - 18 Categorias detalhadas
- `heifer_daily_consumption` - Consumo diário opcional
- `heifer_price_history` - Histórico de preços

### **3. ATUALIZA AS VIEWS EXISTENTES:**
- `v_heifer_total_costs` - Agora com fase atual
- `v_heifer_costs_by_category` - Com novas categorias
- `v_heifer_costs_by_phase` - **NOVA** - Custos por fase

### **4. ADICIONA AUTOMAÇÃO:**
- **Trigger** que detecta fase automaticamente
- **Procedure** para consultar fase de novilha
- **Cálculo automático** de custos totais

---

## 🚀 **COMO INSTALAR:**

### **Passo 1: Backup (Importante!)**
```sql
-- No phpMyAdmin, vá em "Exportar" e salve o banco atual
```

### **Passo 2: Executar SQL**
1. Abra o phpMyAdmin
2. Selecione o banco `lactech_lgmato`
3. Vá na aba "SQL"
4. **Copie e cole TODO o conteúdo** de: `sql_heifer_system_compatible.sql`
5. Clique em "Executar"

### **Passo 3: Verificar Instalação**
```sql
-- Execute para verificar:
SHOW TABLES LIKE 'heifer%';
-- Deve mostrar 5 tabelas
```

---

## 📊 **ESTRUTURA CRIADA:**

### **Tabela `heifer_costs` (Atualizada)**
```
Campos MANTIDOS:
✅ id
✅ animal_id
✅ cost_date
✅ cost_category (Alimentação, Medicamentos, etc)
✅ cost_amount
✅ description
✅ recorded_by
✅ farm_id
✅ created_at
✅ updated_at

Campos NOVOS (opcionais):
🆕 phase_id - Fase de criação
🆕 category_id - Link para categorias detalhadas
🆕 quantity - Quantidade (litros, kg, etc)
🆕 unit - Unidade (Litros, Kg, Dias, etc)
🆕 unit_price - Preço unitário
🆕 total_cost - Custo total calculado
🆕 is_automatic - Se foi calculado automaticamente
```

### **Tabela `heifer_phases` (Nova)**
```
6 Fases pré-configuradas:
1. Aleitamento (0-60 dias)
2. Transição/Desmame (61-90 dias)
3. Recria Inicial (91-180 dias)
4. Recria Intermediária (181-365 dias)
5. Crescimento/Desenvolvimento (366-540 dias)
6. Pré-parto (541-780 dias / 26 meses)
```

### **Tabela `heifer_cost_categories` (Nova)**
```
18 Categorias organizadas:

📦 ALIMENTAÇÃO:
- Leite Integral
- Sucedâneo
- Concentrado Inicial
- Concentrado Crescimento
- Volumoso (Silagem)
- Volumoso (Feno)
- Pastagem

💊 SANIDADE:
- Medicamentos
- Vacinas
- Vermífugos
- Exames Veterinários

🛠️ MANEJO:
- Mão de Obra
- Descorna
- Identificação
- Transporte

🏗️ INSTALAÇÕES:
- Instalações/Depreciação
- Energia/Água

📋 OUTROS:
- Outros Custos
```

---

## 🔄 **COMPATIBILIDADE GARANTIDA:**

### **Registros Antigos:**
✅ Seus custos existentes **continuam funcionando**
✅ Campo `cost_amount` é **copiado automaticamente** para `total_cost`
✅ Campo `cost_category` é **mapeado** para `category_id`

### **Novos Registros:**
✅ Podem usar a **estrutura antiga** (cost_category + cost_amount)
✅ Podem usar a **estrutura nova** (category_id + quantity + unit_price)
✅ **Trigger automático** preenche campos faltantes

---

## 🎯 **COMO USAR APÓS INSTALAÇÃO:**

### **Opção 1: API Antiga (ainda funciona)**
```javascript
// Funciona igual antes!
fetch('api/heifer_costs.php?action=insert', {
    method: 'POST',
    body: JSON.stringify({
        animal_id: 4,
        cost_date: '2025-10-20',
        cost_category: 'Alimentação',
        cost_amount: 150.00,
        description: 'Ração 50kg'
    })
});
```

### **Opção 2: Nova API (mais completa)**
```javascript
// Nova estrutura com mais detalhes
fetch('api/heifer_management.php?action=add_cost', {
    method: 'POST',
    body: JSON.stringify({
        animal_id: 4,
        category_id: 2, // Sucedâneo
        cost_date: '2025-10-20',
        quantity: 6, // litros
        unit: 'Litros',
        unit_price: 0.60, // R$ 0,60/litro
        // total_cost calculado automaticamente: 6 × 0.60 = R$ 3,60
        description: 'Leite sucedâneo diário'
    })
});
```

---

## 🎨 **NOVA INTERFACE:**

### **Dashboard Automático:**
- Total de novilhas
- Investimento total
- Custo médio
- Novilhas por fase
- Top 10 mais caras
- Gráficos por categoria

### **Lista de Novilhas:**
- Brinco / Nome
- Idade (meses e dias)
- Fase atual (calculada automaticamente)
- Custo total acumulado
- Quantidade de registros
- Último custo registrado
- Ações rápidas (Ver, Adicionar, Relatório)

### **Detalhes por Novilha:**
- Custos por categoria (gráficos)
- Custos por fase
- Histórico completo
- Custo médio diário
- Previsão até 26 meses

---

## ⚠️ **IMPORTANTE:**

### **Antes de Executar:**
1. ✅ Faça **BACKUP** do banco de dados
2. ✅ Teste em **ambiente de desenvolvimento** primeiro
3. ✅ Leia **todo o SQL** antes de executar

### **Durante a Execução:**
- ⏱️ Pode levar **1-2 minutos** dependendo do tamanho do banco
- 📊 Vai mostrar **mensagens de verificação** ao final
- ✅ Procure por: "✅ SISTEMA DE CONTROLE DE NOVILHAS INSTALADO COM SUCESSO!"

### **Após a Instalação:**
- 🔍 Verifique se todas as tabelas foram criadas
- 🧪 Teste adicionar um custo pela **API antiga** (deve funcionar)
- 🎨 Acesse o sistema pelo menu "Controle de Novilhas"

---

## 🐛 **SOLUÇÃO DE PROBLEMAS:**

### **Erro: "Table 'heifer_phases' already exists"**
✅ Normal se executar duas vezes. Ignora e continua.

### **Erro: "Column 'phase_id' already exists"**
✅ Normal se executar duas vezes. Usa `IF NOT EXISTS`.

### **Erro: "Syntax error near DELIMITER"**
❌ Execute o SQL pelo **phpMyAdmin**, não pelo código PHP.

### **Erro: "Unknown column 'total_cost'"**
❌ Execute o SQL completo novamente. Pode ter parado no meio.

---

## 📞 **SUPORTE:**

Se encontrar algum erro:
1. Copie a **mensagem de erro completa**
2. Informe em qual **linha do SQL** parou
3. Verifique se fez **backup** antes

---

## ✨ **RESULTADO FINAL:**

Após a instalação você terá:
- ✅ Sistema **100% compatível** com registros antigos
- ✅ Nova interface **profissional** de controle
- ✅ Dashboard com **gráficos e estatísticas**
- ✅ **6 fases** de criação automatizadas
- ✅ **18 categorias** de custos detalhadas
- ✅ Cálculo **automático** de fase por idade
- ✅ Relatórios **completos** por novilha
- ✅ API **robusta** com 12 endpoints

---

## 🎉 **PRONTO PARA USAR!**

O sistema foi projetado para **não quebrar nada** do que já existe.
Você pode continuar usando da forma antiga enquanto migra gradualmente para a nova estrutura.

**Boa sorte e bom gerenciamento de novilhas!** 🐄📊💚

