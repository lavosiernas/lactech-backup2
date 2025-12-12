<?php
/**
 * SafeNode - Generate Report
 * Gera e envia relatórios automatizados
 * 
 * Executar via cron:
 * - Diário: 0 8 * * * php /caminho/safenode/api/generate-report.php daily
 * - Semanal: 0 9 * * 1 php /caminho/safenode/api/generate-report.php weekly
 * - Mensal: 0 10 1 * * php /caminho/safenode/api/generate-report.php monthly
 */

set_time_limit(0);

$reportType = $argv[1] ?? 'daily';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();

if (!$db) {
    error_log("SafeNode Report: Erro ao conectar ao banco");
    exit(1);
}

try {
    require_once __DIR__ . '/../includes/ReportGenerator.php';
    $reportGenerator = new ReportGenerator($db);
    
    // Obter todos os usuários para enviar relatórios
    $stmt = $db->query("SELECT DISTINCT user_id FROM safenode_sites WHERE is_active = 1");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($users as $userId) {
        // Obter email do usuário
        $stmt = $db->prepare("SELECT email FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || empty($user['email'])) {
            continue;
        }
        
        // Gerar relatório
        switch ($reportType) {
            case 'daily':
                $report = $reportGenerator->generateDailyReport(null, $userId);
                break;
            case 'weekly':
                $report = $reportGenerator->generateWeeklyReport(null, $userId);
                break;
            case 'monthly':
                $report = $reportGenerator->generateMonthlyReport(null, $userId);
                break;
            default:
                continue 2;
        }
        
        // Enviar por email
        $sent = $reportGenerator->sendReportByEmail($report, $user['email']);
        
        if ($sent) {
            error_log("SafeNode Report: Relatório $reportType enviado para {$user['email']}");
        } else {
            error_log("SafeNode Report: Erro ao enviar relatório para {$user['email']}");
        }
    }
    
    exit(0);
} catch (Exception $e) {
    error_log("SafeNode Report Error: " . $e->getMessage());
    exit(1);
}





