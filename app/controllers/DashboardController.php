<?php
namespace App\Controllers;

class DashboardController
{
    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            header('Location: /pos/public/login');
            exit;
        }
    }

    public function index()
    {
        // Database connection using config
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
        $result = $conn->query("SELECT SUM(total) as total FROM sales");
        $stats['total_sales'] = $result->fetch_assoc()['total'] ?? 0;

        // Total users
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $stats['total_users'] = $result->fetch_assoc()['count'] ?? 0;

        // Inventory items
        $result = $conn->query("SELECT COUNT(*) as count FROM products");
        $stats['inventory_items'] = $result->fetch_assoc()['count'] ?? 0;

        // Total stock
        $result = $conn->query("SELECT SUM(stock) as total FROM products");
        $stats['total_stock'] = $result->fetch_assoc()['total'] ?? 0;

        // Low stock items (threshold < 10)
        $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock < 10");
        $stats['low_stock_items'] = $result->fetch_assoc()['count'] ?? 0;

        // Daily sales and transactions
        $today = date('Y-m-d');
        $result = $conn->query("SELECT SUM(total) as total, COUNT(*) as count FROM sales WHERE DATE(timestamp) = '$today'");
        $row = $result->fetch_assoc();
        $stats['daily_sales'] = $row['total'] ?? 0;
        $stats['daily_transactions'] = $row['count'] ?? 0;

        // Top-selling products (last 30 days)
        $top_products = [];
        $result = $conn->query("
            SELECT p.name, SUM(si.quantity) as total_sold
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE s.timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        while ($row = $result->fetch_assoc()) {
            $top_products[] = $row;
        }

        // Recent sales
        $recent_sales = [];
        $result = $conn->query("
            SELECT s.id, s.total, s.timestamp, u.name as cashier
            FROM sales s
            LEFT JOIN users u ON s.user_id = u.id
            ORDER BY s.timestamp DESC
            LIMIT 5
        ");
        while ($row = $result->fetch_assoc()) {
            $recent_sales[] = $row;
        }

        // Sales chart data (last 7 days)
        $chart_labels = [];
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('M d', strtotime($date));
            $result = $conn->query("SELECT SUM(total) as total FROM sales WHERE DATE(timestamp) = '$date'");
            $chart_data[] = $result->fetch_assoc()['total'] ?? 0;
        }

        $conn->close();

        define('IN_CONTROLLER', true);
        include BASE_PATH . 'app/views/admin/dashboard.php';
    }
}