<?php
require_once 'Database.php';

class Transaction {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($user_id, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, a.name as account_name, c.name as category_name 
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?
                ORDER BY t.transaction_date DESC, t.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, (int)$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAll: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAllWithFilters($user_id, $type = 'all', $account_id = '', $category_id = '', $date_from = '', $date_to = '') {
        try {
            $sql = "
                SELECT t.*, a.name as account_name, c.name as category_name 
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?
            ";
            $params = [$user_id];
            
            if ($type != 'all') {
                $sql .= " AND t.type = ?";
                $params[] = $type;
            }
            
            if (!empty($account_id)) {
                $sql .= " AND t.account_id = ?";
                $params[] = $account_id;
            }
            
            if (!empty($category_id)) {
                $sql .= " AND t.category_id = ?";
                $params[] = $category_id;
            }
            
            if (!empty($date_from)) {
                $sql .= " AND t.transaction_date >= ?";
                $params[] = $date_from;
            }
            
            if (!empty($date_to)) {
                $sql .= " AND t.transaction_date <= ?";
                $params[] = $date_to;
            }
            
            $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAllWithFilters: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, a.name as account_name, c.name as category_name 
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.id = ? AND t.user_id = ?
            ");
            $stmt->execute([$id, $user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getById: " . $e->getMessage());
            return null;
        }
    }
    
    // ========== MODIFIED CREATE METHOD - TETAP UPDATE BALANCE UNTUK TRANSFER ==========
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, account_id, category_id, type, amount, description, transaction_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['user_id'],
                $data['account_id'],
                $data['category_id'],
                $data['type'],
                $data['amount'],
                $data['description'],
                $data['transaction_date']
            ]);
            
            if ($result) {
                // UPDATE BALANCE UNTUK SEMUA TYPE (income, expense, transfer)
                if ($data['type'] == 'income') {
                    $this->updateAccountBalance($data['account_id'], 'income', $data['amount']);
                } elseif ($data['type'] == 'expense') {
                    $this->updateAccountBalance($data['account_id'], 'expense', $data['amount']);
                } elseif ($data['type'] == 'transfer') {
                    // Untuk transfer, kita update balance sesuai dengan aturan:
                    // Transfer dari akun A ke akun B: 
                    // - Akun A berkurang (expense)
                    // - Akun B bertambah (income)
                    // Tapi karena ini hanya 1 transaksi, kita hanya update balance akun yang bersangkutan
                    // Untuk transfer, kita tidak update balance di sini karena akan di-handle di method createTransfer()
                    // Atau jika Anda ingin update balance, bisa diaktifkan:
                    $this->updateAccountBalance($data['account_id'], 'expense', $data['amount']);
                }
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in create transaction: " . $e->getMessage());
            return false;
        }
    }
    
