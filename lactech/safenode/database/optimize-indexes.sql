-- =====================================================
-- SafeNode - Script de Otimização de Índices
-- Melhoria #2: Otimização de Queries com Índices
-- 
-- Este script cria índices otimizados para melhorar
-- drasticamente a performance das queries mais comuns
-- =====================================================

-- IMPORTANTE: Execute este script no banco de dados safend
-- USE safend;

-- =====================================================
-- 1. ÍNDICES PARA safenode_security_logs
-- =====================================================

-- Índice composto para queries por IP e data (rate limiting, análise)
CREATE INDEX IF NOT EXISTS idx_ip_created 
ON safenode_security_logs(ip_address, created_at);

-- Índice composto para queries por site e data (dashboard, estatísticas)
CREATE INDEX IF NOT EXISTS idx_site_created 
ON safenode_security_logs(site_id, created_at);

-- Índice composto para queries por ação e data (filtros de bloqueio)
CREATE INDEX IF NOT EXISTS idx_action_created 
ON safenode_security_logs(action_taken, created_at);

-- Índice composto para queries de ameaças (análise de threat types)
CREATE INDEX IF NOT EXISTS idx_threat_created 
ON safenode_security_logs(threat_type, created_at, threat_score);

-- Índice para queries por IP, site e data (análise combinada)
CREATE INDEX IF NOT EXISTS idx_ip_site_created 
ON safenode_security_logs(ip_address, site_id, created_at);

-- Índice para queries por país e data (análise geográfica)
CREATE INDEX IF NOT EXISTS idx_country_created 
ON safenode_security_logs(country_code, created_at);

-- Índice para queries de ameaças críticas (threat_score alto)
CREATE INDEX IF NOT EXISTS idx_threat_score_created 
ON safenode_security_logs(threat_score, created_at);

-- Índice para URI (análise de endpoints atacados)
CREATE INDEX IF NOT EXISTS idx_request_uri 
ON safenode_security_logs(request_uri(200));

-- =====================================================
-- 2. ÍNDICES PARA safenode_blocked_ips
-- =====================================================

-- Índice composto para verificação rápida de IPs bloqueados
CREATE INDEX IF NOT EXISTS idx_blocked_ip_expires 
ON safenode_blocked_ips(ip_address, expires_at, is_active);

-- Índice para queries por tipo de ameaça
CREATE INDEX IF NOT EXISTS idx_blocked_threat_type 
ON safenode_blocked_ips(threat_type, is_active);

-- Índice para queries por data de expiração (limpeza automática)
CREATE INDEX IF NOT EXISTS idx_blocked_expires_at 
ON safenode_blocked_ips(expires_at);

-- =====================================================
-- 3. ÍNDICES PARA safenode_rate_limits
-- =====================================================

-- Índice para queries de rate limits ativos
CREATE INDEX IF NOT EXISTS idx_rate_limits_active 
ON safenode_rate_limits(is_active, priority);

-- =====================================================
-- 4. ÍNDICES PARA safenode_rate_limits_violations
-- =====================================================

-- Índice composto para análise de violações por IP
CREATE INDEX IF NOT EXISTS idx_violations_ip_created 
ON safenode_rate_limits_violations(ip_address, created_at);

-- =====================================================
-- 5. ÍNDICES PARA safenode_ip_reputation
-- =====================================================

-- Índice para queries por trust_score (já existe, mas garantir)
CREATE INDEX IF NOT EXISTS idx_reputation_trust_score 
ON safenode_ip_reputation(trust_score, last_seen);

-- Índice composto para queries de IPs suspeitos
CREATE INDEX IF NOT EXISTS idx_reputation_low_trust 
ON safenode_ip_reputation(trust_score, is_blacklisted, last_seen);

-- =====================================================
-- 6. ÍNDICES PARA safenode_sites
-- =====================================================

-- Índice para busca por domínio (já deve existir, mas garantir)
CREATE INDEX IF NOT EXISTS idx_sites_domain 
ON safenode_sites(domain, is_active);

-- Índice para queries por usuário
CREATE INDEX IF NOT EXISTS idx_sites_user 
ON safenode_sites(user_id, is_active);

-- =====================================================
-- 7. ÍNDICES PARA safenode_whitelist
-- =====================================================

-- Índice para verificação rápida de whitelist
CREATE INDEX IF NOT EXISTS idx_whitelist_ip_active 
ON safenode_whitelist(ip_address, is_active);

-- =====================================================
-- 8. ÍNDICES PARA safenode_firewall_rules
-- =====================================================

-- Índice para queries de regras ativas por site
CREATE INDEX IF NOT EXISTS idx_firewall_site_active 
ON safenode_firewall_rules(site_id, is_active, priority);

-- =====================================================
-- 9. ÍNDICES PARA safenode_incidents
-- =====================================================

-- Índice para queries de incidentes por site e status
CREATE INDEX IF NOT EXISTS idx_incidents_site_status 
ON safenode_incidents(site_id, status, last_seen);

-- =====================================================
-- 10. ÍNDICES PARA safenode_threat_patterns
-- =====================================================

-- Índice para queries de padrões ativos
CREATE INDEX IF NOT EXISTS idx_threat_patterns_active 
ON safenode_threat_patterns(is_active, threat_type);

-- =====================================================
-- VERIFICAÇÃO DE ÍNDICES CRIADOS
-- =====================================================

-- Para verificar os índices criados, execute:
-- SHOW INDEX FROM safenode_security_logs;
-- SHOW INDEX FROM safenode_blocked_ips;
-- SHOW INDEX FROM safenode_rate_limits;
-- etc.

-- =====================================================
-- ANÁLISE DE PERFORMANCE
-- =====================================================

-- Para analisar o uso dos índices, execute:
-- EXPLAIN SELECT * FROM safenode_security_logs 
-- WHERE ip_address = '192.168.1.1' 
-- AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================

-- 1. Índices aumentam ligeiramente o tempo de INSERT/UPDATE
--    mas melhoram drasticamente SELECT (compensa muito mais)

-- 2. Índices compostos são mais eficientes quando a ordem
--    das colunas corresponde à ordem na cláusula WHERE

-- 3. Índices em colunas de texto usam prefixo (primeiros N caracteres)
--    para economizar espaço

-- 4. Execute ANALYZE TABLE após criar índices para otimizar:
--    ANALYZE TABLE safenode_security_logs;
--    ANALYZE TABLE safenode_blocked_ips;
--    etc.

-- 5. Monitore o uso de espaço em disco após criar índices
--    Índices ocupam espaço adicional no banco


