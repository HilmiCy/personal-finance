<?php
// pages/accounts/transfer.php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Account.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$from_account_id = isset($_POST['from_account_id']) ? (int)$_POST['from_account_id'] : 0;
$to_account_id = isset($_POST['to_account_id']) ? (int)$_POST['to_account_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Log untuk debugging
error_log("Transfer attempt - From: $from_account_id, To: $to_account_id, Amount: $amount");

// Validate input
if ($from_account_id <= 0 || $to_account_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

if ($from_account_id == $to_account_id) {
    echo json_encode(['success' => false, 'message' => 'Tidak dapat transfer ke akun yang sama']);
    exit;
}

// Process transfer
try {
    $account = new Account();
    $result = $account->transfer($from_account_id, $to_account_id, $amount, $description);
    
    // Log result
    error_log("Transfer result: " . print_r($result, true));
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Transfer exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>