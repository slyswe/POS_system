<?php
namespace App\Controllers;

use App\Models\ProductModel;

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once BASE_PATH . 'app/models/ProductModel.php';

class ProductController
{
    private $model;

    public function __construct()
    {
        session_start();
        // Allow cashier role for filter endpoint, but restrict others to admin/inventory_clerk
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory_clerk', 'cashier'])) {
            header('Location: /pos/public/login');
            exit;
        }
        $this->model = new ProductModel();
    }

    public function index()
    {
        if (!in_array($_SESSION['user']['role'], ['admin'])) {
                header('Location: /pos/public/login');
                exit;
            }
            
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
            $perPage = 10;
            $filter = filter_input(INPUT_GET, 'filter') === 'low_stock' ? 'low_stock' : '';
            
            // Get approved products
            $products = $this->model->getAllProducts($page, $perPage, $filter);
            $total = $this->model->getTotalProducts($filter);
            
            // Get pending data
            $pendingProducts = $this->model->getPendingProducts();
            $pendingAdjustments = $this->model->getPendingAdjustments();
            $pendingCostChanges = $this->model->getPendingCostChanges();
            
            $pendingAdjustmentCount = count($pendingAdjustments);
            $pendingCostChangeCount = count($pendingCostChanges);
            
            $viewFile = BASE_PATH . 'app/views/products/index.php';
            require_once $viewFile;
    }

public function approveProduct($id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $price = filter_var($input['price'], FILTER_VALIDATE_FLOAT);
    
    if ($price === false || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid price']);
        exit;
    }
    
    $success = $this->model->approveProduct($id, $price, $_SESSION['user']['id']);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Approval failed']);
    }
    exit;
}

public function rejectProduct($id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $reason = filter_var($input['reason'], FILTER_SANITIZE_STRING);
    
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Reason is required']);
        exit;
    }
    
    $success = $this->model->rejectProduct($id, $reason, $_SESSION['user']['id']);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rejection failed']);
    }
    exit;
}

    public function inventoryClerkDashboard()
    {
        if ($_SESSION['user']['role'] !== 'inventory_clerk') {
            header('Location: /pos/public/login');
            exit;
        }
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 10;
        $filter = filter_input(INPUT_GET, 'filter') === 'low_stock' ? 'low_stock' : '';
        $products = $this->model->getAllProducts($page, $perPage, $filter);
        $total = $this->model->getTotalProducts($filter);
        $suppliers = $this->getSuppliers();
        $products = $this->model->getAllProducts($page, $perPage, $filter);

        define('IN_CONTROLLER', true);
        include BASE_PATH . 'app/views/products/inventory_clerk_dashboard.php';
    }

    public function getNextInvoiceNumber()
{
    $date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING) ?: date('Ymd');
    $invoiceNumber = 'INV-' . $date . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'invoiceNumber' => $invoiceNumber]);
    exit;
}

