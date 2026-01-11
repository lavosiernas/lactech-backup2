<?php
/**
 * SafeNode Maintenance - Email Saver
 * Saves emails to a protected JSON file for simplicity and robustness during maintenance.
 */

header('Content-Type: application/json');

// basic security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

// Database Storage
require_once __DIR__ . '/includes/config.php';
$db = getSafeNodeDatabase();

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

try {
    // Check duplicate
    $stmt = $db->prepare("SELECT id FROM safenode_maintenance_emails WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'You are already on the list!']);
        exit;
    }

    // Insert new
    $stmt = $db->prepare("INSERT INTO safenode_maintenance_emails (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);

    echo json_encode(['success' => true, 'message' => 'You will be notified effectively!']);

} catch (PDOException $e) {
    error_log("Notify Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error saving email.']);
}
