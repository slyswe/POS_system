<?php
namespace App\Controllers;
use \Exception;

class SalesController
{
    public function __construct()
    {
        session_start();
        // No role check here to allow access to pos() and checkout() for cashiers
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            header('Location: /pos/public/login');
            exit;
        }
    }

    public function index()
    {
        // Restrict to admins only
        if ($_SESSION['user']['role'] !== 'admin') {
            // Redirect cashiers to POS page
            if ($_SESSION['user']['role'] === 'cashier') {
                header('Location: /pos/public/sales/pos');
                exit;
            }
            header('Location: /pos/public/login');
            exit;
        }

        require_once BASE_PATH . 'config/database.php';
        $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Connection failed: Please contact the administrator.");
        }

        // Initialize data arrays
        $kpi_data = [
            'total_sales' => 0,
            'transactions' => 0,
            'discounts' => 0,
            'refunds' => 0,
            'refund_count' => 0
        ];
        $sales_by_cashier = [];
        $sales_by_hour = [];
        $payment_methods = [];
        $top_products = [];
        $recent_sales = [];
        $active_cashiers = [];
        $pending_approvals = []; // Placeholder for future implementation

        // KPI: Total Sales
        $stmt = $conn->prepare("SELECT SUM(total) as total FROM sales WHERE discount = 0");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $kpi_data['total_sales'] = $result->fetch_assoc()['total'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for total_sales: " . $conn->error);
        }

        // KPI: Transactions
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sales");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $kpi_data['transactions'] = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for transactions: " . $conn->error);
        }

        // KPI: Discounts
        $stmt = $conn->prepare("SELECT SUM(discount) as total FROM sales WHERE discount > 0");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $kpi_data['discounts'] = $result->fetch_assoc()['total'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for discounts: " . $conn->error);
        }

        // KPI: Refunds
        $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(total) as total FROM sales WHERE total < 0");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $kpi_data['refunds'] = abs($row['total'] ?? 0);
            $kpi_data['refund_count'] = $row['count'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for refunds: " . $conn->error);
        }

        // Sales by Cashier
        $stmt = $conn->prepare("
            SELECT u.id, u.name, COUNT(s.id) as transactions, AVG(s.total) as avg_sale,
                   SUM(s.discount) as discounts, SUM(CASE WHEN s.total < 0 THEN s.total ELSE 0 END) as refunds,
                   SUM(CASE WHEN s.total > 0 THEN s.total ELSE 0 END) as total_sales
            FROM sales s
            JOIN users u ON s.user_id = u.id
            WHERE u.role = 'cashier'
            GROUP BY u.id, u.name
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $sales_by_cashier[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'transactions' => $row['transactions'],
                    'avg_sale' => $row['avg_sale'],
                    'discounts' => $row['discounts'],
                    'refunds' => abs($row['refunds']),
                    'total_sales' => $row['total_sales']
                ];
            }
            $stmt->close();
        } else {
            error_log("Prepare failed for sales_by_cashier: " . $conn->error);
        }

        // Sales by Hour
        $stmt = $conn->prepare("
            SELECT HOUR(timestamp) as hour, SUM(total) as total
            FROM sales
            WHERE DATE(timestamp) = CURDATE()
            GROUP BY HOUR(timestamp)
            ORDER BY hour
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $hours = array_fill(0, 24, 0);
            while ($row = $result->fetch_assoc()) {
                $hours[$row['hour']] = $row['total'];
            }
            $sales_by_hour = [
                'labels' => array_map(function($h) { return sprintf("%02d:00", $h); }, range(0, 23)),
                'data' => array_values($hours)
            ];
            $stmt->close();
        } else {
            error_log("Prepare failed for sales_by_hour: " . $conn->error);
        }

        // Payment Methods
        $stmt = $conn->prepare("
            SELECT payment_method, SUM(total) as total
            FROM sales
            WHERE total > 0
            GROUP BY payment_method
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $payment_methods[] = [
                    'method' => $row['payment_method'],
                    'total' => $row['total']
                ];
            }
            $stmt->close();
        } else {
            error_log("Prepare failed for payment_methods: " . $conn->error);
        }

        // Top-selling products (last 30 days)
        $stmt = $conn->prepare("
            SELECT p.name, SUM(si.quantity) as total_sold
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE s.timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $top_products[] = $row;
            }
            $stmt->close();
        } else {
            error_log("Prepare failed for top_products: " . $conn->error);
        }

        // Recent sales
        $stmt = $conn->prepare("
            SELECT s.id, s.total, s.timestamp, u.name as cashier
            FROM sales s
            LEFT JOIN users u ON s.user_id = u.id
            ORDER BY s.timestamp DESC
            LIMIT 5
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $recent_sales[] = $row;
            }
            $stmt->close();
        } else {
            error_log("Prepare failed for recent_sales: " . $conn->error);
        }

        // Active Cashiers
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.last_login
            FROM users u
            WHERE u.role = 'cashier' AND u.status = 'active'
            ORDER BY u.last_login DESC
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $active_cashiers[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'last_active' => $row['last_login'] ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : 'N/A'
                ];
            }
            $stmt->close();
        } else {
            error_log("Prepare failed for active_cashiers: " . $conn->error);
        }

        $conn->close();
        define('IN_CONTROLLER', true);
        include BASE_PATH . 'app/views/sales/sales_repo.php';
    }

    public function pos()
    {
        // Allow access to cashiers and admins
        if ($_SESSION['user']['role'] !== 'cashier' && $_SESSION['user']['role'] !== 'admin') {
            header('Location: /pos/public/login');
            exit;
        }

        require_once BASE_PATH . 'config/database.php';
        $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
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
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
            if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                $quantity = max(1, (int)($_POST['quantity'] ?? 1));
                $result = $conn->query("SELECT name, price, stock FROM products WHERE id = $product_id");
                if ($row = $result->fetch_assoc()) {
                    if ($row['stock'] >= $quantity) {
                        $_SESSION['cart'][$product_id] = [
                            'name' => $row['name'],
                            'price' => (float)$row['price'],
                            'quantity' => ($_SESSION['cart'][$product_id]['quantity'] ?? 0) + $quantity,
                            'stock' => (int)$row['stock']
                        ];
                        $message = 'Product added to cart.';
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => $message]);
                            exit;
                        }
                        $_SESSION['success'] = $message;
                    } else {
                        $message = 'Insufficient stock.';
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $message]);
                            exit;
                        }
                        $_SESSION['error'] = $message;
                    }
                } else {
                    $message = 'Product not found.';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $message]);
                        exit;
                    }
                    $_SESSION['error'] = $message;
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
        if (!defined('IN_CONTROLLER')) {
            define('IN_CONTROLLER', true);
        }
        include BASE_PATH . 'app/views/sales/pos.php';
    }

    public function checkout()
    {
        // Allow access to cashiers and admins
        if ($_SESSION['user']['role'] !== 'cashier' && $_SESSION['user']['role'] !== 'admin') {
            header('Location: /pos/public/login');
            exit;
        }

        if ($_SESSION['training_mode'] ?? false) {
            error_log("Checkout attempted in training mode. Sale not saved.");
            $_SESSION['success'] = 'Sale processed in training mode (not saved).';
            // Store cart data for receipt in training mode
            $_SESSION['receipt_data'] = [
                'cart' => $_SESSION['cart'] ?? [],
                'subtotal' => 0,
                'tax' => 0,
                'discount' => floatval($_POST['discount'] ?? 0),
                'total' => 0,
                'amount_paid' => floatval($_POST['amount_paid'] ?? 0),
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'cashier_name' => $_SESSION['user']['name'] ?? 'Unknown',
                'sale_id' => 'TRAINING-' . rand(1000, 9999),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            foreach ($_SESSION['cart'] as $item) {
                $_SESSION['receipt_data']['subtotal'] += $item['price'] * $item['quantity'];
            }
            $_SESSION['receipt_data']['tax'] = $_SESSION['receipt_data']['subtotal'] * 0.16;
            $_SESSION['receipt_data']['total'] = $_SESSION['receipt_data']['subtotal'] + $_SESSION['receipt_data']['tax'] - $_SESSION['receipt_data']['discount'];
            $_SESSION['cart'] = [];
            header('Location: /pos/public/sales/pos');
            exit;
        }

        require_once BASE_PATH . 'config/database.php';
        $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed in checkout: " . $conn->connect_error);
            $_SESSION['error'] = 'Database connection failed. Please try again.';
            header('Location: /pos/public/sales/pos');
            exit;
        }

        // Validate user_id
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : ($_SESSION['user']['id'] ?? null);
        if (!$userId) {
            error_log("Invalid or missing user_id in checkout.");
            $_SESSION['error'] = 'User authentication failed. Please log in again.';
            $conn->close();
            header('Location: /pos/public/login');
            exit;
        }

        $customerId = isset($_POST['customer_id']) && $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null;
        $discount = floatval($_POST['discount'] ?? 0);
        $amountPaid = floatval($_POST['amount_paid'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $cart = $_SESSION['cart'] ?? [];

        if (empty($cart)) {
            error_log("Checkout attempted with empty cart.");
            $_SESSION['error'] = 'Cart is empty.';
            $conn->close();
            header('Location: /pos/public/sales/pos');
            exit;
        }

        // Calculate subtotal and total
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax = $subtotal * 0.16;
        $total = $subtotal + $tax - $discount;

        if ($amountPaid < $total) {
            error_log("Checkout failed: Insufficient amount paid. Paid: $amountPaid, Required: $total");
            $_SESSION['error'] = 'Insufficient amount paid.';
            $conn->close();
            header('Location: /pos/public/sales/pos');
            exit;
        }

        // Begin transaction
        $conn->begin_transaction();
        try {
            // Verify user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                throw new Exception("Invalid user_id: $userId");
            }
            $stmt->close();

            // Insert sale
            $query = "INSERT INTO sales (user_id, total, payment_method, timestamp, customer_id, discount) 
                    VALUES (?, ?, ?, NOW(), ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for sales insert: " . $conn->error);
            }
            $stmt->bind_param("idssd", $userId, $total, $paymentMethod, $customerId, $discount);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert sale: " . $stmt->error);
            }
            $saleId = $conn->insert_id;
            $stmt->close();

            // Insert sale items
            $query = "INSERT INTO sale_items (sale_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for sale_items insert: " . $conn->error);
            }
            foreach ($cart as $productId => $item) {
                $productId = (int)$productId;
                // Verify product exists and has sufficient stock
                $checkStmt = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
                $checkStmt->bind_param("i", $productId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if ($row['stock'] < $item['quantity']) {
                        throw new Exception("Insufficient stock for product ID $productId");
                    }
                } else {
                    throw new Exception("Product ID $productId not found");
                }
                $checkStmt->close();

                $stmt->bind_param("iiid", $saleId, $productId, $item['quantity'], $item['price']);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert sale item for product ID $productId: " . $stmt->error);
                }

                // Update product stock
                $updateQuery = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                if ($updateStmt === false) {
                    throw new Exception("Prepare failed for stock update: " . $conn->error);
                }
                $updateStmt->bind_param("ii", $item['quantity'], $productId);
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update stock for product ID $productId: " . $updateStmt->error);
                }
                $updateStmt->close();
            }
            $stmt->close();

            $conn->commit();
            error_log("Sale completed successfully. Sale ID: $saleId, User ID: $userId, Total: $total");

            // Prepare receipt data
            $_SESSION['receipt_data'] = [
                'cart' => $cart,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'payment_method' => $paymentMethod,
                'cashier_name' => $_SESSION['user']['name'] ?? 'Unknown',
                'sale_id' => $saleId,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Clear cart
            $_SESSION['cart'] = [];
            $_SESSION['success'] = 'Sale completed successfully.';
        } catch (\Exception $e) {
            $conn->rollback();
            error_log("Checkout failed: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to process sale: ' . $e->getMessage();
        }

        $conn->close();
        header('Location: /pos/public/sales/pos');
        exit;
    }
}