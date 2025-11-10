# 🔧 Instruções para Corrigir os Erros

## ❌ Erros Identificados:

1. **Tabela `volume_records` não existe** - Causa erro SQL
2. **ERR_TOO_MANY_REDIRECTS em `api/inicio-login.php`** - Redirect loop
3. **Service Worker com redirect** - Caminhos incorretos com `/lactech/`

## ✅ Soluções Aplicadas:

### 1. Tabela `volume_records` faltante

**Arquivo criado:** `lactech/api/fix-database.php`

**Como executar:**
1. Acesse no navegador: `https://lactechsys.com/api/fix-database.php`
2. O script criará automaticamente a tabela `volume_records`
3. Você verá uma mensagem de sucesso quando concluir

**Ou execute manualmente via SQL:**
```sql
CREATE TABLE IF NOT EXISTS `volume_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_date` date NOT NULL COMMENT 'Data do registro',
  `shift` enum('manha','tarde','noite') NOT NULL COMMENT 'Turno da coleta',
  `total_volume` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Volume total coletado (litros)',
  `total_animals` int(11) DEFAULT 0 COMMENT 'Número de animais ordenhados',
  `average_per_animal` decimal(10,2) DEFAULT NULL COMMENT 'Média por animal (litros)',
  `notes` text DEFAULT NULL COMMENT 'Observações sobre a coleta',
  `recorded_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que registrou',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_record_date` (`record_date`),
  KEY `idx_shift` (`shift`),
  KEY `idx_recorded_by` (`recorded_by`),
  KEY `idx_farm_date` (`farm_id`, `record_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registros de volume de leite coletado na fazenda';
```

### 2. Caminhos corrigidos (removido `/lactech/`)

**Arquivos corrigidos:**
- ✅ `sw-manager.js` - Caminhos do Service Worker
- ✅ `manifest.json` - URLs do PWA
- ✅ `gerente-completo.php` - Referências ao manifest e Service Worker
- ✅ `offline-manager.js` - Caminhos da API

**Caminhos corrigidos:**
- `/lactech/sw-manager.js` → `/sw-manager.js`
- `/lactech/manifest.json` → `/manifest.json`
- `/lactech/api/` → `/api/`
- `/lactech/gerente-completo.php` → `/gerente-completo.php`

### 3. Redirect loop em `api/inicio-login.php`

**Problema:** Não existe arquivo `api/inicio-login.php`, mas algum código está tentando acessá-lo.

**Solução:** Verifique se há algum código tentando acessar `api/inicio-login.php` e remova ou corrija.

## 📋 Checklist de Verificação:

1. [ ] Executar `api/fix-database.php` para criar a tabela `volume_records`
2. [ ] Verificar se os caminhos estão corretos (sem `/lactech/`)
3. [ ] Limpar cache do navegador (Ctrl+F5)
4. [ ] Desregistrar Service Worker antigo (DevTools → Application → Service Workers → Unregister)
5. [ ] Recarregar a página e verificar se os erros desapareceram

## 🚀 Depois de corrigir:

1. A tabela `volume_records` será criada
2. O erro SQL desaparecerá
3. Os redirects funcionarão corretamente
4. O Service Worker será registrado sem erros



