<?php
// update.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// DEFINE FUNCTION ISLOGGEDIN LANGSUNG DI SINI
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to return JSON response
function sendJsonResponse($success, $message, $data = null) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

try {
    require_once '../../config/config.php';
    require_once '../../config/session.php';
    require_once '../../classes/Database.php';
    require_once '../../classes/Transaction.php';
    require_once '../../classes/Account.php';
    
    // Check login
    if (!isLoggedIn()) {
        sendJsonResponse(false, 'Harus login terlebih dahulu');
    }
    
    // Get POST data
    $data = $_POST;
    
    // Validate required fields
    if (empty($data['id'])) {
        sendJsonResponse(false, 'ID transaksi tidak ditemukan');
    }
    
    if (empty($data['type'])) {
        sendJsonResponse(false, 'Tipe transaksi harus diisi');
    }
    
    if (empty($data['amount']) || $data['amount'] <= 0) {
        sendJsonResponse(false, 'Jumlah transaksi harus lebih dari 0');
    }
    
    if (empty($data['account_id'])) {
        sendJsonResponse(false, 'Akun harus dipilih');
    }
    
    if (empty($data['transaction_date'])) {
        sendJsonResponse(false, 'Tanggal transaksi harus diisi');
    }
    
    // Add user_id to data
    $data['user_id'] = $_SESSION['user_id'];
    
    // Set default description if empty
    if (empty($data['description'])) {
        $data['description'] = '';
    }
    
    // Initialize transaction
    $transaction = new Transaction();
    $account = new Account();
    
    // Get old transaction data untuk rollback jika perlu
    $oldTransaction = $transaction->getById($data['id'], $_SESSION['user_id']);
    
    if (!$oldTransaction) {
        sendJsonResponse(false, 'Transaksi tidak ditemukan');
    }
    
    // Handle transfer transaction
    if ($data['type'] === 'transfer') {
        if (empty($data['to_account_id'])) {
            sendJsonResponse(false, 'Akun tujuan harus dipilih untuk transfer');
        }
        
        if ($data['account_id'] == $data['to_account_id']) {
            sendJsonResponse(false, 'Akun sumber dan tujuan tidak boleh sama');
        }
        
        $transferData = [
            'id' => $data['id'],
            'user_id' => $data['user_id'],
            'from_account_id' => $data['account_id'],
            'to_account_id' => $data['to_account_id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'transaction_date' => $data['transaction_date']
        ];
        
        $result = $transaction->updateTransfer($transferData);
        
        if ($result) {
            sendJsonResponse(true, 'Transfer berhasil diupdate');
        } else {
            sendJsonResponse(false, 'Gagal mengupdate transfer');
        }
    } 
    // Handle income/expense transaction
    else {
        // Validate category for income/expense
        if (empty($data['category_id'])) {
            sendJsonResponse(false, 'Kategori harus dipilih untuk ' . ($data['type'] == 'income' ? 'pemasukan' : 'pengeluaran'));
        }
        
        $result = $transaction->update($data);
        
        if ($result) {
            sendJsonResponse(true, 'Transaksi berhasil diupdate');
        } else {
            sendJsonResponse(false, 'Gagal mengupdate transaksi');
        }
    }
} catch (Exception $e) {
    sendJsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage());
}
?>