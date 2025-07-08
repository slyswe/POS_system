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

    // Update createProduct to handle clerk submissions
public function createProduct($data)
{
    try {
        $status = ($_SESSION['user']['role'] === 'inventory_clerk') ? 'pending' : 'approved';
        $submittedBy = ($_SESSION['user']['role'] === 'inventory_clerk') ? $_SESSION['user']['id'] : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO products 
            (name, category_id, price, cost_price, stock, barcode, status, submitted_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $categoryId = $data['category_id'] ?: null;
        $barcode = $data['barcode'] ?: null;
        $costPrice = $data['cost_price'] ?? null;
        
        $stmt->bind_param(
            "sidddssi", 
            $data['name'], 
            $categoryId, 
            $data['price'], 
            $costPrice,
            $data['stock'], 
            $barcode,
            $status,
            $submittedBy
        );
        
        $success = $stmt->execute();
        $newId = $success ? $this->db->insert_id : 0;
        $stmt->close();
        
        return $newId > 0 ? $newId : false;
    } catch (Exception $e) {
        error_log("Error creating product: " . $e->getMessage());
        return false;
    }
}

    public function getProductById($id)
    {
        try {
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.category_id, c.name as category_name, 
                   p.price, p.cost_price, p.stock, p.barcode 
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
            SELECT p.id, p.name, c.name as category_name, c.id as category_id, 
                   p.price, p.cost_price, p.stock, p.barcode 
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
            SET name = ?, category_id = ?, price = ?, cost_price = ?, stock = ?, barcode = ? 
            WHERE id = ?
        ");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }
        $categoryId = $data['category_id'] ?: null;
        $barcode = $data['barcode'] ?: null;
        $costPrice = $data['cost_price'] ?? null;
        $stmt->bind_param(
            "sidddsi", 
            $data['name'], 
            $categoryId, 
            $data['price'], 
            $costPrice,
            $data['stock'], 
            $barcode,
            $id
        );
        $success = $stmt->execute();
        $stmt->close();
        $this->db->commit();
        return $success;
    } catch (Exception $e) {
        error_log("Error updating product: " . $e->getMessage());
        return false;
    }
}
    public function updateSingleField($id, $field, $value)
{
    try {
        // For admin, allow more fields to be updated directly
        $allowedFields = ['name', 'price', 'stock', 'cost_price', 'barcode'];
        if (!in_array($field, $allowedFields)) {
            throw new Exception("Invalid field: $field");
        }
        
        $this->db->begin_transaction();
        
        $query = "UPDATE products SET $field = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }
        
        // Bind parameters based on field type
        if ($field === 'price' || $field === 'cost_price') {
            $stmt->bind_param("di", $value, $id);
        } elseif ($field === 'stock') {
            $stmt->bind_param("ii", $value, $id);
        } else {
            $stmt->bind_param("si", $value, $id);
        }
        
        $success = $stmt->execute();
        $stmt->close();
        
        $this->db->commit();
        return $success;
    } catch (Exception $e) {
        $this->db->rollback();
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

    public function adjustProductStock($id, $change, $reason)
{
    try {
        $this->db->begin_transaction();
        $stmt = $this->db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $newStock = $row['stock'] + $change;
            if ($newStock < 0) {
                throw new Exception("Cannot reduce stock below zero");
            }
            $stmt = $this->db->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $newStock, $id);
            $stmt->execute();
            $stmt = $this->db->prepare("INSERT INTO stock_adjustments (product_id, change_amount, reason, adjusted_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $id, $change, $reason, $_SESSION['user']['id']);
            $stmt->execute();
            $this->db->commit();
            return $newStock;
        }
        $this->db->rollback();
        return false;
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Stock adjustment error: " . $e->getMessage());
        return false;
    }
}

public function createAdjustmentRequest($data)
{
    try {
        $stmt = $this->db->prepare("
            INSERT INTO pending_stock_adjustments 
            (product_id, batch_id, change_amount, change_type, reason, other_reason, 
             supplier_id, unit_cost, invoice_ref, submitted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiisssidsi",
            $data['product_id'],
            $data['batch_id'],
            $data['change_amount'],
            $data['change_type'],
            $data['reason'],
            $data['other_reason'],
            $data['supplier_id'],
            $data['unit_cost'],
            $data['invoice_ref'],
            $data['submitted_by']
        );
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error creating adjustment request: " . $e->getMessage());
        return false;
    }
}

public function createCostChangeRequest($data)
{
    try {
        $stmt = $this->db->prepare("
            INSERT INTO pending_cost_changes 
            (product_id, old_cost, new_cost, reason, submitted_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iddsi",
            $data['product_id'],
            $data['old_cost'],
            $data['new_cost'],
            $data['reason'],
            $data['submitted_by']
        );
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error creating cost change request: " . $e->getMessage());
        return false;
    }
}

public function search(string $term): array
{
    try {
        $likeTerm = '%' . $term . '%';
        $stmt = $this->db->prepare("SELECT * FROM products WHERE (name LIKE ? OR barcode LIKE ?) AND active = 1");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }
        $stmt->bind_param("ss", $likeTerm, $likeTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $products;
    } catch (Exception $e) {
        error_log("Error searching products: " . $e->getMessage());
        return [];
    }
}

public function getPendingProducts()
{
    try {
        $query = "
            SELECT p.*, c.name as category_name, u.name as submitted_by_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.submitted_by = u.id
            WHERE p.status = 'pending'
            ORDER BY p.created_at DESC
        ";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        error_log("Error fetching pending products: " . $e->getMessage());
        return [];
    }
}

public function approveProduct($id, $price, $approvedBy)
{
    try {
        $this->db->begin_transaction();
        
        $stmt = $this->db->prepare("
            UPDATE products 
            SET price = ?, status = 'approved', approved_by = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->bind_param("dii", $price, $approvedBy, $id);
        $success = $stmt->execute();
        
        if ($success && $stmt->affected_rows > 0) {
            $this->db->commit();
            return true;
        }
        
        $this->db->rollback();
        return false;
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Error approving product: " . $e->getMessage());
        return false;
    }
}

public function rejectProduct($id, $reason, $rejectedBy)
{
    try {
        $this->db->begin_transaction();
        
        $stmt = $this->db->prepare("
            UPDATE products 
            SET status = 'rejected', notes = ?, approved_by = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->bind_param("sii", $reason, $rejectedBy, $id);
        $success = $stmt->execute();
        
        if ($success && $stmt->affected_rows > 0) {
            $this->db->commit();
            return true;
        }
        
        $this->db->rollback();
        return false;
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Error rejecting product: " . $e->getMessage());
        return false;
    }
}

public function approveAdjustment($id, $approvedBy, $notes = null)
{
    try {
        $this->db->begin_transaction();
        
        // Get the adjustment
        $stmt = $this->db->prepare("
            SELECT * FROM pending_stock_adjustments 
            WHERE id = ? AND status = 'pending'
            FOR UPDATE
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $adjustment = $stmt->get_result()->fetch_assoc();
        
        if (!$adjustment) {
            throw new Exception("Adjustment not found or already processed");
        }
        
        // Update product stock
        $change = $adjustment['change_type'] === 'add' ? 
            $adjustment['change_amount'] : -$adjustment['change_amount'];
        
        $stmt = $this->db->prepare("
            UPDATE products 
            SET stock = stock + ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $change, $adjustment['product_id']);
        $stmt->execute();
        
        // Record the adjustment
        $stmt = $this->db->prepare("
            INSERT INTO stock_adjustments 
            (product_id, batch_id, change_amount, reason, adjusted_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiisi",
            $adjustment['product_id'],
            $adjustment['batch_id'],
            $change,
            $adjustment['reason'],
            $approvedBy
        );
        $stmt->execute();
        
        // Update the pending adjustment
        $stmt = $this->db->prepare("
            UPDATE pending_stock_adjustments 
            SET status = 'approved', 
                approved_by = ?,
                notes = ?,
                approved_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $approvedBy, $notes, $id);
        $stmt->execute();
        
        $this->db->commit();
        return true;
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Error approving adjustment: " . $e->getMessage());
        return false;
    }
}

public function rejectAdjustment($id, $approvedBy, $reason)
{
    try {
        $stmt = $this->db->prepare("
            UPDATE pending_stock_adjustments 
            SET status = 'rejected', 
                approved_by = ?,
                notes = ?,
                approved_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->bind_param("isi", $approvedBy, $reason, $id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error rejecting adjustment: " . $e->getMessage());
        return false;
    }
}

public function approveCostChange($id, $approvedBy, $notes = null)
{
    try {
        $this->db->begin_transaction();
        
        // Get the cost change request
        $stmt = $this->db->prepare("
            SELECT * FROM pending_cost_changes 
            WHERE id = ? AND status = 'pending'
            FOR UPDATE
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $change = $stmt->get_result()->fetch_assoc();
        
        if (!$change) {
            throw new Exception("Cost change not found or already processed");
        }
        
        // Update product cost
        $stmt = $this->db->prepare("
            UPDATE products 
            SET cost_price = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $change['new_cost'], $change['product_id']);
        $stmt->execute();
        
        // Record the cost change
        $stmt = $this->db->prepare("
            INSERT INTO cost_change_history 
            (product_id, old_cost, new_cost, reason, changed_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iddsi",
            $change['product_id'],
            $change['old_cost'],
            $change['new_cost'],
            $change['reason'],
            $approvedBy
        );
        $stmt->execute();
        
        // Update the pending cost change
        $stmt = $this->db->prepare("
            UPDATE pending_cost_changes 
            SET status = 'approved', 
                approved_by = ?,
                notes = ?,
                approved_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $approvedBy, $notes, $id);
        $stmt->execute();
        
        $this->db->commit();
        return true;
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Error approving cost change: " . $e->getMessage());
        return false;
    }
}

public function rejectCostChange($id, $approvedBy, $reason)
{
    try {
        $stmt = $this->db->prepare("
            UPDATE pending_cost_changes 
            SET status = 'rejected', 
                approved_by = ?,
                notes = ?,
                approved_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->bind_param("isi", $approvedBy, $reason, $id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error rejecting cost change: " . $e->getMessage());
        return false;
    }
}

public function getPendingAdjustments()
{
    try {
        $query = "
            SELECT psa.*, p.name as product_name, u.name as submitted_by_name
            FROM pending_stock_adjustments psa
            JOIN products p ON psa.product_id = p.id
            JOIN users u ON psa.submitted_by = u.id
            WHERE psa.status = 'pending'
            ORDER BY psa.submitted_at DESC
        ";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        error_log("Error fetching pending adjustments: " . $e->getMessage());
        return [];
    }
}

public function getPendingCostChanges()
{
    try {
        $query = "
            SELECT pcc.*, p.name as product_name, u.name as submitted_by_name
            FROM pending_cost_changes pcc
            JOIN products p ON pcc.product_id = p.id
            JOIN users u ON pcc.submitted_by = u.id
            WHERE pcc.status = 'pending'
            ORDER BY pcc.submitted_at DESC
        ";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        error_log("Error fetching pending cost changes: " . $e->getMessage());
        return [];
    }
}



}