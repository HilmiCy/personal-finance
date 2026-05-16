<?php
require_once 'Database.php';

class Asset {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                    COALESCE((
                        SELECT SUM(CASE WHEN type = 'buy' THEN quantity ELSE -quantity END)
                        FROM asset_transactions 
                        WHERE asset_id = a.id AND user_id = ?
                    ), 0) as total_quantity,

                    COALESCE((
                        SELECT price FROM asset_prices 
                        WHERE asset_id = a.id 
                        ORDER BY updated_at DESC LIMIT 1
                    ), 0) as current_price,

                    COALESCE((
                        SELECT SUM(CASE WHEN type = 'buy' THEN total_price ELSE 0 END)
                        FROM asset_transactions 
                        WHERE asset_id = a.id AND user_id = ?
                    ), 0) as total_buy,

                    COALESCE((
                        SELECT SUM(CASE WHEN type = 'sell' THEN total_price ELSE 0 END)
                        FROM asset_transactions 
                        WHERE asset_id = a.id AND user_id = ?
                    ), 0) as total_sell

                FROM assets a
                WHERE a.user_id = ?
                ORDER BY a.name ASC
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAll assets: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                    COALESCE((
                        SELECT SUM(CASE WHEN type = 'buy' THEN quantity ELSE -quantity END)
                        FROM asset_transactions 
                        WHERE asset_id = a.id AND user_id = ?
                    ), 0) as total_quantity,
                    COALESCE((
                        SELECT price FROM asset_prices 
                        WHERE asset_id = a.id 
                        ORDER BY updated_at DESC LIMIT 1
                    ), 0) as current_price,
                    COALESCE((
                        SELECT SUM(CASE WHEN type = 'buy' THEN total_price ELSE 0 END)
                        FROM asset_transactions 
                        WHERE asset_id = a.id AND user_id = ?
                    ), 0) as total_investment
                FROM assets a
                WHERE a.id = ? AND a.user_id = ?
            ");
            $stmt->execute([$user_id, $user_id, $id, $user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getById asset: " . $e->getMessage());
            return null;
        }
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO assets (user_id, name, type, symbol) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['user_id'],
                $data['name'],
                $data['type'],
                $data['symbol']
            ]);
        } catch (PDOException $e) {
            error_log("Error in create asset: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE assets 
                SET name = ?, type = ?, symbol = ? 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([
                $data['name'],
                $data['type'],
                $data['symbol'],
                $data['id'],
                $data['user_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error in update asset: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id, $user_id) {
        try {
            // Check if asset has transactions
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM asset_transactions WHERE asset_id = ?
            ");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                return false; // Cannot delete asset with transactions
            }
            
            $stmt = $this->db->prepare("
                DELETE FROM assets WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error in delete asset: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPortfolioSummary($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT a.id) as total_assets,
                    COALESCE(SUM(
                        (
                            SELECT COALESCE(SUM(CASE WHEN type = 'buy' THEN quantity ELSE -quantity END), 0)
                            FROM asset_transactions 
                            WHERE asset_id = a.id AND user_id = ?
                        ) * (
                            SELECT price FROM asset_prices 
                            WHERE asset_id = a.id 
                            ORDER BY updated_at DESC LIMIT 1
                        )
                    ), 0) as total_value,
                    COALESCE(SUM(
                        (
                            SELECT COALESCE(SUM(CASE WHEN type = 'buy' THEN total_price ELSE 0 END), 0)
                            FROM asset_transactions 
                            WHERE asset_id = a.id AND user_id = ?
                        )
                    ), 0) as total_investment
                FROM assets a
                WHERE a.user_id = ?
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
            $result = $stmt->fetch();
            
            $total_value = $result['total_value'] ?? 0;
            $total_investment = $result['total_investment'] ?? 0;
            $profit_loss = $total_value - $total_investment;
            $profit_loss_percent = $total_investment > 0 ? ($profit_loss / $total_investment) * 100 : 0;
            
            return [
                'total_assets' => $result['total_assets'] ?? 0,
                'total_value' => $total_value,
                'total_investment' => $total_investment,
                'profit_loss' => $profit_loss,
                'profit_loss_percent' => $profit_loss_percent
            ];
        } catch (PDOException $e) {
            error_log("Error in getPortfolioSummary: " . $e->getMessage());
            return [
                'total_assets' => 0,
                'total_value' => 0,
                'total_investment' => 0,
                'profit_loss' => 0,
                'profit_loss_percent' => 0
            ];
        }
    }

    public function recalculateAssetTotals($asset_id, $user_id) {
    try {
        // Hitung ulang total quantity
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'buy' THEN quantity ELSE -quantity END), 0) as total_quantity,
                COALESCE(SUM(CASE WHEN type = 'buy' THEN total_price ELSE 0 END), 0) as total_investment
            FROM asset_transactions 
            WHERE asset_id = ? AND user_id = ?
        ");
        $stmt->execute([$asset_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Jika total_quantity minus, berarti ada error
        if ($result['total_quantity'] < 0) {
            // Log error
            error_log("ERROR: Asset $asset_id has negative quantity: " . $result['total_quantity']);
            return false;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error recalculating asset totals: " . $e->getMessage());
        return false;
    }
}
}
?>