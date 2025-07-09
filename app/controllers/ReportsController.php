<?php

namespace App\Controllers;

use App\Models\SalesModel;

class ReportsController
{
    private $db;
    private $salesModel;
    

    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /pos/public/login');
            exit;
        }
        
        require_once dirname(__DIR__, 2) . '/config/database.php';
        $this->db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Database connection failed: " . $this->db->connect_error);
        }
        
        $this->salesModel = new SalesModel($this->db);
    }

    public function __destruct()
    {
        if ($this->db) {
            $this->db->close();
        }
    }

    public function profitLoss()

    {
         // Use default "all time" range if no parameters
        if (empty($_GET)) {
            $firstSale = $this->salesModel->getFirstSaleDate();
            $lastSale = $this->salesModel->getLastSaleDate();
            
            $_GET['range'] = 'custom';
            $_GET['start'] = $firstSale ?: date('Y-m-01');
            $_GET['end'] = $lastSale ?: date('Y-m-d');
        }

        $dateRange = $_GET['range'] ?? 'this_month';
        $startDate = $_GET['start'] ?? '';
        $endDate = $_GET['end'] ?? '';
        
        list($startDate, $endDate) = $this->calculateDateRange($dateRange, $startDate, $endDate);

        // DEBUG: Output the dates being used
        //error_log("Using date range: $startDate to $endDate");
        
        // Get all data through the SalesModel
        $salesData = $this->salesModel->getSalesSummary($startDate, $endDate);
        
        // Get additional data
        $salesData['by_category'] = $this->salesModel->getSalesByCategory($startDate, $endDate);
        $salesData['by_payment'] = $this->salesModel->getSalesByPaymentMethod($startDate, $endDate);
        $salesData['top_products'] = $this->salesModel->getTopSellingProducts($startDate, $endDate);
        
        // Get expenses data
        $expensesData = $this->getExpensesData($startDate, $endDate);
        
        // Prepare view data - ensure ALL fields are included
        $data = [
            'title' => 'Profit & Loss Report',
            'dateRange' => $dateRange,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'salesData' => $salesData, // Contains all the counts
            'expensesData' => $expensesData,
            'netProfit' => $salesData['net_profit'],
            'netProfitClass' => $salesData['net_profit'] >= 0 ? 'text-success' : 'text-danger',
            'user_name' => $_SESSION['user']['name'] ?? '',
            'role' => $_SESSION['user']['role'] ?? ''
        ];
        
        include BASE_PATH . 'app/views/reports/profit_loss.php';
    }

    private function calculateDateRange($range, $customStart, $customEnd)
    {
        // Debug input
        error_log("Calculating range: $range, Custom Start: $customStart, Custom End: $customEnd");
        
        if (!empty($customStart) && !empty($customEnd)) {
            // Debug custom dates
            error_log("Using custom dates: $customStart to $customEnd");
            return [$customStart, $customEnd];
        }

        $today = date('Y-m-d');
        $result = [$today, $today]; // Default to today
        
        switch ($range) {
            case 'today':
                error_log("Date range: Today ($today)");
                break;
                
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $result = [$yesterday, $yesterday];
                error_log("Date range: Yesterday ($yesterday)");
                break;
                
            case 'this_week':
                $monday = date('Y-m-d', strtotime('monday this week'));
                $result = [$monday, $today];
                error_log("Date range: This week ($monday to $today)");
                break;
                
            case 'last_week':
                $monday = date('Y-m-d', strtotime('monday last week'));
                $sunday = date('Y-m-d', strtotime('sunday last week'));
                $result = [$monday, $sunday];
                error_log("Date range: Last week ($monday to $sunday)");
                break;
                
            case 'this_month':
                $firstDay = date('Y-m-01');
                $result = [$firstDay, $today];
                error_log("Date range: This month ($firstDay to $today)");
                break;
                
            case 'last_month':
                $firstDay = date('Y-m-01', strtotime('first day of last month'));
                $lastDay = date('Y-m-t', strtotime('last day of last month'));
                $result = [$firstDay, $lastDay];
                error_log("Date range: Last month ($firstDay to $lastDay)");
                break;
                
            case 'this_year':
                $firstDay = date('Y-01-01');
                $result = [$firstDay, $today];
                error_log("Date range: This year ($firstDay to $today)");
                break;
                
            case 'last_year':
                $firstDay = date('Y-01-01', strtotime('-1 year'));
                $lastDay = date('Y-12-31', strtotime('-1 year'));
                $result = [$firstDay, $lastDay];
                error_log("Date range: Last year ($firstDay to $lastDay)");
                break;
                
            default:
                error_log("Using default range (today)");
        }
        
        return $result;
    }

    private function getSalesData($startDate, $endDate)
    {
        $data = [
            'total_sales' => 0,
            'gross_profit' => 0,
            'discounts' => 0,
            'refunds' => 0,
            'by_category' => [],
            'by_payment' => [],
            'top_products' => []
        ];

        // Total sales
        $query = "SELECT SUM(total) as total FROM sales 
                  WHERE timestamp BETWEEN ? AND ? AND total > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data['total_sales'] = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Gross profit (sales - cost)
        $query = "SELECT SUM(si.price * si.quantity) as revenue, 
                         SUM(p.cost_price * si.quantity) as cost
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.id
                  JOIN sales s ON si.sale_id = s.id
                  WHERE s.timestamp BETWEEN ? AND ? AND s.total > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $data['gross_profit'] = ($row['revenue'] ?? 0) - ($row['cost'] ?? 0);
        $stmt->close();

        // Discounts
        $query = "SELECT SUM(discount) as total FROM sales 
                  WHERE timestamp BETWEEN ? AND ? AND discount > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data['discounts'] = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Refunds
        $query = "SELECT SUM(total) as total FROM sales 
                  WHERE timestamp BETWEEN ? AND ? AND total < 0";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data['refunds'] = abs($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        // Sales by category
        $query = "SELECT c.name as category, SUM(si.price * si.quantity) as total
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.id
                  JOIN categories c ON p.category_id = c.id
                  JOIN sales s ON si.sale_id = s.id
                  WHERE s.timestamp BETWEEN ? AND ? AND s.total > 0
                  GROUP BY c.id
                  ORDER BY total DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['by_category'][] = $row;
        }
        $stmt->close();

        // Sales by payment method
        $query = "SELECT payment_method, SUM(total) as total
                  FROM sales
                  WHERE timestamp BETWEEN ? AND ? AND total > 0
                  GROUP BY payment_method
                  ORDER BY total DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['by_payment'][] = $row;
        }
        $stmt->close();

        // Top selling products
        $query = "SELECT p.name, SUM(si.quantity) as quantity, 
                         SUM(si.price * si.quantity) as total
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.id
                  JOIN sales s ON si.sale_id = s.id
                  WHERE s.timestamp BETWEEN ? AND ? AND s.total > 0
                  GROUP BY p.id
                  ORDER BY total DESC
                  LIMIT 5";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['top_products'][] = $row;
        }
        $stmt->close();

        return $data;
    }

    private function getExpensesData($startDate, $endDate)
    {
        $data = [
            'total' => 0,
            'by_category' => []
        ];

        // Total expenses
        $query = "SELECT SUM(amount) as total FROM expenses 
                  WHERE timestamp BETWEEN ? AND ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data['total'] = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Expenses by category
        $query = "SELECT category, SUM(amount) as total
                  FROM expenses
                  WHERE timestamp BETWEEN ? AND ?
                  GROUP BY category
                  ORDER BY total DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['by_category'][] = $row;
        }
        $stmt->close();

        return $data;
    }

    public function exportProfitLoss()
    {
        $dateRange = $_GET['range'] ?? 'this_month';
        $startDate = $_GET['start'] ?? '';
        $endDate = $_GET['end'] ?? '';
        
        list($startDate, $endDate) = $this->calculateDateRange($dateRange, $startDate, $endDate);
        
        $salesData = $this->getSalesData($startDate, $endDate);
        $expensesData = $this->getExpensesData($startDate, $endDate);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="profit_loss_report.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, ['Profit & Loss Report']);
        fputcsv($output, ['Period', "$startDate to $endDate"]);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, ['Generated by', $_SESSION['user']['name']]);
        fputcsv($output, []);
        
        // Sales Summary
        fputcsv($output, ['Sales Summary']);
        fputcsv($output, ['Total Sales', number_format($salesData['total_sales'], 2)]);
        fputcsv($output, ['Gross Profit', number_format($salesData['gross_profit'], 2)]);
        fputcsv($output, ['Discounts Given', number_format($salesData['discounts'], 2)]);
        fputcsv($output, ['Refunds', number_format($salesData['refunds'], 2)]);
        fputcsv($output, []);
        
        // Expenses Summary
        fputcsv($output, ['Expenses Summary']);
        fputcsv($output, ['Total Expenses', number_format($expensesData['total'], 2)]);
        fputcsv($output, []);
        
        // Net Profit
        $netProfit = $salesData['gross_profit'] - $expensesData['total'];
        fputcsv($output, ['Net Profit', number_format($netProfit, 2)]);
        fputcsv($output, []);
        
        // Sales by Category
        fputcsv($output, ['Sales by Category']);
        foreach ($salesData['by_category'] as $category) {
            fputcsv($output, [$category['category'], number_format($category['total'], 2)]);
        }
        fputcsv($output, []);
        
        // Expenses by Category
        fputcsv($output, ['Expenses by Category']);
        foreach ($expensesData['by_category'] as $category) {
            fputcsv($output, [$category['category'], number_format($category['total'], 2)]);
        }
        
        fclose($output);
        exit;
    }

    

}