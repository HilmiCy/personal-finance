<?php
require_once 'Database.php';
require_once 'FinancialAnalytics.php';

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
    
    public function getCountWithFilters($user_id, $type = 'all', $account_id = '', $category_id = '', $date_from = '', $date_to = '') {
        try {
            $sql = "SELECT COUNT(*) FROM transactions t WHERE t.user_id = ?";
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
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in getCountWithFilters: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getAllWithFiltersPaginated($user_id, $type = 'all', $account_id = '', $category_id = '', $date_from = '', $date_to = '', $limit = 50, $offset = 0) {
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
            
            $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $this->db->prepare($sql);
            // We need to use bindValue for LIMIT and OFFSET to ensure they are treated as integers
            $i = 1;
            foreach ($params as $param) {
                $type_param = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($i++, $param, $type_param);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAllWithFiltersPaginated: " . $e->getMessage());
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
                if ($data['type'] == 'income') {
                    $this->updateAccountBalance($data['account_id'], 'income', $data['amount']);
                } elseif ($data['type'] == 'expense') {
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
    
    public function createTransfer($data) {
        try {
            $this->db->beginTransaction();
            $category_id = $this->getOrCreateTransferCategory($data['user_id']);
            
            $outgoing = [
                'user_id' => $data['user_id'],
                'account_id' => $data['from_account_id'],
                'category_id' => $category_id,
                'type' => 'expense',
                'amount' => $data['amount'],
                'description' => "Transfer ke: " . ($data['to_account_name'] ?? 'Akun lain'),
                'transaction_date' => $data['transaction_date']
            ];
            
            $incoming = [
                'user_id' => $data['user_id'],
                'account_id' => $data['to_account_id'],
                'category_id' => $category_id,
                'type' => 'income',
                'amount' => $data['amount'],
                'description' => "Transfer dari: " . ($data['from_account_name'] ?? 'Akun lain'),
                'transaction_date' => $data['transaction_date']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([$outgoing['user_id'], $outgoing['account_id'], $outgoing['category_id'], $outgoing['type'], $outgoing['amount'], $outgoing['description'], $outgoing['transaction_date']]);
            $this->updateAccountBalance($data['from_account_id'], 'expense', $data['amount']);
            
            $stmt->execute([$incoming['user_id'], $incoming['account_id'], $incoming['category_id'], $incoming['type'], $incoming['amount'], $incoming['description'], $incoming['transaction_date']]);
            $this->updateAccountBalance($data['to_account_id'], 'income', $data['amount']);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    private function getOrCreateTransferCategory($user_id) {
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Transfer' LIMIT 1");
        $stmt->execute([$user_id]);
        $res = $stmt->fetch();
        if ($res) return $res['id'];
        
        $stmt = $this->db->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, 'Transfer', 'transfer')");
        $stmt->execute([$user_id]);
        return $this->db->lastInsertId();
    }

    public function update($data) {
        try {
            $old_trans = $this->getById($data['id'], $data['user_id']);
            if (!$old_trans) return false;
            
            $this->db->beginTransaction();
            if ($old_trans['type'] == 'income') $this->updateAccountBalance($old_trans['account_id'], 'expense', $old_trans['amount']);
            else $this->updateAccountBalance($old_trans['account_id'], 'income', $old_trans['amount']);
            
            $stmt = $this->db->prepare("UPDATE transactions SET account_id = ?, category_id = ?, type = ?, amount = ?, description = ?, transaction_date = ? WHERE id = ?");
            $stmt->execute([$data['account_id'], $data['category_id'], $data['type'], $data['amount'], $data['description'], $data['transaction_date'], $data['id']]);
            
            if ($data['type'] == 'income') $this->updateAccountBalance($data['account_id'], 'income', $data['amount']);
            else $this->updateAccountBalance($data['account_id'], 'expense', $data['amount']);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Train XGBoost Model and get accuracy metrics
     */
    public function trainAIModel($user_id) {
        try {
            $history = [];
            for ($i = 23; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
                $stmt->execute([$user_id, $month]);
                $total = (float)$stmt->fetchColumn();
                if ($total > 0 || $i < 6) $history[] = ['month' => $month, 'total' => $total];
            }

            $ch = curl_init('http://127.0.0.1:8001/train');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['history' => $history]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                return json_decode($response, true);
            }
            return ['status' => 'error', 'message' => 'AI Service tidak merespon dengan benar.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Transaction Anomalies
     */
    public function getAIAnomalies($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.id, t.amount, c.name as category, t.transaction_date as date 
                FROM transactions t 
                JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = ? AND t.type = 'expense' 
                AND t.transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)
            ");
            $stmt->execute([$user_id]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($transactions) < 5) return [];

            $ch = curl_init('http://127.0.0.1:8001/anomalies');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['transactions' => $transactions]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                return $result['anomalies'] ?? [];
            }
            return [];
        } catch (Exception $e) { return []; }
    }

    /**
     * Get Comprehensive AI Financial Report
     */
    public function getAIFinancialReport($user_id) {
        try {
            // Get stats for current month
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
                FROM transactions 
                WHERE user_id = ? AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Savings (Emergency Fund)
            $stmt = $this->db->prepare("SELECT current_amount FROM emergency_fund WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $savings = (float)$stmt->fetchColumn();

            // Debt (Installments)
            $stmt = $this->db->prepare("SELECT SUM(remaining_amount) FROM installments WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$user_id]);
            $debt = (float)$stmt->fetchColumn();

            // Recent transactions for pattern analysis
            $stmt = $this->db->prepare("
                SELECT t.id, t.amount, c.name as category, t.transaction_date as date 
                FROM transactions t 
                JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = ? AND t.type = 'expense' 
                ORDER BY t.transaction_date DESC LIMIT 100
            ");
            $stmt->execute([$user_id]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $payload = [
                'income' => (float)($row['income'] ?? 0),
                'expense' => (float)($row['expense'] ?? 0),
                'savings' => $savings,
                'debt' => $debt,
                'transactions' => $transactions
            ];

            $ch = curl_init('http://127.0.0.1:8001/financial_report');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                return json_decode($response, true);
            }
            return null;
        } catch (Exception $e) { return null; }
    }

    public function delete($id, $user_id) {
        try {
            $transaction = $this->getById($id, $user_id);
            if (!$transaction) return false;
            
            $this->db->beginTransaction();
            if ($transaction['type'] == 'income') $this->updateAccountBalance($transaction['account_id'], 'expense', $transaction['amount']);
            else $this->updateAccountBalance($transaction['account_id'], 'income', $transaction['amount']);
            
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->execute([$id]);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    private function updateAccountBalance($account_id, $type, $amount) {
        if ($type == 'income') $sql = "UPDATE accounts SET balance = balance + ? WHERE id = ?";
        else $sql = "UPDATE accounts SET balance = balance - ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $account_id]);
    }
    
    public function getTransferHistory($user_id, $limit = 50) {
        $stmt = $this->db->prepare("SELECT t.*, a.name as account_name, c.name as category_name FROM transactions t LEFT JOIN accounts a ON t.account_id = a.id LEFT JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = 'transfer' ORDER BY t.transaction_date DESC LIMIT ?");
        $stmt->execute([$user_id, (int)$limit]);
        return $stmt->fetchAll();
    }

    public function getMonthlySummary($user_id, $year, $month) {
        $stmt = $this->db->prepare("SELECT type, SUM(amount) as total FROM transactions WHERE user_id = ? AND YEAR(transaction_date) = ? AND MONTH(transaction_date) = ? GROUP BY type");
        $stmt->execute([$user_id, $year, $month]);
        $rows = $stmt->fetchAll();
        $summary = ['total_income' => 0, 'total_expense' => 0];
        foreach ($rows as $row) {
            if ($row['type'] == 'income') $summary['total_income'] = (float)$row['total'];
            elseif ($row['type'] == 'expense') $summary['total_expense'] = (float)$row['total'];
        }
        return $summary;
    }

    public function getByDateRange($user_id, $start_date, $end_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, a.name as account_name, c.name as category_name 
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
                ORDER BY t.transaction_date DESC, t.created_at DESC
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getByDateRange: " . $e->getMessage());
            return [];
        }
    }

    public function getDailySummary($user_id, $start_date, $end_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT transaction_date as date, 
                       SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                       SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
                FROM transactions 
                WHERE user_id = ? AND transaction_date BETWEEN ? AND ?
                GROUP BY transaction_date
                ORDER BY transaction_date ASC
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getDailySummary: " . $e->getMessage());
            return [];
        }
    }

    public function getSummaryByCategory($user_id, $month, $year, $type) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.name, SUM(t.amount) as total 
                FROM transactions t 
                JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = ? AND t.type = ? 
                AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ? 
                GROUP BY c.id, c.name
                ORDER BY total DESC
            ");
            $stmt->execute([$user_id, $type, (int)$month, (int)$year]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSummaryByCategory: " . $e->getMessage());
            return [];
        }
    }

    public function getTopTransactions($user_id, $start_date, $end_date, $type, $limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, c.name as category_name 
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.type = ? AND t.transaction_date BETWEEN ? AND ?
                ORDER BY t.amount DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $type, $start_date, $end_date, (int)$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getTopTransactions: " . $e->getMessage());
            return [];
        }
    }

    public function updateTransfer($data) {
        try {
            $old_transfer = $this->getById($data['id'], $data['user_id']);
            if (!$old_transfer) return false;

            // This is complex because a transfer is two transactions (or one that we need to find its pair)
            // But looking at update.php, it seems it treats the main transaction.
            // Actually, createTransfer creates two transactions. 
            // If we update, we should ideally update both.
            
            // Simplified: Delete old and create new
            // Or just update the current one and assume the user manages the other?
            // Usually, transfers are linked. Let's see if there's a reference.
            // Currently, no reference. 
            
            // For now, let's just update the current transaction to maintain compatibility
            // but a better way is needed for proper transfer management.
            return $this->update($data);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function add($data) {
        return $this->create($data);
    }

    public function getRootCauseAnalysis($user_id) {
        $stmt = $this->db->prepare("SELECT c.name, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = 'expense' AND MONTH(t.transaction_date) = MONTH(CURRENT_DATE()) GROUP BY c.id ORDER BY total DESC LIMIT 3");
        $stmt->execute([$user_id]);
        $top = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($top as &$cat) $cat['advice'] = FinancialAnalytics::getSavingAdvice($cat['name'], $cat['total']);
        return $top;
    }

    /**
     * Advanced Analytics: Predict next month's expenses using XGBoost Microservice
     */
    public function predictNextMonthExpense($user_id) {
        try {
            $history = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
                $stmt->execute([$user_id, $month]);
                $total = (float)$stmt->fetchColumn();
                if ($total > 0 || $i < 6) $history[] = ['month' => $month, 'total' => $total];
            }

            $ch = curl_init('http://127.0.0.1:8001/predict');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['history' => $history]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                return ['amount' => $result['prediction'], 'trend' => $result['trend'], 'algorithm' => 'XGBoost (AI Service)', 'count' => count($history)];
            }

            $monthly_values = array_column($history, 'total');
            $analysis = FinancialAnalytics::calculateLinearRegression(array_slice($monthly_values, -6));
            return ['amount' => $analysis['prediction'], 'trend' => $analysis['trend'], 'algorithm' => 'Linear Regression (Fallback)', 'count' => count($history)];
        } catch (Exception $e) {
            return ['amount' => 0, 'trend' => 'error', 'count' => 0];
        }
    }

    /**
     * Deep Insight AI: Analyze category spending changes vs last month
     */
    public function getDeepAIInsights($user_id) {
        try {
            $curr_month = date('m'); $curr_year = date('Y');
            $curr_data = $this->getSummaryByCategoryForAI($user_id, $curr_month, $curr_year);
            $prev_month = date('m', strtotime('-1 month')); $prev_year = date('Y', strtotime('-1 month'));
            $prev_data = $this->getSummaryByCategoryForAI($user_id, $prev_month, $prev_year);

            if (empty($curr_data) || empty($prev_data)) return [];

            $ch = curl_init('http://127.0.0.1:8001/analyze_deep_insights');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['current_month' => $curr_data, 'previous_month' => $prev_data]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                return $result['insights'] ?? [];
            }
            return [];
        } catch (Exception $e) { return []; }
    }

    private function getSummaryByCategoryForAI($user_id, $month, $year) {
        $stmt = $this->db->prepare("SELECT c.name as category, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND t.type = 'expense' AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ? GROUP BY c.id");
        $stmt->execute([$user_id, (int)$month, (int)$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>