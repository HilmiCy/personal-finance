<?php
class Installment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $total_amount = $data['amount_per_tenor'] * $data['tenor'];
        
        // Hitung end_date
        $start_date = new DateTime($data['start_date']);
        $end_date = clone $start_date;
        
        switch($data['tenor_type']) {
            case 'days':
                $end_date->modify("+{$data['tenor']} days");
                break;
            case 'months':
                $end_date->modify("+{$data['tenor']} months");
                break;
            case 'years':
                $end_date->modify("+{$data['tenor']} years");
                break;
        }
        
        $sql = "INSERT INTO installments 
                (user_id, account_id, name, total_amount, remaining_amount, tenor, 
                 tenor_type, amount_per_tenor, start_date, end_date, interest_rate, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['account_id'],
            $data['name'],
            $total_amount,
            $total_amount,
            $data['tenor'],
            $data['tenor_type'],
            $data['amount_per_tenor'],
            $data['start_date'],
            $end_date->format('Y-m-d'),
            $data['interest_rate'] ?? 0,
            $data['notes'] ?? ''
        ]);
    }
    
    public function getAll($user_id, $status = null) {
        $sql = "SELECT i.*, a.name as account_name 
                FROM installments i 
                LEFT JOIN accounts a ON i.account_id = a.id 
                WHERE i.user_id = ?";
        $params = [$user_id];
        
        if ($status) {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY i.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id, $user_id) {
        $sql = "SELECT i.*, a.name as account_name 
                FROM installments i 
                LEFT JOIN accounts a ON i.account_id = a.id 
                WHERE i.id = ? AND i.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch();
    }
    
    public function makePayment($installment_id, $user_id, $account_id, $amount, $payment_date, $notes = '') {
    // Mulai transaksi
    $this->db->beginTransaction();
    
    try {
        $installment = $this->getById($installment_id, $user_id);
        
        if (!$installment) {
            throw new Exception("Installment not found");
        }
        
        if ($installment['status'] != 'active') {
            throw new Exception("Installment is not active");
        }
        
        $due_date = $this->getDueDate($installment);
        $current_payment_number = $installment['current_tenor'] + 1;
        
        // Format tanggal dengan waktu (jam menit detik)
        // Jika $payment_date hanya berisi tanggal, tambahkan waktu sekarang
        if (strlen($payment_date) <= 10) {
            $payment_date = $payment_date . ' ' . date('H:i:s');
        }
        
        // Format due_date juga menjadi DATETIME (tambah waktu 23:59:59)
        $due_date_datetime = $due_date . ' 23:59:59';
        
        // Cek keterlambatan (bandingkan dengan due_date yang sudah termasuk waktu)
        $is_late = false;
        $penalty = 0;
        if (new DateTime($payment_date) > new DateTime($due_date_datetime)) {
            $is_late = true;
            $penalty = $amount * 0.02; // 2% denda
        }
        
        $total_paid = $amount + $penalty;
        
        // 1. Catat pembayaran ke installment_payments
        $sql = "INSERT INTO installment_payments 
                (installment_id, user_id, account_id, payment_number, amount, 
                 penalty_amount, total_paid, payment_date, due_date, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $status = $is_late ? 'late' : 'paid';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $installment_id, $user_id, $account_id, $current_payment_number,
            $amount, $penalty, $total_paid, $payment_date, $due_date_datetime, $status, $notes
        ]);
        
        // 2. Update installment
        $new_paid_amount = $installment['paid_amount'] + $amount;
        $new_remaining = $installment['remaining_amount'] - $amount;
        $new_current_tenor = $current_payment_number;
        
        $new_status = ($new_remaining <= 0) ? 'completed' : 'active';
        
        $sql = "UPDATE installments SET 
                paid_amount = ?, 
                remaining_amount = ?, 
                current_tenor = ?,
                status = ? 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $new_paid_amount, $new_remaining, $new_current_tenor, $new_status, $installment_id
        ]);
        
        // 3. Update saldo akun (kurangi balance)
        $sql = "UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$total_paid, $account_id, $user_id]);
        
        // 4. Get atau buat kategori "Cicilan"
        $category_id = $this->getOrCreateInstallmentCategory($user_id);
        
        // 5. Buat deskripsi transaksi
        $description = "Pembayaran cicilan: {$installment['name']} - Angsuran ke-{$current_payment_number}";
        if ($penalty > 0) {
            $description .= " (Denda: Rp " . number_format($penalty, 0, ',', '.') . ")";
        }
        
        // 6. Catat transaksi pengeluaran (gunakan DATETIME juga)
        $sql = "INSERT INTO transactions 
                (user_id, account_id, category_id, type, amount, description, transaction_date) 
                VALUES (?, ?, ?, 'expense', ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $user_id,
            $account_id,
            $category_id,
            $total_paid,
            $description,
            $payment_date // sudah dalam format Y-m-d H:i:s
        ]);
        
        $this->db->commit();
        return true;
    } catch (Exception $e) {
        $this->db->rollback();
        return false;
    }
}
    
    /**
     * Get or create category "Cicilan" for expense transactions
     */
    private function getOrCreateInstallmentCategory($user_id) {
        // Cek apakah kategori sudah ada
        $sql = "SELECT id FROM categories WHERE user_id = ? AND name = 'Cicilan' AND type = 'expense' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        $category = $stmt->fetch();
        
        if ($category) {
            return $category['id'];
        }
        
        // Buat kategori baru jika belum ada
        $sql = "INSERT INTO categories (user_id, name, type) VALUES (?, 'Cicilan', 'expense')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        
        return $this->db->lastInsertId();
    }
    
    // Ubah dari private menjadi public
    public function getDueDate($installment) {
    $start_date = new DateTime($installment['start_date']);
    $current_tenor = $installment['current_tenor'];
    
    switch($installment['tenor_type']) {
        case 'days':
            $start_date->modify("+{$current_tenor} days");
            break;
        case 'months':
            $start_date->modify("+{$current_tenor} months");
            break;
        case 'years':
            $start_date->modify("+{$current_tenor} years");
            break;
    }
    
    return $start_date->format('Y-m-d'); // Hanya tanggal untuk due_date
}
    
    public function getPaymentHistory($installment_id, $user_id) {
        $sql = "SELECT ip.*, a.name as account_name 
                FROM installment_payments ip 
                LEFT JOIN accounts a ON ip.account_id = a.id 
                WHERE ip.installment_id = ? AND ip.user_id = ? 
                ORDER BY ip.payment_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$installment_id, $user_id]);
        return $stmt->fetchAll();
    }
    
    public function getSummary($user_id) {
        $sql = "SELECT 
                    COUNT(*) as total_installments,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_installments,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_installments,
                    SUM(remaining_amount) as total_remaining,
                    SUM(paid_amount) as total_paid
                FROM installments 
                WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    // Method untuk update cicilan
    public function update($id, $user_id, $data) {
        $total_amount = $data['amount_per_tenor'] * $data['tenor'];
        
        // Hitung end_date baru
        $start_date = new DateTime($data['start_date']);
        $end_date = clone $start_date;
        
        switch($data['tenor_type']) {
            case 'days':
                $end_date->modify("+{$data['tenor']} days");
                break;
            case 'months':
                $end_date->modify("+{$data['tenor']} months");
                break;
            case 'years':
                $end_date->modify("+{$data['tenor']} years");
                break;
        }
        
        $sql = "UPDATE installments SET 
                name = ?,
                total_amount = ?,
                tenor = ?,
                tenor_type = ?,
                amount_per_tenor = ?,
                start_date = ?,
                end_date = ?,
                interest_rate = ?,
                notes = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $total_amount,
            $data['tenor'],
            $data['tenor_type'],
            $data['amount_per_tenor'],
            $data['start_date'],
            $end_date->format('Y-m-d'),
            $data['interest_rate'] ?? 0,
            $data['notes'] ?? '',
            $id,
            $user_id
        ]);
    }
    
    // Method untuk delete cicilan
    public function delete($id, $user_id) {
        // Cek apakah cicilan sudah memiliki pembayaran
        $sql = "SELECT COUNT(*) as payment_count FROM installment_payments WHERE installment_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $user_id]);
        $result = $stmt->fetch();
        
        if ($result['payment_count'] > 0) {
            return false; // Tidak bisa hapus jika sudah ada pembayaran
        }
        
        $sql = "DELETE FROM installments WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $user_id]);
    }
}
?>