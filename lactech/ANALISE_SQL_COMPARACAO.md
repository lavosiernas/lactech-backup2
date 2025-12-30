# Análise Comparativa: SQL Fornecido vs Banco Atual

## 📊 Resumo Executivo

O SQL fornecido (`u311882628_lactech_lgmato (4).sql`) **CONDIZ** com o banco de dados atual do sistema LacTech, com algumas observações importantes.

## ✅ Tabelas Principais - COMPATÍVEIS

### 1. **users** ✅
- **SQL fornecido**: Estrutura completa com campos: id, name, email, password, role, farm_id, phone, profile_photo, etc.
- **Banco atual**: Usa os mesmos campos (verificado em `gerente-completo.php` linha 99)
- **Status**: ✅ COMPATÍVEL

### 2. **animals** ✅
- **SQL fornecido**: Campos: id, animal_number, name, breed, gender, birth_date, status, reproductive_status, etc.
- **Banco atual**: Usa `getAllAnimals()` que retorna os mesmos campos
- **Status**: ✅ COMPATÍVEL

### 3. **volume_records** ✅
- **SQL fornecido**: Campos: id, record_date, shift, total_volume, total_animals, average_per_animal, notes, recorded_by, farm_id
- **Banco atual**: Migration `create_volume_records_table.sql` tem estrutura similar
- **Diferença**: SQL fornecido não tem AUTO_INCREMENT no id, migration tem
- **Status**: ⚠️ COMPATÍVEL COM PEQUENAS DIFERENÇAS

### 4. **milk_production** ✅
- **SQL fornecido**: Campos: id, animal_id, production_date, shift, volume, fat_content, protein_content, somatic_cells, etc.
- **Banco atual**: Usado em queries (linha 174 de gerente-completo.php)
- **Status**: ✅ COMPATÍVEL

### 5. **quality_tests** ✅
- **SQL fornecido**: Campos: id, test_date, test_type, animal_id, fat_content, protein_content, somatic_cells, etc.
- **Banco atual**: Usado no sistema (tabela quality_tests)
- **Status**: ✅ COMPATÍVEL

### 6. **financial_records** ✅
- **SQL fornecido**: Campos: id, record_date, type, status, category, subcategory, description, amount, payment_method, etc.
- **Banco atual**: Usado em `api/endpoints/financial.php`
- **Status**: ✅ COMPATÍVEL

### 7. **notifications** ✅
- **SQL fornecido**: Campos: id, user_id, title, message, link, type, notification_type, priority, is_read, etc.
- **Banco atual**: Usado em `api/notifications-api.php`
- **Status**: ✅ COMPATÍVEL

## ⚠️ Observações Importantes

### 1. **Tabela `push_subscriptions` - FALTANDO NO SQL**
- **Status**: ❌ NÃO EXISTE no SQL fornecido
- **Ação necessária**: Adicionar tabela `push_subscriptions` (já criada migration em `includes/migrations/create_push_subscriptions_table.sql`)

### 2. **Índices e Foreign Keys**
- **SQL fornecido**: Tem PRIMARY KEYs, mas alguns FOREIGN KEYs podem estar faltando
- **Banco atual**: Migration de `volume_records` tem FOREIGN KEYs explícitos
- **Recomendação**: Verificar se todas as FOREIGN KEYs estão presentes

### 3. **Triggers**
- **SQL fornecido**: Tem triggers como `tr_animals_updated` e `tr_users_updated`
- **Banco atual**: Não verificado se existem no banco atual
- **Status**: ⚠️ VERIFICAR

### 4. **Views**
- **SQL fornecido**: Tem várias views (v_active_pregnancies, v_animals_complete, etc.)
- **Banco atual**: Não verificado se existem
- **Status**: ⚠️ VERIFICAR

## 📋 Tabelas Adicionais no SQL (Não Verificadas no Código)

