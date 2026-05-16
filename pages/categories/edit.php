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
    
    $id = $_POST['id'] ?? 0;
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
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID kategori tidak valid!'
        ]);
        exit;
    }
    
    // Cek apakah kategori milik user yang sedang login
    $categoryData = $category->getCategoryByIdAndUser($id, $_SESSION['user_id']);
    if (!$categoryData) {
        echo json_encode([
            'success' => false, 
            'message' => 'Kategori tidak ditemukan atau tidak memiliki akses!'
        ]);
        exit;
    }
    
    // Cek apakah kategori sudah ada (kecuali untuk kategori yang sedang diedit)
    $existingCategory = $category->checkExists($_SESSION['user_id'], $name, $type, $id);
    if ($existingCategory) {
        echo json_encode([
            'success' => false, 
            'message' => "Kategori '$name' sudah ada untuk tipe " . ($type == 'income' ? 'Pemasukan' : 'Pengeluaran') . "!"
        ]);
        exit;
    }
    
    $data = [
        'name' => $name,
        'type' => $type
    ];
    
    if ($category->update($id, $_SESSION['user_id'], $data)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Kategori berhasil diupdate!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal mengupdate kategori!'
        ]);
    }
    exit;
}

echo json_encode([
    'success' => false, 
    'message' => 'Metode request tidak valid!'
]);
exit;
?>