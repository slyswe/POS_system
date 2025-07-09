<?php
$title = "Profit & Loss Report";
$netProfitClass = $netProfit >= 0 ? 'text-success' : 'text-danger';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - POS System</title>
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
        
        /* Reports specific styling */
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
        .section-description {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 5px;
        }
        
        /* Filters section */
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
            gap: 15px;
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
        
        /* Summary cards */
        .report-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-2px);
        }
        .summary-card.highlight {
            border-top: 4px solid #3b82f6;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .card-header h3 {
            font-size: 1rem;
            color: #374151;
            margin: 0;
            text-transform: uppercase;
        }
        .card-header i {
            color: #1e3a8a;
            font-size: 1.3rem;
            background-color: #dbeafe;
            padding: 5px;
            border-radius: 50%;
        }
        .card-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        .text-success {
            color: #10b981;
        }
        .text-danger {
            color: #ef4444;
        }
        
        /* Charts section */
        .report-charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: #374151;
        }
        .chart {
            position: relative;
            height: 250px;
            margin-bottom: 15px;
        }
        
        /* Tables */
        .report-tables-section {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table th, .report-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .report-table th {
            background-color: #f9fafb;
            color: #374151;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .report-table td {
            font-size: 0.9rem;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .card-subtext {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
        }
        .btn {
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
        .btn:hover {
            background-color: #1e40af;
        }
        .btn-export {
            background-color: #10b981;
        }
        .btn-export:hover {
            background-color: #0d9c6e;
        }
        .btn-print {
            background-color: #3b82f6;
        }
        .btn-print:hover {
            background-color: #2563eb;
        }
        
        /* Breadcrumbs */
        .breadcrumbs {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
        }
        .breadcrumb-link {
            color: #3b82f6;
            text-decoration: none;
        }
        .breadcrumb-link:hover {
            text-decoration: underline;
        }
        .breadcrumb-separator {
            margin: 0 5px;
        }
        .breadcrumb-current {
            color: #374151;
            font-weight: 500;
        }
        
        /* Dark theme styles */
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
        .dark-theme .summary-card, 
        .dark-theme .chart-container, 
        .dark-theme .report-tables-section,
        .dark-theme .filters-section {
            background-color: #374151;
        }
        .dark-theme .section-title,
        .dark-theme .card-header h3,
        .dark-theme .chart-container h3,
        .dark-theme .card-value {
            color: #d1d5db;
        }
        .dark-theme .card-header i {
            color: #3b82f6;
            background-color: #4b5563;
        }
        .dark-theme .report-table th {
            background-color: #4b5563;
            color: #d1d5db;
        }
        .dark-theme .report-table td {
            border-bottom-color: #4b5563;
        }
        .dark-theme .breadcrumb-current {
            color: #d1d5db;
        }
        .dark-theme .section-description {
            color: #9ca3af;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 60px;
            }
            .sidebar:not(.collapsed) {
                width: 250px;
            }
            .sidebar.collapsed .sidebar-title, 
            .sidebar.collapsed .nav-item span, 
            .sidebar.collapsed .user-name, 
            .sidebar.collapsed .logout-link span {
                display: none;
            }
            .main-content {
                margin-left: 60px;
            }
            .sidebar-toggle {
                display: block;
            }
            .report-charts-section {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 576px) {
            .report-summary-cards, 
            .filters-grid {
                grid-template-columns: 1fr;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                    <!-- <a href="" class="nav-item">
                        <i class="fas fa-user-friends"></i> <span>Customers</span>
                    </a> -->
                    <a href="/pos/public/reports/profit_loss" class="nav-item">
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
                    <h1 class="dashboard-title">Profit & Loss Report</h1>
                    <span class="connectivity-status" id="connectivity-status">Online</span>
                </div>
                <div class="header-right">
                    <span class="last-updated">Last Updated: <span id="current-time"><?= date('Y-m-d H:i:s') ?></span></span>
                    <div class="header-actions">
                        <button class="btn-theme-toggle" onclick="toggleTheme()" id="btn-theme-toggle" title="Toggle Theme" aria-label="Toggle Theme">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Header -->
            <div class="section-header">
                <div>
                    <h3 class="section-title">Profit & Loss Report</h3>
                    <p class="section-description">Analyze your business performance with detailed profit and loss metrics</p>
                    <div class="breadcrumbs">
                        <a href="/pos/public/dashboard" class="breadcrumb-link">Dashboard</a> 
                        <span class="breadcrumb-separator">></span>
                        <span class="breadcrumb-link">Reports</span>
                        <span class="breadcrumb-separator">></span>
                        <span class="breadcrumb-current">Profit & Loss</span>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="/pos/public/reports/export-profit-loss?range=<?= $dateRange ?>&start=<?= $startDate ?>&end=<?= $endDate ?>" class="btn btn-export">
                        <i class="fas fa-file-export"></i> Export
                    </a>
                    <button class="btn btn-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
                    <!-- Filters Section -->
            <section class="filters-section">
                <form method="get" action="">
                    <input type="hidden" name="range" value="<?= htmlspecialchars($dateRange) ?>">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="date_range">Date Range:</label>
                            <select id="date_range" class="form-select" onchange="updateDateRange()">
                                <option value="today" <?= $dateRange === 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="yesterday" <?= $dateRange === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                                <option value="this_week" <?= $dateRange === 'this_week' ? 'selected' : '' ?>>This Week</option>
                                <option value="last_week" <?= $dateRange === 'last_week' ? 'selected' : '' ?>>Last Week</option>
                                <option value="this_month" <?= $dateRange === 'this_month' ? 'selected' : '' ?>>This Month</option>
                                <option value="last_month" <?= $dateRange === 'last_month' ? 'selected' : '' ?>>Last Month</option>
                                <option value="this_year" <?= $dateRange === 'this_year' ? 'selected' : '' ?>>This Year</option>
                                <option value="last_year" <?= $dateRange === 'last_year' ? 'selected' : '' ?>>Last Year</option>
                                <option value="custom" <?= $dateRange === 'custom' ? 'selected' : '' ?>>Custom</option>
                            </select>
                        </div>
                        <div class="form-group" id="custom-date-range" style="<?= $dateRange === 'custom' ? 'display:block' : 'display:none' ?>">
                            <label for="start_date">From:</label>
                            <input type="date" id="start_date" name="start" class="form-input" 
                                value="<?= htmlspecialchars($startDate) ?>">
                            <label for="end_date">To:</label>
                            <input type="date" id="end_date" name="end" class="form-input" 
                                value="<?= htmlspecialchars($endDate) ?>">
                        </div>
                        <button type="submit" class="btn">Apply</button>
                    </div>
                </form>
            </section>

            <!-- Summary Cards Section -->
            <section class="report-summary-cards">
                <!-- Total Sales Card -->
                <div class="summary-card">
                    <div class="card-header">
                    <h3>Total Sales</h3>
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-value"><?= number_format($salesData['total_sales'], 2) ?> KSh</div>
                <div class="card-subtext">
                    <?= $salesData['transaction_count'] ?> transactions total<br>
                    (<?= $salesData['sale_count'] ?> sales, <?= $salesData['refund_count'] ?> refunds)<br>
                    <?= number_format($salesData['discounts'], 2) ?> KSh discounts
                </div>
                </div>
                
                <!-- Net Sales Card -->
                <div class="summary-card">
                    <div class="card-header">
                        <h3>Net Sales</h3>
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="card-value"><?= number_format($salesData['net_sales'], 2) ?> KSh</div>
                    <div class="card-subtext">
                        After <?= number_format($salesData['refunds'], 2) ?> KSh refunds
                    </div>
                </div>
                
                <!-- Gross Profit Card -->
                <div class="summary-card">
                    <div class="card-header">
                        <h3>Gross Profit</h3>
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-value"><?= number_format($salesData['gross_profit'], 2) ?> KSh</div>
                    <div class="card-subtext">
                        COGS: <?= number_format($salesData['cost_of_goods_sold'], 2) ?> KSh
                    </div>
                </div>
                
                <!-- Total Expenses Card -->
                <div class="summary-card">
                    <div class="card-header">
                        <h3>Total Expenses</h3>
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="card-value"><?= number_format($salesData['total_expenses'], 2) ?> KSh</div>
                    <div class="card-subtext">
                        Operating: <?= number_format($salesData['other_expenses'], 2) ?> KSh
                    </div>
                </div>
                
                <!-- Net Profit Card -->
                <div class="summary-card highlight">
                    <div class="card-header">
                        <h3>Net Profit</h3>
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="card-value <?= $netProfitClass ?>">
                        <?= number_format($salesData['net_profit'], 2) ?> KSh
                    </div>
                    <div class="card-subtext">
                        <small>
                            (<?= number_format($salesData['total_sales'], 2) ?> Sales<br>
                            - <?= number_format($salesData['cost_of_goods_sold'], 2) ?> COGS<br>
                            - <?= number_format($salesData['refunds'], 2) ?> Refunds<br>
                            - <?= number_format($salesData['other_expenses'], 2) ?> Op. Expenses)
                        </small>
                    </div>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="report-charts-section">
                <div class="chart-container">
                    <h3>Sales by Category</h3>
                    <div class="chart">
                        <canvas id="sales-by-category-chart" height="250"></canvas>
                    </div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesData['by_category'] as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['category']) ?></td>
                                <td><?= number_format($category['total'], 2) ?> KSh</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="chart-container">
                    <h3>Expenses by Category</h3>
                    <div class="chart">
                        <canvas id="expenses-by-category-chart" height="250"></canvas>
                    </div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expensesData['by_category'] as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['category'] ?? 'Uncategorized') ?></td>
                                <td><?= number_format($category['total'], 2) ?> KSh</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

        // Toggle custom date range visibility
        function updateDateRange() {
            const rangeSelect = document.getElementById('date_range');
            const customRangeDiv = document.getElementById('custom-date-range');
            customRangeDiv.style.display = rangeSelect.value === 'custom' ? 'block' : 'none';
        }

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
                // Verify data is available
                console.log('Sales by category data:', <?= json_encode($salesData['by_category']) ?>);
                console.log('Expenses by category data:', <?= json_encode($expensesData['by_category']) ?>);

                // Sales by Category Chart
                const salesCtx = document.getElementById('sales-by-category-chart').getContext('2d');
                if (salesCtx) {
                    new Chart(salesCtx, {
                        type: 'doughnut',
                        data: {
                            labels: <?= json_encode(array_column($salesData['by_category'], 'category')) ?>,
                            datasets: [{
                                data: <?= json_encode(array_column($salesData['by_category'], 'total')) ?>,
                                backgroundColor: [
                                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                                    '#ec4899', '#14b8a6', '#f97316', '#64748b', '#a855f7'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': KSh ' + context.raw.toLocaleString('en-US');
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    console.error('Sales chart canvas not found');
                }

                // Expenses by Category Chart
                const expensesCtx = document.getElementById('expenses-by-category-chart').getContext('2d');
                if (expensesCtx) {
                    new Chart(expensesCtx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode(array_column($expensesData['by_category'], 'category')) ?>,
                            datasets: [{
                                label: 'Expenses (KSh)',
                                data: <?= json_encode(array_column($expensesData['by_category'], 'total')) ?>,
                                backgroundColor: '#ef4444',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'KSh ' + value.toLocaleString('en-US');
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'KSh ' + context.raw.toLocaleString('en-US');
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    console.error('Expenses chart canvas not found');
                }

            // Set the select dropdown to match current range
            document.getElementById('date_range').value = '<?= $dateRange ?>';

            updateDateRange();
        });
    </script>
</body>
</html>