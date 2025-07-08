<?php
//session_start();

//if (!defined('IN_CONTROLLER')) {
    //die('Direct access not allowed');
//}

if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /pos/public/login');
    exit;
}

$title = "Admin Dashboard";
$user_name = htmlspecialchars($_SESSION['user']['name'] ?? 'Admin User');
$role = htmlspecialchars($_SESSION['user']['role'] ?? 'admin');

// Ensure variables are set
$stats = $stats ?? [
    'total_sales' => 0,
    'total_users' => 0,
    'inventory_items' => 0,
    'total_stock' => 0,
    'low_stock_items' => 0,
    'daily_sales' => 0,
    'daily_transactions' => 0
];
$chart_labels = $chart_labels ?? [];
$chart_data = $chart_data ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .stats-section, .charts-section {
            margin-bottom: 30px;
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
        }
        .stat-icon {
            color: #1e3a8a;
            font-size: 1.3rem;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        .stat-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn-info {
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .btn-info:hover {
            background-color: #1e40af;
        }
        .canvas-container {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            height: 350px;
            width: 70%;
        }
        #salesChart {
            width: 100% !important;
            height: 100% !important;
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
        .dark-theme .stat-card, .dark-theme .canvas-container {
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
        }
        .dark-theme .btn-info {
            background-color: #3b82f6;
        }
        .dark-theme .btn-info:hover {
            background-color: #1e40af;
        }
        .dark-theme .connectivity-status {
            background-color: #4b5563;
        }
        .dark-theme .connectivity-status.offline {
            background-color: #f87171;
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
        }
        @media (max-width: 576px) {
            .stats-grid {
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
                <h2 class="sidebar-title">Dashboard</h2>
                <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <a href="/pos/public/dashboard" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i> <span>Overview</span>
                </a>
                <a href="/pos/public/products" class="nav-item">
                    <i class="fas fa-box"></i> <span>Inventory</span>
                </a>
                <a href="/pos/public/sales_repo" class="nav-item">
                    <i class="fas fa-shopping-cart"></i> <span>Sales</span>
                </a>
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <a href="/pos/public/users" class="nav-item">
                        <i class="fas fa-users"></i> <span>Staff</span>
                    </a>
                    <a href="/pos/public/suppliers" class="nav-item">
                        <i class="fas fa-truck"></i> <span>Suppliers</span>
                    </a>
                    
                    <a href="/pos/public/profit_loss" class="nav-item">
                        <i class="fas fa-file-alt"></i> <span>Reports</span>
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($role); ?>)</span>
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
                    <h1 class="dashboard-title">Dashboard</h1>
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
                    <?php if ($stats['low_stock_items'] > 0): ?>
                        <div class="alert alert-warning">Low Stock Alert: <a href="/pos/public/products?filter=low_stock"><?php echo $stats['low_stock_items']; ?> items need restocking.</a></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); ?><?php unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?><?php unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Section -->
            <section class="stats-section">
                <div class="section-header">
                    <h3 class="section-title">Store Overview</h3>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Total Sales</h4>
                            <i class="fas fa-dollar-sign stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo number_format($stats['total_sales'], 2); ?> KSh</p>
                        <div class="stat-actions">
                            <a href="/pos/public/sales_repo" class="btn btn-info">View Sales</a>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Daily Sales</h4>
                            <i class="fas fa-dollar-sign stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo number_format($stats['daily_sales'], 2); ?> KSh (<?php echo $stats['daily_transactions']; ?> transactions)</p>
                        <div class="stat-actions">
                            <a href="/pos/public/sales_repo?filter=today" class="btn btn-info">View today's sales</a>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Total Users</h4>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo $stats['total_users']; ?></p>
                        <div class="stat-actions">
                            <a href="/pos/public/users" class="btn btn-info">Manage Users</a>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Inventory Items</h4>
                            <i class="fas fa-boxes stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo $stats['inventory_items']; ?></p>
                        <div class="stat-actions">
                            <a href="/pos/public/products" class="btn btn-info">View Inventory</a>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Total Stock</h4>
                            <i class="fas fa-box stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo $stats['total_stock']; ?></p>
                        <div class="stat-actions">
                            <a href="/pos/public/products" class="btn btn-info">View Stock</a>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h4 class="stat-title">Low Stock Items</h4>
                            <i class="fas fa-exclamation-circle stat-icon"></i>
                        </div>
                        <p class="stat-value"><?php echo $stats['low_stock_items']; ?></p>
                        <div class="stat-actions">
                            <a href="/pos/public/products?filter=low_stock" class="btn btn-info">Manage Stock</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts-section">
                <div class="section-header">
                    <h3 class="section-title">Sales Trends (Last 7 Days)</h3>
                </div>
                <div class="canvas-container">
                    <canvas id="salesChart"></canvas>
                </div>
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

        // Sales chart
        const ctx = document.getElementById('salesChart')?.getContext('2d');
        if (!ctx) {
            console.error('Sales chart canvas not found');
        } else {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo is_array($chart_labels) ? json_encode($chart_labels) : json_encode([]); ?>,
                    datasets: [{
                        label: 'Daily Sales (KSh)',
                        data: <?php echo is_array($chart_data) ? json_encode($chart_data) : json_encode([]); ?>,
                        borderColor: '#1e3a8a',
                        backgroundColor: 'rgba(30, 58, 138, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
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
                    elements: {
                        point: {
                            radius: 4,
                            hoverRadius: 6
                        }
                    },
                    animation: {
                        duration: 1000
                    }
                }
            });
        }
    </script>
</body>
</html>