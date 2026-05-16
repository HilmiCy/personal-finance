<?php
// get_detail.php
// Matikan semua output buffer
ob_start();

// Set header JSON
header('Content-Type: application/json');

// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan display error, tapi log tetap jalan
ini_set('log_errors', 1);

// Function untuk send JSON response
function sendJsonResponse($success, $message, $data = null) {
    ob_clean();
    echo json_encode([
        'success' => $success, 
        'message' => $message, 
        'data' => $data,
        'transaction' => $data // Biar konsisten dengan JavaScript
    ]);
    exit;
}

try {
    // Include files dengan benar
    require_once '../../config/config.php';
    require_once '../../config/session.php';
    require_once '../../classes/Database.php';
    require_once '../../classes/Transaction.php';
    require_once '../../classes/Account.php';
    require_once '../../classes/Category.php';
    
    // Debug: Cek session
    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
    
    // Cek login
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        sendJsonResponse(false, 'Harus login terlebih dahulu');
    }
    
    // Ambil ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        sendJsonResponse(false, 'ID transaksi tidak ditemukan');
    }
    
    $id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    if ($id <= 0) {
        sendJsonResponse(false, 'ID transaksi tidak valid');
    }
    
    error_log("Getting transaction ID: $id for user: $user_id");
    
    // Buat instance Transaction
    $transaction = new Transaction();
    
    // Method getById harus mengembalikan data lengkap
    $transData = $transaction->getById($id, $user_id);
    
    error_log("Transaction data: " . print_r($transData, true));
    
    if ($transData && !empty($transData)) {
        // Format response sesuai yang diharapkan JavaScript
        $responseData = [
            'id' => $transData['id'],
            'type' => $transData['type'],
            'transaction_date' => $transData['transaction_date'],
            'amount' => (float)$transData['amount'],
            'description' => $transData['description'] ?? '',
            'account_id' => $transData['account_id'],
            'account_name' => $transData['account_name'] ?? '',
            'category_id' => $transData['category_id'] ?? '',
            'category_name' => $transData['category_name'] ?? '',
            'to_account_id' => $transData['to_account_id'] ?? ''
        ];
        
        sendJsonResponse(true, 'Success', $responseData);
    } else {
        error_log("Transaction not found for ID: $id, User: $user_id");
        sendJsonResponse(false, 'Transaksi tidak ditemukan');
    }
    
} catch (Exception $e) {
    error_log("Error in get_detail.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
}
?>