O SQL fornecido contém muitas tabelas que podem não estar sendo usadas ativamente:
- `action_lists_cache`
- `ai_predictions`
- `animal_groups`
- `animal_photos`
- `animal_transponders`
- `backup_records`
- `backup_settings`
- `births`
- `body_condition_scores`
- `bulls` (sistema de touros - pode estar em uso)
- `bull_body_condition`
- `bull_coatings`
- `bull_documents`
- `bull_health_records`
- `bull_offspring`
- `bull_performance`
- `email_verifications`
- `farms`
- `feed_records`
- `google_accounts`
- `group_movements`
- `health_alerts`
- `health_records`
- `heat_cycles`
- `heifer_costs`
- `heifer_cost_categories`
- `heifer_daily_consumption`
- `heifer_phases`
- `heifer_price_history`
- `inseminations`
- `lactations`
- `maternity_alerts`
- `medications`
- `medication_applications`
- `otp_codes`
- `password_requests`
- `pedigree_records`
- `pix_payments`
- `pregnancy_controls`
- `secondary_accounts`
- `security_audit_log`
- `semen_catalog`
- `semen_movements`
- `sync_logs`
- `transponder_readings`
- `two_factor_auth`
- `user_preferences`
- `user_sessions`
- `vaccination_programs`

## ✅ Conclusão

**O SQL fornecido é COMPATÍVEL com o banco atual**, mas:

1. ✅ **Tabelas principais estão corretas**: users, animals, volume_records, milk_production, quality_tests, financial_records, notifications
2. ⚠️ **Falta tabela `push_subscriptions`** (necessária para push notifications)
3. ⚠️ **Algumas diferenças menores** em índices e constraints
4. ✅ **Estrutura geral está correta** e pode ser usada como base

## 🔧 Recomendações

1. **Adicionar tabela `push_subscriptions`** ao SQL antes de importar
2. **Verificar FOREIGN KEYs** - garantir que todas estão presentes
3. **Verificar triggers** - garantir que estão funcionando
4. **Testar importação** em ambiente de desenvolvimento primeiro
5. **Fazer backup completo** antes de importar em produção

## 📝 Próximos Passos

1. ✅ **Script criado**: `includes/migrations/add_push_subscriptions_to_sql.sql`
2. ⚠️ **Verificar FOREIGN KEYs**: Algumas podem estar faltando no SQL fornecido
3. ⚠️ **Testar importação**: Fazer em ambiente de teste primeiro
4. ✅ **Documentação**: Este arquivo documenta as diferenças

## 🔍 Diferenças Específicas Encontradas

### 1. **volume_records**
- **SQL fornecido**: `id` sem AUTO_INCREMENT explícito no CREATE TABLE (mas tem no ALTER TABLE)
- **Migration atual**: `id` com AUTO_INCREMENT
- **Impacto**: Pode causar problemas ao inserir registros
- **Solução**: Verificar se AUTO_INCREMENT está presente

### 2. **FOREIGN KEYs**
- **SQL fornecido**: Tem algumas FOREIGN KEYs, mas não todas (ex: falta `fk_volume_records_user`)
- **Migration atual**: Tem FOREIGN KEYs mais completas
- **Impacto**: Pode afetar integridade referencial
- **Solução**: Adicionar FOREIGN KEYs faltantes (script criado)

### 3. **Triggers**
- **SQL fornecido**: Tem triggers `tr_animals_updated` e `tr_users_updated`
- **Banco atual**: Não verificado se existem
- **Impacto**: Pode afetar atualizações automáticas de timestamps
- **Solução**: Verificar se triggers estão funcionando

## ✅ Checklist de Compatibilidade

- [x] Tabela `users` - COMPATÍVEL
- [x] Tabela `animals` - COMPATÍVEL
- [x] Tabela `volume_records` - COMPATÍVEL (com pequenas diferenças)
- [x] Tabela `milk_production` - COMPATÍVEL
- [x] Tabela `quality_tests` - COMPATÍVEL
- [x] Tabela `financial_records` - COMPATÍVEL
- [x] Tabela `notifications` - COMPATÍVEL
- [x] Tabela `push_subscriptions` - Script criado para adicionar
- [ ] FOREIGN KEYs completas - Script criado para adicionar
- [ ] Triggers - VERIFICAR
- [ ] Views - VERIFICAR

