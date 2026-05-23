<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}
?>

<style>
    .card {
        border-radius: 20px !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        margin-bottom: 20px !important;
        overflow: hidden !important;
        background: white !important;
    }
    
    .card-body {
        padding: 24px !important;
    }
    
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
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
    }

    .result-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-left: 5px solid #667eea !important;
    }

    .result-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #764ba2;
    }

    .info-icon {
        width: 40px;
        height: 40px;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        margin-bottom: 15px;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
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
                <div class="col-md-12">
                    <h1 class="welcome-title"><i class="fas fa-calculator me-2"></i> Kalkulator Tabungan</h1>
                    <p class="welcome-subtitle">Rencanakan tabungan Anda untuk mencapai target finansial tepat waktu</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Input Card -->
            <div class="col-md-5 animated" style="animation-delay: 0.1s;">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Input Target</h5>
                        <form id="calcForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Target Uang yang Dibutuhkan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control money" id="target_amount" placeholder="Contoh: 5.000.000" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Target Tanggal Terkumpul</label>
                                <input type="date" class="form-control" id="target_date" required min="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Sudah Ada Tabungan? (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control money" id="current_savings" placeholder="Contoh: 500.000">
                                </div>
                                <small class="text-muted">Masukkan jika Anda sudah mulai menabung</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-calculator me-2"></i> Hitung Sekarang
                            </button>
                            <button type="button" id="resetBtn" class="btn btn-outline-secondary w-100 mt-2" style="border-radius: 12px;">
                                <i class="fas fa-undo me-2"></i> Reset
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Result Card -->
            <div class="col-md-7 animated" style="animation-delay: 0.2s;">
                <div id="resultArea" style="display: none;">
                    <div class="card result-card">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-4">Hasil Kalkulasi</h5>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-3 bg-white rounded-3 shadow-sm h-100">
                                        <div class="info-icon">
                                            <i class="fas fa-calendar-day"></i>
                                        </div>
                                        <div class="text-muted small mb-1">Tabungan Per Hari</div>
                                        <div class="result-value" id="daily_result">Rp 0</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-white rounded-3 shadow-sm h-100">
                                        <div class="info-icon">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="text-muted small mb-1">Tabungan Per Bulan</div>
                                        <div class="result-value" id="monthly_result">Rp 0</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-white rounded-3 shadow-sm h-100">
                                        <div class="info-icon">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div class="text-muted small mb-1">Sisa Waktu</div>
                                        <div class="fw-bold" id="time_left">0 Hari</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-white rounded-3 shadow-sm h-100">
                                        <div class="info-icon">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                        <div class="text-muted small mb-1">Total Kekurangan</div>
                                        <div class="fw-bold text-danger" id="total_needed">Rp 0</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 p-3 bg-white rounded-3 shadow-sm border-start border-4 border-info">
                                <h6 class="fw-bold mb-2"><i class="fas fa-lightbulb text-warning me-2"></i> Tips Menabung</h6>
                                <p class="small text-muted mb-0" id="tips_text">
                                    Tetapkan prioritas dan kurangi pengeluaran yang tidak perlu untuk mencapai target tepat waktu.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="emptyArea" class="text-center py-5">
                    <i class="fas fa-calculator fa-4x text-muted mb-3 opacity-20"></i>
                    <h5 class="text-muted">Masukkan data di sebelah kiri untuk melihat hasil kalkulasi</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

document.getElementById('calcForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const targetAmount = parseInt(document.getElementById('target_amount').value.replace(/[^\d]/g, '')) || 0;
    const currentSavings = parseInt(document.getElementById('current_savings').value.replace(/[^\d]/g, '')) || 0;
    const targetDate = new Date(document.getElementById('target_date').value);
    const today = new Date();
    today.setHours(0,0,0,0);

    if (targetDate <= today) {
        Swal.fire({
            icon: 'error',
            title: 'Tanggal Tidak Valid',
            text: 'Pilihlah tanggal di masa depan.'
        });
        return;
    }

    const diffTime = Math.abs(targetDate - today);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    const diffMonths = diffDays / 30.44; // Rata-rata hari dalam sebulan

    const totalNeeded = targetAmount - currentSavings;
    
    if (totalNeeded <= 0) {
        Swal.fire({
            icon: 'success',
            title: 'Target Tercapai!',
            text: 'Tabungan Anda saat ini sudah cukup atau melebihi target.'
        });
        return;
    }

    const dailySavings = Math.ceil(totalNeeded / diffDays);
    const monthlySavings = Math.ceil(totalNeeded / (diffMonths < 1 ? 1 : diffMonths));

    // Update UI
    document.getElementById('daily_result').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(dailySavings);
    document.getElementById('monthly_result').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(monthlySavings);
    document.getElementById('time_left').innerText = diffDays + ' Hari (' + Math.ceil(diffMonths) + ' Bulan)';
    document.getElementById('total_needed').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalNeeded);

    // Tips based on daily savings
    let tip = "";
    if (dailySavings > 100000) {
        tip = "Target Anda cukup besar. Pertimbangkan untuk memperpanjang waktu atau mencari penghasilan tambahan.";
    } else if (dailySavings > 50000) {
        tip = "Konsistensi adalah kunci. Sisihkan uang di awal hari agar target tercapai.";
    } else {
        tip = "Target yang sangat realistis! Dengan sedikit penghematan kopi harian, Anda pasti bisa mencapainya.";
    }
    document.getElementById('tips_text').innerText = tip;

    document.getElementById('emptyArea').style.display = 'none';
    document.getElementById('resultArea').style.display = 'block';
    
    // Smooth scroll to results on mobile
    if (window.innerWidth < 768) {
        document.getElementById('resultArea').scrollIntoView({ behavior: 'smooth' });
    }
});

document.getElementById('resetBtn').addEventListener('click', function() {
    document.getElementById('calcForm').reset();
    document.getElementById('emptyArea').style.display = 'block';
    document.getElementById('resultArea').style.display = 'none';
});
</script>

<?php require_once '../../includes/footer.php'; ?>