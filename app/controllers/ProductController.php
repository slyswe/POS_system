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

    public function index()
    {
        if (!in_array($_SESSION['user']['role'], ['admin'])) {
            header('Location: /pos/public/login');
            exit;
        }
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 10;
        $filter = filter_input(INPUT_GET, 'filter') === 'low_stock' ? 'low_stock' : '';
        $products = $this->model->getAllProducts($page, $perPage, $filter);
        $total = $this->model->getTotalProducts($filter);
        $viewFile = ($_SESSION['user']['role'] === 'admin')
            ? BASE_PATH . 'app/views/products/index.php'
            : BASE_PATH . 'app/views/products/inventory_clerk_dashboard.php';
        require_once $viewFile;
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
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($input['id'], FILTER_VALIDATE_INT);
        $field = filter_var($input['field'], FILTER_SANITIZE_STRING);
        $value = $input['value'];

        if (!in_array($field, ['name', 'price', 'stock'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid field']);
            exit;
        }

        if ($field === 'name') {
            $value = filter_var($value, FILTER_SANITIZE_STRING);
        } elseif ($field === 'price') {
            $value = filter_var($value, FILTER_VALIDATE_FLOAT) ?: 0.0;
        } elseif ($field === 'stock') {
            $value = filter_var($value, FILTER_VALIDATE_INT) ?: 0;
        }

        if ($value === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid value for ' . $field]);
            exit;
        }

        $data = [$field => $value];
        if ($this->model->updateProduct($id, $data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed.']);
        }
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
        fputcsv($output, ['ID', 'Name', 'Category', 'Price', 'Barcode', 'Stock']);
        foreach ($products as $row) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['category_name'] ?? 'Uncategorized',
                $row['price'],
                $row['barcode'] ?? 'N/A',
                $row['stock']
            ]);
        }
        fclose($output);
        exit;
    }

    public function adjustStock()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'inventory_clerk') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($input['id'], FILTER_VALIDATE_INT);
        $change = filter_var($input['change'], FILTER_VALIDATE_INT);
        $reason = filter_var($input['reason'], FILTER_SANITIZE_STRING);
        if (!$id || !$change || !$reason) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        $newStock = $this->model->adjustProductStock($id, $change, $reason);
        if ($newStock !== false) {
            echo json_encode(['success' => true, 'newStock' => $newStock]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Stock adjustment failed']);
        }
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

    $query = "SELECT id, name, price, stock FROM products WHERE 1=1";
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
}