<?php
function formatRupiah($number) {
    if (!$number) $number = 0;
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function formatDate($date) {
    if (!$date || $date == '0000-00-00') return '-';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

function getTotalBalance($db, $user_id) {
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(balance), 0) as total FROM accounts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return (float)$result['total'];
    } catch (PDOException $e) {
        error_log("Error in getTotalBalance: " . $e->getMessage());
        return 0;
    }
}

function getMonthlyIncome($db, $user_id) {
    try {
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? 
                AND type = 'income' 
                AND MONTH(transaction_date) = MONTH(CURRENT_DATE())
                AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return (float)$result['total'];
    } catch (PDOException $e) {
        error_log("Error in getMonthlyIncome: " . $e->getMessage());
        return 0;
    }
}

function getMonthlyExpense($db, $user_id) {
    try {
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? 
                AND type = 'expense' 
                AND MONTH(transaction_date) = MONTH(CURRENT_DATE())
                AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return (float)$result['total'];
    } catch (PDOException $e) {
        error_log("Error in getMonthlyExpense: " . $e->getMessage());
        return 0;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getAccountIcon($account_name) {
    $name = strtolower($account_name);
    
    if (strpos($name, 'bca') !== false || strpos($name, 'mandiri') !== false || strpos($name, 'bri') !== false) {
        return 'university';
    } elseif (strpos($name, 'cash') !== false || strpos($name, 'tunai') !== false) {
        return 'money-bill-wave';
    } elseif (strpos($name, 'ovo') !== false) {
        return 'mobile-alt';
    } elseif (strpos($name, 'gopay') !== false) {
        return 'qrcode';
    } elseif (strpos($name, 'dana') !== false) {
        return 'wallet';
    } else {
        return 'piggy-bank';
    }
}

function showAlert() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> ' . $_SESSION['success'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['error']);
    }
}


function bulanIndonesia($month) {
    $bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
    return $bulan[(int)$month];
}


// Clean number from currency format (remove dots and commas)
function cleanNumber($number) {
    // Remove all non-numeric characters except decimal point
    $clean = preg_replace('/[^0-9]/', '', $number);
    return (float)$clean;
}

// Format number with thousand separator
function formatNumber($number) {
    return number_format($number, 0, ',', '.');
}

// Get asset type icon
function getAssetTypeIcon($type) {
    $icons = [
        'crypto' => 'fab fa-bitcoin',
        'stock' => 'fas fa-chart-line',
        'gold' => 'fas fa-coins',
        'reksadana' => 'fas fa-chart-pie',
        'other' => 'fas fa-box'
    ];
    return $icons[$type] ?? 'fas fa-box';
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' detik yang lalu';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit yang lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam yang lalu';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' hari yang lalu';
    } else {
        return date('d M Y', $time);
    }
}

// Get asset type badge class
function getAssetTypeClass($type) {
    $classes = [
        'crypto' => 'crypto',
        'stock' => 'stock',
        'gold' => 'gold',
        'reksadana' => 'reksadana',
        'other' => 'other'
    ];
    return $classes[$type] ?? 'other';
}

// Get asset type label
function getAssetTypeLabel($type) {
    $labels = [
        'crypto' => 'Crypto Currency',
        'stock' => 'Saham',
        'gold' => 'Emas',
        'reksadana' => 'Reksadana',
        'other' => 'Lainnya'
    ];
    return $labels[$type] ?? 'Lainnya';
}

// Get transaction type badge
function getTransactionTypeBadge($type) {
    if ($type == 'buy') {
        return '<span class="badge-income"><i class="fas fa-shopping-cart"></i> Beli</span>';
    } else {
        return '<span class="badge-expense"><i class="fas fa-chart-line"></i> Jual</span>';
    }
}

// Get profit/loss color
function getProfitLossColor($profit) {
    return $profit >= 0 ? 'text-success' : 'text-danger';
}

// Get profit/loss icon
function getProfitLossIcon($profit) {
    return $profit >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
}


?>