<?php
namespace App\Models;

use \Exception;

class CategoryModel {
    private $db;

    public function __construct() {
        require_once dirname(__DIR__, 2) . '/config/database.php';
        $this->db = getDbConnection();
        if ($this->db->connect_error) {
            throw new Exception("Database connection failed: " . $this->db->connect_error);
        }
    }

    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }

    public function getAllCategories() {
        try {
            $result = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }

    public function createCategory($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO categories (name) VALUES (?)");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param("s", $data['name']);
            $success = $stmt->execute();
            $newId = $success ? $this->db->insert_id : 0;
            $stmt->close();
            return $newId > 0 ? $newId : false;
        } catch (Exception $e) {
            error_log("Error creating category: " . $e->getMessage());
            return false;
        }
    }

    public function getCategoryById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM categories WHERE id = ? LIMIT 1");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();
            $stmt->close();
            return $category ?: null;
        } catch (Exception $e) {
            error_log("Error fetching category by ID: " . $e->getMessage());
            return null;
        }
    }
}