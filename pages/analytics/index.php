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
$deep_insights = $transaction->getDeepAIInsights($_SESSION['user_id']);
$anomalies = $transaction->getAIAnomalies($_SESSION['user_id']);
$financial_report = $transaction->getAIFinancialReport($_SESSION['user_id']);

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
        background: var(--bg-card);
        border-radius: 24px;
        padding: 30px;
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
        color: var(--text-main);
    }
    .ai-badge {
        background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
        color: white;
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .prediction-highlight { font-size: 36px; font-weight: 800; color: var(--text-main); margin: 15px 0; }
    .trend-indicator { display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px; border-radius: 15px; font-weight: 600; }
    .trend-up { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .trend-down { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .trend-stable { background: var(--bg-hover); color: var(--text-muted); }
    .insight-item { display: flex; gap: 15px; margin-bottom: 20px; }
    .insight-icon { width: 40px; height: 40px; min-width: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .status-badge { padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 14px; }
    .status-healthy { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .status-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-critical { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="welcome-card animated">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <span class="ai-badge mb-2 d-inline-block">XGBoost ML Engine Active</span>
                    <h1 class="welcome-title">AI Financial Analysis</h1>
                    <p class="welcome-subtitle">Model XGBoost Anda telah dilatih pada <?= $prediction['count'] ?> bulan data.</p>
                </div>
                <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end">
                    <button id="btnTrain" class="btn btn-dark-custom">
                        <i class="fas fa-sync-alt me-2"></i> Latih Model (Train)
                    </button>
                    <a href="export_pdf.php" class="btn btn-primary-custom">
                        <i class="fas fa-file-pdf me-2"></i> Export Analysis
                    </a>
                </div>
            </div>
        </div>

        <div id="trainResult" class="mb-4" style="display: none;">
            <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" onclick="document.getElementById('trainResult').style.display='none'"></button>
                <div class="d-flex align-items-center gap-3">
                    <div class="h2 mb-0"><i class="fas fa-brain text-success"></i></div>
                    <div>
                        <h5 class="fw-bold mb-1 text-success">Model XGBoost Berhasil Diperbarui</h5>
                        <p class="mb-0" id="accuracyMetrics" style="font-size: 14px;"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Prediction & Health -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="analytics-card h-100">
                            <div class="card-title-custom"><i class="fas fa-magic"></i> Prediksi Pengeluaran</div>
                            <div class="prediction-highlight"><?= formatRupiah($prediction['amount']) ?></div>
                            <div class="trend-indicator <?= 'trend-' . ($prediction['trend'] ?? 'stable') ?> mb-3">
                                <i class="fas fa-arrow-trend-<?= ($prediction['trend'] ?? 'stable') == 'up' ? 'up' : (($prediction['trend'] ?? 'stable') == 'down' ? 'down' : 'right') ?>"></i> 
                                <?= ucfirst($prediction['trend'] ?? 'Stabil') ?>
                            </div>
                            <p class="text-muted small">Target saldo akhir bulan: <span class="fw-bold text-success"><?= formatRupiah(($financial_report['income'] ?? 0) - $prediction['amount']) ?></span></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="analytics-card h-100">
                            <div class="card-title-custom"><i class="fas fa-shield-alt"></i> Kondisi Keuangan</div>
                            <div class="mt-3 mb-3">
                                <span class="status-badge status-<?= strtolower($financial_report['condition'] ?? 'Healthy') ?>">
                                    <?= $financial_report['condition'] ?? 'Healthy' ?>
                                </span>
                            </div>
                            <p class="fw-bold mb-1">Risk Level: <span class="text-<?= ($financial_report['risk_level'] ?? 'Low') == 'High' ? 'danger' : (($financial_report['risk_level'] ?? 'Low') == 'Medium' ? 'warning' : 'success') ?>"><?= $financial_report['risk_level'] ?? 'Low' ?></span></p>
                            <p class="text-muted small"><?= $financial_report['condition_message'] ?? 'Data tidak cukup.' ?></p>
                        </div>
                    </div>
                </div>

                <!-- Pattern & Anomalies -->
                <div class="analytics-card">
                    <div class="card-title-custom"><i class="fas fa-search-dollar"></i> Pola Spending & Anomali</div>
                    <div class="p-3 bg-light rounded-4 mb-3 border-start border-4 border-primary">
                        <div class="d-flex gap-3">
                            <i class="fas fa-info-circle text-primary mt-1"></i>
                            <p class="mb-0 small"><strong>AI Pattern Analysis:</strong> <?= $financial_report['spending_pattern'] ?? 'Sedang menganalisis...' ?></p>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">Deteksi Anomali Transaksi (Outliers)</h6>
                    <?php if(!empty($anomalies)): ?>
                        <?php foreach($anomalies as $anomaly): ?>
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                <div>
                                    <div class="fw-bold"><?= $anomaly['category'] ?></div>
                                    <div class="text-muted small"><?= $anomaly['reason'] ?></div>
                                </div>
                                <div class="text-danger fw-bold"><?= formatRupiah($anomaly['amount']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3 opacity-50">
                            <p class="small">Tidak ada anomali yang ditemukan dalam 3 bulan terakhir.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Deep Insights -->
                <div class="analytics-card">
                    <div class="card-title-custom"><i class="fas fa-chart-line"></i> Perubahan Kategori (Month-to-Month)</div>
                    <?php if (!empty($deep_insights)): ?>
                        <?php foreach($deep_insights as $insight): ?>
                            <div class="insight-item p-3 rounded-4 mb-3" style="background: var(--bg-hover); border-left: 4px solid <?= $insight['type'] == 'increase' ? '#ef4444' : '#10b981' ?>;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="insight-icon" style="background: <?= $insight['type'] == 'increase' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)' ?>; color: <?= $insight['type'] == 'increase' ? '#ef4444' : '#10b981' ?>;">
                                        <i class="fas <?= $insight['type'] == 'increase' ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small"><?= $insight['message'] ?></div>
                                        <div class="text-muted small">Fluktuasi: <span class="fw-bold text-dark"><?= $insight['percentage'] ?>%</span></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted py-3">Belum ada fluktuasi signifikan dibanding bulan lalu.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="analytics-card">
                    <div class="card-title-custom"><i class="fas fa-database"></i> Training Data History</div>
                    <canvas id="predictionChart" height="250"></canvas>
                    <div class="table-responsive mt-4">
                        <table class="table table-sm small">
                            <thead><tr class="text-muted"><th>Bulan</th><th class="text-end">Expense</th></tr></thead>
                            <tbody>
                                <?php foreach($history_data as $data): ?>
                                <tr><td><?= $data['label'] ?></td><td class="text-end fw-bold"><?= formatRupiah($data['expense']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-title-custom"><i class="fas fa-bullseye"></i> AI Budgeting Target</div>
                    <p class="small text-muted">Berdasarkan prediksi, berikut alokasi ideal Anda:</p>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1"><span>Kebutuhan Pokok (50%)</span><span class="fw-bold"><?= formatRupiah($prediction['amount'] * 0.5) ?></span></div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-primary" style="width: 50%"></div></div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1"><span>Keinginan (30%)</span><span class="fw-bold"><?= formatRupiah($prediction['amount'] * 0.3) ?></span></div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: 30%"></div></div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between small mb-1"><span>Tabungan/Investasi (20%)</span><span class="fw-bold"><?= formatRupiah($prediction['amount'] * 0.2) ?></span></div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-success" style="width: 20%"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Chart
    const ctx = document.getElementById('predictionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($history_data, 'label')) ?>,
            datasets: [{
                label: 'Pengeluaran',
                data: <?= json_encode(array_column($history_data, 'expense')) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true, tension: 0.4, pointRadius: 5
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // Train AJAX
    document.getElementById('btnTrain').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Training...';

        fetch('train_ajax.php')
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.status === 'success') {
                    document.getElementById('trainResult').style.display = 'block';
                    document.getElementById('accuracyMetrics').innerHTML = 
                        `Akurasi Model: <strong>${data.accuracy}%</strong> | ` +
                        `RMSE: <strong>${data.rmse}</strong> | ` +
                        `Data Points: <strong>${data.data_points}</strong>`;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Training Selesai',
                        text: 'Model XGBoost telah diperbarui dengan data terbaru.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                Swal.fire('Error', 'Gagal menghubungi server AI.', 'error');
            });
    });
</script>

<?php include '../../includes/footer.php'; ?>
