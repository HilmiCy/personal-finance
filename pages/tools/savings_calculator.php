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
    /* ========== SAVINGS CALCULATOR SPECIFIC STYLES ========== */
    .tool-card { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        border-radius: 32px; 
        padding: 35px; 
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04); 
        transition: var(--transition); 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        margin-bottom: 30px;
    }
    
    .tool-card:hover { transform: translateY(-5px); box-shadow: 0 25px 60px rgba(0, 0, 0, 0.07); }
    
    .form-label { font-size: 13px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
    
    .input-group-custom {
        background: var(--surface);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 16px;
        padding: 8px 20px;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .input-group-custom:focus-within { background: white; border-color: var(--info); box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.1); }
    .input-group-custom input { border: none; background: transparent; padding: 10px 0; font-weight: 700; color: var(--fg); width: 100%; outline: none; }
    .input-group-custom span { font-weight: 800; color: var(--muted); font-size: 14px; }

    .btn-calculate {
        background: #1e293b !important;
        color: #ffffff !important;
        border: none;
        padding: 18px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        margin-top: 10px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        cursor: pointer;
    }
    .btn-calculate:hover { 
        background: #000000 !important; 
        transform: translateY(-3px); 
        box-shadow: 0 15px 30px rgba(0,0,0,0.2); 
        color: #ffffff !important;
    }
    
    .btn-reset-custom {
        background: transparent;
        color: var(--muted);
        border: 1px solid var(--border);
        padding: 14px;
        border-radius: 16px;
        font-weight: 700;
        font-size: 13px;
        transition: var(--transition);
        width: 100%;
        margin-top: 12px;
    }
    .btn-reset-custom:hover { background: var(--surface); color: var(--fg); }

    .result-item-card {
        background: white;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 24px;
        padding: 25px;
        height: 100%;
        transition: var(--transition);
        box-shadow: 0 8px 20px rgba(0,0,0,0.02);
    }
    .result-item-card:hover { transform: scale(1.03); box-shadow: 0 12px 30px rgba(0,0,0,0.05); }
    
    .info-icon-circle { 
        width: 48px; height: 48px; 
        background: var(--surface); 
        border-radius: 14px; 
        display: flex; align-items: center; justify-content: center; 
        color: var(--info); font-size: 20px; margin-bottom: 20px; 
        border: 1px solid var(--border);
    }
    
    .result-value { font-size: 24px; font-weight: 850; color: var(--fg); letter-spacing: -0.02em; }
    .result-label { font-size: 10px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }

    .tips-card {
        background: rgba(66, 133, 244, 0.05);
        border-radius: 24px;
        padding: 25px 30px;
        border-left: 8px solid var(--info);
        margin-top: 30px;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <h1 class="welcome-title">Kalkulator Tabungan</h1>
                    <p class="welcome-subtitle">Capai tujuan finansial Anda dengan perencanaan yang presisi</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Input Card -->
            <div class="col-lg-5 animated" style="animation-delay: 0.1s;">
                <div class="tool-card">
                    <h5 style="font-weight: 850; font-size: 18px; margin-bottom: 30px; color: var(--fg);">Atur Target Anda</h5>
                    <form id="calcForm">
                        <div class="mb-4">
                            <label class="form-label">Target Dana</label>
                            <div class="input-group-custom">
                                <span>Rp</span>
                                <input type="text" class="money" id="target_amount" placeholder="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Batas Waktu</label>
                            <div class="input-group-custom">
                                <input type="date" id="target_date" required min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label">Dana Tersedia (Opsional)</label>
                            <div class="input-group-custom">
                                <span>Rp</span>
                                <input type="text" class="money" id="current_savings" placeholder="0">
                            </div>
                            <div style="font-size: 11px; font-weight: 700; color: var(--muted); margin-top: 10px; padding-left: 5px;">* Masukkan jika Anda sudah memiliki simpanan awal</div>
                        </div>

                        <button type="submit" class="btn-calculate">
                            <i class="fas fa-magic"></i> Hitung Tabungan
                        </button>
                        <button type="button" id="resetBtn" class="btn-reset-custom">
                            <i class="fas fa-rotate-left me-2"></i> Mulai Ulang
                        </button>
                    </form>
                </div>
            </div>

            <!-- Result Area -->
            <div class="col-lg-7 animated" style="animation-delay: 0.2s;">
                <div id="resultArea" style="display: none;">
                    <div class="tool-card" style="background: rgba(255,255,255,0.7);">
                        <h5 style="font-weight: 850; font-size: 18px; margin-bottom: 30px; color: var(--fg);">Hasil Analisis</h5>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="result-item-card">
                                    <div class="info-icon-circle"><i class="fas fa-calendar-day"></i></div>
                                    <div class="result-label">Simpanan Per Hari</div>
                                    <div class="result-value text-primary" id="daily_result">Rp 0</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="result-item-card">
                                    <div class="info-icon-circle" style="color: #34a853;"><i class="fas fa-calendar-week"></i></div>
                                    <div class="result-label">Simpanan Per Bulan</div>
                                    <div class="result-value text-success" id="monthly_result">Rp 0</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="result-item-card">
                                    <div class="info-icon-circle" style="color: #fbbc05;"><i class="fas fa-hourglass-start"></i></div>
                                    <div class="result-label">Sisa Waktu</div>
                                    <div class="result-value" id="time_left" style="font-size: 18px;">0 Hari</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="result-item-card">
                                    <div class="info-icon-circle" style="color: #ea4335;"><i class="fas fa-exclamation-circle"></i></div>
                                    <div class="result-label">Total Kekurangan</div>
                                    <div class="result-value text-danger" id="total_needed" style="font-size: 20px;">Rp 0</div>
                                </div>
                            </div>
                        </div>

                        <div class="tips-card">
                            <h6 style="font-weight: 850; color: var(--fg); margin-bottom: 10px;"><i class="fas fa-lightbulb text-warning me-2"></i> Strategi Anda</h6>
                            <p style="font-size: 14px; line-height: 1.6; color: var(--muted); font-weight: 600; margin: 0;" id="tips_text">
                                Melakukan kalkulasi...
                            </p>
                        </div>
                    </div>
                </div>

                <div id="emptyArea" class="text-center py-5">
                    <div style="width: 100px; height: 100px; background: var(--surface); border-radius: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; font-size: 40px; color: var(--muted); opacity: 0.5;">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h5 style="font-weight: 800; color: var(--muted);">Lengkapi data di kiri</h5>
                    <p class="small text-muted fw-bold">Hasil perhitungan akan muncul di sini secara otomatis</p>
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