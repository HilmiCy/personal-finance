<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Account.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Cek apakah ada ID yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID akun tidak ditemukan.';
    header('Location: index.php');
    exit;
}

$account_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Inisialisasi database dan account
$db = Database::getInstance()->getConnection();
$account = new Account();

// Cek apakah akun milik user yang login
// Perbaikan: memberikan 2 parameter (id dan user_id)
$account_data = $account->getById($account_id, $user_id);
if (!$account_data) {
    $_SESSION['error_message'] = 'Akun tidak ditemukan atau tidak memiliki akses.';
    header('Location: index.php');
    exit;
}

// Cek apakah akun memiliki transaksi terkait
$query = "SELECT COUNT(*) as total FROM transactions WHERE account_id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$account_id, $user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['total'] > 0) {
    // Akun memiliki transaksi, beri peringatan
    $_SESSION['error_message'] = 'Akun ini memiliki ' . $result['total'] . ' transaksi. Silahkan hapus transaksi terlebih dahulu atau pindahkan ke akun lain.';
    header('Location: index.php');
    exit;
}

// Lakukan penghapusan
try {
    $query = "DELETE FROM accounts WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$account_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = 'Akun berhasil dihapus.';
    } else {
        $_SESSION['error_message'] = 'Gagal menghapus akun.';
    }
} catch (PDOException $e) {
    error_log("Error deleting account: " . $e->getMessage());
    $_SESSION['error_message'] = 'Terjadi kesalahan saat menghapus akun.';
}

// Redirect kembali ke halaman utama
header('Location: index.php');
exit;
?>