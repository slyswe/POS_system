<?php
namespace App\Controllers;

class SalesController
{
    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
            header('Location: /pos/public/login');
            exit;
        }
    }

    public function pos()
    {
        require_once BASE_PATH . 'config/database.php';
        $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Connection failed: Please contact the administrator.");
        }

        // Fetch categories
        $categories = [];
        $result = $conn->query("SELECT id, name FROM categories ORDER BY name");
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        // Fetch products
        $products = [];
        $result = $conn->query("SELECT id, name, price, stock, category_id FROM products ORDER BY name");
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        // Handle cart actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                $quantity = max(1, (int)($_POST['quantity'] ?? 1));
                $result = $conn->query("SELECT name, price, stock FROM products WHERE id = $product_id");
                if ($row = $result->fetch_assoc()) {
                    if ($row['stock'] >= $quantity) {
                        $_SESSION['cart'][$product_id] = [
                            'name' => $row['name'],
                            'price' => $row['price'],
                            'quantity' => ($_SESSION['cart'][$product_id]['quantity'] ?? 0) + $quantity,
                            'stock' => $row['stock']
                        ];
                        $_SESSION['success'] = 'Product added to cart.';
                    } else {
                        $_SESSION['error'] = 'Insufficient stock.';
                    }
                }
                header('Location: /pos/public/sales/pos');
                exit;
            } elseif (isset($_POST['decrease_qty']) && isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                if (isset($_SESSION['cart'][$product_id]) && $_SESSION['cart'][$product_id]['quantity'] > 0) {
                    $_SESSION['cart'][$product_id]['quantity']--;
                    if ($_SESSION['cart'][$product_id]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$product_id]);
                    }
                }
                header('Location: /pos/public/sales/pos');
                exit;
            } elseif (isset($_POST['increase_qty']) && isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                $result = $conn->query("SELECT stock FROM products WHERE id = $product_id");
                if ($row = $result->fetch_assoc()) {
                    if (isset($_SESSION['cart'][$product_id]) && $_SESSION['cart'][$product_id]['quantity'] < $row['stock']) {
                        $_SESSION['cart'][$product_id]['quantity']++;
                    } else {
                        $_SESSION['error'] = 'Cannot increase quantity beyond stock.';
                    }
                }
                header('Location: /pos/public/sales/pos');
                exit;
            } elseif (isset($_POST['remove_from_cart']) && isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                unset($_SESSION['cart'][$product_id]);
                $_SESSION['success'] = 'Product removed from cart.';
                header('Location: /pos/public/sales/pos');
                exit;
            }
        } elseif (isset($_GET['cancel_sale'])) {
            $_SESSION['cart'] = [];
            $_SESSION['success'] = 'Sale cancelled.';
            header('Location: /pos/public/sales/pos');
            exit;
        }

        $conn->close();
        define('IN_CONTROLLER', true);
        include BASE_PATH . 'app/views/sales/pos.php';
    }

    public function checkout()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Basic checkout logic
            $discount = max(0, (float)($_POST['discount'] ?? 0));
            $amount_paid = max(0, (float)($_POST['amount_paid'] ?? 0));
            $payment_method = $_POST['payment_method'] ?? 'cash';
            $receipt_method = $_POST['receipt_method'] ?? 'print';

            if (empty($_SESSION['cart'])) {
                $_SESSION['error'] = 'Cart is empty.';
                header('Location: /pos/public/sales/pos');
                exit;
            }

            // Placeholder: Save sale to database
            $_SESSION['success'] = 'Sale completed successfully.';
            $_SESSION['cart'] = [];
        }
        header('Location: /pos/public/sales/pos');
        exit;
    }
}