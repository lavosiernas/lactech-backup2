<?php
/**
 * Script para gerar hash da senha do admin
 * Execute: php generate-admin-hash.php
 */

$password = 'lnassfnd017852';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Senha: {$password}\n";
echo "Hash: {$hash}\n\n";

echo "SQL para inserir no banco:\n";
echo "UPDATE safenode_survey_admin SET password_hash = '{$hash}' WHERE username = 'admin';\n";
echo "OU\n";
echo "INSERT INTO safenode_survey_admin (username, password_hash, email) VALUES ('admin', '{$hash}', 'safenodemail@safenode.cloud') ON DUPLICATE KEY UPDATE password_hash = '{$hash}';\n";

