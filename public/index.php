<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__) . '/');

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

require_once '../config/database.php';

// Routing
$request = $_SERVER['REQUEST_URI'];

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
    case str_starts_with($request, '/pos/public/users/edit/'):
        $id = (int)substr($request, strrpos($request, '/') + 1);
        $controller = new \App\Controllers\UserController();
        $controller->edit($id);
        break;
    case '/pos/public/products/create':
        $controller = new \App\Controllers\ProductController();
        $controller->create();
        break;
    case (preg_match('#^products/edit/(\d+)$#', $path, $matches) ? true : false):
        $id = (int)$matches[1];
        $controller = new \App\Controllers\ProductController();
        $controller->edit($id);
        break;
    case (preg_match('#^products/delete/(\d+)$#', $path, $matches) ? true : false):
        $id = (int)$matches[1];
        $controller = new \App\Controllers\ProductController();
        $controller->delete($id);
        break;
    case 'products/inline-update':
        $controller = new \App\Controllers\ProductController();
        $controller->inlineUpdate();
        break;
    case '/pos/public/products/export':
        $controller = new \App\Controllers\ProductController();
        $controller->export();
        break;
    case '/pos/public/sales/pos':
        $controller = new \App\Controllers\SalesController();
        $controller->pos();
    break;
    case '/pos/public/sales/checkout':
        $controller = new \App\Controllers\SalesController();
        $controller->checkout();
    break;
    default:
        http_response_code(404);
        echo "Page not found";
        break;
}