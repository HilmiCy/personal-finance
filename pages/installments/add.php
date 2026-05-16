<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Installment.php';
require_once '../../classes/Account.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Session expired. Silakan login kembali.'
    ]);
    exit;
}

// Set header untuk JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $installment = new Installment();
    $account = new Account();
    
    // Ambil dan validasi input
    $name = trim($_POST['name'] ?? '');
    $tenor = isset($_POST['tenor']) ? (int)$_POST['tenor'] : 0;
    $tenor_type = $_POST['tenor_type'] ?? 'months';
    $amount_per_tenor = isset($_POST['amount_per_tenor']) ? (float)str_replace(['Rp', '.', ',', ' '], '', $_POST['amount_per_tenor']) : 0;
    $start_date = $_POST['start_date'] ?? '';
    $interest_rate = isset($_POST['interest_rate']) ? (float)$_POST['interest_rate'] : 0;
    $notes = trim($_POST['notes'] ?? '');
    
    // Validasi field wajib
    if (empty($name)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Nama cicilan tidak boleh kosong!'
        ]);
        exit;
    }
    
    if ($tenor <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tenor harus lebih dari 0!'
        ]);
        exit;
    }
    
    if (!in_array($tenor_type, ['days', 'months', 'years'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Jenis tenor tidak valid!'
        ]);
        exit;
    }
    
    if ($amount_per_tenor <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Jumlah per tenor harus lebih dari 0!'
        ]);
        exit;
    }
    
    if (empty($start_date)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tanggal mulai harus diisi!'
        ]);
        exit;
    }
    
    // Validasi tanggal
    $date = DateTime::createFromFormat('Y-m-d', $start_date);
    if (!$date || $date->format('Y-m-d') !== $start_date) {
        echo json_encode([
            'success' => false, 
            'message' => 'Format tanggal tidak valid!'
        ]);
        exit;
    }
    
    // Hitung total cicilan
    $total_amount = $amount_per_tenor * $tenor;
    
    // Buat akun baru untuk cicilan ini
    $accountData = [
        'user_id' => $_SESSION['user_id'],
        'name' => $name, // Nama akun sama dengan nama cicilan
        'type' => 'expense', // Akun untuk cicilan adalah pengeluaran
        'balance' => 0,
        'description' => 'Akun untuk cicilan: ' . $name,
        'is_active' => 1
    ];
    
    $accountId = $account->create($accountData);
    
    if (!$accountId) {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal membuat akun untuk cicilan!'
        ]);
        exit;
    }
    
    // Siapkan data untuk insert cicilan
    $data = [
        'user_id' => $_SESSION['user_id'],
        'account_id' => $accountId,
        'name' => $name,
        'tenor' => $tenor,
        'tenor_type' => $tenor_type,
        'amount_per_tenor' => $amount_per_tenor,
        'start_date' => $start_date,
        'interest_rate' => $interest_rate,
        'notes' => $notes
    ];
    
    // Simpan ke database
    if ($installment->create($data)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Cicilan berhasil ditambahkan! Akun "' . $name . '" telah dibuat otomatis.'
        ]);
    } else {
        // Jika gagal, hapus akun yang sudah dibuat
        $account->delete($accountId, $_SESSION['user_id']);
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menambahkan cicilan. Silakan coba lagi!'
        ]);
    }
    exit;
}

// Jika bukan method POST
echo json_encode([
    'success' => false, 
    'message' => 'Metode request tidak valid!'
]);
exit;
?>