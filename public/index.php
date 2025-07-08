<?php
session_start();
// Enable error reporting for development
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__) . '/');

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

require_once '../config/database.php';

// Log the incoming request for debugging
error_log("Incoming request: " . $_SERVER['REQUEST_URI']);
error_log("Parsed request path: " . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Routing
//$request = $_SERVER['REQUEST_URI'];
// Strip query string from REQUEST_URI
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($request) {
    case '/pos/public/login':
        $controller = new \App\Controllers\AuthController();
        $controller->login();
        break;
    case '/pos/public/register':
        $controller = new \App\Controllers\AuthController();
        $controller->register();
        break;
    case '/pos/public/logout':
        $controller = new \App\Controllers\AuthController();
        $controller->logout();
        break;
    case '/pos/public/dashboard':
        $controller = new \App\Controllers\DashboardController();
        $controller->index();
        break;
    case '/pos/public/users':
        $controller = new \App\Controllers\UserController();
        $controller->index();
        break;
    case '/pos/public/users/create':
        $controller = new \App\Controllers\UserController();
        $controller->create();
        break;
    case '/pos/public/sales_repo':
        $controller = new \App\Controllers\SalesController();
        $controller->index();
        break;
    case str_starts_with($request, '/pos/public/users/edit/'):
        $id = (int)substr($request, strrpos($request, '/') + 1);
        $controller = new \App\Controllers\UserController();
        $controller->edit($id);
        break;
    case '/pos/public/products':
        $controller = new \App\Controllers\ProductController();
        $controller->index();
        break;
    case '/pos/public/products/inventory_clerk_dashboard':
        $controller = new \App\Controllers\ProductController();
        $controller->inventoryClerkDashboard();
        break;
    case '/pos/public/products/create':
        $controller = new \App\Controllers\ProductController();
        $controller->create();
        break;
    case '/pos/public/products/filter':
        $controller = new \App\Controllers\ProductController();
        $controller->filter();
        break;
    case (preg_match('#^/pos/public/products/edit/(\d+)$#', $request, $matches) ? true : false):
        $id = (int)$matches[1];
        $controller = new \App\Controllers\ProductController();
        $controller->edit($id);
        break;
    case (preg_match('#^/pos/public/products/delete/(\d+)$#', $request, $matches) ? true : false):
        $id = (int)$matches[1];
        $controller = new \App\Controllers\ProductController();
        $controller->delete($id);
        break;
    case '/pos/public/products/inline-update':
        $controller = new \App\Controllers\ProductController();
        $controller->inlineUpdate();
        break;
    case '/pos/public/products/export':
        $controller = new \App\Controllers\ProductController();
        $controller->export();
        break;
    //case '/pos/public/products/adjust-stock':
       // $controller = new \App\Controllers\ProductController();
        //$controller->adjustStock();
       // break;
    case '/pos/public/api/inventory/adjustment-request':
        $controller = new \App\Controllers\ProductController();
        $controller->submitAdjustmentRequest();
        break;
    case '/pos/public/api/inventory/cost-change-request':
        $controller = new \App\Controllers\ProductController();
        $controller->submitCostChangeRequest();
        break;
    case (preg_match('#^/pos/public/api/inventory/approve-adjustment/(\d+)$#', $request, $matches) ? true : false):
        $controller = new \App\Controllers\ProductController();
        $controller->approveAdjustment($matches[1]);
        break;
    case (preg_match('#^/pos/public/api/inventory/reject-adjustment/(\d+)$#', $request, $matches) ? true : false):
        $controller = new \App\Controllers\ProductController();
        $controller->rejectAdjustment($matches[1]);
        break;
        case (preg_match('#^/pos/public/products/approve/(\d+)$#', $request, $matches) ? true : false):
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$matches[1];
            $controller = new \App\Controllers\ProductController();
            $controller->approveProduct($id);
        } else {
            http_response_code(405); // Method Not Allowed
            echo "Method not allowed";
        }
        break;

    case (preg_match('#^/pos/public/products/reject/(\d+)$#', $request, $matches) ? true : false):
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$matches[1];
            $controller = new \App\Controllers\ProductController();
            $controller->rejectProduct($id);
        } else {
            http_response_code(405); // Method Not Allowed
            echo "Method not allowed";
        }
        break;
    case (preg_match('#^/pos/public/api/inventory/approve-cost-change/(\d+)$#', $request, $matches) ? true : false):
        $controller = new \App\Controllers\ProductController();
        $controller->approveCostChange($matches[1]);
        break;
    case (preg_match('#^/pos/public/api/inventory/reject-cost-change/(\d+)$#', $request, $matches) ? true : false):
        $controller = new \App\Controllers\ProductController();
        $controller->rejectCostChange($matches[1]);
        break;
    case '/pos/public/sales/pos':
        $controller = new \App\Controllers\SalesController();
        $controller->pos();
        break;
    case '/pos/public/sales/checkout':
        $controller = new \App\Controllers\SalesController();
        $controller->checkout();
        break;
    case (strpos($request, '/pos/public/customer/lookup') === 0):
        $searchTerm = $_GET['q'] ?? '';
        $controller = new \App\Controllers\CustomerController();
        header('Content-Type: application/json');
        echo json_encode($controller->lookup($searchTerm));
        break;
    case (preg_match('#^/pos/public/customer/autocomplete\?q=([^&]+)$#', $request, $matches) ? true : false):
        $searchTerm = urldecode($matches[1]);
        $controller = new \App\Controllers\CustomerController();
        header('Content-Type: application/json');
        echo json_encode($controller->autocomplete($searchTerm));
        break;
    case (preg_match('#^/pos/public/customer/create$#i', $request) ? true : false):
        $controller = new \App\Controllers\CustomerController();
        header('Content-Type: application/json');
        echo json_encode($controller->create());
        break;
    case (preg_match('#^/pos/public/customer/update/(\d+)$#i', $request, $matches) ? true : false):
        $customerId = $matches[1];
        $controller = new \App\Controllers\CustomerController();
        header('Content-Type: application/json');
        echo json_encode($controller->update($customerId));
        break;
    case '/pos/public/suppliers':
        $controller = new \App\Controllers\SupplierController();
        $controller->index();
        break;
    case '/pos/public/suppliers/create':
        $controller = new \App\Controllers\SupplierController();
        $controller->create();
        break;
    case (preg_match('#^/pos/public/suppliers/edit/(\d+)$#', $request, $matches) ? true : false):
        $id = (int)$matches[1];
        $controller = new \App\Controllers\SupplierController();
        $controller->edit($id);
        break;
    case '/pos/public/suppliers/purchase':
        $controller = new \App\Controllers\SupplierController();
        $controller->createPurchase();
        break;
    case '/pos/public/suppliers/payment':
        $controller = new \App\Controllers\SupplierController();
        $controller->recordPayment();
        break;
    case (preg_match('#^/pos/public/suppliers/delivery/(\d+)$#', $request, $matches) ? true : false):
        $id = (int)$matches[1];
        $controller = new \App\Controllers\SupplierController();
        $controller->updateDeliveryStatus($id);
        break;
    case (preg_match('#^/pos/public/suppliers/history/(\d+)$#', $request, $matches) ? true : false):
        $id = (int)$matches[1];
        $controller = new \App\Controllers\SupplierController();
        $controller->getPurchaseHistory($id);
        break;
    case (preg_match('#^/pos/public/suppliers/export/(pdf|excel)$#', $request, $matches) ? true : false):
        $format = $matches[1];
        $controller = new \App\Controllers\SupplierController();
        $controller->export($format);
        break;
    case '/pos/public/api/inventory/next-invoice':
        $controller = new \App\Controllers\ProductController();
        $controller->getNextInvoiceNumber();
        break;
    case '/pos/public/api/suppliers':
        $controller = new \App\Controllers\ProductController();
        $controller->fetchSuppliers();
        break;
    case '/pos/public/api/inventory/valuation':
        $controller = new \App\Controllers\ProductController();
        $controller->getValuationData();
        break;
    case '/pos/public/reports/profit_loss':
        $controller = new \App\Controllers\ReportsController();
        $controller->profitLoss();
        break;
    case '/pos/public/reports/export-profit-loss':
        $controller = new \App\Controllers\ReportsController();
        $controller->exportProfitLoss();
        break;
    default:
        http_response_code(404);
        echo "Page not found";
        break;
}