    // ========== METHOD CREATE TRANSFER ==========
    /**
     * Create transfer transaction between two accounts
     * @param array $data Transfer data with from_account_id, to_account_id, amount, etc.
     * @return bool Success or failure
     */
    public function createTransfer($data) {
        try {
            $this->db->beginTransaction();
            
            // Get or create transfer category
            $category_id = $this->getOrCreateTransferCategory($data['user_id']);
            
            // Record outgoing transaction (from source account) - type 'expense'
            $outgoing = [
                'user_id' => $data['user_id'],
                'account_id' => $data['from_account_id'],
                'category_id' => $category_id,
                'type' => 'expense', // Ubah jadi expense agar update balance otomatis
                'amount' => $data['amount'],
                'description' => "Transfer ke: " . ($data['to_account_name'] ?? 'Akun lain') . " - " . ($data['description'] ?? 'Transfer dana'),
                'transaction_date' => $data['transaction_date']
            ];
            
            // Record incoming transaction (to destination account) - type 'income'
            $incoming = [
                'user_id' => $data['user_id'],
                'account_id' => $data['to_account_id'],
                'category_id' => $category_id,
                'type' => 'income', // Ubah jadi income agar update balance otomatis
                'amount' => $data['amount'],
                'description' => "Transfer dari: " . ($data['from_account_name'] ?? 'Akun lain') . " - " . ($data['description'] ?? 'Transfer dana'),
                'transaction_date' => $data['transaction_date']
            ];
            
            // Insert outgoing transaction (akan otomatis update balance karena type 'expense')
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, account_id, category_id, type, amount, description, transaction_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result1 = $stmt->execute([
                $outgoing['user_id'],
                $outgoing['account_id'],
                $outgoing['category_id'],
                $outgoing['type'],
                $outgoing['amount'],
                $outgoing['description'],
                $outgoing['transaction_date']
            ]);
            
            // Update balance untuk outgoing (kurangi saldo akun sumber)
            if ($result1) {
                $this->updateAccountBalance($data['from_account_id'], 'expense', $data['amount']);
            }
            
            // Insert incoming transaction (akan otomatis update balance karena type 'income')
            $result2 = $stmt->execute([
                $incoming['user_id'],
                $incoming['account_id'],
                $incoming['category_id'],
                $incoming['type'],
                $incoming['amount'],
                $incoming['description'],
                $incoming['transaction_date']
            ]);
            
            // Update balance untuk incoming (tambah saldo akun tujuan)
            if ($result2) {
                $this->updateAccountBalance($data['to_account_id'], 'income', $data['amount']);
            }
            
            if ($result1 && $result2) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in createTransfer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get or create transfer category
     */
    private function getOrCreateTransferCategory($user_id) {
        try {
            // Cek apakah kategori transfer sudah ada
            $stmt = $this->db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Transfer' AND type = 'transfer' LIMIT 1");
            $stmt->execute([$user_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                return $category['id'];
            }
            
            // Buat kategori transfer baru
            $stmt = $this->db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, 'Transfer', 'transfer')");
            $stmt->execute([$user_id]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("getOrCreateTransferCategory error: " . $e->getMessage());
            // Fallback: cari kategori expense
            $stmt = $this->db->prepare("SELECT id FROM categories WHERE user_id = ? AND type = 'expense' LIMIT 1");
            $stmt->execute([$user_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            return $category ? $category['id'] : null;
        }
    }
    
    // ========== MODIFIED UPDATE METHOD ==========
    public function update($data) {
        try {
            $id = isset($data['id']) ? $data['id'] : 0;
            $user_id = isset($data['user_id']) ? $data['user_id'] : 0;
            
            $old_trans = $this->getById($id, $user_id);
            if (!$old_trans) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Reverse old balance
            if ($old_trans['type'] == 'income') {
                $this->updateAccountBalance($old_trans['account_id'], 'expense', $old_trans['amount']);
            } elseif ($old_trans['type'] == 'expense') {
                $this->updateAccountBalance($old_trans['account_id'], 'income', $old_trans['amount']);
            } elseif ($old_trans['type'] == 'transfer') {
                // Reverse transfer: jika sebelumnya transfer, kembalikan balance
                $this->updateAccountBalance($old_trans['account_id'], 'income', $old_trans['amount']);
            }
            
            // Update transaction
            $stmt = $this->db->prepare("
                UPDATE transactions 
                SET account_id = ?, category_id = ?, type = ?, amount = ?, description = ?, transaction_date = ?
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([
                $data['account_id'],
                $data['category_id'],
                $data['type'],
                $data['amount'],
                $data['description'],
                $data['transaction_date'],
                $id,
                $user_id
            ]);
            
            if ($result) {
                // Apply new balance
                if ($data['type'] == 'income') {
                    $this->updateAccountBalance($data['account_id'], 'income', $data['amount']);
                } elseif ($data['type'] == 'expense') {
                    $this->updateAccountBalance($data['account_id'], 'expense', $data['amount']);
                } elseif ($data['type'] == 'transfer') {
                    // Untuk transfer, update balance (kurangi)
                    $this->updateAccountBalance($data['account_id'], 'expense', $data['amount']);
                }
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in update transaction: " . $e->getMessage());
            return false;
        }
    }
    
    // ========== MODIFIED DELETE METHOD ==========
    public function delete($id, $user_id) {
        try {
            $transaction = $this->getById($id, $user_id);
            
            if ($transaction) {
                $this->db->beginTransaction();
                
                // Reverse balance
                if ($transaction['type'] == 'income') {
                    $this->updateAccountBalance($transaction['account_id'], 'expense', $transaction['amount']);
                } elseif ($transaction['type'] == 'expense') {
                    $this->updateAccountBalance($transaction['account_id'], 'income', $transaction['amount']);
                } elseif ($transaction['type'] == 'transfer') {
                    // Reverse transfer: tambah balik saldo
                    $this->updateAccountBalance($transaction['account_id'], 'income', $transaction['amount']);
                }
                
                $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$id, $user_id]);
                
                if ($result) {
                    $this->db->commit();
                    return true;
                } else {
                    $this->db->rollBack();
                    return false;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in delete transaction: " . $e->getMessage());
            return false;
        }
    }
    
    private function updateAccountBalance($account_id, $type, $amount) {
        try {
            if ($type == 'income') {
                $sql = "UPDATE accounts SET balance = balance + ? WHERE id = ?";
            } else {
                $sql = "UPDATE accounts SET balance = balance - ? WHERE id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$amount, $account_id]);
        } catch (PDOException $e) {
            error_log("Error in updateAccountBalance: " . $e->getMessage());
            return false;
        }
    }
    
    // ========== NEW METHOD: GET TRANSFER HISTORY ==========
    public function getTransferHistory($user_id, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, a.name as account_name, c.name as category_name 
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.type = 'transfer'
                ORDER BY t.transaction_date DESC, t.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, (int)$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getTransferHistory: " . $e->getMessage());
            return [];
        }
    }
    
    // ========== METHOD UNTUK GET TOTAL TRANSFER ==========
    public function getTotalTransfer($user_id, $start_date = null, $end_date = null) {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'transfer'";
            $params = [$user_id];
            
            if ($start_date && $end_date) {
                $sql .= " AND transaction_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error in getTotalTransfer: " . $e->getMessage());
            return 0;
        }
    }
    
    // Method lainnya tetap sama...
    public function getByDateRange($user_id, $start_date, $end_date, $type = null) {
        try {
            $sql = "
                SELECT t.*, a.name as account_name, c.name as category_name 
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                AND t.transaction_date BETWEEN ? AND ?
            ";
            
            $params = [$user_id, $start_date, $end_date];
            
            if ($type && $type != 'all') {
                $sql .= " AND t.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY t.transaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getByDateRange: " . $e->getMessage());
            return [];
        }
    }
    
    public function getSummaryByCategory($user_id, $month, $year, $type) {
    try {
        $stmt = $this->db->prepare("
            SELECT c.name, SUM(t.amount) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.type = ?
            AND MONTH(t.transaction_date) = ?
            AND YEAR(t.transaction_date) = ?
            GROUP BY c.id, c.name
            ORDER BY total DESC
        ");
        $stmt->execute([$user_id, $type, $month, $year]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getSummaryByCategory: " . $e->getMessage());
        return [];
    }
}
    public function getMonthlySummary($user_id, $year, $month) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                    SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END) as total_transfer
                FROM transactions
                WHERE user_id = ? 
                AND YEAR(transaction_date) = ? 
                AND MONTH(transaction_date) = ?
            ");
            $stmt->execute([$user_id, $year, $month]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getMonthlySummary: " . $e->getMessage());
            return ['total_income' => 0, 'total_expense' => 0, 'total_transfer' => 0];
        }
    }
    
    /**
 * Get daily summary for chart (income and expense per day)
 * @param int $user_id User ID
 * @param string $start_date Start date (Y-m-d)
 * @param string $end_date End date (Y-m-d)
 * @return array Array of daily data with income and expense
 */
public function getDailySummary($user_id, $start_date, $end_date) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                transaction_date,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
            FROM transactions
            WHERE user_id = ? 
            AND transaction_date BETWEEN ? AND ?
            AND type != 'transfer'  -- Tambahkan ini: exclude transfer
            GROUP BY transaction_date
            ORDER BY transaction_date ASC
        ");
        $stmt->execute([$user_id, $start_date, $end_date]);
        $results = $stmt->fetchAll();
        
        // Fill missing dates with zeros
        $daily_data = [];
        $current = strtotime($start_date);
        $end = strtotime($end_date);
        
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $found = false;
            
            foreach ($results as $row) {
                if ($row['transaction_date'] == $date) {
                    $daily_data[] = [
                        'transaction_date' => $date,
                        'income' => (float)$row['income'],
                        'expense' => (float)$row['expense']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $daily_data[] = [
                    'transaction_date' => $date,
                    'income' => 0,
                    'expense' => 0
                ];
            }
            
            $current = strtotime('+1 day', $current);
        }
        
        return $daily_data;
    } catch (PDOException $e) {
        error_log("Error in getDailySummary: " . $e->getMessage());
        return [];
    }
}
    
    /**
 * Get yearly summary for chart (income and expense per month)
 * @param int $user_id User ID
 * @param int $year Year
 * @return array Array of monthly data for all 12 months
 */
public function getYearlyMonthlySummary($user_id, $year) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                MONTH(transaction_date) as month,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
            FROM transactions
            WHERE user_id = ? 
            AND YEAR(transaction_date) = ?
            AND type != 'transfer'  -- Tambahkan ini: exclude transfer
            GROUP BY MONTH(transaction_date)
            ORDER BY month ASC
        ");
        $stmt->execute([$user_id, $year]);
        $results = $stmt->fetchAll();
        
        // Fill missing months with zeros
        $monthly_data = [];
        for ($i = 1; $i <= 12; $i++) {
            $found = false;
            foreach ($results as $row) {
                if ($row['month'] == $i) {
                    $monthly_data[] = [
                        'month' => $i,
                        'income' => (float)$row['income'],
                        'expense' => (float)$row['expense']
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $monthly_data[] = [
                    'month' => $i,
                    'income' => 0,
                    'expense' => 0
                ];
            }
        }
        
        return $monthly_data;
    } catch (PDOException $e) {
        error_log("Error in getYearlyMonthlySummary: " . $e->getMessage());
        return [];
    }
}
    
    /**
     * Get yearly summary by category
     * @param int $user_id User ID
     * @param int $year Year
     * @param string $type Transaction type (income/expense)
     * @return array Array of category totals
     */
    public function getYearlySummaryByCategory($user_id, $year, $type) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.name, COALESCE(SUM(t.amount), 0) as total
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? 
                AND t.type = ?
                AND YEAR(t.transaction_date) = ?
                GROUP BY c.id, c.name
                ORDER BY total DESC
            ");
            $stmt->execute([$user_id, $type, $year]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getYearlySummaryByCategory: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get account breakdown for specific period
     * @param int $user_id User ID
     * @param int $month Month
     * @param int $year Year
     * @return array Array of account breakdown
     */
    public function getAccountBreakdown($user_id, $month, $year) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.name, 
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as expense
                FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                WHERE t.user_id = ? 
                AND MONTH(t.transaction_date) = ? 
                AND YEAR(t.transaction_date) = ?
                GROUP BY a.id, a.name
            ");
            $stmt->execute([$user_id, $month, $year]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAccountBreakdown: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get yearly account breakdown
     * @param int $user_id User ID
     * @param int $year Year
     * @return array Array of yearly account breakdown
     */
    public function getYearlyAccountBreakdown($user_id, $year) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.name, 
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as expense
                FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                WHERE t.user_id = ? 
                AND YEAR(t.transaction_date) = ?
                GROUP BY a.id, a.name
            ");
            $stmt->execute([$user_id, $year]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getYearlyAccountBreakdown: " . $e->getMessage());
            return [];
        }
    }

    // Tambahkan method khusus untuk summary transfer
public function getTransferSummaryByCategory($user_id, $month, $year) {
    try {
        $stmt = $this->db->prepare("
            SELECT c.name, SUM(t.amount) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.type = 'transfer'
            AND MONTH(t.transaction_date) = ?
            AND YEAR(t.transaction_date) = ?
            GROUP BY c.id, c.name
            ORDER BY total DESC
        ");
        $stmt->execute([$user_id, $month, $year]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getTransferSummaryByCategory: " . $e->getMessage());
        return [];
    }
}
    
    /**
 * Get transaction statistics
 * @param int $user_id User ID
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return array Statistics data
 */
public function getStatistics($user_id, $start_date, $end_date) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
                COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count,
                COUNT(CASE WHEN type = 'transfer' THEN 1 END) as transfer_count,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
                COALESCE(SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END), 0) as total_transfer,
                COALESCE(AVG(CASE WHEN type = 'income' THEN amount END), 0) as avg_income,
                COALESCE(AVG(CASE WHEN type = 'expense' THEN amount END), 0) as avg_expense
            FROM transactions
            WHERE user_id = ? 
            AND transaction_date BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $start_date, $end_date]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error in getStatistics: " . $e->getMessage());
        return [
            'total_transactions' => 0,
            'income_count' => 0,
            'expense_count' => 0,
            'transfer_count' => 0,
            'total_income' => 0,
            'total_expense' => 0,
            'total_transfer' => 0,
            'avg_income' => 0,
            'avg_expense' => 0
        ];
    }
}

/**
 * Get total count of transactions with filters (for pagination)
 */
public function getCountWithFilters($user_id, $type, $account, $category, $date_from, $date_to) {
    $sql = "SELECT COUNT(*) as total FROM transactions WHERE user_id = :user_id";
    $params = [':user_id' => $user_id];
    
    if ($type != 'all') {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }
    
    if (!empty($account)) {
        $sql .= " AND account_id = :account";
        $params[':account'] = $account;
    }
    
    if (!empty($category)) {
        $sql .= " AND category_id = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($date_from)) {
        $sql .= " AND transaction_date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $sql .= " AND transaction_date <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    // PERBAIKAN: pakai $this->db bukan $this->conn
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

/**
 * Get transactions with filters and pagination
 */
/**
 * Get transactions with filters and pagination
 */
public function getAllWithFiltersPaginated($user_id, $type, $account, $category, $date_from, $date_to, $limit, $offset) {
    $sql = "SELECT t.*, 
            c.name as category_name, 
            a.name as account_name
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN accounts a ON t.account_id = a.id
            WHERE t.user_id = :user_id";
    $params = [':user_id' => $user_id];
    
    if ($type != 'all') {
        $sql .= " AND t.type = :type";
        $params[':type'] = $type;
    }
    
    if (!empty($account)) {
        $sql .= " AND t.account_id = :account";
        $params[':account'] = $account;
    }
    
    if (!empty($category)) {
        $sql .= " AND t.category_id = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($date_from)) {
        $sql .= " AND t.transaction_date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $sql .= " AND t.transaction_date <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";
    
    if ($limit != 999999) {
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    }
    
    $stmt = $this->db->prepare($sql);
    
    if ($limit != 999999) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    }
    
    foreach($params as $key => $value) {
        if ($key != ':limit' && $key != ':offset') {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function getTopTransactions($user_id, $start_date, $end_date, $type, $limit = 5) {
    try {
        // Cast limit to integer for safety
        $limit = (int)$limit;
        
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name, a.name as account_name
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            JOIN accounts a ON t.account_id = a.id
            WHERE t.user_id = ? 
            AND t.transaction_date BETWEEN ? AND ?
            AND t.type = ?
            ORDER BY t.amount DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$user_id, $start_date, $end_date, $type]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getTopTransactions: " . $e->getMessage());
        return [];
    }
}

}
?>