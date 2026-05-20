<?php
require_once 'Database.php';
require_once 'CurrencyService.php';

class Account {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM accounts 
                WHERE user_id = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAll accounts: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM accounts 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getById account: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get account by ID without user_id check (for internal use)
     */
    public function getByIdSimple($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM accounts WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getByIdSimple: " . $e->getMessage());
            return null;
        }
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO accounts (user_id, name, balance, currency) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['user_id'],
                $data['name'],
                $data['balance'] ?? 0,
                $data['currency'] ?? 'IDR'
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in create account: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $user_id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE accounts 
                SET name = ?, balance = ?, currency = ? 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([
                $data['name'],
                $data['balance'],
                $data['currency'] ?? 'IDR',
                $id,
                $user_id
            ]);
        } catch (PDOException $e) {
            error_log("Error in update account: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id, $user_id) {
        try {
            // Check if account has transactions
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM transactions WHERE account_id = ?
            ");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                DELETE FROM accounts WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error in delete account: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTotalBalance($user_id) {
        try {
            $accounts = $this->getAll($user_id);
            $total_idr = 0;
            
            foreach ($accounts as $account) {
                $total_idr += CurrencyService::convertToIDR($account['balance'], $account['currency']);
            }
            
            return $total_idr;
        } catch (PDOException $e) {
            error_log("Error in getTotalBalance: " . $e->getMessage());
            return 0;
        }
    }
    
   /**
 * Transfer between accounts
 */
public function transfer($from_account_id, $to_account_id, $amount, $description = '', $user_id = null) {
    // Get user_id from session if not provided
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    if (!$user_id) {
        return ['success' => false, 'message' => 'User tidak teridentifikasi'];
    }
    
    try {
        $this->db->beginTransaction();
        
        // Get both accounts
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$from_account_id]);
        $from_account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$to_account_id]);
        $to_account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Validate accounts exist
        if (!$from_account) {
            throw new Exception("Akun sumber tidak ditemukan");
        }
        
        if (!$to_account) {
            throw new Exception("Akun tujuan tidak ditemukan");
        }
        
        // Validate accounts belong to user
        if ($from_account['user_id'] != $user_id) {
            throw new Exception("Akun sumber tidak valid");
        }
        
        if ($to_account['user_id'] != $user_id) {
            throw new Exception("Akun tujuan tidak valid");
        }
        
        // Check sufficient balance
        if ($from_account['balance'] < $amount) {
            throw new Exception("Saldo tidak mencukupi. Saldo saat ini: Rp " . number_format($from_account['balance'], 0, ',', '.'));
        }
        
        // Update from account balance
        $stmt = $this->db->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $from_account_id]);
        
        // Update to account balance
        $stmt = $this->db->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $to_account_id]);
        
        // PASTIKAN TABEL TRANSFERS ADA
        $this->ensureTransferTable();
        
        // RECORD TRANSFER KE TABEL TRANSFERS
        $transfer_date = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            INSERT INTO transfers (user_id, from_account_id, to_account_id, amount, description, transfer_date) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $user_id, 
            $from_account_id, 
            $to_account_id, 
            $amount, 
            $description, 
            $transfer_date
        ]);
        
        if (!$result) {
            throw new Exception("Gagal menyimpan riwayat transfer: " . print_r($stmt->errorInfo(), true));
        }
        
        $transfer_id = $this->db->lastInsertId();
        error_log("Transfer recorded with ID: " . $transfer_id);
        
        $this->db->commit();
        return ['success' => true, 'message' => 'Transfer berhasil', 'transfer_id' => $transfer_id];
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Transfer error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Ensure transfer table exists
 */
