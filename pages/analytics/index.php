<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Analisis AI & Prediksi';
$current_page = 'analytics';

$db = Database::getInstance()->getConnection();
$transaction = new Transaction();

// Ambil data untuk analisis
$prediction = $transaction->predictNextMonthExpense($_SESSION['user_id']);
$root_causes = $transaction->getRootCauseAnalysis($_SESSION['user_id']);

// Ambil histori 6 bulan terakhir untuk tabel detail
$history_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_ts = strtotime("-$i months");
    $month_key = date('Y-m', $month_ts);
    $month_label = date('F Y', $month_ts);
    
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
        FROM transactions 
        WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $month_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $history_data[] = [
        'label' => $month_label,
        'income' => (float)$row['income'],
        'expense' => (float)$row['expense']
    ];
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .analytics-card {
        background: white;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #f3f4f6;
        margin-bottom: 24px;
    }

    .ai-badge {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: white;
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .prediction-highlight {
        font-size: 36px;
        font-weight: 800;
        color: #1f2937;
        margin: 15px 0;
    }

    .trend-indicator {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 20px;
        border-radius: 15px;
        font-weight: 600;
    }

    .trend-up { background: #fee2e2; color: #dc2626; }
    .trend-down { background: #d1fae5; color: #059669; }
    .trend-stable { background: #f1f5f9; color: #64748b; }

    .formula-box {
        background: #f8fafc;
        border-radius: 15px;
        padding: 20px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 14px;
        border-left: 5px solid #6366f1;
    }

    .insight-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .insight-icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="ai-badge mb-2 d-inline-block">AI Module Active</span>
                    <h1 class="welcome-title">Analisis & Prediksi Pintar</h1>
                    <p class="welcome-subtitle">Menggunakan algoritma Linear Regression untuk memproyeksi keuangan Anda.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="export_pdf.php" class="btn-primary-custom">
                        <i class="fas fa-file-pdf me-2"></i> Unduh Analisis (PDF)
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Prediction Card -->
            <div class="col-lg-7">
                <div class="analytics-card animated" style="animation-delay: 0.1s">
                    <div class="card-title-custom">
                        <i class="fas fa-magic"></i> Hasil Prediksi Bulan Depan
                    </div>
                    <p class="text-muted small">Berdasarkan pola pengeluaran Anda selama <?= $prediction['count'] ?> bulan terakhir:</p>
                    
                    <div class="prediction-highlight">
                        <?= formatRupiah($prediction['amount']) ?>
                    </div>

                    <div class="trend-indicator <?= 'trend-' . $prediction['trend'] ?> mb-4">
                        <?php if($prediction['trend'] == 'up'): ?>
                            <i class="fas fa-arrow-trend-up"></i> Tren Pengeluaran Meningkat
                        <?php elseif($prediction['trend'] == 'down'): ?>
                            <i class="fas fa-arrow-trend-down"></i> Tren Pengeluaran Menurun
                        <?php else: ?>
                            <i class="fas fa-minus"></i> Tren Pengeluaran Stabil
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <h5>Bagaimana angka ini didapat?</h5>
                        <p class="text-muted">Sistem kami menghitung <strong>Slope (Kemiringan)</strong> dari data Anda. Slope Anda saat ini adalah <strong><?= formatRupiah($prediction['slope']) ?></strong> per bulan.</p>
                        <div class="formula-box">
                            y = mx + c <br>
                            y = (<?= number_format($prediction['slope'], 2) ?> * <?= $prediction['count'] + 1 ?>) + Intercept
                        </div>
                    </div>
                </div>

                <div class="analytics-card animated" style="animation-delay: 0.2s">
                    <div class="card-title-custom">
                        <i class="fas fa-lightbulb"></i> AI Financial Insights
                    </div>
                    
                    <div class="insight-item">
                        <div class="insight-icon bg-primary text-white">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Analisis Tren</h6>
                            <p class="text-muted small">
                                <?php if($prediction['trend'] == 'up'): ?>
                                    Pengeluaran Anda cenderung naik sekitar <?= formatRupiah($prediction['slope']) ?> setiap bulannya. Disarankan untuk meninjau kembali kategori pengeluaran terbesar.
                                <?php elseif($prediction['trend'] == 'down'): ?>
                                    Hebat! Anda berhasil menekan pengeluaran rata-rata <?= formatRupiah(abs($prediction['slope'])) ?> per bulan. Pertahankan pola ini!
                                <?php else: ?>
                                    Pengeluaran Anda sangat stabil. Ini memudahkan dalam perencanaan anggaran jangka panjang.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="insight-item">
                        <div class="insight-icon bg-info text-white">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Rekomendasi Anggaran</h6>
                            <p class="text-muted small">
                                Untuk bulan depan, kami merekomendasikan Anda untuk menyiapkan dana cadangan sebesar <strong><?= formatRupiah($prediction['amount'] * 1.1) ?></strong> (Prediksi + 10% buffer).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="analytics-card animated" style="animation-delay: 0.25s">
                    <div class="card-title-custom">
                        <i class="fas fa-search-dollar"></i> Analisis "Biang Kerok" Pengeluaran
                    </div>
                    <p class="text-muted small mb-4">Berikut adalah kategori yang paling banyak menguras kantong Anda bulan ini:</p>

                    <?php foreach($root_causes as $index => $cause): ?>
                    <div class="mb-4 p-3 border-start border-4 <?= $index == 0 ? 'border-danger bg-light' : 'border-warning' ?>" style="border-radius: 0 15px 15px 0;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($cause['name']) ?></h6>
                            <span class="badge bg-dark"><?= formatRupiah($cause['total']) ?></span>
                        </div>
                        <div class="small text-muted">
                            <i class="fas fa-info-circle me-1"></i> <strong>Saran Hemat:</strong> <?= $cause['advice'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Side Data Card -->
            <div class="col-lg-5">
                <div class="analytics-card animated" style="animation-delay: 0.3s">
                    <div class="card-title-custom">
                        <i class="fas fa-database"></i> Titik Data Histori
                    </div>
                    <canvas id="predictionChart" height="250"></canvas>
                    
                    <div class="table-responsive mt-4">
                        <table class="table table-sm small">
                            <thead>
                                <tr class="text-muted">
                                    <th>Bulan</th>
                                    <th class="text-end">Pengeluaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($history_data as $data): ?>
                                <tr>
                                    <td><?= $data['label'] ?></td>
                                    <td class="text-end fw-bold"><?= formatRupiah($data['expense']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('predictionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($history_data, 'label')) ?>,
            datasets: [{
                label: 'Pengeluaran Riil',
                data: <?= json_encode(array_column($history_data, 'expense')) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>
