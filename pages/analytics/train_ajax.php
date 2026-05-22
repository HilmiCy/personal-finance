<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$transaction = new Transaction();
$result = $transaction->trainAIModel($_SESSION['user_id']);

header('Content-Type: application/json');
echo json_encode($result);
