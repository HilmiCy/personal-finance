<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Category.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Set header untuk JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = new Category();
    
    // Validasi input
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    
    if (empty($name)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Nama kategori tidak boleh kosong!'
        ]);
        exit;
    }
    
    if (!in_array($type, ['income', 'expense'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tipe kategori tidak valid!'
        ]);
        exit;
    }
    
    // Cek apakah kategori sudah ada
    $existingCategory = $category->checkExists($_SESSION['user_id'], $name, $type);
    if ($existingCategory) {
        echo json_encode([
            'success' => false, 
            'message' => "Kategori '$name' sudah ada untuk tipe " . ($type == 'income' ? 'Pemasukan' : 'Pengeluaran') . "!"
        ]);
        exit;
    }
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'name' => $name,
        'type' => $type
    ];
    
    if ($category->create($data)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Kategori berhasil ditambahkan!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menambahkan kategori!'
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