<?php
require_once 'Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['created_at'] = $user['created_at'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function register($name, $email, $password) {
        try {
            // Check if email exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return false;
            }
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password) 
                VALUES (?, ?, ?)
            ");
            
            if ($stmt->execute([$name, $email, $hashed_password])) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Register error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, created_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function update($id, $name, $email) {
        try {
            // Check if email already used by another user
            $stmt = $this->db->prepare("
                SELECT id FROM users 
                WHERE email = ? AND id != ?
            ");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                return false; // Email already used
            }
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET name = ?, email = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$name, $email, $id]);
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updatePassword($id, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$hashed_password, $id]);
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }
}
?>