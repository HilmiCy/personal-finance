<?php
require_once '../../vendor/autoload.php';
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../classes/Database.php';
require_once '../../classes/Transaction.php';
require_once '../../classes/FinancialAnalytics.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$transaction = new Transaction();
$user_id = $_SESSION['user_id'];

// Get Analysis Data
$prediction = $transaction->predictNextMonthExpense($user_id);
$root_causes = $transaction->getRootCauseAnalysis($user_id);

// Get History for Table
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
    $stmt->execute([$user_id, $month_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $history_data[] = [
        'label' => $month_label,
        'income' => (float)$row['income'],
        'expense' => (float)$row['expense']
    ];
}

// Generate HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #4285f4; padding-bottom: 20px; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: bold; color: #4285f4; }
        .subtitle { font-size: 14px; color: #4285f4; }
        .section-title { font-size: 18px; font-weight: bold; margin-top: 30px; margin-bottom: 15px; color: #1f2937; border-left: 4px solid #4285f4; padding-left: 10px; }
        .card { background: #f9fafb; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .prediction-box { text-align: center; background: #EEF2FF; padding: 25px; border-radius: 15px; border: 1px solid #C7D2FE; }
        .prediction-amount { font-size: 28px; font-weight: bold; color: #4338CA; margin: 10px 0; }
        .trend-up { color: #dc2626; font-weight: bold; }
        .trend-down { color: #059669; font-weight: bold; }
        .trend-stable { color: #4b5563; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-size: 12px; }
        th { background-color: #f3f4f6; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #9ca3af; }
        .root-cause-item { margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        .advice { font-style: italic; color: #4b5563; font-size: 11px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Laporan Analisis & Prediksi AI</div>
        <div class="subtitle">Personal Finance Management System</div>
        <p style="font-size: 12px; color: #6b7280;">Dicetak pada: ' . date('d F Y H:i') . '</p>
    </div>

    <div class="section-title">Hasil Prediksi Bulan Depan</div>
    <div class="prediction-box">
        <div style="font-size: 12px; color: #4285f4; text-transform: uppercase;">Estimasi Pengeluaran</div>
        <div class="prediction-amount">' . formatRupiah($prediction['amount']) . '</div>
        <div class="trend-' . $prediction['trend'] . '">
            Status Tren: ' . ($prediction['trend'] == 'up' ? 'Meningkat' : ($prediction['trend'] == 'down' ? 'Menurun' : 'Stabil')) . '
        </div>
    </div>

    <div class="section-title">Analisis Biang Kerok Pengeluaran</div>
    <div class="card">
        <p style="font-size: 12px; margin-bottom: 15px;">Berikut adalah kategori pengeluaran terbesar bulan ini yang mempengaruhi pola keuangan Anda:</p>';

foreach ($root_causes as $cause) {
    $html .= '
        <div class="root-cause-item">
            <div style="font-weight: bold;">' . htmlspecialchars($cause['name']) . ' <span style="float: right; color: #6b7280;">' . formatRupiah($cause['total']) . '</span></div>
            <div class="advice"><strong>Saran AI:</strong> ' . $cause['advice'] . '</div>
        </div>';
}

$html .= '
    </div>

    <div class="section-title">Data Historis (6 Bulan Terakhir)</div>
    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th>Pemasukan</th>
                <th>Pengeluaran</th>
                <th>Selisih (Saving)</th>
            </tr>
        </thead>
        <tbody>';

foreach ($history_data as $data) {
    $selisih = $data['income'] - $data['expense'];
    $html .= '
            <tr>
                <td>' . $data['label'] . '</td>
                <td>' . formatRupiah($data['income']) . '</td>
                <td>' . formatRupiah($data['expense']) . '</td>
                <td style="color: ' . ($selisih >= 0 ? '#059669' : '#dc2626') . '">' . formatRupiah($selisih) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini dihasilkan secara otomatis oleh sistem menggunakan algoritma Linear Regression.<br>
        © ' . date('Y') . ' ' . APP_NAME . ' - Financial Analytics Module</p>
    </div>
</body>
</html>';

// Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the generated PDF
$dompdf->stream("Analisis_Keuangan_AI_" . date('Y-m-d') . ".pdf", array("Attachment" => 1));
exit;
