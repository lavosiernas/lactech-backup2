-- =====================================================
-- SafeNode - Script de Otimização de Índices (SEGURO)
-- Versão compatível com Hostinger e MySQL/MariaDB
-- 
-- Este script verifica índices existentes antes de criar
-- Evita erros e é compatível com planos compartilhados
-- =====================================================

-- IMPORTANTE: Execute este script no banco de dados u311882628_safend
-- USE u311882628_safend;

-- =====================================================
-- 1. ÍNDICES PARA safenode_security_logs
-- =====================================================

-- Índice composto para queries por IP e data
-- Verifica se já existe antes de criar
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_ip_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_ip_created ON safenode_security_logs(ip_address, created_at)',
    'SELECT ''Índice idx_ip_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice composto para queries por site e data
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_site_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_site_created ON safenode_security_logs(site_id, created_at)',
    'SELECT ''Índice idx_site_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice composto para queries por ação e data
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_action_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_action_created ON safenode_security_logs(action_taken, created_at)',
    'SELECT ''Índice idx_action_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice composto para queries de ameaças
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_threat_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_threat_created ON safenode_security_logs(threat_type, created_at, threat_score)',
    'SELECT ''Índice idx_threat_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para queries por IP, site e data
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_ip_site_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_ip_site_created ON safenode_security_logs(ip_address, site_id, created_at)',
    'SELECT ''Índice idx_ip_site_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para queries por país e data
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_country_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_country_created ON safenode_security_logs(country_code, created_at)',
    'SELECT ''Índice idx_country_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para queries de ameaças críticas (threat_score alto)
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_threat_score_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_threat_score_created ON safenode_security_logs(threat_score, created_at)',
    'SELECT ''Índice idx_threat_score_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para URI (análise de endpoints atacados) - prefixo de 200 caracteres
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_security_logs' 
               AND index_name = 'idx_request_uri_prefix');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_request_uri_prefix ON safenode_security_logs(request_uri(200))',
    'SELECT ''Índice idx_request_uri_prefix já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 2. ÍNDICES PARA safenode_blocked_ips
-- =====================================================

-- Índice composto para verificação rápida de IPs bloqueados
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_blocked_ips' 
               AND index_name = 'idx_blocked_ip_expires');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_blocked_ip_expires ON safenode_blocked_ips(ip_address, expires_at, is_active)',
    'SELECT ''Índice idx_blocked_ip_expires já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para queries por tipo de ameaça
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_blocked_ips' 
               AND index_name = 'idx_blocked_threat_type');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_blocked_threat_type ON safenode_blocked_ips(threat_type, is_active)',
    'SELECT ''Índice idx_blocked_threat_type já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 3. ÍNDICES PARA safenode_rate_limits
-- =====================================================

-- Índice para queries de rate limits ativos
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_rate_limits' 
               AND index_name = 'idx_rate_limits_active');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_rate_limits_active ON safenode_rate_limits(is_active, priority)',
    'SELECT ''Índice idx_rate_limits_active já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 4. ÍNDICES PARA safenode_rate_limits_violations
-- =====================================================

-- Índice composto para análise de violações por IP
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_rate_limits_violations' 
               AND index_name = 'idx_violations_ip_created');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_violations_ip_created ON safenode_rate_limits_violations(ip_address, created_at)',
    'SELECT ''Índice idx_violations_ip_created já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 5. ÍNDICES PARA safenode_ip_reputation
-- =====================================================

-- Índice para queries por trust_score
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_ip_reputation' 
               AND index_name = 'idx_reputation_trust_score');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_reputation_trust_score ON safenode_ip_reputation(trust_score, last_seen)',
    'SELECT ''Índice idx_reputation_trust_score já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice composto para queries de IPs suspeitos
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_ip_reputation' 
               AND index_name = 'idx_reputation_low_trust');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_reputation_low_trust ON safenode_ip_reputation(trust_score, is_blacklisted, last_seen)',
    'SELECT ''Índice idx_reputation_low_trust já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 6. ÍNDICES PARA safenode_sites
-- =====================================================

-- Índice para busca por domínio
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_sites' 
               AND index_name = 'idx_sites_domain_active');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_sites_domain_active ON safenode_sites(domain, is_active)',
    'SELECT ''Índice idx_sites_domain_active já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 7. ÍNDICES PARA safenode_whitelist
-- =====================================================

-- Índice para verificação rápida de whitelist
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_whitelist' 
               AND index_name = 'idx_whitelist_ip_active');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_whitelist_ip_active ON safenode_whitelist(ip_address, is_active)',
    'SELECT ''Índice idx_whitelist_ip_active já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 8. ÍNDICES PARA safenode_firewall_rules
-- =====================================================

-- Índice para queries de regras ativas por site
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_firewall_rules' 
               AND index_name = 'idx_firewall_site_active');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_firewall_site_active ON safenode_firewall_rules(site_id, is_active, priority)',
    'SELECT ''Índice idx_firewall_site_active já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 9. ÍNDICES PARA safenode_incidents
-- =====================================================

-- Índice para queries de incidentes por site e status
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_incidents' 
               AND index_name = 'idx_incidents_site_status');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_incidents_site_status ON safenode_incidents(site_id, status, last_seen)',
    'SELECT ''Índice idx_incidents_site_status já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 10. ÍNDICES PARA safenode_threat_patterns
-- =====================================================

-- Índice para queries de padrões ativos
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'safenode_threat_patterns' 
               AND index_name = 'idx_threat_patterns_active');
SET @sqlstmt := IF(@exist = 0, 
    'CREATE INDEX idx_threat_patterns_active ON safenode_threat_patterns(is_active, threat_type)',
    'SELECT ''Índice idx_threat_patterns_active já existe'' AS msg');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- VERIFICAÇÃO FINAL
-- =====================================================

-- Lista todos os índices criados
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as 'Colunas'
FROM information_schema.statistics
WHERE table_schema = DATABASE()
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================

-- 1. Este script é seguro para executar múltiplas vezes
-- 2. Não remove índices existentes, apenas adiciona os faltantes
-- 3. Compatível com MySQL 5.7+ e MariaDB 10.2+
-- 4. Funciona em planos compartilhados da Hostinger
-- 5. Execute ANALYZE TABLE após criar índices:
--    ANALYZE TABLE safenode_security_logs;
--    ANALYZE TABLE safenode_blocked_ips;
--    etc.

