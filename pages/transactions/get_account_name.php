<?php
// get_account_name.php
ob_start();
header('Content-Type: application/json');

function sendJsonResponse($success, $message, $data = null) {
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message, 'account_name' => $data]);
    exit;
}

try {
    require_once '../../config/config.php';
    require_once '../../config/session.php';
    require_once '../../classes/Database.php';
    require_once '../../classes/Account.php';
    
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        sendJsonResponse(false, 'Not logged in');
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        sendJsonResponse(false, 'Invalid account ID');
    }
    
    $id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    $account = new Account();
    $accountData = $account->getById($id, $user_id);
    
    if ($accountData) {
        sendJsonResponse(true, 'Success', $accountData['name']);
    } else {
        sendJsonResponse(false, 'Account not found');
    }
    
} catch (Exception $e) {
    error_log("Error in get_account_name.php: " . $e->getMessage());
    sendJsonResponse(false, 'System error');
}
?>