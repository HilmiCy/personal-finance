<?php
require_once 'Database.php';

class AssetTransaction {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByAsset($asset_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM asset_transactions 
                WHERE asset_id = ? AND user_id = ?
                ORDER BY transaction_date DESC, created_at DESC
            ");
            $stmt->execute([$asset_id, $user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getByAsset: " . $e->getMessage());
            return [];
        }
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO asset_transactions 
                (asset_id, user_id, type, quantity, price_per_unit, total_price, transaction_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['asset_id'],
                $data['user_id'],
                $data['type'],
                $data['quantity'],
                $data['price_per_unit'],
                $data['total_price'],
                $data['transaction_date']
            ]);
            
            if ($result) {
                // Update current price in asset_prices
                $this->updateCurrentPrice($data['asset_id'], $data['price_per_unit']);
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in create asset transaction: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($data) {
        try {
            // Get old transaction data
            $stmt = $this->db->prepare("
                SELECT * FROM asset_transactions WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$data['id'], $data['user_id']]);
            $old = $stmt->fetch();
            
            if (!$old) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Update transaction
            $stmt = $this->db->prepare("
                UPDATE asset_transactions 
                SET quantity = ?, price_per_unit = ?, total_price = ?, transaction_date = ?
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([
                $data['quantity'],
                $data['price_per_unit'],
                $data['total_price'],
                $data['transaction_date'],
                $data['id'],
                $data['user_id']
            ]);
            
            if ($result) {
                // Update current price with the latest price
                $this->updateCurrentPrice($old['asset_id'], $data['price_per_unit']);
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in update asset transaction: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id, $user_id) {
        try {
            // Get transaction details
            $stmt = $this->db->prepare("
                SELECT * FROM asset_transactions WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $user_id]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Delete transaction
            $stmt = $this->db->prepare("
                DELETE FROM asset_transactions WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$id, $user_id]);
            
            if ($result) {
                // Update current price with latest remaining transaction
                $this->updateCurrentPrice($transaction['asset_id'], null);
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in delete asset transaction: " . $e->getMessage());
            return false;
        }
    }
    
    private function updateCurrentPrice($asset_id, $price) {
        try {
            if ($price) {
                // Insert or update current price
                $stmt = $this->db->prepare("
                    INSERT INTO asset_prices (asset_id, price, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    price = VALUES(price), 
                    updated_at = NOW()
                ");
                $stmt->execute([$asset_id, $price]);
            } else {
                // Get latest price from remaining transactions
                $stmt = $this->db->prepare("
                    SELECT price_per_unit 
                    FROM asset_transactions 
                    WHERE asset_id = ? 
                    ORDER BY transaction_date DESC, created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$asset_id]);
                $latest = $stmt->fetch();
                
                if ($latest) {
                    $stmt = $this->db->prepare("
                        UPDATE asset_prices 
                        SET price = ?, updated_at = NOW()
                        WHERE asset_id = ?
                    ");
                    $stmt->execute([$latest['price_per_unit'], $asset_id]);
                } else {
                    // No transactions left, delete price record
                    $stmt = $this->db->prepare("
                        DELETE FROM asset_prices WHERE asset_id = ?
                    ");
                    $stmt->execute([$asset_id]);
                }
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error in updateCurrentPrice: " . $e->getMessage());
            return false;
        }
    }
}
?>