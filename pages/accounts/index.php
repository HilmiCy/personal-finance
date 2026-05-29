<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Account.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Manajemen Akun';
$current_page = 'accounts';

$account = new Account();

// Get all accounts
$accounts = $account->getAll($_SESSION['user_id']);
$total_balance = $account->getTotalBalance($_SESSION['user_id']);
$transfer_history = $account->getTransferHistory($_SESSION['user_id'], 10);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .account-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 35px; 
        transition: var(--transition); 
        height: 100%; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        position: relative;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .account-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.07);
        border-color: rgba(66, 133, 244, 0.3);
    }
    .account-icon { 
        width: 56px; 
        height: 56px; 
        border-radius: 20px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 24px; 
        margin-bottom: 24px;
        background: rgba(66, 133, 244, 0.08);
        color: var(--info);
    }
    .account-name { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
    .account-balance { font-size: 28px; font-weight: 750; color: #1e293b; letter-spacing: -0.04em; }
    .summary-balance-card { background: rgba(255, 255, 255, 0.95); border-radius: 32px; padding: 40px; border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); margin-bottom: 35px; backdrop-filter: blur(10px); }
    .history-card { background: rgba(255, 255, 255, 0.95); border-radius: 32px; overflow: hidden; margin-top: 35px; border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); backdrop-filter: blur(10px); }
    .history-header { padding: 25px 32px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); background: rgba(255, 255, 255, 0.2); }
    .history-item { padding: 20px 32px; border-bottom: 1px solid rgba(0, 0, 0, 0.03); transition: all 0.2s ease; }
    .history-item:hover { background: rgba(0, 0, 0, 0.01); }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="welcome-title">Daftar Akun</h1>
                    <p class="welcome-subtitle">Ringkasan seluruh sumber dana Anda</p>
                </div>
                <div class="col-lg-6 text-lg-end mt-3 mt-lg-0 header-actions">
                    <a href="historytransfer.php" class="btn-action-minimal">
                        <i class="fas fa-history"></i> Riwayat
                    </a>
                    <?php if (count($accounts) >= 2): ?>
                    <button class="btn-action-minimal" data-bs-toggle="modal" data-bs-target="#transferModal">
                        <i class="fas fa-exchange-alt"></i> Transfer
                    </button>
                    <?php endif; ?>
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                        <i class="fas fa-plus"></i> Akun Baru
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="summary-balance-card animated" style="animation-delay: 0.1s">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div>
                            <div style="font-size: 14px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Total Saldo Terkonsolidasi</div>
                            <div class="account-balance" style="font-size: 2.5rem; color: #10b981; margin-top: 5px;"><?= formatRupiah($total_balance) ?></div>
                        </div>
                        <div class="text-muted d-none d-md-block" style="opacity: 0.15;">
                            <i class="fas fa-shield-alt fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div class="row g-4">
            <?php if (count($accounts) > 0): ?>
                <?php foreach ($accounts as $index => $acc): ?>
                <div class="col-md-6 col-xl-4" style="animation: fadeInUp 0.5s ease <?= $index * 0.05 ?>s both;">
                    <div class="account-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="account-icon">
                                <i class="fas fa-<?= getAccountIcon($acc['name']) ?>"></i>
                            </div>
                            <div class="dropdown dropdown-minimal">
                                <button class="btn btn-link" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" onclick="editAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars($acc['name']) ?>', <?= $acc['balance'] ?>, '<?= $acc['currency'] ?>')">
                                            <i class="fas fa-pencil-alt me-2 text-primary"></i> Ubah Nama/Saldo
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteAccount(<?= $acc['id'] ?>)">
                                            <i class="fas fa-trash-alt me-2"></i> Hapus Akun
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="account-name"><?= htmlspecialchars($acc['name']) ?></div>
                        <div class="account-balance"><?= formatCurrency($acc['balance'], $acc['currency']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="empty-state animated">
                        <i class="fas fa-wallet fa-4x mb-3 opacity-20"></i>
                        <p class="text-muted">Belum ada akun yang terdaftar.</p>
                        <button class="btn-primary-custom mt-2" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                            Buat Akun Pertama
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Transfer History -->
        <?php if (count($transfer_history) > 0): ?>
        <div class="history-card animated" style="animation-delay: 0.2s">
            <div class="history-header">
                <h6 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 1px; color: #6b7280;">Aktivitas Transfer</h6>
                <a href="historytransfer.php" class="text-decoration-none small fw-bold" style="color: #4285f4;">Lihat Semua</a>
            </div>
            <div class="history-list">
                <?php foreach ($transfer_history as $history): ?>
                <div class="history-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light rounded-circle p-2 text-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-exchange-alt text-muted small"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size: 14px; color: #374151;">
                                <?= htmlspecialchars($history['from_account_name']) ?> <i class="fas fa-long-arrow-alt-right mx-1 text-muted"></i> <?= htmlspecialchars($history['to_account_name']) ?>
                            </div>
                            <div style="font-size: 12px; color: #9ca3af;"><?= formatDateTime($history['transfer_date']) ?></div>
                        </div>
                    </div>
                    <div class="text-end fw-bold" style="color: #ef4444;">
                        -<?= formatRupiah($history['amount']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Akun -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Akun Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addAccountForm" action="add.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Akun</label>
                        <input type="text" name="name" id="add_account_name" class="form-control" placeholder="Contoh: Bank BCA, Cash, OVO, Dana" required>
                        <small class="text-muted">Masukkan nama akun yang mudah diingat</small>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Saldo Awal</label>
                        <input type="text" name="balance_display" id="balance_display" class="form-control currency-input" placeholder="0" value="0">
                        <input type="hidden" name="balance" id="balance_hidden">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mata Uang</label>
                        <select name="currency" class="form-select">
                            <option value="IDR">IDR (Rupiah)</option>
                            <option value="USD">USD (Dollar)</option>
                            <option value="EUR">EUR (Euro)</option>
                            <option value="SGD">SGD (Singapore Dollar)</option>
                            <option value="JPY">JPY (Yen)</option>
                        </select>
                    </div>
                        <small class="text-muted">Masukkan saldo awal akun ini</small>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Akun -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Akun
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAccountForm" action="edit.php" method="POST">
                <input type="hidden" name="id" id="edit_account_id">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Akun</label>
                        <input type="text" name="name" id="edit_account_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Saldo</label>
                        <input type="text" id="edit_balance_display" class="form-control currency-input">
                        <input type="hidden" name="balance" id="edit_balance_hidden">
                        <small class="text-muted">Perubahan saldo akan mempengaruhi laporan keuangan</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mata Uang</label>
                        <select name="currency" id="edit_account_currency" class="form-select">
                            <option value="IDR">IDR (Rupiah)</option>
                            <option value="USD">USD (Dollar)</option>
                            <option value="EUR">EUR (Euro)</option>
                            <option value="SGD">SGD (Singapore Dollar)</option>
                            <option value="JPY">JPY (Yen)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-1"></i> Update Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Transfer Antar Akun -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i> Transfer Antar Akun
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transferForm">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dari Akun</label>
                        <select name="from_account_id" id="from_account_id" class="form-select" required>
                            <option value="">Pilih Akun Sumber</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>" data-balance="<?= $acc['balance'] ?>">
                                    <?= htmlspecialchars($acc['name']) ?> (<?= formatRupiah($acc['balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Pilih akun sumber dana</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ke Akun</label>
                        <select name="to_account_id" id="to_account_id" class="form-select" required>
                            <option value="">Pilih Akun Tujuan</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>">
                                    <?= htmlspecialchars($acc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Pilih akun tujuan transfer</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Transfer</label>
                        <input type="text" id="transfer_amount_display" class="form-control currency-input" placeholder="0" required>
                        <input type="hidden" name="amount" id="transfer_amount_hidden">
                        <small class="text-muted">Masukkan jumlah yang akan ditransfer</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi (Opsional)</label>
                        <textarea name="description" id="transfer_description" class="form-control" rows="2" placeholder="Contoh: Transfer untuk keperluan..."></textarea>
                    </div>
                    <div class="alert alert-info-custom" id="balanceAlert" style="display: none;">
                        <i class="fas fa-info-circle"></i> Saldo tersedia: <strong id="availableBalance">Rp 0</strong>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn-primary-custom" id="btnTransfer">
                        <i class="fas fa-paper-plane me-1"></i> Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Global Styles for rounded SweetAlert (applied via CSS at the top)
    
    // Success / Error Session Alerts
    <?php if (isset($_SESSION['success_message']) || isset($_SESSION['success'])): ?>
    <?php $msg = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : $_SESSION['success']; ?>
    Swal.fire({
        title: 'Berhasil!',
        text: <?= json_encode($msg) ?>,
        icon: 'success',
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
            confirmButton: 'swal2-confirm'
        },
        buttonsStyling: false
    }).then(function() {
        window.location.href = 'index.php'; // Force refresh and clear session
    });
    <?php unset($_SESSION['success_message'], $_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message']) || isset($_SESSION['error'])): ?>
    <?php $msg = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : $_SESSION['error']; ?>
    Swal.fire({
        title: 'Gagal!',
        text: <?= json_encode($msg) ?>,
        icon: 'error',
        customClass: {
            popup: 'swal2-popup',
            title: 'swal2-title',
            confirmButton: 'swal2-confirm'
        },
        buttonsStyling: false
    });
    <?php unset($_SESSION['error_message'], $_SESSION['error']); ?>
    <?php endif; ?>

    // Format currency input
    var currencyInputs = document.querySelectorAll('.currency-input');
    for (var i = 0; i < currencyInputs.length; i++) {
        currencyInputs[i].addEventListener('input', function(e) {
            var val = this.value.replace(/[^\d]/g, '');
            var num = val ? parseInt(val, 10) : 0;
            if (this.id === 'balance_display') document.getElementById('balance_hidden').value = num;
            if (this.id === 'edit_balance_display') document.getElementById('edit_balance_hidden').value = num;
            if (this.id === 'transfer_amount_display') document.getElementById('transfer_amount_hidden').value = num;
            this.value = val ? num.toLocaleString('id-ID') : '0';
        });
    }

    // Transfer Select Change logic
    var fromSelect = document.getElementById('from_account_id');
    var toSelect = document.getElementById('to_account_id');
    var balanceAlert = document.getElementById('balanceAlert');
    var availableSpan = document.getElementById('availableBalance');

    if (fromSelect) {
        fromSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            var bal = opt ? opt.getAttribute('data-balance') : null;
            if (bal && this.value) {
                availableSpan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(bal);
                balanceAlert.style.display = 'block';
            } else {
                balanceAlert.style.display = 'none';
            }
        });
    }

    if (fromSelect && toSelect) {
        toSelect.addEventListener('change', function() {
            if (fromSelect.value && this.value === fromSelect.value) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Akun sumber dan tujuan tidak boleh sama',
                    icon: 'warning',
                    customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm' },
                    buttonsStyling: false
                });
                this.value = '';
            }
        });
    }

    // Form Submissions
    var addForm = document.getElementById('addAccountForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Simpan Akun?',
                text: 'Pastikan data sudah benar',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal',
                customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm', cancelButton: 'swal2-cancel' },
                buttonsStyling: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('balance_display').removeAttribute('name');
                    addForm.submit();
                }
            });
        });
    }

    var editForm = document.getElementById('editAccountForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Update Akun?',
                text: 'Simpan perubahan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Update!',
                cancelButtonText: 'Batal',
                customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm', cancelButton: 'swal2-cancel' },
                buttonsStyling: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('edit_balance_display').removeAttribute('name');
                    editForm.submit();
                }
            });
        });
    }

    var transferForm = document.getElementById('transferForm');
    if (transferForm) {
        transferForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var amt = document.getElementById('transfer_amount_hidden').value;
            if (!fromSelect.value || !toSelect.value || !amt || parseFloat(amt) <= 0) {
                Swal.fire({ title: 'Oops!', text: 'Lengkapi data transfer', icon: 'error', customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm' }, buttonsStyling: false });
                return;
            }
            Swal.fire({
                title: 'Konfirmasi Transfer',
                text: 'Proses transfer dana antar akun?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Transfer!',
                cancelButtonText: 'Batal',
                customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm', cancelButton: 'swal2-cancel' },
                buttonsStyling: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); }, customClass: { popup: 'swal2-popup' }, buttonsStyling: false });
                    fetch('transfer.php', { method: 'POST', body: new FormData(transferForm), headers: { 'Accept': 'application/json' } })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (data.success) {
                            Swal.fire({ title: 'Berhasil!', text: d.message, icon: 'success', customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm' }, buttonsStyling: false }).then(function() { window.location.reload(); });
                        } else {
                            Swal.fire({ title: 'Gagal!', text: d.message, icon: 'error', customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm' }, buttonsStyling: false });
                        }
                    })
                    .catch(function() { Swal.fire({ title: 'Error!', text: 'Terjadi kesalahan sistem', icon: 'error', customClass: { popup: 'swal2-popup', confirmButton: 'swal2-confirm' }, buttonsStyling: false }); });
                }
            });
        });
    }

    // Modal Cleanup
    var transModal = document.getElementById('transferModal');
    if (transModal) {
        transModal.addEventListener('hidden.bs.modal', function() {
            if (transferForm) transferForm.reset();
            if (balanceAlert) balanceAlert.style.display = 'none';
            document.getElementById('transfer_amount_hidden').value = '';
            document.getElementById('transfer_amount_display').value = '0';
        });
    }
});

// Global Helpers for onclick
function editAccount(id, name, balance, currency) {
    document.getElementById('edit_account_id').value = id;
    document.getElementById('edit_account_name').value = name;
    document.getElementById('edit_balance_display').value = new Intl.NumberFormat('id-ID').format(balance);
    document.getElementById('edit_balance_hidden').value = balance;
    document.getElementById('edit_account_currency').value = currency || 'IDR';
    var modalEl = document.getElementById('editAccountModal');
    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function deleteAccount(id) {
    Swal.fire({
        title: 'Hapus Akun?',
        text: 'Data tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal2-popup',
            confirmButton: 'swal2-confirm bg-danger-custom',
            cancelButton: 'swal2-cancel'
        },
        buttonsStyling: false
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + id;
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>