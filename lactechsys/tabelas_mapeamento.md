# 🗂️ MAPEAMENTO DE TABELAS - LACTECH

## 📋 TABELAS ANTIGAS → NOVAS

### 🏢 **TABELAS PRINCIPAIS**
| ANTIGA | NOVA | DESCRIÇÃO |
|--------|------|-----------|
| `users` | `lactech_users` | Usuários do sistema |
| `farms` | `lactech_farms` | Fazendas |
| `milk_production` | `lactech_production` | Produção de leite |
| `quality_tests` | `lactech_quality` | Testes de qualidade |
| `financial_records` | `lactech_financial` | Registros financeiros |

### 🔧 **TABELAS AUXILIARES**
| ANTIGA | NOVA | DESCRIÇÃO |
|--------|------|-----------|
| `secondary_accounts` | `lactech_accounts` | Contas secundárias |
| `profile-photos` | `lactech_photos` | Fotos de perfil |

### 🐄 **TABELAS VETERINÁRIAS**
| ANTIGA | NOVA | DESCRIÇÃO |
|--------|------|-----------|
| `animals` | `lactech_animals` | Animais |
| `treatments` | `lactech_treatments` | Tratamentos veterinários |
| `animal_health_records` | `lactech_health` | Registros de saúde |
| `artificial_inseminations` | `lactech_insemination` | Inseminações |

## ⚡ FUNÇÕES RPC ATUALIZADAS

### 🔐 **AUTENTICAÇÃO E USUÁRIOS**
| ANTIGA | NOVA | DESCRIÇÃO |
|--------|------|-----------|
| `create_initial_user` | `lactech_create_user` | Criar usuário |
| `get_user_profile` | `lactech_get_profile` | Obter perfil |
| `check_user_exists` | `lactech_check_user` | Verificar usuário |
| `update_user_report_settings` | `lactech_update_settings` | Atualizar configurações |

### 🏭 **FAZENDAS**
| ANTIGA | NOVA | DESCRIÇÃO |
|--------|------|-----------|
| `create_initial_farm` | `lactech_create_farm` | Criar fazenda |
| `check_farm_exists` | `lactech_check_farm` | Verificar fazenda |
| `complete_farm_setup` | `lactech_complete_setup` | Completar configuração |

### 🥛 **PRODUÇÃO E QUALIDADE**
| ANTIGA | NOVA | DESCRIÇÃO |
|--------|------|-----------|
| `register_quality_test` | `lactech_register_quality` | Registrar qualidade |

### 🐮 **VETERINÁRIA**
| ANTIGA | NOVA | DESCRIÇÃO |
|--------|------|-----------|
| `register_artificial_insemination` | `lactech_register_insemination` | Registrar inseminação |
| `confirm_pregnancy` | `lactech_confirm_pregnancy` | Confirmar prenhez |

## 📊 OPERАÇÕES POR TABELA

### `lactech_users`
- **SELECT**: Buscar usuários, perfis, autenticação
- **INSERT**: Criar usuários
- **UPDATE**: Editar usuários, status, configurações
- **DELETE**: Remover usuários

### `lactech_farms`
- **SELECT**: Buscar dados da fazenda
- **INSERT**: Criar fazenda
- **UPDATE**: Atualizar dados da fazenda

### `lactech_production`
- **SELECT**: Buscar produção, histórico, relatórios
- **INSERT**: Registrar nova produção
- **DELETE**: Remover registros

### `lactech_quality`
- **SELECT**: Buscar testes, histórico, relatórios
- **INSERT**: Registrar novos testes
- **DELETE**: Remover testes

### `lactech_financial`
- **SELECT**: Buscar registros financeiros, relatórios
- **INSERT**: Registrar novos registros

### `lactech_accounts`
- **SELECT**: Buscar contas secundárias
- **INSERT**: Criar contas secundárias
- **UPDATE**: Atualizar contas
- **DELETE**: Remover contas

### `lactech_photos`
- **SELECT**: Buscar fotos de perfil
- **INSERT**: Upload de fotos

### `lactech_animals`
- **SELECT**: Buscar animais
- **INSERT**: Cadastrar animais
- **UPDATE**: Atualizar dados dos animais
- **DELETE**: Remover animais

### `lactech_treatments`
- **SELECT**: Buscar tratamentos
- **INSERT**: Registrar tratamentos
- **UPDATE**: Atualizar tratamentos
- **DELETE**: Remover tratamentos

### `lactech_health`
- **SELECT**: Buscar registros de saúde
- **INSERT**: Registrar saúde animal

### `lactech_insemination`
- **SELECT**: Buscar inseminações
- **INSERT**: Registrar inseminações
- **UPDATE**: Atualizar inseminações

## 🎯 PRÓXIMOS PASSOS

1. ✅ Criar mapeamento (FEITO)
2. 🔄 Atualizar arquivo JS unificado
3. 🔄 Criar banco com novos nomes
4. 🔄 Atualizar páginas HTML
5. 🔄 Testar sistema completo
