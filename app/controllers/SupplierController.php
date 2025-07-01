<?php
namespace App\Controllers;

use mysqli;

class SupplierController
{
    private $conn;

    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /pos/public/login');
            exit;
        }
        require_once BASE_PATH . 'config/database.php';
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            error_log("Database connection failed: " . $this->conn->connect_error);
            die("Connection failed: Please contact the administrator.");
        }
    }

    public function index()
    {
        $suppliers = [];
    $query = "
        SELECT s.*, 
               COALESCE(SUM(p.total_amount - COALESCE((
                   SELECT SUM(sp.amount) 
                   FROM supplier_payments sp 
                   WHERE sp.purchase_order_id = p.id
               ), 0)), 0) as outstanding_balance 
        FROM suppliers s 
        LEFT JOIN purchase_orders p ON s.id = p.supplier_id 
        GROUP BY s.id
    ";
    $result = $this->conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
        $result->free();
    } else {
        error_log("Query failed: " . $this->conn->error);
        $_SESSION['error'] = "Failed to load suppliers. Please try again.";
    }
    include BASE_PATH . 'app/views/admin/suppliers.php';
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $name = $this->conn->real_escape_string($data['name']);
            $contact_info = $this->conn->real_escape_string($data['contact_info'] ?? '');
            $description = $this->conn->real_escape_string($data['description'] ?? '');
            $category = $this->conn->real_escape_string($data['category'] ?? '');
            
            $stmt = $this->conn->prepare("INSERT INTO suppliers (name, contact_info, description, category) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $contact_info, $description, $category);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Supplier added successfully.";
                header('Location: /pos/public/suppliers');
            } else {
                $_SESSION['error'] = "Failed to add supplier.";
            }
            $stmt->close();
        }
    }

    public function edit($id)
    {
        // Handle JSON or POST data
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(file_get_contents('php://input'))) {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $_POST;
    }
    $name = $this->conn->real_escape_string($data['name'] ?? '');
    $contact_info = $this->conn->real_escape_string($data['contact_info'] ?? '');
    $description = $this->conn->real_escape_string($data['description'] ?? '');
    $category = $this->conn->real_escape_string($data['category'] ?? '');
    $query = "UPDATE suppliers SET name='$name', contact_info='$contact_info', description='$description', category='$category' WHERE id=$id";
    if ($this->conn->query($query)) {
        $_SESSION['success'] = 'Supplier updated successfully.';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(file_get_contents('php://input'))) {
            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully.']);
        } else {
            header('Location: /pos/public/suppliers');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Failed to update supplier.';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(file_get_contents('php://input'))) {
            echo json_encode(['success' => false, 'message' => 'Failed to update supplier.']);
        } else {
            header('Location: /pos/public/suppliers/edit/' . $id);
            exit;
        }
    }
    }

    public function createPurchase()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $supplier_id = (int)$data['supplier_id'];
            $items = $data['items'];
            $notes = $this->conn->real_escape_string($data['notes'] ?? '');
            
            $this->conn->begin_transaction();
            try {
                $total_amount = 0;
                foreach ($items as $item) {
                    $total_amount += $item['quantity'] * $item['unit_price'];
                }
                
                $stmt = $this->conn->prepare("INSERT INTO purchase_orders (supplier_id, total_amount, notes) VALUES (?, ?, ?)");
                $stmt->bind_param("ids", $supplier_id, $total_amount, $notes);
                $stmt->execute();
                $purchase_order_id = $this->conn->insert_id;
                
                $stmt = $this->conn->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                foreach ($items as $item) {
                    $product_id = (int)$item['product_id'];
                    $quantity = (int)$item['quantity'];
                    $unit_price = (float)$item['unit_price'];
                    $stmt->bind_param("iiid", $purchase_order_id, $product_id, $quantity, $unit_price);
                    $stmt->execute();
                }
                
                $this->conn->commit();
                $_SESSION['success'] = "Purchase order created successfully.";
                header('Location: /pos/public/suppliers');
            } catch (\Exception $e) {
                $this->conn->rollback();
                $_SESSION['error'] = "Failed to create purchase order: " . $e->getMessage();
            }
            $stmt->close();
        }
    }

    public function recordPayment()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $supplier_id = (int)$data['supplier_id'];
            $purchase_order_id = isset($data['purchase_order_id']) ? (int)$data['purchase_order_id'] : null;
            $amount = (float)$data['amount'];
            $payment_method = $this->conn->real_escape_string($data['payment_method']);
            $notes = $this->conn->real_escape_string($data['notes'] ?? '');
            
            $stmt = $this->conn->prepare("INSERT INTO supplier_payments (supplier_id, purchase_order_id, amount, payment_method, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iids", $supplier_id, $purchase_order_id, $amount, $payment_method, $notes);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Payment recorded successfully.";
                header('Location: /pos/public/suppliers');
            } else {
                $_SESSION['error'] = "Failed to record payment.";
            }
            $stmt->close();
        }
    }

    public function updateDeliveryStatus($purchase_order_id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $status = $this->conn->real_escape_string($data['delivery_status']);
            
            $stmt = $this->conn->prepare("UPDATE purchase_orders SET delivery_status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $purchase_order_id);
            if ($stmt->execute()) {
                // Update product stock if delivered
                if ($status === 'delivered') {
                    $stmt = $this->conn->prepare("SELECT product_id, quantity FROM purchase_order_items WHERE purchase_order_id = ?");
                    $stmt->bind_param("i", $purchase_order_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($item = $result->fetch_assoc()) {
                        $this->conn->query("UPDATE products SET stock = stock + {$item['quantity']} WHERE id = {$item['product_id']}");
                    }
                }
                $_SESSION['success'] = "Delivery status updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update delivery status.";
            }
            $stmt->close();
            header('Location: /pos/public/suppliers');
        }
    }

    public function getPurchaseHistory($supplier_id)
    {
        $history = [];
        $stmt = $this->conn->prepare("
            SELECT po.*, COALESCE((SELECT SUM(sp.amount) FROM supplier_payments sp WHERE sp.purchase_order_id = po.id), 0) as paid_amount
            FROM purchase_orders po
            WHERE po.supplier_id = ?
            ORDER BY po.order_date DESC
        ");
        $stmt->bind_param("i", $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode($history);
    }

    public function export($format = 'pdf')
    {
        $suppliers = [];
        $result = $this->conn->query("
            SELECT s.*, COALESCE(SUM(p.total_amount - COALESCE((SELECT SUM(sp.amount) FROM supplier_payments sp WHERE sp.purchase_order_id = p.id), 0)), 0) as outstanding_balance
            FROM suppliers s
            LEFT JOIN purchase_orders p ON s.id = p.supplier_id
            GROUP BY s.id
        ");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row;
            }
        }

        if ($format === 'pdf') {
            require_once BASE_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
            $pdf = new \TCPDF();
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('POS System');
            $pdf->SetTitle('Suppliers Report');
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $html = '<h1>Suppliers Report</h1><table border="1"><tr><th>Name</th><th>Contact Info</th><th>Category</th><th>Outstanding Balance</th></tr>';
            foreach ($suppliers as $supplier) {
                $html .= "<tr><td>{$supplier['name']}</td><td>{$supplier['contact_info']}</td><td>{$supplier['category']}</td><td>" . number_format($supplier['outstanding_balance'], 2) . " KSh</td></tr>";
            }
            $html .= '</table>';
            $pdf->writeHTML($html);
            $pdf->Output('suppliers_report.pdf', 'D');
        } elseif ($format === 'excel') {
            require_once BASE_PATH . 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
            require_once BASE_PATH . 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('B1', 'Contact Info');
            $sheet->setCellValue('C1', 'Category');
            $sheet->setCellValue('D1', 'Outstanding Balance');
            $row = 2;
            foreach ($suppliers as $supplier) {
                $sheet->setCellValue('A' . $row, $supplier['name']);
                $sheet->setCellValue('B' . $row, $supplier['contact_info']);
                $sheet->setCellValue('C' . $row, $supplier['category']);
                $sheet->setCellValue('D' . $row, number_format($supplier['outstanding_balance'], 2) . ' KSh');
                $row++;
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="suppliers_report.xlsx"');
            $writer->save('php://output');
            exit;
        }
    }

    public function __destruct()
    {
        $this->conn->close();
    }
}