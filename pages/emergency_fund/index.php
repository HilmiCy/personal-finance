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
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        color: #1f2937;
        position: relative;
        overflow: hidden;
        width: 100%;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    
    .welcome-title {
        font-size: 1.6rem;
        font-weight: 700;
        margin: 0;
        color: #1f2937;
    }
    
    .welcome-subtitle {
        margin: 8px 0 0 0;
        color: #6b7280;
        font-size: 0.95rem;
    }
    
    /* Button Styles */
    .btn {
        border-radius: 12px !important;
        padding: 10px 20px !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
        color: white !important;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4) !important;
    }
    
    .btn-group {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
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
    
    .display-amount {
        font-size: 28px;
        font-weight: 800;
        margin: 16px 0;
        color: #1f2937;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    /* Progress Bar */
    .progress {
        border-radius: 20px !important;
        background-color: #f3f4f6 !important;
        overflow: hidden !important;
        height: 12px !important;
        margin: 15px 0;
    }
    
    .progress-bar {
        border-radius: 20px !important;
        background: linear-gradient(90deg, #10b981 0%, #34d399 100%) !important;
    }
    
    /* Badge */
    .badge {
        border-radius: 12px !important;
        padding: 6px 12px !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
    }
    
    /* Alert */
    .alert {
        border-radius: 20px !important;
        border: none !important;
        padding: 20px !important;
        margin-bottom: 24px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    /* Table */
    .table {
        margin-bottom: 0 !important;
    }
    
    .table thead th {
        background-color: #f9fafb !important;
        border-bottom: 2px solid #e5e7eb !important;
        padding: 16px 12px !important;
        font-weight: 600 !important;
        color: #4b5563 !important;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table tbody td {
        padding: 16px 12px !important;
        vertical-align: middle !important;
        border-bottom: 1px solid #f3f4f6 !important;
        color: #4b5563;
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 24px !important;
        border: none !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2) !important;
        overflow: hidden;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 24px !important;
        border-bottom: none !important;
        color: white;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .modal-body {
        padding: 32px !important;
        background: #fff;
    }

    .modal-footer {
        padding: 20px 32px !important;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb !important;
    }

    /* SweetAlert2 Style */
    .swal2-popup {
        border-radius: 24px !important;
        padding: 2em !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }

    .swal2-title {
        font-weight: 700 !important;
        color: #1f2937 !important;
    }

    .swal2-confirm {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
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

        .display-amount {
            font-size: 22px !important;
        }

        /* Mobile Table */
        .table thead {
            display: none;
        }
        
        .table tbody td {
            display: block;
            padding: 12px 16px !important;
            text-align: right;
            position: relative;
            padding-left: 45% !important;
            border-bottom: none !important;
        }
        
        .table tbody td::before {
            content: attr(data-label);
            position: absolute;
            left: 16px;
            width: calc(45% - 20px);
            font-weight: 600;
            color: #6b7280;
            text-align: left;
            font-size: 12px;
        }
        
        .table tbody tr {
            display: block;
            border-bottom: 1px solid #e5e7eb !important;
            margin-bottom: 10px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .btn-group {
            flex-direction: column !important;
        }
        
        .btn-group .btn {
            width: 100% !important;
        }
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
</style>

<div id="content" class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="welcome-title">Dana Darurat</h1>
                    <p class="welcome-subtitle">Kelola dan pantau dana darurat Anda secara cerdas</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#setTargetModal">
                        <i class="fas fa-bullseye me-2"></i> Atur Target
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
                                    </tr>
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
                                            <td data-label="Tanggal" style="white-space: nowrap;">
                                                <i class="fas fa-calendar-alt text-muted"></i> 
                                                <?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?>
                                                <br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                            </td>
                                            <td data-label="Tipe">
                                                <span class="badge bg-<?php echo $transaction['type'] == 'deposit' ? 'success' : 'warning'; ?>">
                                                    <i class="fas fa-<?php echo $transaction['type'] == 'deposit' ? 'arrow-down' : 'arrow-up'; ?> me-1"></i>
                                                    <?php echo $transaction['type'] == 'deposit' ? 'Setoran' : 'Penarikan'; ?>
                                                </span>
                                            </td>
                                            <td data-label="Jumlah" class="fw-bold <?php echo $transaction['type'] == 'deposit' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $transaction['type'] == 'deposit' ? '+' : '-'; ?> 
                                                Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?>
                                            </td>
                                            <td data-label="Sumber/Tujuan"><?php echo htmlspecialchars($transaction['account_name'] ?? '-'); ?></td>
                                            <td data-label="Deskripsi"><?php echo htmlspecialchars($transaction['description'] ?: '-'); ?></td>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-bullseye me-2"></i> Atur Target Dana Darurat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="setTargetForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Jumlah</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control money" name="target_amount" 
                               value="<?php echo $fund ? number_format($fund['target_amount'], 0, ',', '.') : ''; ?>" required>
                        </div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Deposit -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Dana Darurat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="depositForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control money" name="amount" required>
                        </div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">Simpan Setoran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Withdraw -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-arrow-up-circle me-2"></i> Tarik Dana Darurat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control money" name="amount" required>
                        </div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none;">Tarik Dana</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
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

function showSuccessAlert(message) {
    Swal.fire({
        title: 'Berhasil!',
        text: message,
        icon: 'success',
        confirmButtonText: 'OK',
        didOpen: () => {
            canvasConfetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    }).then(() => {
        window.location.reload();
    });
}

function showErrorAlert(message) {
    Swal.fire({
        title: 'Gagal!',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK'
    });
}

// Handle Set Target Form
document.getElementById('setTargetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'set_target');
    
    Swal.fire({
        title: 'Memproses...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    try {
        const response = await fetch('add.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            showSuccessAlert(result.message);
        } else {
            showErrorAlert(result.message);
        }
    } catch (error) {
        showErrorAlert('Terjadi kesalahan sistem');
    }
});

// Handle Deposit Form
document.getElementById('depositForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'deposit');
    
    Swal.fire({
        title: 'Menyimpan...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    try {
        const response = await fetch('add.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            showSuccessAlert(result.message);
        } else {
            showErrorAlert(result.message);
        }
    } catch (error) {
        showErrorAlert('Terjadi kesalahan sistem');
    }
});

// Handle Withdraw Form
document.getElementById('withdrawForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'withdraw');
    
    Swal.fire({
        title: 'Memproses Penarikan...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    try {
        const response = await fetch('add.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            showSuccessAlert(result.message);
        } else {
            showErrorAlert(result.message);
        }
    } catch (error) {
        showErrorAlert('Terjadi kesalahan sistem');
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>