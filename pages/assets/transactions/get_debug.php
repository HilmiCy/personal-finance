<?php
require_once '../../../config/config.php';
require_once '../../../config/session.php';
require_once '../../../includes/functions.php';
require_once '../../../classes/Database.php';

header('Content-Type: text/html');

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set user_id untuk testing (ganti dengan user_id Anda)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

echo "<h2>Debug Transaksi Aset</h2>";
echo "User ID: " . $_SESSION['user_id'] . "<br><br>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Tampilkan daftar aset user
    $stmt = $db->prepare("
        SELECT id, name, symbol, type 
        FROM assets 
        WHERE user_id = ?
        ORDER BY id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Daftar Aset Anda:</h3>";
    if (count($assets) > 0) {
        echo "<ul>";
        foreach ($assets as $asset) {
            echo "<li>ID: {$asset['id']} - {$asset['name']} ({$asset['symbol']}) - {$asset['type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "Tidak ada aset ditemukan.<br>";
    }
    
    if (isset($_GET['asset_id'])) {
        $asset_id = $_GET['asset_id'];
        echo "<br><h3>Detail untuk Asset ID: $asset_id</h3>";
        
        // Cek transaksi untuk asset tertentu
        $stmt = $db->prepare("
            SELECT * FROM asset_transactions 
            WHERE asset_id = ? AND user_id = ?
            ORDER BY transaction_date DESC
        ");
        $stmt->execute([$asset_id, $_SESSION['user_id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($transactions) > 0) {
            echo "<h4>Transaksi Ditemukan: " . count($transactions) . " transaksi</h4>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>ID</th><th>Type</th><th>Quantity</th><th>Price/Unit</th><th>Total</th><th>Date</th>";
            echo "</tr>";
            
            foreach ($transactions as $trans) {
                $bgColor = $trans['type'] == 'buy' ? '#d1fae5' : '#fee2e2';
                echo "<tr style='background: $bgColor;'>";
                echo "<td>{$trans['id']}</td>";
                echo "<td><strong>" . strtoupper($trans['type']) . "</strong></td>";
                echo "<td>{$trans['quantity']}</td>";
                echo "<td>Rp " . number_format($trans['price_per_unit'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($trans['total_price'], 0, ',', '.') . "</td>";
                echo "<td>{$trans['transaction_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>Tidak ada transaksi untuk asset ID $asset_id</p>";
            
            // Cek apakah asset ini punya transaksi dari user lain?
            $stmt = $db->prepare("
                SELECT COUNT(*) as total FROM asset_transactions WHERE asset_id = ?
            ");
            $stmt->execute([$asset_id]);
            $total = $stmt->fetch();
            echo "<small>Total transaksi untuk asset ini (semua user): " . $total['total'] . "</small><br>";
        }
    } else {
        echo "<br><p>Gunakan parameter ?asset_id=ID untuk melihat transaksi aset tertentu.</p>";
        echo "<p>Contoh: <a href='?asset_id={$assets[0]['id']}'>Lihat transaksi untuk {$assets[0]['name']}</a></p>";
    }
    
    // Cek semua transaksi user
    echo "<br><h3>Semua Transaksi User (10 terakhir):</h3>";
    $stmt = $db->prepare("
        SELECT at.*, a.name as asset_name 
        FROM asset_transactions at 
        LEFT JOIN assets a ON at.asset_id = a.id 
        WHERE at.user_id = ?
        ORDER BY at.id DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $allTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($allTransactions) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Asset</th><th>Type</th><th>Quantity</th><th>Total</th><th>Date</th>";
        echo "</tr>";
        
        foreach ($allTransactions as $trans) {
            echo "<tr>";
            echo "<td>{$trans['id']}</td>";
            echo "<td>{$trans['asset_name']} (ID: {$trans['asset_id']})</td>";
            echo "<td>{$trans['type']}</td>";
            echo "<td>{$trans['quantity']}</td>";
            echo "<td>Rp " . number_format($trans['total_price'], 0, ',', '.') . "</td>";
            echo "<td>{$trans['transaction_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Belum ada transaksi sama sekali.<br>";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>