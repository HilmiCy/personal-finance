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
    /* ========== EMERGENCY FUND SPECIFIC STYLES ========== */
    .stat-card-custom { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 35px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        transition: var(--transition); 
        height: 100%; 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }
    .stat-card-custom:hover { transform: translateY(-5px); box-shadow: 0 25px 60px rgba(0, 0, 0, 0.07); }
    
    .stat-card-custom .card-icon { 
        width: 52px; height: 52px; 
        background: var(--surface); 
        border-radius: 16px; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 22px; margin-bottom: 20px; color: var(--info); 
        border: 1px solid var(--border);
    }
    
    .stat-card-custom .card-title { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; }
    .display-amount { font-size: 26px; font-weight: 800; color: var(--fg); letter-spacing: -0.02em; margin: 0; }
    
    .progress-container { margin-top: 25px; }
    .progress { height: 8px; border-radius: 10px; background: rgba(0, 0, 0, 0.04); overflow: hidden; }
    .progress-bar { transition: width 1s cubic-bezier(0.22, 1, 0.36, 1); }
    
    .action-card {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 32px;
        padding: 30px 35px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04);
        backdrop-filter: blur(10px);
        margin-bottom: 35px;
    }
    
    .btn-action-group { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn-custom-action {
        padding: 12px 28px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 14px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .btn-deposit { background: var(--fg); color: white; }
    .btn-deposit:hover { background: #000; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    
    .btn-withdraw { background: var(--surface); color: var(--fg); }
    .btn-withdraw:hover { background: var(--border); transform: translateY(-2px); }
    
    .recent-card {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 32px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        backdrop-filter: blur(10px);
    }
    
    .recent-header { padding: 30px 35px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); display: flex; justify-content: space-between; align-items: center; }
    .recent-header h5 { margin: 0; font-weight: 800; font-size: 18px; display: flex; align-items: center; gap: 12px; }
    
    .table-custom thead th {
        background: rgba(0, 0, 0, 0.01);
        padding: 20px 25px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--muted);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .table-custom tbody td { padding: 22px 25px; vertical-align: middle; border-bottom: 1px solid rgba(0, 0, 0, 0.03); }
    
    .transaction-type-pill { padding: 6px 14px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
    .pill-deposit { background: rgba(52,168,83,0.1); color: #10b981; }
    .pill-withdraw { background: rgba(245,158,11,0.1); color: #f59e0b; }
    
    .recommendation-alert {
        border-radius: 24px; border: 1px solid transparent; padding: 25px 30px; margin-bottom: 35px;
        display: flex; align-items: center; gap: 20px;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="welcome-title">Dana Darurat</h1>
                    <p class="welcome-subtitle">Keamanan finansial adalah prioritas utama Anda</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#setTargetModal">
                        <i class="fas fa-bullseye me-2"></i> Atur Target
                    </button>
                </div>
            </div>
        </div>

        <!-- Recommendation Alert -->
        <div class="recommendation-alert animated <?= $recommendation['status'] == 'achieved' ? 'bg-success' : ($recommendation['status'] == 'good' ? 'bg-info' : ($recommendation['status'] == 'moderate' ? 'bg-warning' : 'bg-danger')) ?>" 
             style="background: rgba(<?= $recommendation['status'] == 'achieved' ? '52,168,83' : ($recommendation['status'] == 'good' ? '66,133,244' : ($recommendation['status'] == 'moderate' ? '251,188,5' : '234,67,53')) ?>, 0.08); border-color: rgba(<?= $recommendation['status'] == 'achieved' ? '52,168,83' : ($recommendation['status'] == 'good' ? '66,133,244' : ($recommendation['status'] == 'moderate' ? '251,188,5' : '234,67,53')) ?>, 0.15); color: var(--fg);">
            <div style="width: 56px; height: 56px; border-radius: 16px; background: white; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); flex-shrink: 0;">
                <i class="fas fa-<?= $recommendation['status'] == 'achieved' ? 'check-circle text-success' : ($recommendation['status'] == 'good' ? 'info-circle text-info' : ($recommendation['status'] == 'moderate' ? 'exclamation-triangle text-warning' : 'times-circle text-danger')) ?>"></i>
            </div>
            <div>
                <div style="font-weight: 800; font-size: 16px; margin-bottom: 4px;"><?php echo $recommendation['message']; ?></div>
                <div style="font-size: 14px; opacity: 0.8; font-weight: 600;"><?php echo $recommendation['suggestion']; ?></div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-5 g-4">
            <div class="col-md-4">
                <div class="stat-card-custom animated" style="animation-delay: 0.1s">
                    <div class="card-icon"><i class="fas fa-bullseye"></i></div>
                    <div class="card-title">Target Dana Darurat</div>
                    <div class="display-amount">Rp <?php echo number_format($fund ? $fund['target_amount'] : 0, 0, ',', '.'); ?></div>
                    <div class="mt-3">
                        <span class="badge" style="background: var(--surface); color: var(--fg); padding: 8px 16px; border-radius: 10px; font-weight: 700; border: 1px solid var(--border);">
                            <i class="fas fa-flag me-2 text-<?= $fund && $fund['priority_level'] == 'critical' ? 'danger' : 'info' ?>"></i>
                            Prioritas: <?= $fund ? ucfirst($fund['priority_level']) : '-' ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card-custom animated" style="animation-delay: 0.2s">
                    <div class="card-icon" style="color: #34a853;"><i class="fas fa-piggy-bank"></i></div>
                    <div class="card-title">Total Terkumpul</div>
                    <div class="display-amount text-success">Rp <?php echo number_format($fund ? $fund['current_amount'] : 0, 0, ',', '.'); ?></div>
                    <div class="progress-container">
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: <?php echo min(100, $progress); ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span style="font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase;">Progress</span>
                            <span style="font-size: 12px; font-weight: 800; color: var(--success);"><?php echo round($progress, 1); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card-custom animated" style="animation-delay: 0.3s">
                    <div class="card-icon" style="color: #ea4335;"><i class="fas fa-chart-pie"></i></div>
                    <div class="card-title">Sisa Kekurangan</div>
                    <?php $gap = ($fund ? max(0, $fund['target_amount'] - $fund['current_amount']) : 0); ?>
                    <div class="display-amount <?= $gap > 0 ? 'text-danger' : '' ?>">Rp <?php echo number_format($gap, 0, ',', '.'); ?></div>
                    <div class="mt-3 d-flex align-items-center gap-2" style="font-size: 13px; font-weight: 600; color: var(--muted);">
                        <?php if ($fund && $gap > 0): ?>
                            <?php $months_needed = $gap / 500000; ?>
                            <i class="fas fa-hourglass-half"></i>
                            Estimasi <?= ceil($months_needed) ?> bulan lagi (Rp 500k/bln)
                        <?php else: ?>
                            <i class="fas fa-check-double text-success"></i> Target Tercapai
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="action-card animated" style="animation-delay: 0.4s">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 style="font-weight: 800; color: var(--fg); margin-bottom: 5px;">Aksi Cepat</h5>
                    <p class="text-muted mb-0" style="font-size: 13px; font-weight: 600;">Lakukan setoran atau penarikan dana darurat Anda</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="btn-action-group justify-content-md-end">
                        <button type="button" class="btn-custom-action btn-deposit" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="fas fa-plus-circle"></i> Tambah Dana
                        </button>
                        <button type="button" class="btn-custom-action btn-withdraw" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            <i class="fas fa-arrow-up-circle"></i> Tarik Dana
                        </button>
                        <a href="history.php" class="btn-custom-action btn-withdraw">
                            <i class="fas fa-history"></i> Riwayat
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="recent-card animated" style="animation-delay: 0.5s">
            <div class="recent-header">
                <h5><i class="fas fa-receipt"></i> Transaksi Terbaru</h5>
                <?php if (!empty($transactions)): ?>
                    <a href="history.php" class="btn-month">Lihat Semua <i class="fas fa-arrow-right ms-2" style="font-size: 12px;"></i></a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-5">Tanggal</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Sumber/Tujuan</th>
                            <th class="pe-5">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div style="opacity: 0.2; margin-bottom: 15px;"><i class="fas fa-receipt fa-4x"></i></div>
                                <p class="text-muted mb-0 fw-bold">Belum ada riwayat transaksi</p>
                                <div class="mt-3">
                                    <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#depositModal">
                                        <i class="fas fa-plus-circle me-2"></i> Tambah Dana Sekarang
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($transactions as $transaction): ?>
                            <tr>
                                <td class="ps-5">
                                    <div class="fw-bold"><?= date('d M Y', strtotime($transaction['transaction_date'])) ?></div>
                                    <div style="font-size: 11px; color: var(--muted);"><?= date('H:i', strtotime($transaction['created_at'])) ?> WIB</div>
                                </td>
                                <td>
                                    <span class="transaction-type-pill <?= $transaction['type'] == 'deposit' ? 'pill-deposit' : 'pill-withdraw' ?>">
                                        <i class="fas fa-<?= $transaction['type'] == 'deposit' ? 'arrow-down' : 'arrow-up' ?>"></i>
                                        <?= $transaction['type'] == 'deposit' ? 'SETORAN' : 'PENARIKAN' ?>
                                    </span>
                                </td>
                                <td class="fw-bold <?= $transaction['type'] == 'deposit' ? 'text-success' : 'text-danger' ?>">
                                    <?= $transaction['type'] == 'deposit' ? '+' : '-' ?> Rp <?= number_format($transaction['amount'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--fg); font-size: 13px;">
                                        <i class="fas fa-credit-card me-2 text-muted" style="font-size: 11px;"></i>
                                        <?= htmlspecialchars($transaction['account_name'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="pe-5">
                                    <span style="font-size: 12px; color: var(--muted); font-weight: 600;"><?= htmlspecialchars($transaction['description'] ?: '-') ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2 text-success"></i> Tambah Dana Darurat
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
                    <button type="submit" class="btn btn-primary">Simpan Setoran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Withdraw -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-arrow-up-circle me-2 text-warning"></i> Tarik Dana Darurat
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
                    <button type="submit" class="btn btn-primary">Tarik Dana</button>
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