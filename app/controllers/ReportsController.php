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
    $dateRange = $_GET['range'] ?? 'this_month';
    $startDate = $_GET['start'] ?? '';
    $endDate = $_GET['end'] ?? '';
    
    list($startDate, $endDate) = $this->calculateDateRange($dateRange, $startDate, $endDate);
    
    // Get all financial data
    $salesData = $this->salesModel->getSalesSummary($startDate, $endDate);
    
    // Get additional report data
    $salesData['by_category'] = $this->salesModel->getSalesByCategory($startDate, $endDate);
    $salesData['top_products'] = $this->salesModel->getTopSellingProducts($startDate, $endDate);
    
    // Prepare view data
    $viewData = [
        'title' => 'Profit & Loss Report',
        'dateRange' => $dateRange,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'salesData' => $salesData,
        'expensesData' => $this->getExpensesData($startDate, $endDate),
        'netProfit' => $salesData['net_profit'],
        'netProfitClass' => $salesData['net_profit'] >= 0 ? 'text-success' : 'text-danger',
        'user' => $_SESSION['user']
    ];
    
    include BASE_PATH . 'app/views/reports/profit_loss.php';
}
    private function calculateDateRange($range, $customStart, $customEnd)
    {
        if ($customStart && $customEnd) {
            return [$customStart, $customEnd];
        }

        $today = date('Y-m-d');
        
        switch ($range) {
            case 'today':
                return [$today, $today];
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                return [$yesterday, $yesterday];
            case 'this_week':
                return [date('Y-m-d', strtotime('monday this week')), $today];
            case 'last_week':
                return [
                    date('Y-m-d', strtotime('monday last week')),
                    date('Y-m-d', strtotime('sunday last week'))
                ];
            case 'this_month':
                return [date('Y-m-01'), $today];
            case 'last_month':
                return [
                    date('Y-m-01', strtotime('first day of last month')),
                    date('Y-m-t', strtotime('last day of last month'))
                ];
            case 'this_year':
                return [date('Y-01-01'), $today];
            case 'last_year':
                return [date('Y-01-01', strtotime('-1 year')), date('Y-12-31', strtotime('-1 year'))];
            default:
                return [date('Y-m-01'), $today];
        }
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