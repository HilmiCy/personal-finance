<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/EmergencyFund.php';
require_once '../../classes/Account.php';
require_once '../../classes/Transaction.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Cek login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Sesi Anda telah berakhir, silakan login kembali']);
    exit;
}

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Cek action
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action tidak ditemukan']);
    exit;
}

$action = $_POST['action'];
$emergencyFund = new EmergencyFund();
$account = new Account();

try {
    switch ($action) {
        case 'set_target':
            // Validasi input
            if (empty($_POST['target_amount'])) {
                echo json_encode(['success' => false, 'message' => 'Target jumlah harus diisi']);
                exit;
            }
            
            // Hapus format currency
            $target_amount = str_replace('.', '', $_POST['target_amount']);
            $target_amount = (float) str_replace(',', '', $target_amount);
            
            $priority_level = $_POST['priority_level'] ?? 'medium';
            
            // Simpan target
            $result = $emergencyFund->setTarget($_SESSION['user_id'], $target_amount, $priority_level);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Target dana darurat berhasil disimpan']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan target dana darurat']);
            }
            break;
            
        case 'deposit':
            // Validasi input
            if (empty($_POST['amount'])) {
                echo json_encode(['success' => false, 'message' => 'Jumlah harus diisi']);
                exit;
            }
            
            if (empty($_POST['account_id'])) {
                echo json_encode(['success' => false, 'message' => 'Sumber dana harus dipilih']);
                exit;
            }
            
            // Hapus format currency
            $amount = str_replace('.', '', $_POST['amount']);
            $amount = (float) str_replace(',', '', $amount);
            
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Jumlah harus lebih dari 0']);
                exit;
            }
            
            $account_id = (int) $_POST['account_id'];
            $description = $_POST['description'] ?? '';
            
            // Cek saldo akun sumber
            $accountData = $account->getById($account_id, $_SESSION['user_id']);
            if (!$accountData) {
                echo json_encode(['success' => false, 'message' => 'Akun tidak ditemukan']);
                exit;
            }
            
            if ($accountData['balance'] < $amount) {
                echo json_encode(['success' => false, 'message' => 'Saldo akun tidak mencukupi']);
                exit;
            }
            
            // Proses deposit ke dana darurat
            $result = $emergencyFund->deposit($_SESSION['user_id'], $amount, $account_id, $description);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Dana berhasil ditambahkan ke dana darurat']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menambahkan dana']);
            }
            break;
            
        case 'withdraw':
            // Validasi input
            if (empty($_POST['amount'])) {
                echo json_encode(['success' => false, 'message' => 'Jumlah harus diisi']);
                exit;
            }
            
            if (empty($_POST['account_id'])) {
                echo json_encode(['success' => false, 'message' => 'Tujuan transfer harus dipilih']);
                exit;
            }
            
            // Hapus format currency
            $amount = str_replace('.', '', $_POST['amount']);
            $amount = (float) str_replace(',', '', $amount);
            
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Jumlah harus lebih dari 0']);
                exit;
            }
            
            $account_id = (int) $_POST['account_id'];
            $description = $_POST['description'] ?? '';
            
            // Cek saldo dana darurat
            $fund = $emergencyFund->getEmergencyFund($_SESSION['user_id']);
            if (!$fund || $fund['current_amount'] < $amount) {
                echo json_encode(['success' => false, 'message' => 'Saldo dana darurat tidak mencukupi']);
                exit;
            }
            
            // Proses withdraw dari dana darurat
            $result = $emergencyFund->withdraw($_SESSION['user_id'], $amount, $account_id, $description);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Dana berhasil ditarik dari dana darurat']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menarik dana']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenal']);
            break;
    }
} catch (Exception $e) {
    // Log error
    error_log("Emergency Fund Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    exit;
}
?>