<?php
/**
 * Setup SafeNode Maintenance Database Table
 */

require_once __DIR__ . '/includes/config.php';

echo "Conectando ao banco de dados...<br>";
$db = getSafeNodeDatabase();

if (!$db) {
    die("Erro: Não foi possível conectar ao banco de dados.");
}

$sql = "
CREATE TABLE IF NOT EXISTS `safenode_maintenance_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_notified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->exec($sql);
    echo "Tabela 'safenode_maintenance_emails' verificada/criada com sucesso!<br>";
    echo "Pronto para uso.";
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
