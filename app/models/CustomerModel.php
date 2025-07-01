<?php
namespace App\Models;

use Exception;

require_once __DIR__ . '/../config/database.php';

class CustomerModel {
    protected $db;

    public function __construct() {
        $this->db = getDbConnection();
        
        // Ensure connection is good
        if ($this->db->connect_error) {
            throw new Exception("Database connection failed: " . $this->db->connect_error);
        }
    }

    public function findCustomer($searchTerm) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM customers 
                WHERE phone = ? OR email = ?
                LIMIT 1
            ");
            
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("findCustomer error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getPurchaseHistory($customerId, $limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, total_amount, purchase_date AS created_at, items_count 
                FROM customer_purchases
                WHERE customer_id = ?
                ORDER BY purchase_date DESC
                LIMIT ?
            ");
            
            $stmt->bind_param("ii", $customerId, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("getPurchaseHistory error: " . $e->getMessage());
            throw $e;
        }
    }

    public function createCustomer($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO customers 
                (name, phone, email, address, loyalty_points, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, NOW(), NOW())
            ");
            
            $email = $data['email'] ?? null;
            $address = $data['address'] ?? null;
            $stmt->bind_param(
                "ssss", 
                $data['name'], 
                $data['phone'], 
                $email, 
                $address
            );
            
            $stmt->execute();
            return $this->db->insert_id;
            
        } catch (Exception $e) {
            error_log("createCustomer error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateCustomer($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE customers SET 
                name = ?, 
                phone = ?, 
                email = ?, 
                address = ?, 
                updated_at = NOW()
                WHERE id = ?
            ");
            
            $email = $data['email'] ?? null;
            $address = $data['address'] ?? null;
            $stmt->bind_param(
                "ssssi", 
                $data['name'], 
                $data['phone'], 
                $email, 
                $address,
                $id
            );
            
            $stmt->execute();
            return $stmt->affected_rows > 0;
            
        } catch (Exception $e) {
            error_log("updateCustomer error: " . $e->getMessage());
            throw $e;
        }
    }

    public function autocomplete($searchTerm) {
        try {
            if (strlen($searchTerm) < 3) {
                return [];
            }
            
            $searchParam = "%{$searchTerm}%";
            $stmt = $this->db->prepare("
                SELECT id, name, phone, email 
                FROM customers 
                WHERE phone LIKE ? OR email LIKE ?
                LIMIT 5
            ");
            
            $stmt->bind_param("ss", $searchParam, $searchParam);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("autocomplete error: " . $e->getMessage());
            return [];
        }
    }
}