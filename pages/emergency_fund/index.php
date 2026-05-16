<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/EmergencyFund.php';
require_once '../../classes/Account.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$emergencyFund = new EmergencyFund();
$fund = $emergencyFund->getEmergencyFund($_SESSION['user_id']);
$transactions = $emergencyFund->getTransactions($_SESSION['user_id'], 10); // Ambil 10 transaksi terbaru
$progress = $emergencyFund->getProgress($_SESSION['user_id']);
$recommendation = $emergencyFund->getRecommendation($_SESSION['user_id']);

// Perbaikan: menggunakan getAll() bukan getAccounts()
$accounts = new Account();
$userAccounts = $accounts->getAll($_SESSION['user_id']);

// Jika transaksi kosong, coba fallback query langsung
if (empty($transactions)) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                eft.*, 
                a.name as account_name
            FROM emergency_fund_transactions eft
            LEFT JOIN accounts a ON eft.account_id = a.id
            WHERE eft.user_id = ?
            ORDER BY eft.transaction_date DESC, eft.id DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Fallback query error in index: " . $e->getMessage());
        $transactions = [];
    }
}
?>

<style>
    /* ========== LAYOUT UTAMA ========== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        overflow-x: hidden !important;
        width: 100% !important;
        position: relative;
    }
    
    /* Pastikan wrapper memiliki display flex */
    .wrapper {
        display: flex !important;
        width: 100% !important;
        align-items: stretch !important;
        overflow-x: hidden !important;
    }
    
    /* Sidebar styling - pastikan lebar tetap */
    #sidebar {
        min-width: 250px !important;
        max-width: 250px !important;
        width: 250px !important;
        transition: all 0.3s;
        flex-shrink: 0 !important;
        background: #2c3e50;
        color: #fff;
    }
    
    /* Konten utama */
    #content, .main-content {
        width: calc(100% - 250px) !important;
        min-height: 100vh !important;
        transition: all 0.3s;
        overflow-x: hidden !important;
        flex: 1 !important;
        background: #f8f9fa;
    }
    
    .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        padding: 20px !important;
        margin: 0 !important;
        overflow-x: hidden !important;
    }
    
    /* ========== RESPONSIVE ========== */
    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px !important;
            position: fixed !important;
            z-index: 1000 !important;
            height: 100vh !important;
        }
        
        #sidebar.active {
            margin-left: 0 !important;
        }
        
        #content, .main-content {
            width: 100% !important;
        }
        
        .container-fluid {
            padding: 15px !important;
        }
    }
    
    /* ========== CARD STYLES ========== */
    .card {
        border-radius: 20px !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-bottom: 20px !important;
        overflow: hidden !important;
        background: white !important;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }
    
    .card-body {
        padding: 24px !important;
    }
    
    .card-title {
        font-size: 14px;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* ========== WELCOME CARD ========== */
    .welcome-card {
    background: linear-gradient(135deg, #FFFFFF 0%, #FFFFFF 100%);
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    color: white;
    position: relative;
    overflow: hidden;
    width: 100%;

    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
    
    .welcome-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    /* Button Styles */
    .btn {
        border-radius: 12px !important;
        padding: 10px 20px !important;
        font-weight: 500 !important;
        transition: all 0.2s !important;
    }
    
    .btn-group {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    
    .btn-group .btn {
        margin: 0 !important;
        flex: 1 !important;
    }
    
    /* Stat Cards */
    .stat-card {
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: rotate(45deg);
        transition: all 0.6s;
    }
    
    .stat-card:hover::before {
        transform: rotate(45deg) translate(50%, 50%);
    }
    
    .display-amount {
        font-size: 28px;
        font-weight: 700;
        margin: 16px 0;
        color: #2c3e50;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    /* Progress Bar */
    .progress {
        border-radius: 20px !important;
        background-color: #f0f0f0 !important;
        overflow: hidden !important;
        height: 10px !important;
    }
    
    .progress-bar {
        border-radius: 20px !important;
    }
    
    /* Badge */
    .badge {
        border-radius: 12px !important;
        padding: 6px 12px !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
    }
    
    /* Alert */
    .alert {
        border-radius: 16px !important;
        border: none !important;
        padding: 16px 20px !important;
        margin-bottom: 20px !important;
    }
    
    /* Table */
    .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    .table {
        border-radius: 16px !important;
        overflow: hidden !important;
        min-width: 500px !important;
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    
    .table thead th {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #e0e0e0 !important;
        padding: 14px 12px !important;
        font-weight: 600 !important;
    }
    
    .table tbody td {
        padding: 12px !important;
        vertical-align: middle !important;
    }
    
    /* Transaction Row Animation */
    .transaction-row {
        transition: background-color 0.2s;
    }
    
    .transaction-row:hover {
        background-color: #f8f9fa !important;
    }
    
    /* Modal */
    .modal-content {
        border-radius: 24px !important;
        border: none !important;
    }
    
    .modal-header {
        border-radius: 24px 24px 0 0 !important;
        padding: 20px 24px !important;
    }
    
    .modal-footer {
        border-radius: 0 0 24px 24px !important;
        padding: 16px 24px !important;
    }
    
    /* Form */
    .form-control, .form-select {
        border-radius: 12px !important;
        border: 1px solid #e0e0e0 !important;
        padding: 10px 14px !important;
        transition: all 0.2s !important;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15) !important;
    }
    
    /* Animasi */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animated {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    
    /* Row dan Column */
    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    [class*="col-"] {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 15px !important;
        }
        
        .card-body {
            padding: 20px !important;
        }
        
        .display-amount {
            font-size: 24px !important;
        }
        
        .btn {
            padding: 8px 16px !important;
            font-size: 14px !important;
        }
        
        .welcome-card {
            padding: 16px 20px !important;
        }
        
        .welcome-title {
            font-size: 1.2rem !important;
        }
        
        .welcome-subtitle {
            font-size: 0.8rem !important;
        }
        
        .btn-group {
            flex-direction: column !important;
        }
        
        .btn-group .btn {
            width: 100% !important;
        }
        
        .modal-dialog {
            margin: 10px !important;
        }
    }
    
    @media (max-width: 576px) {
        .display-amount {
            font-size: 20px !important;
        }
        
        .card-title {
            font-size: 12px !important;
        }
        
        .badge {
            font-size: 11px !important;
            padding: 4px 8px !important;
        }
    }
    
    /* Scrollbar styling */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<!-- PERUBAHAN: Pastikan konten utama menggunakan class yang tepat -->
<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title" style="animation: fadeInUp 0.4s ease;">Dana Darurat</h1>
                    <p class="welcome-subtitle" style="animation: fadeInUp 0.4s ease 0.1s both;">Kelola dan pantau dana darurat Anda</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#setTargetModal" style="background: rgba(255,255,255,0.2); border: none; color: white; animation: fadeInUp 0.4s ease 0.2s both;">
                        <i class="fas fa-plus me-1"></i> Atur Target
                    </button>
                </div>
            </div>
        </div>

        <!-- Recommendation Alert -->
        <div class="row">
            <div class="col-12">
                <div class="alert alert-<?php 
                    echo $recommendation['status'] == 'achieved' ? 'success' : 
                        ($recommendation['status'] == 'good' ? 'info' : 
                        ($recommendation['status'] == 'moderate' ? 'warning' : 'danger')); 
                ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <i class="fas fa-<?php 
                            echo $recommendation['status'] == 'achieved' ? 'check-circle' : 
                                ($recommendation['status'] == 'good' ? 'info-circle' : 
                                ($recommendation['status'] == 'moderate' ? 'exclamation-triangle' : 'times-circle')); 
                        ?> fa-2x"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-1"><?php echo $recommendation['message']; ?></strong>
                            <small><?php echo $recommendation['suggestion']; ?></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-bullseye me-2"></i> Target Dana Darurat
                        </h5>
                        <div class="display-amount">
                            Rp <?php echo number_format($fund ? $fund['target_amount'] : 0, 0, ',', '.'); ?>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-<?php 
                                echo $fund && $fund['priority_level'] == 'critical' ? 'danger' : 
                                    ($fund && $fund['priority_level'] == 'high' ? 'warning' : 
                                    ($fund && $fund['priority_level'] == 'medium' ? 'info' : 'secondary')); 
                            ?>">
                                <i class="fas fa-flag me-1"></i>
                                <?php echo $fund ? ucfirst($fund['priority_level']) : 'Not Set'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-piggy-bank me-2"></i> Terkumpul
                        </h5>
                        <div class="display-amount">
                            Rp <?php echo number_format($fund ? $fund['current_amount'] : 0, 0, ',', '.'); ?>
                        </div>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p class="text-muted mt-2 mb-0">
                            <small>Progress: <?php echo round($progress, 1); ?>%</small>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line me-2"></i> Kekurangan
                        </h5>
                        <div class="display-amount">
                            Rp <?php echo number_format(($fund ? $fund['target_amount'] - $fund['current_amount'] : 0), 0, ',', '.'); ?>
                        </div>
                        <p class="text-muted mt-2 mb-0">
                            <small>
                                <?php if ($fund && $fund['target_amount'] > 0): ?>
                                    <?php $months_needed = ($fund['target_amount'] - $fund['current_amount']) / 500000; ?>
                                    <i class="fas fa-clock me-1"></i>
                                    Estimasi <?php echo ceil($months_needed); ?> bulan lagi
                                <?php endif; ?>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-2 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-bolt me-2"></i> Aksi Cepat
                        </h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
                                <i class="fas fa-plus-circle me-2"></i> Tambah Dana
                            </button>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                                <i class="fas fa-arrow-up-circle me-2"></i> Tarik Dana
                            </button>
                            <a href="history.php" class="btn btn-info">
                                <i class="fas fa-history me-2"></i> Riwayat Transaksi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-receipt me-2"></i> Transaksi Terbaru
                            </h5>
                            <?php if (!empty($transactions) && count($transactions) > 0): ?>
                                <a href="history.php" class="btn btn-sm btn-outline-primary">
                                    Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Tipe</th>
                                        <th>Jumlah</th>
                                        <th>Sumber/Tujuan</th>
                                        <th>Deskripsi</th>
                                    </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                            <span class="text-muted">Belum ada transaksi</span>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
                                                    <i class="fas fa-plus-circle"></i> Tambah Dana Sekarang
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($transactions as $transaction): ?>
                                        <tr class="transaction-row">
                                            <td style="white-space: nowrap;">
                                                <i class="fas fa-calendar-alt text-muted"></i> 
                                                <?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?>
                                                <br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $transaction['type'] == 'deposit' ? 'success' : 'warning'; ?>">
                                                    <i class="fas fa-<?php echo $transaction['type'] == 'deposit' ? 'arrow-down' : 'arrow-up'; ?> me-1"></i>
                                                    <?php echo $transaction['type'] == 'deposit' ? 'Setoran' : 'Penarikan'; ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold <?php echo $transaction['type'] == 'deposit' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $transaction['type'] == 'deposit' ? '+' : '-'; ?> 
                                                Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['account_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description'] ?: '-'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Set Target -->
<div class="modal fade" id="setTargetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-bullseye me-2"></i> Atur Target Dana Darurat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="setTargetForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Jumlah</label>
                        <input type="text" class="form-control money" name="target_amount" 
                               value="<?php echo $fund ? number_format($fund['target_amount'], 0, ',', '.') : ''; ?>" required>
                        <small class="text-muted">Rekomendasi: 3-6 kali pengeluaran bulanan Anda</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tingkat Prioritas</label>
                        <select class="form-select" name="priority_level">
                            <option value="low" <?php echo $fund && $fund['priority_level'] == 'low' ? 'selected' : ''; ?>>Low - Prioritas Rendah</option>
                            <option value="medium" <?php echo $fund && $fund['priority_level'] == 'medium' ? 'selected' : ''; ?>>Medium - Prioritas Sedang</option>
                            <option value="high" <?php echo $fund && $fund['priority_level'] == 'high' ? 'selected' : ''; ?>>High - Prioritas Tinggi</option>
                            <option value="critical" <?php echo $fund && $fund['priority_level'] == 'critical' ? 'selected' : ''; ?>>Critical - Prioritas Kritis</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Deposit -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Dana Darurat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="depositForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah</label>
                        <input type="text" class="form-control money" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sumber Dana</label>
                        <select class="form-select" name="account_id" required>
                            <option value="">Pilih Akun</option>
                            <?php foreach($userAccounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>">
                                <?php echo htmlspecialchars($account['name']); ?> - Rp <?php echo number_format($account['balance'], 0, ',', '.'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Tambah Dana
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Withdraw -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-arrow-up-circle me-2"></i> Tarik Dana Darurat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah</label>
                        <input type="text" class="form-control money" name="amount" required>
                        <small class="text-muted">Saldo saat ini: Rp <?php echo number_format($fund ? $fund['current_amount'] : 0, 0, ',', '.'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tujuan Transfer</label>
                        <select class="form-select" name="account_id" required>
                            <option value="">Pilih Akun Tujuan</option>
                            <?php foreach($userAccounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>">
                                <?php echo htmlspecialchars($account['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Alasan penarikan dana darurat"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-arrow-up me-1"></i> Tarik Dana
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Format currency input
document.querySelectorAll('.money').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/[^\d]/g, '');
        if (value) {
            this.value = new Intl.NumberFormat('id-ID').format(value);
        }
    });
});

// Handle Set Target Form
document.getElementById('setTargetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'set_target');
    
    try {
        const response = await fetch('add.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Terjadi kesalahan: ' + error.message);
    }
});

// Handle Deposit Form
document.getElementById('depositForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'deposit');
    
    try {
        const response = await fetch('add.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Terjadi kesalahan: ' + error.message);
    }
});

// Handle Withdraw Form
document.getElementById('withdrawForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'withdraw');
    
    try {
        const response = await fetch('add.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Terjadi kesalahan: ' + error.message);
    }
});

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    alertDiv.style.maxWidth = '90%';
    alertDiv.style.borderRadius = '12px';
    alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'danger' ? 'times-circle' : 'info-circle')} me-2"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<?php require_once '../../includes/footer.php'; ?>