public function fetchSuppliers()
{
    $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $suppliers = [];
    $result = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
        $result->free();
    }
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'suppliers' => $suppliers]);
    exit;
}

    private function getSuppliers()
    {
        $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return [];
        }
        
        $suppliers = [];
        $result = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row;
            }
            $result->free();
        }
        $conn->close();
        
        return $suppliers;
    }

    

    public function create()
{
    $error = '';
    require_once BASE_PATH . 'app/models/CategoryModel.php';
    $categoryModel = new \App\Models\CategoryModel();
    $categories = $categoryModel->getAllCategories();

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
            'price' => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?: 0.0,
            'cost_price' => filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT) ?: 0.0,
            'stock' => filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT) ?: 0,
            'barcode' => filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING) ?: null
        ];

        $new_category = filter_input(INPUT_POST, 'new_category', FILTER_SANITIZE_STRING);
        if ($new_category && !$data['category_id']) {
            $category_id = $categoryModel->createCategory(['name' => $new_category]);
            if ($category_id) {
                $data['category_id'] = $category_id;
            } else {
                $error = "Failed to create new category.";
            }
        }

        if (!$data['category_id']) {
            $error = "Please select a category or enter a new one.";
        }

        if (!$error && $this->model->createProduct($data)) {
            $_SESSION['success'] = 'Product created successfully.';
            header("Location: /pos/public/products");
            exit;
        } else {
            $error = $error ?: "Failed to add product.";
        }
    }
    require_once BASE_PATH . 'app/views/products/create.php';
}

    public function edit($id)
{
    $error = '';
    $product = $this->model->getProductById($id);
    if (!$product) {
        $_SESSION['error'] = 'Product not found.';
        header("Location: /pos/public/products");
        exit;
    }
    
    require_once BASE_PATH . 'app/models/CategoryModel.php';
    $categoryModel = new \App\Models\CategoryModel();
    $categories = $categoryModel->getAllCategories();

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
            'price' => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?: 0.0,
            'cost_price' => filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT) ?: 0.0,
            'stock' => filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT) ?: 0,
            'barcode' => filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING) ?: null
        ];
        if ($this->model->updateProduct($id, $data)) {
            $_SESSION['success'] = 'Product updated successfully.';
            header("Location: /pos/public/products");
            exit;
        } else {
            $error = "Failed to update product.";
        }
    }
    require_once BASE_PATH . 'app/views/products/edit.php';
}

    public function delete($id)
    {
        if ($_SESSION['user']['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        if ($this->model->deleteProduct($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete product.']);
        }
        exit;
    }

    public function inlineUpdate()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $id = filter_var($input['id'], FILTER_VALIDATE_INT);
    $field = filter_var($input['field'], FILTER_SANITIZE_STRING);
    $value = $input['value'];
    
    if (!$id || !$field) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid ID or field']);
        exit;
    }
    
    // Validate value based on field type
    if ($field === 'price' || $field === 'cost_price') {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid price value']);
            exit;
        }
    } elseif ($field === 'stock') {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || $value < 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid stock value']);
            exit;
        }
    } else {
        $value = filter_var($value, FILTER_SANITIZE_STRING);
    }
    
    $success = $this->model->updateSingleField($id, $field, $value);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $success ? 'Update successful' : 'Update failed']);
    exit;
}

    public function export()
{
    if ($_SESSION['user']['role'] !== 'admin') {
        header('Location: /pos/public/login');
        exit;
    }
    $products = $this->model->getAllProducts(1, PHP_INT_MAX);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="products.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Category', 'Price', 'Cost Price', 'Barcode', 'Stock']);
    foreach ($products as $row) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['category_name'] ?? 'Uncategorized',
            $row['price'],
            $row['cost_price'] ?? 0.00,
            $row['barcode'] ?? 'N/A',
            $row['stock']
        ]);
    }
    fclose($output);
    exit;
}

    
    public function filter()
    {
       error_log("ProductController::filter called");
    
    // Suppress PHP error output to ensure clean JSON
    ini_set('display_errors', 0);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Use htmlspecialchars for sanitization instead of FILTER_SANITIZE_STRING
    $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'all';
    $category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
    error_log("Filter type: $type, Category ID: " . ($category_id ?: 'null'));

    $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $query = "SELECT id, name, price, cost_price, stock FROM products WHERE 1=1";
    $params = [];
    $types = '';

    if ($category_id) {
        $query .= " AND category_id = ?";
        $params[] = $category_id;
        $types .= 'i';
    } elseif ($type === 'frequent') {
        $query = "
            SELECT p.id, p.name, p.price, p.stock
            FROM products p
            LEFT JOIN sale_items si ON p.id = si.product_id
            GROUP BY p.id
            ORDER BY COUNT(si.id) DESC
            LIMIT 20";
    } elseif ($type === 'new') {
        $query .= " ORDER BY id DESC LIMIT 20";
    } else {
        $query .= " ORDER BY name";
    }

    error_log("Executing query: $query");
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
        $conn->close();
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Query execution failed']);
        $conn->close();
        exit;
    }

    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'stock' => (int)$row['stock']
        ];
    }

    error_log("Fetched " . count($products) . " products");

    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'products' => $products]);
    exit;
    }
    public function search() 
    {
        $searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

        header('Content-Type: application/json');

        if (empty($searchTerm)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Search term is required'
            ]);
            exit;
        }

        $products = $this->model->search($searchTerm);

        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
        exit;
    }
    private function jsonResponse($success, $data = [], $message = '')
    {
      header('Content-Type: application/json');
      echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
      ]);
      exit;
    }

    //php API endpoint and refresh function
    public function getValuationData() {
        try {
            $products = $this->model->getAllProducts(1, PHP_INT_MAX);
            
            if (empty($products)) {
                return $this->jsonResponse(true, [
                    'total' => 0,
                    'categories' => []
                ]);
            }
            
            $data = [
                'total' => 0,
                'categories' => []
            ];
            
            foreach ($products as $product) {
                $category = $product['category_name'] ?? 'Uncategorized';
                $cost = (float)($product['cost_price'] ?? 0);
                $stock = (int)($product['stock'] ?? 0);
                $value = $cost * $stock;
                
                $data['total'] += $value;
                
                if (!isset($data['categories'][$category])) {
                    $data['categories'][$category] = 0;
                }
                $data['categories'][$category] += $value;
            }
            
            return $this->jsonResponse(true, $data);
            
        } catch (\Exception $e) {
            error_log("Valuation error: " . $e->getMessage());
            return $this->jsonResponse(false, [], "Error calculating valuation");
        }
    }
    public function submitAdjustmentRequest()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'inventory_clerk') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $data = [
        'product_id' => filter_var($input['product_id'], FILTER_VALIDATE_INT),
        'batch_id' => isset($input['batch_id']) ? filter_var($input['batch_id'], FILTER_VALIDATE_INT) : null,
        'change_type' => in_array($input['change_type'], ['add', 'remove']) ? $input['change_type'] : 'add',
        'change_amount' => filter_var($input['change_amount'], FILTER_VALIDATE_INT),
        'reason' => filter_var($input['reason'], FILTER_SANITIZE_STRING),
        'other_reason' => isset($input['other_reason']) ? filter_var($input['other_reason'], FILTER_SANITIZE_STRING) : null,
        'supplier_id' => isset($input['supplier_id']) ? filter_var($input['supplier_id'], FILTER_VALIDATE_INT) : null,
        'unit_cost' => isset($input['unit_cost']) ? filter_var($input['unit_cost'], FILTER_VALIDATE_FLOAT) : null,
        'invoice_ref' => isset($input['invoice_ref']) ? filter_var($input['invoice_ref'], FILTER_SANITIZE_STRING) : null,
        'submitted_by' => $_SESSION['user']['id']
    ];
    
    $success = $this->model->createAdjustmentRequest($data);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Adjustment request submitted' : 'Failed to submit request'
    ]);
    exit;
}

