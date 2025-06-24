<?php
namespace App\Models;

use Exception;

class ProductModel
{
    private $db;

    public function __construct()
    {
        require_once dirname(__DIR__, 2) . '/config/database.php';
        $this->db = getDbConnection();
        if ($this->db->connect_error) {
            throw new Exception("Database connection failed: " . $this->db->connect_error);
        }
    }

    public function __destruct()
    {
        if ($this->db) {
            $this->db->close();
        }
    }

    public function createProduct($data)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO products (name, category_id, price, stock, barcode) VALUES (?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $categoryId = $data['category_id'] ?: null;
            $barcode = $data['barcode'] ?: null;
            $stmt->bind_param("sidds", $data['name'], $categoryId, $data['price'], $data['stock'], $barcode);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error creating product: " . $e->getMessage());
            return false;
        }
    }

    public function getProductById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.category_id, c.name as category_name, p.price, p.stock, p.barcode 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? 
                LIMIT 1
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            return $product ?: null;
        } catch (Exception $e) {
            error_log("Error fetching product by ID: " . $e->getMessage());
            return null;
        }
    }

    public function getAllProducts($page = 1, $perPage = 10, $filter = '')
    {
        try {
            $offset = ($page - 1) * $perPage;
            $where = $filter === 'low_stock' ? 'WHERE p.stock < 10' : '';
            $query = "
                SELECT p.id, p.name, c.name as category_name, c.id as category_id, p.price, p.stock, p.barcode 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                $where 
                ORDER BY p.name ASC 
                LIMIT ?, ?
            ";
            $stmt = $this->db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param("ii", $offset, $perPage);
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $products;
        } catch (Exception $e) {
            error_log("Error fetching all products: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalProducts($filter = '')
    {
        try {
            $where = $filter === 'low_stock' ? 'WHERE stock < 10' : '';
            $query = "SELECT COUNT(*) as total FROM products $where";
            $result = $this->db->query($query);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->db->error);
            }
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log("Error fetching total products: " . $e->getMessage());
            return 0;
        }
    }

    public function updateProduct($id, $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE products 
                SET name = ?, category_id = ?, price = ?, stock = ?, barcode = ? 
                WHERE id = ?
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $categoryId = $data['category_id'] ?: null;
            $barcode = $data['barcode'] ?: null;
            $stmt->bind_param("siddsi", $data['name'], $categoryId, $data['price'], $data['stock'], $barcode, $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }

    public function updateSingleField($id, $field, $value)
    {
        try {
            if (!in_array($field, ['name', 'price', 'stock'])) {
                throw new Exception("Invalid field: $field");
            }
            $query = "UPDATE products SET $field = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            if ($field === 'price') {
                $stmt->bind_param("di", $value, $id);
            } elseif ($field === 'stock') {
                $stmt->bind_param("ii", $value, $id);
            } else {
                $stmt->bind_param("si", $value, $id);
            }
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error updating product field: " . $e->getMessage());
            return false;
        }
    }

    public function deleteProduct($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }
}