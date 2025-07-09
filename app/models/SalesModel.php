<?php
namespace App\Models;

class SalesModel
{
    private $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function getSalesSummary($startDate, $endDate)
    {
        $data = [
            'total_sales' => 0,
            'gross_profit' => 0,
            'discounts' => 0,
            'refunds' => 0,
            'transaction_count' => 0,
            'sale_count' => 0,
            'refund_count' => 0,
            'cost_of_goods_sold' => 0,
            'other_expenses' => 0,
            'total_expenses' => 0,
            'net_profit' => 0
        ];

        // SINGLE QUERY FOR ALL COUNTS
        $query = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN total > 0 THEN 1 ELSE 0 END) as sale_count,
                    SUM(CASE WHEN total < 0 THEN 1 ELSE 0 END) as refund_count,
                    SUM(CASE WHEN total > 0 THEN total ELSE 0 END) as total_sales,
                    SUM(CASE WHEN total > 0 THEN discount ELSE 0 END) as discounts,
                    ABS(SUM(CASE WHEN total < 0 THEN total ELSE 0 END)) as refunds
                FROM sales 
                WHERE timestamp BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $data['transaction_count'] = $row['total_count'] ?? 0;
        $data['sale_count'] = $row['sale_count'] ?? 0;
        $data['refund_count'] = $row['refund_count'] ?? 0;
        $data['total_sales'] = $row['total_sales'] ?? 0;
        $data['discounts'] = $row['discounts'] ?? 0;
        $data['refunds'] = $row['refunds'] ?? 0;
        
        $stmt->close();


        // 3. Calculate NET SALES (total sales minus refunds)
        $data['net_sales'] = $data['total_sales'] - $data['refunds'];

        // 4. Get Cost of Goods Sold (COGS)
        $query = "SELECT SUM(p.cost_price * si.quantity) as cogs
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.timestamp BETWEEN ? AND ? AND s.total > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data['cost_of_goods_sold'] = $result->fetch_assoc()['cogs'] ?? 0;
        $stmt->close();

        // 5. Calculate Gross Profit
        $data['gross_profit'] = $data['total_sales'] - $data['cost_of_goods_sold'] - $data['refunds'];

        // 6. Get Other Expenses
        $query = "SELECT SUM(amount) as total FROM expenses 
                WHERE timestamp BETWEEN ? AND ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data['other_expenses'] = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // 7. Calculate Net Profit
        $data['total_expenses'] = $data['cost_of_goods_sold'] + $data['other_expenses'];
        $data['net_profit'] = $data['gross_profit'] - $data['other_expenses'];

        return $data;
    }
    public function getSalesByCategory($startDate, $endDate)
    {
        $categories = [];
        
        $query = "SELECT 
                    COALESCE(c.name, 'Uncategorized') as category, 
                    SUM(si.price * si.quantity) as total_sales,
                    SUM(CASE WHEN s.total > 0 THEN si.price * si.quantity ELSE 0 END) as sales,
                    SUM(CASE WHEN s.total < 0 THEN ABS(si.price * si.quantity) ELSE 0 END) as refunds,
                    SUM(si.quantity) as items_sold,
                    COUNT(DISTINCT s.id) as transaction_count
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE s.timestamp BETWEEN ? AND ?
                GROUP BY c.id
                ORDER BY total_sales DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'category' => $row['category'],
                'total' => $row['total_sales'],
                'sales' => $row['sales'],
                'refunds' => $row['refunds'],
                'items_sold' => $row['items_sold'],
                'transaction_count' => $row['transaction_count']
            ];
        }
        
        $stmt->close();
        return $categories;
    }

    public function getSalesByPaymentMethod($startDate, $endDate)
    {
        $methods = [];
        
        $query = "SELECT 
                    payment_method, 
                    SUM(total) as total
                  FROM sales
                  WHERE timestamp BETWEEN ? AND ? AND total > 0
                  GROUP BY payment_method
                  ORDER BY total DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $methods[] = $row;
        }
        
        $stmt->close();
        return $methods;
    }

    public function getTopSellingProducts($startDate, $endDate, $limit = 5)
    {
        $products = [];
        
        $query = "SELECT 
                    p.name, 
                    SUM(si.quantity) as quantity, 
                    SUM(si.price * si.quantity) as total
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.id
                  JOIN sales s ON si.sale_id = s.id
                  WHERE s.timestamp BETWEEN ? AND ? AND s.total > 0
                  GROUP BY p.id
                  ORDER BY total DESC
                  LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssi", $startDate, $endDate, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        $stmt->close();
        return $products;
    }
    public function getFirstSaleDate()
    {
        $result = $this->db->query("SELECT DATE(MIN(timestamp)) as date FROM sales");
        return $result->fetch_assoc()['date'] ?? date('Y-m-01');
    }

    public function getLastSaleDate()
    {
        $result = $this->db->query("SELECT DATE(MAX(timestamp)) as date FROM sales");
        return $result->fetch_assoc()['date'] ?? date('Y-m-d');
    }
}