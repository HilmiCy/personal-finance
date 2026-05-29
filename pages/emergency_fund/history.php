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
    .glass-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-xl); box-shadow: var(--card-shadow); transition: var(--transition); }
    .glass-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
    .summary-item .value { font-size: 28px; font-weight: 800; color: var(--fg); }
    .summary-item .value.deposit { color: var(--success); }
    .summary-item .value.withdraw { color: var(--danger); }
    .filter-btn { padding: 10px 24px; border-radius: var(--radius-full); border: 2px solid var(--border); background: var(--card-bg); cursor: pointer; transition: var(--transition); color: var(--muted); font-weight: 500; }
    .filter-btn.active { background: var(--primary); border-color: var(--primary); color: white; }
    .filter-btn:hover { transform: translateY(-2px); border-color: var(--info); color: var(--info); }
    .badge-deposit, .badge-withdraw { padding: 6px 14px; border-radius: var(--radius-full); font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .badge-deposit { background: rgba(52,168,83,0.1); color: var(--success); }
    .badge-withdraw { background: rgba(234,67,53,0.1); color: var(--danger); }
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