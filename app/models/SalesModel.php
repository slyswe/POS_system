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
            'cost_of_goods_sold' => 0,
            'other_expenses' => 0,
            'total_expenses' => 0,
            'net_profit' => 0
        ];

        // 1. Get POSITIVE sales (matches sales page calculation)
        $query = "SELECT 
                    COUNT(*) as count,
                    SUM(total) as total_sales,
                    SUM(discount) as discounts
                FROM sales 
                WHERE timestamp BETWEEN ? AND ? AND total > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $data['total_sales'] = $row['total_sales'] ?? 0;
        $data['transaction_count'] = $row['count'] ?? 0;
        $data['discounts'] = $row['discounts'] ?? 0;
        $stmt->close();

        // 2. Get NEGATIVE sales (refunds)
        $query = "SELECT 
                    COUNT(*) as count,
                    ABS(SUM(total)) as total_refunds
                FROM sales 
                WHERE timestamp BETWEEN ? AND ? AND total < 0";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $data['refunds'] = $row['total_refunds'] ?? 0;
        $data['refund_count'] = $row['count'] ?? 0;
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
        $data['gross_profit'] = $data['net_sales'] - $data['cost_of_goods_sold'];

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
                    c.name as category, 
                    SUM(si.price * si.quantity) as total
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.id
                  LEFT JOIN categories c ON p.category_id = c.id
                  JOIN sales s ON si.sale_id = s.id
                  WHERE s.timestamp BETWEEN ? AND ? AND s.total > 0
                  GROUP BY c.id
                  ORDER BY total DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'category' => $row['category'] ?? 'Uncategorized',
                'total' => $row['total']
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
}