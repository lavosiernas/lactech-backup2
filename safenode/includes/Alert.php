<?php
/**
 * SafeNode - Alertas
 * Envia alertas de segurança com base em limiares configurados.
 */

class SafeNodeAlert
{
    /**
     * Verifica se deve enviar alerta de volume de ameaças nas últimas 24h / 1h.
     * Usado no dashboard.
     */
    public static function checkThreshold(PDO $db): void
    {
        // Buscar email e threshold; se não configurados, não faz nada
        $alertEmail = class_exists('SafeNodeSettings') ? SafeNodeSettings::get('alert_email', '') : '';
        $threshold  = (int) (class_exists('SafeNodeSettings') ? SafeNodeSettings::get('alert_threshold', 10) : 10);

        if (empty($alertEmail) || $threshold <= 0) {
            return;
        }

        try {
            // Contar ameaças bloqueadas na última hora
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN threat_score >= 70 THEN 1 ELSE 0 END) as critical_count
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                  AND action_taken = 'blocked'
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'critical_count' => 0];

            $totalThreats   = (int) $stats['total'];
            $criticalThreats = (int) $stats['critical_count'];

            if ($totalThreats < $threshold && $criticalThreats < max(1, floor($threshold / 2))) {
                return;
            }

            // Verificar se já enviamos alerta similar na última hora
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM safenode_alerts 
                WHERE alert_type = 'threshold' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 55 MINUTE)
            ");
            $stmt->execute();
            $recent = (int) $stmt->fetchColumn();
            if ($recent > 0) {
                return;
            }

            // Registrar alerta na tabela
            $insert = $db->prepare("
                INSERT INTO safenode_alerts (alert_type, alert_level, title, message, created_at)
                VALUES ('threshold', 'warning', ?, ?, NOW())
            ");

            $title = 'SafeNode - Pico de ameaças detectado';
            $message = sprintf(
                "O SafeNode detectou %d ameaças bloqueadas na última hora, das quais %d foram críticas.\n\n".
                "Recomendado:\n- Verificar os logs recentes no painel do SafeNode\n- Validar regras de firewall e rate limit\n\n".
                "Horário do alerta: %s",
                $totalThreats,
                $criticalThreats,
                date('d/m/Y H:i:s')
            );

            $insert->execute([$title, $message]);

            // Tentar enviar e-mail (silenciosamente se falhar)
            @mail(
                $alertEmail,
                $title,
                $message,
                "Content-Type: text/plain; charset=utf-8\r\n"
            );
        } catch (PDOException $e) {
            error_log("SafeNodeAlert Error: " . $e->getMessage());
        }
    }
}




