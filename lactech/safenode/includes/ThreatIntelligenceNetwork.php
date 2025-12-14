<?php
/**
 * SafeNode - Threat Intelligence Network
 * Sistema de inteligência de ameaças colaborativa e proprietária
 */

class ThreatIntelligenceNetwork {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Reporta uma ameaça para a rede colaborativa (anonimizado)
     */
    public function reportThreat($ipAddress, $threatType, $severity, $attackPattern = null, $siteId = null) {
        if (!$this->db) return false;
        
        try {
            // Hash do site_id para anonimização
            $siteIdHash = $siteId ? hash('sha256', $siteId . 'safenode_salt_2025') : null;
            
            // Verificar se já existe registro desta ameaça
            $stmt = $this->db->prepare("
                SELECT id, total_occurrences, affected_sites_count, confidence_score
                FROM safenode_threat_intelligence
                WHERE ip_address = ? AND threat_type = ?
            ");
            $stmt->execute([$ipAddress, $threatType]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualizar registro existente
                $newOccurrences = $existing['total_occurrences'] + 1;
                $newConfidence = min(100, $existing['confidence_score'] + 2); // Aumenta confiança com mais ocorrências
                
                // Verificar se é um site novo afetado
                $stmtCheck = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM safenode_threat_correlations
                    WHERE threat_intelligence_id = ? AND site_id_hash = ?
                ");
                $stmtCheck->execute([$existing['id'], $siteIdHash]);
                $correlation = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                $newSitesCount = $existing['affected_sites_count'];
                if ($correlation['count'] == 0 && $siteIdHash) {
                    $newSitesCount++;
                    
                    // Adicionar correlação
                    $stmtCorr = $this->db->prepare("
                        INSERT INTO safenode_threat_correlations
                        (threat_intelligence_id, site_id_hash, occurrence_count, first_seen, last_seen)
                        VALUES (?, ?, 1, NOW(), NOW())
                    ");
                    $stmtCorr->execute([$existing['id'], $siteIdHash]);
                } else if ($siteIdHash) {
                    // Atualizar correlação existente
                    $stmtUpdate = $this->db->prepare("
                        UPDATE safenode_threat_correlations
                        SET occurrence_count = occurrence_count + 1,
                            last_seen = NOW()
                        WHERE threat_intelligence_id = ? AND site_id_hash = ?
                    ");
                    $stmtUpdate->execute([$existing['id'], $siteIdHash]);
                }
                
                // Atualizar registro principal
                $stmtUpdate = $this->db->prepare("
                    UPDATE safenode_threat_intelligence
                    SET total_occurrences = ?,
                        affected_sites_count = ?,
                        severity = GREATEST(severity, ?),
                        confidence_score = ?,
                        last_seen = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $newOccurrences,
                    $newSitesCount,
                    $severity,
                    $newConfidence,
                    $existing['id']
                ]);
                
                $threatId = $existing['id'];
            } else {
                // Criar novo registro
                $metadata = json_encode([
                    'attack_pattern' => $attackPattern,
                    'first_reported' => date('Y-m-d H:i:s')
                ]);
                
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_threat_intelligence
                    (ip_address, threat_type, severity, attack_pattern, total_occurrences, 
                     affected_sites_count, confidence_score, metadata, first_seen, last_seen)
                    VALUES (?, ?, ?, ?, 1, 1, 50, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $ipAddress,
                    $threatType,
                    $severity,
                    json_encode($attackPattern),
                    $metadata
                ]);
                
                $threatId = $this->db->lastInsertId();
                
                // Adicionar correlação se site_id fornecido
                if ($siteIdHash) {
                    $stmtCorr = $this->db->prepare("
                        INSERT INTO safenode_threat_correlations
                        (threat_intelligence_id, site_id_hash, occurrence_count, first_seen, last_seen)
                        VALUES (?, ?, 1, NOW(), NOW())
                    ");
                    $stmtCorr->execute([$threatId, $siteIdHash]);
                }
            }
            
            // Verificar se deve ativar bloqueio global
            $this->checkGlobalBlock($threatId);
            
            return $threatId;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se um IP está na base de ameaças global
     */
    public function checkThreat($ipAddress, $threatType = null) {
        if (!$this->db) return null;
        
        try {
            $sql = "
                SELECT id, threat_type, severity, total_occurrences, 
                       affected_sites_count, confidence_score, is_global_block,
                       last_seen, attack_pattern
                FROM safenode_threat_intelligence
                WHERE ip_address = ? AND is_global_block = 1
            ";
            $params = [$ipAddress];
            
            if ($threatType) {
                $sql .= " AND threat_type = ?";
                $params[] = $threatType;
            }
            
            $sql .= " ORDER BY severity DESC, confidence_score DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Check Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém lista de IPs maliciosos globais
     */
    public function getGlobalThreats($limit = 100, $minSeverity = 70, $minConfidence = 60) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT ip_address, threat_type, severity, total_occurrences,
                       affected_sites_count, confidence_score, last_seen
                FROM safenode_threat_intelligence
                WHERE is_global_block = 1
                  AND severity >= ?
                  AND confidence_score >= ?
                ORDER BY severity DESC, confidence_score DESC, last_seen DESC
                LIMIT ?
            ");
            $stmt->execute([$minSeverity, $minConfidence, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetGlobalThreats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas da rede de inteligência
     */
    public function getNetworkStats() {
        if (!$this->db) return null;
        
        try {
            $stats = [];
            
            // Total de ameaças únicas
            $stmt = $this->db->query("
                SELECT COUNT(DISTINCT ip_address) as total_ips,
                       COUNT(*) as total_threats,
                       SUM(total_occurrences) as total_occurrences,
                       SUM(affected_sites_count) as total_affected_sites
                FROM safenode_threat_intelligence
            ");
            $stats['overall'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Ameaças por tipo
            $stmt = $this->db->query("
                SELECT threat_type, COUNT(*) as count, AVG(severity) as avg_severity
                FROM safenode_threat_intelligence
                GROUP BY threat_type
                ORDER BY count DESC
            ");
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Top IPs mais perigosos
            $stmt = $this->db->query("
                SELECT ip_address, threat_type, severity, total_occurrences, affected_sites_count
                FROM safenode_threat_intelligence
                WHERE is_global_block = 1
                ORDER BY severity DESC, total_occurrences DESC
                LIMIT 10
            ");
            $stats['top_threats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetStats Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica se deve ativar bloqueio global baseado em critérios
     */
    private function checkGlobalBlock($threatId) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("
                SELECT severity, total_occurrences, affected_sites_count, confidence_score
                FROM safenode_threat_intelligence
                WHERE id = ?
            ");
            $stmt->execute([$threatId]);
            $threat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$threat) return;
            
            // Critérios para bloqueio global:
            // - Severidade >= 80 E ocorrências >= 5 E afetou >= 3 sites
            // OU
            // - Severidade >= 90 E ocorrências >= 3
            // OU
            // - Afetou >= 10 sites diferentes
            
            $shouldBlock = false;
            
            if ($threat['severity'] >= 80 && $threat['total_occurrences'] >= 5 && $threat['affected_sites_count'] >= 3) {
                $shouldBlock = true;
            } elseif ($threat['severity'] >= 90 && $threat['total_occurrences'] >= 3) {
                $shouldBlock = true;
            } elseif ($threat['affected_sites_count'] >= 10) {
                $shouldBlock = true;
            }
            
            if ($shouldBlock) {
                $stmtUpdate = $this->db->prepare("
                    UPDATE safenode_threat_intelligence
                    SET is_global_block = 1, is_verified = 1
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$threatId]);
            }
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork CheckGlobalBlock Error: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém padrões de ataque identificados
     */
    public function getAttackPatterns($threatType = null) {
        if (!$this->db) return [];
        
        try {
            $sql = "SELECT * FROM safenode_attack_patterns WHERE is_active = 1";
            $params = [];
            
            if ($threatType) {
                $sql .= " AND threat_type = ?";
                $params[] = $threatType;
            }
            
            $sql .= " ORDER BY detection_count DESC, severity DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetPatterns Error: " . $e->getMessage());
            return [];
        }
    }
}

