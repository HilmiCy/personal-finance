<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/User.php';

// Set header untuk JSON response
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired, silakan login kembali']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validasi
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Nama tidak boleh kosong!']);
    exit;
}

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email tidak boleh kosong!']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid!']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user = new User();

// Cek apakah email sudah digunakan oleh user lain
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email sudah digunakan oleh user lain!']);
    exit;
}

// Update user
if ($user->update($_SESSION['user_id'], $name, $email)) {
    // Update session data
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    
    // Ambil data lengkap user yang sudah diupdate
    $updatedUser = $user->getById($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profil berhasil diperbarui!',
        'data' => [
            'name' => $updatedUser['name'],
            'email' => $updatedUser['email'],
            'avatar' => strtoupper(substr($name, 0, 1))
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil! Silakan coba lagi.']);
}

exit;
?>