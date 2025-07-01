<?php
namespace App\Controllers;

class DashboardController
{
    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /pos/public/login');
            exit;
        }
    }

    public function index()
    {
        require_once BASE_PATH . 'config/database.php';
        $conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Connection failed: Please contact the administrator.");
        }

        // Fetch stats
        $stats = [
            'total_sales' => 0,
            'total_users' => 0,
            'inventory_items' => 0,
            'total_stock' => 0,
            'low_stock_items' => 0,
            'daily_sales' => 0,
            'daily_transactions' => 0
        ];

        // Total sales
        $stmt = $conn->prepare("SELECT SUM(total) as total FROM sales");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_sales'] = $result->fetch_assoc()['total'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for total_sales: " . $conn->error);
        }

        // Total users
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_users'] = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for total_users: " . $conn->error);
        }

        // Inventory items
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['inventory_items'] = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for inventory_items: " . $conn->error);
        }

        // Total stock
        $stmt = $conn->prepare("SELECT SUM(stock) as total FROM products");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_stock'] = $result->fetch_assoc()['total'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for total_stock: " . $conn->error);
        }

        // Low stock items (threshold < 10)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE stock < 10");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['low_stock_items'] = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for low_stock_items: " . $conn->error);
        }

        // Daily sales and transactions
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT SUM(total) as total, COUNT(*) as count FROM sales WHERE DATE(timestamp) = ?");
        if ($stmt) {
            $stmt->bind_param("s", $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['daily_sales'] = $row['total'] ?? 0;
            $stats['daily_transactions'] = $row['count'] ?? 0;
            $stmt->close();
        } else {
            error_log("Prepare failed for daily_sales: " . $conn->error);
        }

        // Top-selling products (last 30 days)
        $top_products = [];
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
        $recent_sales = [];
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

        // Sales chart data (last 7 days)
        $chart_labels = [];
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('M d', strtotime($date));
            $stmt = $conn->prepare("SELECT SUM(total) as total FROM sales WHERE DATE(timestamp) = ?");
            if ($stmt) {
                $stmt->bind_param("s", $date);
                $stmt->execute();
                $result = $stmt->get_result();
                $chart_data[] = $result->fetch_assoc()['total'] ?? 0;
                $stmt->close();
            } else {
                error_log("Prepare failed for chart_data on $date: " . $conn->error);
                $chart_data[] = 0;
            }
        }

        $conn->close();
        define('IN_CONTROLLER', true);
        include BASE_PATH . 'app/views/admin/dashboard.php';
    }
}