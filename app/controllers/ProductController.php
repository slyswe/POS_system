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
        if (!isset($_SESSION['user'])) {
            header('Location: /pos/public/login');
            exit;
        }
        $this->model = new ProductModel();
    }

    public function index()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 10;
        $filter = filter_input(INPUT_GET, 'filter') === 'low_stock' ? 'low_stock' : '';

        $products = $this->model->getAllProducts($page, $perPage, $filter);
        $total = $this->model->getTotalProducts($filter);

        $viewFile = ($_SESSION['user']['role'] === 'Admin')
            ? BASE_PATH . 'app/views/products/index.php'
            : BASE_PATH . 'app/views/products/inventory_clerk_dashboard.php';
        require_once $viewFile;
    }

    public function create()
    {
        $error = '';
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = [
                'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
                'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
                'price' => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?: 0.0,
                'stock' => filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT) ?: 0,
                'barcode' => filter_input(INPUT_POST, 'barcode', FILTER_SANITIZE_STRING) ?: null
            ];
            if ($this->model->createProduct($data)) {
                $_SESSION['success'] = 'Product created successfully.';
                header("Location: /pos/public/products");
                exit;
            } else {
                $error = "Failed to add product.";
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
        if ($_SESSION['user']['role'] !== 'Admin') {
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user']['role'] !== 'Admin') {
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
        if ($_SESSION['user']['role'] !== 'Admin') {
            header('Location: /pos/public/login');
            exit;
        }
        $products = $this->model->getAllProducts(1, PHP_INT_MAX); // Get all products
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
}