private function ensureTransferTable() {
    try {
        // Cek apakah tabel transfers ada
        $stmt = $this->db->query("SHOW TABLES LIKE 'transfers'");
        if ($stmt->rowCount() == 0) {
            // Buat tabel transfers
            $sql = "
                CREATE TABLE transfers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    from_account_id INT NOT NULL,
                    to_account_id INT NOT NULL,
                    amount DECIMAL(15,2) NOT NULL,
                    description TEXT,
                    transfer_date DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_transfer_date (transfer_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->db->exec($sql);
            error_log("Transfer table created successfully");
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error ensuring transfer table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create transfer table if not exists
 */
public function createTransferTable() {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS transfers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            from_account_id INT NOT NULL,
            to_account_id INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            description TEXT,
            transfer_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_transfer_date (transfer_date)
        )";
        $this->db->exec($sql);
        
        // Add foreign keys if they don't exist
        try {
            $this->db->exec("ALTER TABLE transfers ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        } catch (PDOException $e) {
            // Foreign key might already exist
        }
        
        try {
            $this->db->exec("ALTER TABLE transfers ADD FOREIGN KEY (from_account_id) REFERENCES accounts(id) ON DELETE CASCADE");
        } catch (PDOException $e) {
            // Foreign key might already exist
        }
        
        try {
            $this->db->exec("ALTER TABLE transfers ADD FOREIGN KEY (to_account_id) REFERENCES accounts(id) ON DELETE CASCADE");
        } catch (PDOException $e) {
            // Foreign key might already exist
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error creating transfer table: " . $e->getMessage());
        return false;
    }
}

/**
 * Create transaction records for transfer
 */
private function createTransferTransactions($user_id, $from_account, $to_account, $amount, $description) {
    try {
        // Check if Transaction class exists
        if (!class_exists('Transaction')) {
            require_once 'Transaction.php';
        }
        
        $transaction = new Transaction();
        
        // Get current date
        $current_date = date('Y-m-d');
        
        // Transaction from source account (expense)
        $transaction->add([
            'user_id' => $user_id,
            'account_id' => $from_account['id'],
            'category_id' => null,
            'type' => 'expense',
            'amount' => $amount,
            'description' => "Transfer ke " . $to_account['name'] . ($description ? " - $description" : ""),
            'transaction_date' => $current_date
        ]);
        
        // Transaction to destination account (income)
        $transaction->add([
            'user_id' => $user_id,
            'account_id' => $to_account['id'],
            'category_id' => null,
            'type' => 'income',
            'amount' => $amount,
            'description' => "Transfer dari " . $from_account['name'] . ($description ? " - $description" : ""),
            'transaction_date' => $current_date
        ]);
        
    } catch (Exception $e) {
        error_log("Error creating transfer transactions: " . $e->getMessage());
        // Don't throw exception - this is secondary, main transfer already recorded
    }
}
    
    /**
 * Get transfer history
 */
public function getTransferHistory($user_id, $limit = 50, $offset = 0) {
    try {
        // Pastikan tabel transfers ada
        $this->ensureTransferTable();
        
        $stmt = $this->db->prepare("
            SELECT t.*, 
                   a1.name as from_account_name, 
                   a2.name as to_account_name
            FROM transfers t
            LEFT JOIN accounts a1 ON t.from_account_id = a1.id
            LEFT JOIN accounts a2 ON t.to_account_id = a2.id
            WHERE t.user_id = ?
            ORDER BY t.transfer_date DESC, t.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($results) . " transfers for user " . $user_id);
        
        return $results;
    } catch (PDOException $e) {
        error_log("Error in getTransferHistory: " . $e->getMessage());
        return [];
    }
}

/**
 * Get transfer history by date range
 */
public function getTransferHistoryByDate($user_id, $start_date, $end_date, $limit = 500) {
    try {
        $this->ensureTransferTable();
        
        $stmt = $this->db->prepare("
        SELECT t.*, 
            a1.name as from_account_name, 
            a2.name as to_account_name
        FROM transfers t
        LEFT JOIN accounts a1 ON t.from_account_id = a1.id
        LEFT JOIN accounts a2 ON t.to_account_id = a2.id
        WHERE t.user_id = ? 
        AND t.transfer_date BETWEEN ? AND ?
        ORDER BY t.transfer_date DESC, t.id DESC
        LIMIT $limit
    ");

    $stmt->execute([$user_id, $start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getTransferHistoryByDate: " . $e->getMessage());
        return [];
    }
}
    
    /**
     * Get total transfer amount for a specific account
     */
    public function getTotalTransferAmount($account_id, $user_id, $type = 'out') {
        try {
            if ($type == 'out') {
                $stmt = $this->db->prepare("
                    SELECT SUM(amount) as total 
                    FROM transfers 
                    WHERE user_id = ? AND from_account_id = ?
                ");
                $stmt->execute([$user_id, $account_id]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT SUM(amount) as total 
                    FROM transfers 
                    WHERE user_id = ? AND to_account_id = ?
                ");
                $stmt->execute([$user_id, $account_id]);
            }
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error in getTotalTransferAmount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if transfer table exists (for installation check)
     */
    public function checkTransferTable() {
        try {
            $stmt = $this->db->query("
                SHOW TABLES LIKE 'transfers'
            ");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>