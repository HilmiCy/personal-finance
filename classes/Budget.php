<?php
require_once 'Database.php';

class Budget {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByMonth($user_id, $month, $year) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    b.id,
                    b.user_id,
                    b.category_id,
                    b.amount as budget_amount,
                    b.month,
                    b.year,
                    c.name as category_name,
                    COALESCE((
                        SELECT SUM(amount) 
                        FROM transactions t 
                        WHERE t.category_id = b.category_id 
                        AND t.user_id = b.user_id
                        AND t.type = 'expense'
                        AND MONTH(t.transaction_date) = ?
                        AND YEAR(t.transaction_date) = ?
                    ), 0) as spent_amount
                FROM budgets b
                JOIN categories c ON b.category_id = c.id
                WHERE b.user_id = ? 
                AND b.month = ? 
                AND b.year = ?
                ORDER BY c.name ASC
            ");
            $stmt->execute([$month, $year, $user_id, $month, $year]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getByMonth: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM budgets 
                WHERE id = ? AND user_id = ?
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
            // Check if budget already exists for this category in this month
            $stmt = $this->db->prepare("
                SELECT id FROM budgets 
                WHERE user_id = ? 
                AND category_id = ? 
                AND month = ? 
                AND year = ?
            ");
            $stmt->execute([
                $data['user_id'],
                $data['category_id'],
                $data['month'],
                $data['year']
            ]);
            
            if ($stmt->fetch()) {
                error_log("Budget already exists for category: " . $data['category_id']);
                return false; // Budget already exists
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO budgets (user_id, category_id, amount, month, year) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $data['user_id'],
                $data['category_id'],
                $data['amount'],
                $data['month'],
                $data['year']
            ]);
            
            if ($result) {
                error_log("Budget created successfully for user: " . $data['user_id']);
            } else {
                error_log("Failed to create budget. PDO Error: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error in create budget: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE budgets 
                SET category_id = ?, amount = ? 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([
                $data['category_id'],
                $data['amount'],
                $data['id'],
                $data['user_id']
            ]);
            
            if ($result) {
                error_log("Budget updated successfully for id: " . $data['id']);
            } else {
                error_log("Failed to update budget. PDO Error: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error in update budget: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM budgets WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$id, $user_id]);
            
            if ($result) {
                error_log("Budget deleted successfully for id: " . $id);
            } else {
                error_log("Failed to delete budget. PDO Error: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error in delete budget: " . $e->getMessage());
            return false;
        }
    }
    
    public function exists($user_id, $category_id, $month, $year) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM budgets 
                WHERE user_id = ? 
                AND category_id = ? 
                AND month = ? 
                AND year = ?
            ");
            $stmt->execute([$user_id, $category_id, $month, $year]);
            return $stmt->fetch() ? true : false;
        } catch (PDOException $e) {
            error_log("Error in exists: " . $e->getMessage());
            return false;
        }
    }
}
?>