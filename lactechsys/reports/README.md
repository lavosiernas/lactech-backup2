# Sistema de Relatórios em PDF - LacTech

Este sistema permite gerar relatórios em PDF usando a biblioteca FPDF, baseado no exemplo do `pdf-generator.js`.

## Instalação

1. **Instalar FPDF:**
   ```bash
   php install-fpdf.php
   ```

2. **Verificar permissões:**
   - Certifique-se de que o diretório `vendor/` tem permissões de escrita
   - O diretório `reports/` deve ter permissões de leitura

## Estrutura dos Arquivos

```
reports/
├── index.php          # Página principal para seleção de relatórios
├── volume.php         # Gerador de relatório de volume
├── quality.php        # Gerador de relatório de qualidade
├── payments.php       # Gerador de relatório de pagamentos
├── example.php        # Exemplo de uso
└── README.md          # Esta documentação

includes/
├── PDFGenerator.php   # Classe principal para gerar PDFs
└── FPDFConfig.php     # Configurações estendidas do FPDF
```

## Tipos de Relatórios

### 1. Relatório de Volume
- **Permissões:** Gerente, Funcionário, Veterinário
- **Dados:** Produção de leite por data, volume, turno, observações
- **Resumo:** Volume total e média por registro

### 2. Relatório de Qualidade
- **Permissões:** Gerente, Funcionário, Veterinário
- **Dados:** Testes de qualidade (gordura, proteína, CCS, CBT)
- **Resumo:** Médias de todos os parâmetros de qualidade

### 3. Relatório de Pagamentos
- **Permissões:** Gerente, Proprietário
- **Dados:** Registros financeiros de receitas
- **Resumo:** Valores brutos e líquidos totais

## Como Usar

### Via Interface Web
1. Acesse `/reports/index.php`
2. Selecione o tipo de relatório
3. Configure as datas de início e fim
4. Escolha se deseja uma prévia (com marca d'água)
5. Clique em "Gerar PDF"

### Via Código PHP
```php
<?php
require_once '../includes/PDFGenerator.php';

// Criar instância do gerador
$pdfGenerator = new PDFGenerator();

// Dados do relatório
$data = [
    [
        'production_date' => '2024-01-15',
        'created_at' => '2024-01-15 08:30:00',
        'volume_liters' => 25.5,
        'shift' => 'Manhã',
        'observations' => 'Produção normal'
    ]
    // ... mais dados
];

// Gerar relatório
$pdfGenerator->generateVolumeReport($data, false); // false = não é prévia
?>
```

## Parâmetros dos Relatórios

### URLs de Acesso
- **Volume:** `/reports/volume.php?start_date=2024-01-01&end_date=2024-01-31&preview=1`
- **Qualidade:** `/reports/quality.php?start_date=2024-01-01&end_date=2024-01-31`
- **Pagamentos:** `/reports/payments.php?start_date=2024-01-01&end_date=2024-01-31`

### Parâmetros
- `start_date`: Data inicial (formato: YYYY-MM-DD)
- `end_date`: Data final (formato: YYYY-MM-DD)
- `preview`: 1 para prévia com marca d'água, 0 ou omitido para relatório final

## Características dos PDFs

### Layout
- **Formato:** A4 (210x297mm)
- **Margem:** 20mm em todos os lados
- **Fonte:** Arial (padrão)

### Elementos Visuais
- **Logo da fazenda:** Canto superior direito (30x30mm)
- **Marca d'água:** Logo da fazenda transparente no centro
- **Logo do sistema:** Rodapé (8x8mm)
- **Cores:** Azul para títulos, cinza para texto secundário

### Estrutura
1. **Cabeçalho:** Título + nome da fazenda + data de geração
2. **Resumo:** Estatísticas principais
3. **Tabela:** Dados detalhados
4. **Rodapé:** Logo do sistema + timestamp

## Configurações da Fazenda

O sistema busca automaticamente:
- **Logo da fazenda:** Campo `report_farm_logo_base64` na tabela `users`
- **Nome da fazenda:** Campo `report_farm_name` na tabela `users`

### Prioridade de Busca
1. Configurações do usuário atual
2. Configurações do gerente da fazenda (fallback)

## Segurança

- **Autenticação:** Requer login e 2FA
- **Autorização:** Verificação de roles por tipo de relatório
- **Sanitização:** Todos os inputs são sanitizados
- **Validação:** Datas são validadas antes do processamento

## Troubleshooting

### Erro: "Class 'FPDF' not found"
- Execute `php install-fpdf.php`
- Verifique se o arquivo `vendor/fpdf/fpdf.php` existe

### Erro: "Permission denied"
- Verifique permissões do diretório `vendor/`
- Certifique-se de que o PHP pode escrever arquivos temporários

### PDF não gera
- Verifique logs de erro do PHP
- Confirme que há dados para o período selecionado
- Teste com dados de exemplo em `/reports/example.php`

### Logo não aparece
- Verifique se o campo `report_farm_logo_base64` está preenchido
- Confirme que a imagem está em formato base64 válido
- Teste com uma imagem pequena (máximo 100KB)

## Exemplo de Teste

Para testar o sistema:
1. Acesse `/reports/example.php?type=volume`
2. Um PDF de exemplo será gerado com dados fictícios
3. Verifique se o layout e formatação estão corretos

## Suporte

Para problemas ou dúvidas:
1. Verifique os logs de erro do PHP
2. Teste com dados de exemplo
3. Confirme permissões e configurações
4. Consulte a documentação do FPDF: http://www.fpdf.org/
