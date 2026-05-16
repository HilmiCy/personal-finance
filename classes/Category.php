<?php
require_once 'Database.php';

class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($user_id, $type = null) {
        try {
            $sql = "SELECT * FROM categories WHERE user_id = ?";
            $params = [$user_id];
            
            if ($type && $type != 'all') {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAll categories: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM categories 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getById category: " . $e->getMessage());
            return null;
        }
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO categories (user_id, name, type) 
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([
                $data['user_id'],
                $data['name'],
                $data['type']
            ]);
        } catch (PDOException $e) {
            error_log("Error in create category: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $user_id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE categories 
                SET name = ?, type = ? 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([
                $data['name'],
                $data['type'],
                $id,
                $user_id
            ]);
        } catch (PDOException $e) {
            error_log("Error in update category: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id, $user_id) {
        try {
            // Check if category is used in transactions
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM transactions WHERE category_id = ?
            ");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                DELETE FROM categories WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error in delete category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if category name already exists for a user and type
     * @param int $user_id User ID
     * @param string $name Category name
     * @param string $type Category type (income/expense)
     * @param int|null $exclude_id Category ID to exclude (for update operation)
     * @return bool|array Returns category data if exists, false otherwise
     */
    public function checkExists($user_id, $name, $type, $exclude_id = null) {
        try {
            $sql = "SELECT id, name, type FROM categories WHERE user_id = ? AND name = ? AND type = ?";
            $params = [$user_id, $name, $type];
            
            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ? $result : false;
        } catch (PDOException $e) {
            error_log("Error in checkExists category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get category by ID without user check (for internal use)
     * @param int $id Category ID
     * @return array|false Category data
     */
    public function getCategoryById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM categories WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getCategoryById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get category by ID with user verification
     * @param int $id Category ID
     * @param int $user_id User ID
     * @return array|false Category data
     */
    public function getCategoryByIdAndUser($id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM categories WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getCategoryByIdAndUser: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if category has transactions
     * @param int $id Category ID
     * @return bool True if has transactions, false otherwise
     */
    public function hasTransactions($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM transactions WHERE category_id = ?
            ");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            error_log("Error in hasTransactions: " . $e->getMessage());
            return true; // Assume has transactions to be safe
        }
    }
    
    /**
     * Get category count by type
     * @param int $user_id User ID
     * @param string $type Category type (income/expense)
     * @return int Count of categories
     */
    public function getCountByType($user_id, $type) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM categories WHERE user_id = ? AND type = ?
            ");
            $stmt->execute([$user_id, $type]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in getCountByType: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all categories with transaction count
     * @param int $user_id User ID
     * @param string|null $type Category type filter
     * @return array Categories with transaction counts
     */
    public function getAllWithTransactionCount($user_id, $type = null) {
        try {
            $sql = "
                SELECT c.*, 
                       (SELECT COUNT(*) FROM transactions WHERE category_id = c.id) as transaction_count
                FROM categories c
                WHERE c.user_id = ?
            ";
            $params = [$user_id];
            
            if ($type && $type != 'all') {
                $sql .= " AND c.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY c.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAllWithTransactionCount: " . $e->getMessage());
            return [];
        }
    }
}
?>