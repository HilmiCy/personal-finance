<?php
require_once '../../../config/config.php';
require_once '../../../config/session.php';
require_once '../../../includes/functions.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/AssetTransaction.php';

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['asset_id'])) {
    try {
        $asset_id = intval($_GET['asset_id']);
        $user_id = $_SESSION['user_id'];
        
        // Log untuk debugging
        error_log("get.php called - Asset ID: $asset_id, User ID: $user_id");
        
        // Coba query langsung tanpa class
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM asset_transactions 
            WHERE asset_id = ? AND user_id = ?
            ORDER BY transaction_date DESC
        ");
        $stmt->execute([$asset_id, $user_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Direct query found: " . count($transactions) . " transactions");
        
        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
            'count' => count($transactions),
            'debug' => [
                'asset_id' => $asset_id,
                'user_id' => $user_id,
                'query_method' => 'direct'
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error in get.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request'
    ]);
}
exit;
?>