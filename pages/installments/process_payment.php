<?php
// pages/installments/process_payment.php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Installment.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Session expired. Silakan login kembali.'
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $installment = new Installment();
    
    $installment_id = isset($_POST['installment_id']) ? (int)$_POST['installment_id'] : 0;
    $account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    $amount_raw = isset($_POST['amount']) ? $_POST['amount'] : '0';
    $amount = (float)str_replace(['.', ',', ' '], '', $amount_raw);
    $payment_date = $_POST['payment_date'] ?? ''; // Sekarang format YYYY-MM-DD HH:MM:SS
    $notes = trim($_POST['notes'] ?? '');
    
    // Validasi
    if ($installment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID cicilan tidak valid!']);
        exit;
    }
    
    if ($account_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Akun sumber dana harus dipilih!']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Jumlah pembayaran harus lebih dari 0!']);
        exit;
    }
    
    if (empty($payment_date)) {
        echo json_encode(['success' => false, 'message' => 'Tanggal pembayaran harus diisi!']);
        exit;
    }
    
    // Proses pembayaran (akan otomatis mencatat transaksi dan mengurangi saldo)
    $result = $installment->makePayment(
        $installment_id,
        $_SESSION['user_id'],
        $account_id,
        $amount,
        $payment_date,
        $notes
    );
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil! Transaksi pengeluaran telah dicatat secara otomatis.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal melakukan pembayaran. Silakan coba lagi!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid!']);
exit;
?>