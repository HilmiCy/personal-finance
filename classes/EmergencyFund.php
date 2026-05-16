<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Transaction.php'; // Tambahkan ini

class EmergencyFund {
    private $db;
    private $transaction;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->transaction = new Transaction(); // Inisialisasi Transaction
    }
    
    public function getEmergencyFund($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM emergency_fund WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("getEmergencyFund error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getHistory($user_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    eft.*, 
                    a.name as account_name,
                    DATE_FORMAT(eft.transaction_date, '%Y-%m-%d %H:%i:%s') as formatted_date
                FROM emergency_fund_transactions eft
                LEFT JOIN accounts a ON eft.account_id = a.id
                WHERE eft.user_id = ?
                ORDER BY eft.transaction_date DESC, eft.id DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("getHistory error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalHistory($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM emergency_fund_transactions 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch(PDOException $e) {
            error_log("getTotalHistory error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalDeposit($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM emergency_fund_transactions 
                WHERE user_id = ? AND type = 'deposit'
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch(PDOException $e) {
            error_log("getTotalDeposit error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalWithdraw($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM emergency_fund_transactions 
                WHERE user_id = ? AND type = 'withdraw'
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch(PDOException $e) {
            error_log("getTotalWithdraw error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function setTarget($user_id, $target_amount, $priority_level = 'medium') {
        return $this->createOrUpdate($user_id, $target_amount, $priority_level);
    }
    
    public function createOrUpdate($user_id, $target_amount, $priority_level = 'medium') {
        try {
            $existing = $this->getEmergencyFund($user_id);
            
            if ($existing) {
                $stmt = $this->db->prepare("UPDATE emergency_fund SET target_amount = ?, priority_level = ? WHERE user_id = ?");
                return $stmt->execute([$target_amount, $priority_level, $user_id]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO emergency_fund (user_id, target_amount, priority_level, current_amount) VALUES (?, ?, ?, 0)");
                return $stmt->execute([$user_id, $target_amount, $priority_level]);
            }
        } catch(PDOException $e) {
            error_log("Error in createOrUpdate: " . $e->getMessage());
            return false;
        }
    }
    
    // ========== PERBAIKAN METHOD DEPOSIT ==========
public function deposit($user_id, $amount, $account_id, $description = '') {
    try {
        // Mulai transaksi
        $this->db->beginTransaction();
        
        // Get emergency fund
        $fund = $this->getEmergencyFund($user_id);
        if (!$fund) {
            throw new Exception("Emergency fund not found for user: $user_id");
        }
        
        // Get account data
        $account = new Account();
        $accountData = $account->getById($account_id, $user_id);
        
        if (!$accountData) {
            throw new Exception("Account not found - ID: $account_id, User: $user_id");
        }
        
        // Check balance
        if ($accountData['balance'] < $amount) {
            throw new Exception("Saldo tidak mencukupi. Saldo tersedia: Rp " . number_format($accountData['balance'], 0, ',', '.'));
        }
        
        // 1. Update emergency fund current amount (tambah)
        $new_current = $fund['current_amount'] + $amount;
        $stmt = $this->db->prepare("UPDATE emergency_fund SET current_amount = ? WHERE id = ?");
        if (!$stmt->execute([$new_current, $fund['id']])) {
            throw new Exception("Failed to update emergency fund");
        }
        
        // 2. Update account balance (kurangi)
        $new_balance = $accountData['balance'] - $amount;
        $stmt = $this->db->prepare("UPDATE accounts SET balance = ? WHERE id = ? AND user_id = ?");
        if (!$stmt->execute([$new_balance, $account_id, $user_id])) {
            throw new Exception("Failed to update account balance");
        }
        
        // 3. Record transaction in emergency_fund_transactions
        $stmt = $this->db->prepare("INSERT INTO emergency_fund_transactions (user_id, emergency_fund_id, account_id, type, amount, description, transaction_date) VALUES (?, ?, ?, 'deposit', ?, ?, NOW())");
        if (!$stmt->execute([$user_id, $fund['id'], $account_id, $amount, $description])) {
            throw new Exception("Failed to record emergency fund transaction");
        }
        
        // 4. Get or create category "Dana Darurat" dengan type 'transfer'
        $category_id = $this->getOrCreateEmergencyCategory($user_id);
        
        // 5. Catat ke transaksi utama dengan type 'transfer' (bukan expense)
        $stmt = $this->db->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, amount, description, transaction_date) VALUES (?, ?, ?, 'transfer', ?, ?, NOW())");
        if (!$stmt->execute([$user_id, $account_id, $category_id, $amount, "Transfer ke Dana Darurat: " . ($description ?: "Setoran dana darurat")])) {
            throw new Exception("Failed to record transaction");
        }
        
        // Commit transaksi
        $this->db->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback jika ada transaksi aktif
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        error_log("Deposit error: " . $e->getMessage());
        return false;
    }
}

// ========== PERBAIKAN METHOD WITHDRAW ==========
public function withdraw($user_id, $amount, $account_id, $description = '') {
    try {
        // Mulai transaksi
        $this->db->beginTransaction();
        
        // Get emergency fund
        $fund = $this->getEmergencyFund($user_id);
        if (!$fund) {
            throw new Exception("Emergency fund not found");
        }
        
        if ($fund['current_amount'] < $amount) {
            throw new Exception("Saldo dana darurat tidak mencukupi");
        }
        
        // Get account data
        $account = new Account();
        $accountData = $account->getById($account_id, $user_id);
        if (!$accountData) {
            throw new Exception("Account not found");
        }
        
        // 1. Update emergency fund current amount (kurangi)
        $new_current = $fund['current_amount'] - $amount;
        $stmt = $this->db->prepare("UPDATE emergency_fund SET current_amount = ? WHERE id = ?");
        if (!$stmt->execute([$new_current, $fund['id']])) {
            throw new Exception("Failed to update emergency fund");
        }
        
        // 2. Update account balance (tambah)
        $new_balance = $accountData['balance'] + $amount;
        $stmt = $this->db->prepare("UPDATE accounts SET balance = ? WHERE id = ? AND user_id = ?");
        if (!$stmt->execute([$new_balance, $account_id, $user_id])) {
            throw new Exception("Failed to update account balance");
        }
        
        // 3. Record transaction in emergency_fund_transactions
        $stmt = $this->db->prepare("INSERT INTO emergency_fund_transactions (user_id, emergency_fund_id, account_id, type, amount, description, transaction_date) VALUES (?, ?, ?, 'withdraw', ?, ?, NOW())");
        if (!$stmt->execute([$user_id, $fund['id'], $account_id, $amount, $description])) {
            throw new Exception("Failed to record emergency fund transaction");
        }
        
        // 4. Get or create category "Dana Darurat" dengan type 'transfer'
        $category_id = $this->getOrCreateEmergencyCategory($user_id);
        
        // 5. Catat ke transaksi utama dengan type 'transfer' (bukan income)
        $stmt = $this->db->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, amount, description, transaction_date) VALUES (?, ?, ?, 'transfer', ?, ?, NOW())");
        if (!$stmt->execute([$user_id, $account_id, $category_id, $amount, "Tarik dari Dana Darurat: " . ($description ?: "Penarikan dana darurat")])) {
            throw new Exception("Failed to record transaction");
        }
        
        // Commit transaksi
        $this->db->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback jika ada transaksi aktif
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        error_log("Withdraw error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get or create category for Dana Darurat
 */
private function getOrCreateEmergencyCategory($user_id) {
    try {
        // Cek apakah kategori sudah ada (type transfer)
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Dana Darurat' AND type = 'transfer' LIMIT 1");
        $stmt->execute([$user_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            return $category['id'];
        }
        
        // Buat kategori baru dengan type 'transfer'
        $stmt = $this->db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, 'Dana Darurat', 'transfer')");
        $stmt->execute([$user_id]);
        
        return $this->db->lastInsertId();
    } catch (PDOException $e) {
        error_log("getOrCreateEmergencyCategory error: " . $e->getMessage());
        
        // Fallback: cari kategori transfer yang sudah ada
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE user_id = ? AND type = 'transfer' LIMIT 1");
        $stmt->execute([$user_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($category) {
            return $category['id'];
        }
        
        // Last fallback: buat kategori expense
        $stmt = $this->db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, 'Dana Darurat', 'expense')");
        $stmt->execute([$user_id]);
        return $this->db->lastInsertId();
    }
}
    
    public function getTransactions($user_id, $limit = 50) {
        return $this->getHistory($user_id, $limit, 0);
    }
    
    public function getProgress($user_id) {
        $fund = $this->getEmergencyFund($user_id);
        if (!$fund || $fund['target_amount'] == 0) {
            return 0;
        }
        return ($fund['current_amount'] / $fund['target_amount']) * 100;
    }
    
    public function getRecommendation($user_id) {
        $fund = $this->getEmergencyFund($user_id);
        if (!$fund) {
            return [
                'status' => 'not_setup',
                'message' => 'Anda belum mengatur dana darurat. Segera tentukan target dana darurat Anda!',
                'suggestion' => 'Target dana darurat ideal adalah 3-6 kali pengeluaran bulanan'
            ];
        }
        
        $progress = $this->getProgress($user_id);
        
        if ($fund['current_amount'] >= $fund['target_amount']) {
            return [
                'status' => 'achieved',
                'message' => 'Selamat! Target dana darurat Anda telah tercapai.',
                'suggestion' => 'Pertahankan dan jangan digunakan kecuali benar-benar darurat'
            ];
        } elseif ($progress >= 75) {
            return [
                'status' => 'good',
                'message' => "Progress dana darurat: " . round($progress, 1) . "%",
                'suggestion' => 'Terus konsisten menabung, target hampir tercapai!'
            ];
        } elseif ($progress >= 50) {
            return [
                'status' => 'moderate',
                'message' => "Progress dana darurat: " . round($progress, 1) . "%",
                'suggestion' => 'Coba alokasikan 10-20% dari penghasilan untuk dana darurat'
            ];
        } elseif ($progress > 0) {
            return [
                'status' => 'low',
                'message' => "Progress dana darurat: " . round($progress, 1) . "%",
                'suggestion' => 'Prioritaskan pembentukan dana darurat sebelum investasi'
            ];
        } else {
            return [
                'status' => 'empty',
                'message' => 'Dana darurat masih kosong',
                'suggestion' => 'Mulai sisihkan penghasilan Anda untuk dana darurat sekarang!'
            ];
        }
    }
    
    public function checkTransaction($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM emergency_fund_transactions 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch(PDOException $e) {
            error_log("checkTransaction error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function deleteTransaction($user_id, $transaction_id) {
        try {
            $this->db->beginTransaction();
            
            // Get transaction details
            $stmt = $this->db->prepare("SELECT * FROM emergency_fund_transactions WHERE id = ? AND user_id = ?");
            $stmt->execute([$transaction_id, $user_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception("Transaction not found");
            }
            
            // Get emergency fund
            $fund = $this->getEmergencyFund($user_id);
            
            // Get account data
            $account = new Account();
            $accountData = $account->getById($transaction['account_id'], $user_id);
            
            // Reverse transaction
            if ($transaction['type'] == 'deposit') {
                // Reverse deposit: add back to account, remove from emergency fund
                $new_balance = $accountData['balance'] + $transaction['amount'];
                $updateData = [
                    'name' => $accountData['name'],
                    'balance' => $new_balance
                ];
                $account->update($transaction['account_id'], $user_id, $updateData);
                $new_current = $fund['current_amount'] - $transaction['amount'];
            } else {
                // Reverse withdraw: deduct from account, add back to emergency fund
                $new_balance = $accountData['balance'] - $transaction['amount'];
                $updateData = [
                    'name' => $accountData['name'],
                    'balance' => $new_balance
                ];
                $account->update($transaction['account_id'], $user_id, $updateData);
                $new_current = $fund['current_amount'] + $transaction['amount'];
            }
            
            // Update emergency fund balance
            $stmt = $this->db->prepare("UPDATE emergency_fund SET current_amount = ? WHERE id = ?");
            $stmt->execute([$new_current, $fund['id']]);
            
            // Delete transaction from emergency_fund_transactions
            $stmt = $this->db->prepare("DELETE FROM emergency_fund_transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            
            // ========== TAMBAHAN: HAPUS JUGA DARI TABEL TRANSACTIONS ==========
            // Cari dan hapus transaksi terkait
            $description_pattern = "%Dana Darurat%";
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE user_id = ? AND account_id = ? AND amount = ? AND description LIKE ? AND transaction_date >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->execute([$user_id, $transaction['account_id'], $transaction['amount'], $description_pattern]);
            // ========== END TAMBAHAN ==========
            
            $this->db->commit();
            return true;
        } catch(Exception $e) {
            $this->db->rollBack();
            error_log("Delete transaction error: " . $e->getMessage());
            return false;
        }
    }
    
    public function debugTransactions($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total, 
                       MIN(transaction_date) as first_date,
                       MAX(transaction_date) as last_date
                FROM emergency_fund_transactions 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("=== DEBUG TRANSACTIONS ===");
            error_log("User ID: $user_id");
            error_log("Total: " . $result['total']);
            error_log("First date: " . $result['first_date']);
            error_log("Last date: " . $result['last_date']);
            
            return $result;
        } catch(PDOException $e) {
            error_log("Debug error: " . $e->getMessage());
            return null;
        }
    }
}
?>