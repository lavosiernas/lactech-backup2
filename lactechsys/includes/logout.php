<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'security.php';

$auth = new Auth();
$security = Security::getInstance();

// Log logout event
if (isset($_SESSION['user_id'])) {
    $security->logSecurityEvent('logout', ['user_id' => $_SESSION['user_id']]);
}

// Destroy session
$auth->logout();

// Redirect to login
header('Location: ' . LOGIN_URL);
exit;
?>
