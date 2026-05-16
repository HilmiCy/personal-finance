<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/EmergencyFund.php';
require_once '../../classes/Account.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Riwayat Dana Darurat';
$current_page = 'emergency_fund';

$user_id = $_SESSION['user_id'];
$emergencyFund = new EmergencyFund();

// Ambil data dana darurat
$fund = $emergencyFund->getEmergencyFund($user_id);
$current_amount = isset($fund['current_amount']) ? $fund['current_amount'] : 0;

// Ambil semua transaksi - gunakan method yang sudah ada
// Karena di EmergencyFund class Anda ada method getTransactions
$transactions = $emergencyFund->getTransactions($user_id, 100);

// Jika getTransactions tidak mengembalikan data, coba langsung query
if (empty($transactions)) {
    // Fallback: Query langsung ke database
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                eft.*, 
                a.name as account_name,
                DATE_FORMAT(eft.transaction_date, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM emergency_fund_transactions eft
            LEFT JOIN accounts a ON eft.account_id = a.id
            WHERE eft.user_id = ?
            ORDER BY eft.transaction_date DESC, eft.id DESC
        ");
        $stmt->execute([$user_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Fallback query error: " . $e->getMessage());
        $transactions = [];
    }
}

// Hitung total deposit dan withdraw
$total_deposit = 0;
$total_withdraw = 0;
foreach ($transactions as $trans) {
    if ($trans['type'] == 'deposit') {
        $total_deposit += $trans['amount'];
    } else if ($trans['type'] == 'withdraw') {
        $total_withdraw += $trans['amount'];
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: rgba(255, 255, 255, 0.5);
        --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        --gradient-start: #667eea;
        --gradient-end: #764ba2;
    }
    
    /* Hapus background gradient dari body */
    body {
        background: #f8f9fa !important;
    }
    
    .main-content {
        padding: 20px;
    }
    
    /* Glassmorphism Card */
    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }
    
    /* Welcome Header - Tetap menggunakan gradient tapi lebih soft */
    .welcome-card {
        background: #ffffffff;
        border-radius: 24px;
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    .welcome-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        background-size: cover;
        opacity: 0.3;
        pointer-events: none;
    }
    
    .welcome-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .welcome-subtitle {
        margin: 10px 0 0 0;
        opacity: 0.95;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }
    
    /* Summary Cards */
    .summary-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }
    
    .summary-item {
        text-align: center;
    }
    
    .summary-item .label {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 12px;
    }
    
    .summary-item .value {
        font-size: 28px;
        font-weight: 800;
        color: #2c3e50;
    }
    
    .summary-item .value.deposit {
        color: #10b981;
    }
    
    .summary-item .value.withdraw {
        color: #ef4444;
    }
    
    /* Filter Buttons */
    .filter-buttons {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 10px 24px;
        border-radius: 50px;
        border: 2px solid #e9ecef;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #495057;
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    
    .filter-btn.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-color: transparent;
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .filter-btn:hover {
        transform: translateY(-2px);
        border-color: #667eea;
        color: #667eea;
    }
    
    /* Table Styles */
    .history-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .history-table th,
    .history-table td {
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
        color: #495057;
    }
    
    .history-table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6c757d;
    }
    
    .history-table tr {
        transition: all 0.3s ease;
    }
    
    .history-table tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
    }
    
    /* Badge Styles */
    .badge-deposit, .badge-withdraw {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .badge-deposit {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-withdraw {
        background: #fee2e2;
        color: #991b1b;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }
    
    .empty-state i {
        font-size: 80px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
    }
    
    .empty-state h4 {
        color: #2c3e50;
        font-size: 24px;
        margin-bottom: 12px;
    }
    
    .empty-state p {
        color: #6c757d;
    }
    
    /* Buttons */
    .btn-emergency-target, .btn-emergency-deposit {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .btn-emergency-target {
    background: white;
    color: #1f2937;
    font-weight: 600;
}
    
    .btn-emergency-target:hover {
        background: #A9A9A9;
        transform: translateY(-2px);
        color: white;
    }
    
    .btn-emergency-deposit {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-emergency-deposit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .animated {
        animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .history-row {
        animation: slideIn 0.4s ease forwards;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .summary-item .value {
            font-size: 20px;
        }
        
        .history-table th,
        .history-table td {
            padding: 12px 8px;
            font-size: 12px;
        }
        
        .welcome-title {
            font-size: 1.3rem;
        }
        
        .filter-btn {
            padding: 6px 16px;
            font-size: 12px;
        }
    }
    
    /* Glass effect untuk card history */
    .glass-card {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
    }
    
    .text-success {
        color: #10b981 !important;
    }
    
    .text-danger {
        color: #ef4444 !important;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">
                        <i class="fas fa-history"></i> Riwayat Dana Darurat
                    </h1>
                    <p class="welcome-subtitle">
                        Semua transaksi masuk dan keluar dana darurat Anda
                    </p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <a href="index.php" class="btn-emergency-target">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="summary-card">
                    <div class="summary-item">
                        <div class="label">
                            <i class="fas fa-wallet"></i> Saldo Saat Ini
                        </div>
                        <div class="value">Rp <?= number_format($current_amount, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="summary-card">
                    <div class="summary-item">
                        <div class="label">
                            <i class="fas fa-arrow-down"></i> Total Setoran
                        </div>
                        <div class="value deposit">Rp <?= number_format($total_deposit, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="summary-card">
                    <div class="summary-item">
                        <div class="label">
                            <i class="fas fa-arrow-up"></i> Total Penarikan
                        </div>
                        <div class="value withdraw">Rp <?= number_format($total_withdraw, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">
                <i class="fas fa-list"></i> Semua Transaksi
            </button>
            <button class="filter-btn" data-filter="deposit">
                <i class="fas fa-arrow-down"></i> Setoran
            </button>
            <button class="filter-btn" data-filter="withdraw">
                <i class="fas fa-arrow-up"></i> Penarikan
            </button>
        </div>

        <!-- History Table -->
        <div class="glass-card" style="padding: 20px;">
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h4>Belum Ada Riwayat Transaksi</h4>
                    <p class="text-muted">Mulai kelola dana darurat Anda sekarang</p>
                    <a href="index.php" class="btn-emergency-deposit mt-3">
                        <i class="fas fa-plus-circle"></i> Tambah Dana Sekarang
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-alt"></i> Tanggal & Waktu</th>
                                <th><i class="fas fa-tag"></i> Tipe</th>
                                <th><i class="fas fa-money-bill-wave"></i> Jumlah</th>
                                <th><i class="fas fa-building"></i> Sumber/Tujuan</th>
                                <th><i class="fas fa-align-left"></i> Deskripsi</th>
                            </thead>
                        <tbody>
                            <?php 
                            $running_balance = $current_amount;
                            foreach ($transactions as $transaction): 
                                // Hitung saldo berjalan
                                if ($transaction['type'] == 'deposit') {
                                    $running_balance -= $transaction['amount'];
                                } else {
                                    $running_balance += $transaction['amount'];
                                }
                            ?>
                            <tr class="history-row" data-type="<?= $transaction['type'] ?>">
                                <td style="white-space: nowrap;">
                                    <div><?= date('d/m/Y', strtotime($transaction['transaction_date'])) ?></div>
                                    <small class="text-muted">
                                        <i class="far fa-clock"></i> <?= date('H:i', strtotime($transaction['created_at'])) ?>
                                    </small>
                                   </td>
                                   <td>
                                    <span class="badge-<?= $transaction['type'] == 'deposit' ? 'deposit' : 'withdraw' ?>">
                                        <i class="fas fa-<?= $transaction['type'] == 'deposit' ? 'arrow-down' : 'arrow-up' ?>"></i>
                                        <?= $transaction['type'] == 'deposit' ? 'Setoran' : 'Penarikan' ?>
                                    </span>
                                   </td>
                                <td class="<?= $transaction['type'] == 'deposit' ? 'text-success' : 'text-danger' ?> fw-bold">
                                    <?= $transaction['type'] == 'deposit' ? '+' : '-' ?> Rp <?= number_format($transaction['amount'], 0, ',', '.') ?>
                                   </td>
                                <td><?= htmlspecialchars($transaction['account_name'] ?? 'Akun tidak ditemukan') ?></td>
                                <td><?= htmlspecialchars($transaction['description'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Menampilkan <?= count($transactions) ?> transaksi
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Filter functionality with animation
    const filterButtons = document.querySelectorAll('.filter-btn');
    const historyRows = document.querySelectorAll('.history-row');
    
    if (filterButtons.length > 0 && historyRows.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                let visibleCount = 0;
                
                historyRows.forEach((row, index) => {
                    if (filter === 'all' || row.dataset.type === filter) {
                        row.style.display = '';
                        row.style.animation = 'none';
                        setTimeout(() => {
                            row.style.animation = `slideIn 0.4s ease ${visibleCount * 0.05}s forwards`;
                        }, 10);
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }
    
    // Animate rows on load
    if (historyRows.length > 0) {
        historyRows.forEach((row, index) => {
            row.style.animation = `slideIn 0.4s ease ${index * 0.03}s forwards`;
        });
    }
    
    // Add hover effect to summary cards
    const summaryCards = document.querySelectorAll('.summary-card');
    summaryCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>