public function submitCostChangeRequest()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'inventory_clerk') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $data = [
        'product_id' => filter_var($input['product_id'], FILTER_VALIDATE_INT),
        'new_cost' => filter_var($input['new_cost'], FILTER_VALIDATE_FLOAT),
        'reason' => filter_var($input['reason'], FILTER_SANITIZE_STRING),
        'submitted_by' => $_SESSION['user']['id']
    ];
    
    // Get current cost for record
    $product = $this->model->getProductById($data['product_id']);
    $data['old_cost'] = $product['cost_price'];
    
    $success = $this->model->createCostChangeRequest($data);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Cost change request submitted' : 'Failed to submit request'
    ]);
    exit;
}

public function approveAdjustment($id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $notes = filter_var($input['notes'], FILTER_SANITIZE_STRING);
    $approvedBy = $_SESSION['user']['id'];
    
    $success = $this->model->approveAdjustment($id, $approvedBy, $notes);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Adjustment approved' : 'Approval failed'
    ]);
    exit;
}

public function rejectAdjustment($id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $reason = filter_var($input['reason'], FILTER_SANITIZE_STRING);
    $approvedBy = $_SESSION['user']['id'];
    
    $success = $this->model->rejectAdjustment($id, $approvedBy, $reason);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Adjustment rejected' : 'Rejection failed'
    ]);
    exit;
}

public function approveCostChange($id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $notes = filter_var($input['notes'], FILTER_SANITIZE_STRING);
    $approvedBy = $_SESSION['user']['id'];
    
    $success = $this->model->approveCostChange($id, $approvedBy, $notes);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Cost change approved' : 'Approval failed'
    ]);
    exit;
}

public function rejectCostChange($id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $reason = filter_var($input['reason'], FILTER_SANITIZE_STRING);
    $approvedBy = $_SESSION['user']['id'];
    
    $success = $this->model->rejectCostChange($id, $approvedBy, $reason);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Cost change rejected' : 'Rejection failed'
    ]);
    exit;
}

}