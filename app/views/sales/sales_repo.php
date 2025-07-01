<?php
if (!defined('IN_CONTROLLER')) {
    header('Location: /pos/public/login');
    exit;
}

if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /pos/public/login');
    exit;
}

$title = "Sales Summary";
$kpi_data = $kpi_data ?? [
    'total_sales' => 0,
    'transactions' => 0,
    'discounts' => 0,
    'refunds' => 0,
    'refund_count' => 0
];
$sales_by_cashier = $sales_by_cashier ?? [];
$sales_by_hour = $sales_by_hour ?? ['labels' => [], 'data' => []];
$payment_methods = $payment_methods ?? [];
$top_products = $top_products ?? [];
$recent_sales = $recent_sales ?? [];
$active_cashiers = $active_cashiers ?? [];
$pending_approvals = $pending_approvals ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f1f3f5;
            color: #212529;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #fff;
            padding: 15px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: width 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .sidebar.collapsed {
            width: 60px;
        }
        .sidebar.collapsed .sidebar-title, .sidebar.collapsed .nav-item span, .sidebar.collapsed .user-name, .sidebar.collapsed .logout-link span {
            display: none;
        }
        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .sidebar-title {
            font-size: 1.5rem;
            color: #1e3a8a;
            margin: 0;
        }
        .sidebar-toggle {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 1.2rem;
        }
        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .nav-item {
            color: #374151;
            text-decoration: none;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.3s, color 0.3s;
        }
        .nav-item:hover, .nav-item.active {
            background-color: #dbeafe;
            color: #1e3a8a;
        }
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        .user-profile {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .user-name {
            color: #374151;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logout-link {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logout-link:hover {
            color: #dc2626;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f1f3f5;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .dashboard-title {
            font-size: 1.8rem;
            margin: 0;
        }
        .connectivity-status {
            font-size: 1rem;
            background-color: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 6px;
        }
        .connectivity-status.offline {
            background-color: #ef4444;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .last-updated {
            font-size: 0.9rem;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn-theme-toggle {
            background-color: #6b7280;
            color: #fff;
            border: none;
            padding: 8px;
            border-radius: 6px;
            font-size: 1rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-theme-toggle:hover {
            background-color: #4b5563;
        }
        .alerts-section {
            margin-bottom: 20px;
        }
        .alerts {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .alert {
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 1.3rem;
            color: #374151;
            margin: 0;
        }
        .stats-section, .charts-section, .sales-by-cashier-section, .top-products-section, .recent-sales-section, .active-cashiers-section, .pending-approvals-section {
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
        }
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .stat-title {
            font-size: 1rem;
            color: #374151;
            margin: 0;
            text-transform: uppercase;
        }
        .stat-icon {
            color: #1e3a8a;
            font-size: 1.3rem;
            background-color: #dbeafe;
            padding: 5px;
            border-radius: 50%;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        .stat-subtext {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
        }
        .stat-subtext.negative {
            color: #dc2626;
        }
        .filters-section {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            align-items: center;
        }
        .filters-grid input, .filters-grid select {
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.9rem;
            width: 100%;
        }
        .filters-grid button {
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .filters-grid button:hover {
            background-color: #1e40af;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .table th {
            background-color: #f9fafb;
            color: #374151;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .table td {
            font-size: 0.9rem;
        }
        .cashier-row:hover {
            background-color: #f9fafb;
        }
        .cashier-avatar {
            width: 30px;
            height: 30px;
            background-color: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #374151;
            margin-right: 10px;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .canvas-container {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            height: 350px;
        }
        #salesByHourChart, #paymentMethodsChart {
            width: 100% !important;
            height: 100% !important;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-export, .btn-print {
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-export:hover, .btn-print:hover {
            background-color: #1e40af;
        }
        .dark-theme {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .main-content {
            background-color: #1f2937;
        }
        .dark-theme .sidebar {
            background-color: #374151;
        }
        .dark-theme .sidebar-title {
            color: #3b82f6;
        }
        .dark-theme .nav-item {
            color: #d1d5db;
        }
        .dark-theme .nav-item:hover, .dark-theme .nav-item.active {
            background-color: #4b5563;
            color: #3b82f6;
        }
        .dark-theme .user-name {
            color: #d1d5db;
        }
        .dark-theme .logout-link {
            color: #f87171;
        }
        .dark-theme .logout-link:hover {
            color: #ef4444;
        }
        .dark-theme .dashboard-header {
            background: linear-gradient(90deg, #374151, #4b5563);
        }
        .dark-theme .stat-card, .dark-theme .table, .dark-theme .canvas-container, .dark-theme .filters-section {
            background-color: #374151;
        }
        .dark-theme .stat-title {
            color: #d1d5db;
        }
        .dark-theme .stat-value {
            color: #e5e7eb;
        }
        .dark-theme .stat-icon {
            color: #3b82f6;
            background-color: #4b5563;
        }
        .dark-theme .btn-info, .dark-theme .btn-export, .dark-theme .btn-print {
            background-color: #3b82f6;
        }
        .dark-theme .btn-info:hover, .dark-theme .btn-export:hover, .dark-theme .btn-print:hover {
            background-color: #1e40af;
        }
        .dark-theme .connectivity-status {
            background-color: #4b5563;
        }
        .dark-theme .connectivity-status.offline {
            background-color: #f87171;
        }
        .dark-theme .table th {
            background-color: #4b5563;
            color: #d1d5db;
        }
        .dark-theme .filters-grid input, .dark-theme .filters-grid select {
            background-color: #4b5563;
            color: #d1d5db;
            border-color: #6b7280;
        }
        .dark-theme .cashier-row:hover {
            background-color: #4b5563;
        }
        .dark-theme .cashier-avatar {
            background-color: #6b7280;
            color: #d1d5db;
        }
        @media (max-width: 992px) {
            .sidebar {
                width: 60px;
            }
            .sidebar:not(.collapsed) {
                width: 250px;
            }
            .sidebar.collapsed .sidebar-title, .sidebar.collapsed .nav-item span, .sidebar.collapsed .user-name, .sidebar.collapsed .logout-link span {
                display: none;
            }
            .main-content {
                margin-left: 60px;
            }
            .sidebar-toggle {
                display: block;
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 576px) {
            .stats-grid, .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Admin Dashboard</h2>
                <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <a href="/pos/public/dashboard" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i> <span>Overview</span>
                </a>
                <a href="/pos/public/products" class="nav-item">
                    <i class="fas fa-box"></i> <span>Inventory</span>
                </a>
                <a href="/pos/public/sales" class="nav-item active">
                    <i class="fas fa-shopping-cart"></i> <span>Sales</span>
                </a>
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <a href="/pos/public/users" class="nav-item">
                        <i class="fas fa-users"></i> <span>Users</span>
                    </a>
                    <a href="/pos/public/suppliers" class="nav-item">
                        <i class="fas fa-truck"></i> <span>Suppliers</span>
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Admin User'); ?> (<?php echo htmlspecialchars($_SESSION['user']['role'] ?? 'admin'); ?>)</span>
                    <a href="/pos/public/logout" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1 class="dashboard-title">Sales Summary</h1>
                    <span class="connectivity-status" id="connectivity-status">Online</span>
                </div>
                <div class="header-right">
                    <span class="last-updated">Last Updated: <span id="current-time"><?php echo date('Y-m-d H:i:s'); ?></span></span>
                    <div class="header-actions">
                        <button class="btn-theme-toggle" onclick="toggleTheme()" id="btn-theme-toggle" title="Toggle Theme" aria-label="Toggle Theme">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Alerts Section -->
            <div class="alerts-section">
                <div id="alerts" class="alerts">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Page Header -->
            <div class="section-header">
                <div>
                    <h3 class="section-title">Sales Summary</h3>
                    <p style="font-size: 0.9rem; color: #6b7280;">Oversee cashier sales, analyze performance, and spot trends.</p>
                </div>
                <div class="action-buttons">
                    <button class="btn-export"><i class="fas fa-download"></i> Export</button>
                    <button class="btn-print"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>

            <!-- KPI Cards Section -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Total Sales</h4>
                            <i class="fas fa-dollar-sign stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo number_format($kpi_data['total_sales'], 2); ?> KSh</p>
                        <p class="stat-subtext"><span style="color: #10b981;">+12.5%</span> vs last period</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Transactions</h4>
                            <i class="fas fa-receipt stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo $kpi_data['transactions']; ?></p>
                        <p class="stat-subtext">Avg. Sale: <?php echo number_format($kpi_data['total_sales'] / max(1, $kpi_data['transactions']), 2); ?> KSh</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Discounts</h4>
                            <i class="fas fa-percentage stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo number_format($kpi_data['discounts'], 2); ?> KSh</p>
                        <p class="stat-subtext">in <?php echo $kpi_data['transactions']; ?> transactions</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Refunds</h4>
                            <i class="fas fa-undo stat-icon"></i>
                        </div>
                        <p class="stat-value negative"><?php echo number_format($kpi_data['refunds'], 2); ?> KSh</p>
                        <p class="stat-subtext"><?php echo $kpi_data['refund_count']; ?> refunds processed</p>
                    </div>
                </div>
            </section>

            <!-- Filters Section -->
            <section class="filters-section">
                <div class="filters-grid">
                    <input type="date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    <input type="date" value="<?php echo date('Y-m-d'); ?>">
                    <select>
                        <option>All Cashiers</option>
                        <?php foreach ($sales_by_cashier as $cashier): ?>
                            <option value="<?php echo htmlspecialchars($cashier['id']); ?>">
                                <?php echo htmlspecialchars($cashier['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select>
                        <option>All Locations</option>
                        <option>Main Street</option>
                        <option>Downtown</option>
                    </select>
                    <button>Filter</button>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts-section">
                <div class="section-header">
                    <h3 class="section-title">Sales Analytics</h3>
                </div>
                <div class="charts-grid">
                    <div class="canvas-container">
                        <canvas id="salesByHourChart"></canvas>
                    </div>
                    <div class="canvas-container">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- Active Cashiers Section -->
            <section class="active-cashiers-section">
                <div class="section-header">
                    <h3 class="section-title">Active Cashiers</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th>ID</th>
                            <th>Last Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($active_cashiers)): ?>
                            <?php foreach ($active_cashiers as $cashier): ?>
                                <tr class="cashier-row">
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="cashier-avatar"><?php echo htmlspecialchars(substr($cashier['name'], 0, 2)); ?></div>
                                            <?php echo htmlspecialchars($cashier['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($cashier['id']); ?></td>
                                    <td><?php echo htmlspecialchars($cashier['last_active']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No active cashiers at the moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Sales by Cashier Section -->
            <section class="sales-by-cashier-section">
                <div class="section-header">
                    <h3 class="section-title">Sales by Cashier</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th>Transactions</th>
                            <th>Avg Sale</th>
                            <th>Discounts</th>
                            <th>Refunds</th>
                            <th style="text-align: right;">Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sales_by_cashier)): ?>
                            <?php foreach ($sales_by_cashier as $cashier): ?>
                                <tr class="cashier-row">
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="cashier-avatar"><?php echo htmlspecialchars(substr($cashier['name'], 0, 2)); ?></div>
                                            <div>
                                                <div style="font-weight: 700;"><?php echo htmlspecialchars($cashier['name']); ?></div>
                                                <div style="font-size: 0.8rem; color: #6b7280;">ID: <?php echo htmlspecialchars($cashier['id']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $cashier['transactions']; ?></td>
                                    <td><?php echo number_format($cashier['avg_sale'], 2); ?> KSh</td>
                                    <td><?php echo number_format($cashier['discounts'], 2); ?> KSh</td>
                                    <td style="color: #dc2626;"><?php echo number_format($cashier['refunds'], 2); ?> KSh</td>
                                    <td style="text-align: right; font-weight: 700;"><?php echo number_format($cashier['total_sales'], 2); ?> KSh</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No sales data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Top-Selling Products Section -->
            <section class="top-products-section">
                <div class="section-header">
                    <h3 class="section-title">Top-Selling Products (Last 30 Days)</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity Sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_products)): ?>
                            <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo number_format($product['total_sold']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No sales in the past 30 days.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Recent Sales Section -->
            <section class="recent-sales-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Sales</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Total</th>
                            <th>Cashier</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_sales)): ?>
                            <?php foreach ($recent_sales as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['id']); ?></td>
                                    <td><?php echo number_format($sale['total'], 2); ?> KSh</td>
                                    <td><?php echo htmlspecialchars($sale['cashier'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($sale['timestamp'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No recent sales.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Pending Approvals Section -->
            <section class="pending-approvals-section">
                <div class="section-header">
                    <h3 class="section-title">Pending Approvals</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Type</th>
                            <th>Submitted By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_approvals)): ?>
                            <?php foreach ($pending_approvals as $approval): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($approval['id']); ?></td>
                                    <td><?php echo htmlspecialchars($approval['type']); ?></td>
                                    <td><?php echo htmlspecialchars($approval['submitted_by']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($approval['timestamp'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No pending approvals.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
        // Update date and time
        function updateTime() {
            const now = new Date().toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = now;
        }
        setInterval(updateTime, 1000);

        // Check connectivity
        function checkConnectivity() {
            const status = navigator.onLine ? 'Online' : 'Offline';
            document.getElementById('connectivity-status').textContent = status;
            document.getElementById('connectivity-status').className = `connectivity-status ${status.toLowerCase()}`;
        }
        window.addEventListener('online', checkConnectivity);
        window.addEventListener('offline', checkConnectivity);
        checkConnectivity();

        // Toggle theme
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const icon = document.querySelector('.btn-theme-toggle i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        }

        // Toggle sidebar
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }

        // Show alerts
        function showAlert(type, message) {
            const alertsDiv = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertsDiv.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        // Sales by Hour Chart
        const salesByHourCtx = document.getElementById('salesByHourChart')?.getContext('2d');
        if (salesByHourCtx) {
            new Chart(salesByHourCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($sales_by_hour['labels']); ?>,
                    datasets: [{
                        label: 'Sales by Hour (KSh)',
                        data: <?php echo json_encode($sales_by_hour['data']); ?>,
                        backgroundColor: '#1e3a8a',
                        borderColor: '#1e3a8a',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Sales (KSh)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'KSh ' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Hour of Day'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'KSh ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000
                    }
                }
            });
        } else {
            console.error('Sales by Hour chart canvas not found');
        }

        // Payment Methods Chart
        const paymentMethodsCtx = document.getElementById('paymentMethodsChart')?.getContext('2d');
        if (paymentMethodsCtx) {
            new Chart(paymentMethodsCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($payment_methods, 'method')); ?>,
                    datasets: [{
                        label: 'Payment Methods',
                        data: <?php echo json_encode(array_column($payment_methods, 'total')); ?>,
                        backgroundColor: ['#1e3a8a', '#3b82f6', '#60a5fa', '#93c5fd'],
                        borderColor: ['#fff', '#fff', '#fff', '#fff'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': KSh ' + context.parsed.toLocaleString();
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000
                    }
                }
            });
        } else {
            console.error('Payment Methods chart canvas not found');
        }
    </script>
</body>
</html>