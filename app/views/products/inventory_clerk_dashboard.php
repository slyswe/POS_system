<?php
// Role check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'inventory_clerk') {
    header('Location: /pos/public/login');
    exit;
}

// Initialize variables with proper fallbacks
$title = $title ?? "Inventory Clerk Dashboard";
$products = $products ?? [];
$total = $total ?? 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = $perPage ?? 10;
$clerk = $_SESSION['clerk'] ?? ['name' => 'Clerk1', 'id' => '12345'];
$suppliers = $suppliers ?? [];
$batches = $batches ?? [];

// Initialize data structures
$grouped_products = [];
$categories = [];
$categoryValues = []; // array for individual category values.
$totalInventoryValue = 0;

// Single-pass processing of products
foreach ($products as $product) {
    $category = $product['category_name'] ?? 'Uncategorized';
    $category_id = $product['category_id'] ?? md5($category);
    
    // Initialize category if not exists
    if (!isset($grouped_products[$category_id])) {
        $grouped_products[$category_id] = [
            'name' => $category,
            'products' => []
        ];
        $categories[$category_id] = $category;
        $categoryValues[$category_id] = 0; // Initialize category value
    }
    
    // Add product to group
    $grouped_products[$category_id]['products'][] = $product;
    
    // Calculate and accumulate values
    $productValue = ($product['cost_price'] ?? 0) * $product['stock'];
    $categoryValues[$category_id] += $productValue;
    $totalInventoryValue += $productValue;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Base Styles */
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f1f3f5;
            color: #212529;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        
        .header-left {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .page-title {
            font-size: 1.8rem;
            margin: 0;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Main Content Layout */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }
        
        .primary-panel {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        
        /* Panel Styles */
        .panel {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .panel-title {
            font-size: 1.3rem;
            color: #374151;
            margin: 0;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }
        
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
        }
        
        /* Stock Status Indicators */
        .low-stock { background-color: #fef3c7; color: #b45309; }
        .out-of-stock { background-color: #fee2e2; color: #991b1b; }
        .aging-stock { background-color: #fce7f3; color: #9d174d; }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        /* Button Styles */
        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: #1e3a8a;
            color: #fff;
        }
        
        .btn-success {
            background-color: #10b981;
            color: #fff;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: #fff;
        }
        
        .btn-secondary {
            background-color: #6b7280;
            color: #fff;
        }
        .btn-
        
        /* Tabs Navigation */
        .tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 15px;
        }
        
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .tab.active {
            border-bottom-color: #1e3a8a;
            font-weight: 600;
        }
        
        /* Valuation Card */
        .valuation-card {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            max-height: 150px;
        }
        
        .valuation-title {
            font-size: 1rem;
            margin-top: 0;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .valuation-amount {
            font-size: 1.8rem;
            margin: 0;
            font-weight: 600;
        }
        #inventoryValueChart {
            max-height: 300px;
            width: 100% !important;
            height: auto !important;
        }
        #inventoryValueChart-container {
            position: relative;
            height: 300px;
            max-height: 50vh;
            width: 100%;
        }

        .chart-fallback {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .chart-fallback i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        /* Dark Theme */
        .dark-theme {
            background-color: #1f2937;
            color: #d1d5db;
        }
        
        .dark-theme .panel {
            background-color: #374151;
        }
        
        .dark-theme .panel-title {
            color: #d1d5db;
        }
        
        .dark-theme .data-table th {
            background-color: #1f2937;
            color: #d1d5db;
        }
        
        .dark-theme .data-table td {
            border-bottom-color: #6b7280;
        }
        
        .dark-theme .valuation-card {
            background: linear-gradient(135deg, #374151, #4b5563);
        }
            /* Modal Overlay Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow: auto;
    }
    
    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        width: 90%;
        max-width: 500px;
    }
    
    .modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .modal-close:hover {
        color: #000;
    }
    
    .modal h2 {
        margin-top: 0;
        color: #1e3a8a;
    }
    
    .dark-theme .modal-content {
        background-color: #374151;
        color: #d1d5db;
    }
    
    .dark-theme .modal h2 {
        color: #3b82f6;
    }
    
    .dark-theme .modal-close {
        color: #9ca3af;
    }
    
    .dark-theme .modal-close:hover {
        color: #fff;
    }
        
        /* Responsive Adjustments */
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            #inventoryValueChart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Header -->
    <header class="page-header">
        <div class="header-left">
            <h1 class="page-title"><?= htmlspecialchars($title) ?></h1>
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <span class="breadcrumb-current" aria-current="page">Inventory Management</span>
            </nav>
        </div>
        <div class="header-right">
            <span class="clerk-info" aria-label="Clerk information">
                <i class="fas fa-user-circle" aria-hidden="true"></i>
                <?= htmlspecialchars($clerk['name']) ?> (ID: <?= htmlspecialchars($clerk['id']) ?>)
            </span>
            <button class="btn btn-secondary" onclick="toggleFinancialView()" id="financial-view-toggle">
                <i class="fas fa-dollar-sign"></i> Financial View
            </button>
            <button class="btn btn-secondary" onclick="toggleTheme()" title="Toggle Theme" aria-label="Toggle theme">
                <i class="fas fa-moon" aria-hidden="true"></i>
            </button>
            <a href="/pos/public/logout" class="btn btn-danger" title="Logout" aria-label="Logout">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="primary-panel">
            <!-- Inventory Management Panel -->
            <div class="panel inventory-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Stock Management</h2>
                    <div>
                        <button class="btn btn-success" onclick="showReceivingPanel()">
                            <i class="fas fa-truck"></i> Receive Inventory
                        </button>
                        <a href="/pos/public/products/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Product
                        </a>
                    </div>
                </div>
                
                <div class="filter-section">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon" aria-hidden="true"></i>
                        <input type="text" id="search-input" class="form-control" placeholder="Search products..." 
                               oninput="searchProducts()" aria-label="Search products">
                    </div>
                    <div class="filter-group">
                        <select id="category-filter" class="form-control" onchange="filterProducts()" 
                                aria-label="Filter by category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $id => $name): ?>
                                <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="stock-filter" class="form-control" onchange="filterProducts()" 
                                aria-label="Filter by stock level">
                            <option value="">All Stock Levels</option>
                            <option value="low">Low Stock (<10)</option>
                            <option value="out">Out of Stock</option>
                            <option value="aging">Aging Stock (>90 days)</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="data-table" id="inventory-table" aria-label="Product stock list">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th class="financial-column">Buying Price</th>
                                <th>Selling Price</th>
                                <th>Barcode</th>
                                <th>Batch</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_products as $cat_id => $cat): ?>
                                <?php foreach ($cat['products'] as $product): 
                                    $stock_class = '';
                                    if ($product['stock'] <= 0) $stock_class = 'out-of-stock';
                                    elseif ($product['stock'] < 10) $stock_class = 'low-stock';
                                    elseif (isset($product['days_in_stock']) && $product['days_in_stock'] > 90) $stock_class = 'aging-stock';
                                    
                                    $product_batches = array_filter($batches, function($b) use ($product) {
                                        return $b['product_id'] == $product['id'];
                                    });
                                ?>
                                <tr data-product-id="<?= $product['id'] ?>" data-stock-level="<?= $stock_class ?>">
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($cat['name']) ?></td>
                                    <td class="financial-column">
                                        <span class="cost-price"><?= number_format($product['cost_price'], 2) ?></span> KSh
                                        <button class="btn-edit-cost" onclick="editCostPrice(event, <?= $product['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                    <td><?= number_format($product['price'], 2) ?> KSh</td>
                                    <td><?= htmlspecialchars($product['barcode'] ?? 'N/A') ?></td>
                                    <td>
                                        <select class="batch-select" data-product="<?= $product['id'] ?>">
                                            <?php foreach ($product_batches as $batch): ?>
                                            <option value="<?= $batch['id'] ?>">
                                                #<?= $batch['id'] ?> (<?= date('m/d/Y', strtotime($batch['received_date'])) ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="stock-cell <?= $stock_class ?>">
                                        <?= htmlspecialchars($product['stock']) ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="adjustStock(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">
                                            <i class="fas fa-exchange-alt"></i> Adjust
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Inventory Valuation Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Inventory Valuation</h2>
                </div>
                <div class="valuation-card">
                    <h3 class="valuation-title">Total Inventory Value</h3>
                    <p class="valuation-amount"><?= number_format($totalInventoryValue, 2) ?> KSh</p>
                </div>
                <div id="inventoryValueChart-container">
                    <canvas id="inventoryValueChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Panels -->
        <div class="secondary-panel">
            <!-- Receiving Panel (Initially Hidden) -->
            <div class="panel receiving-panel" id="receiving-panel" style="display: none;">
                <div class="panel-header">
                    <h2 class="panel-title">Receive Inventory</h2>
                    <button class="btn btn-danger btn-sm" onclick="hideReceivingPanel()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="receiving-form">
                    <div class="form-group">
                        <label for="supplier">Supplier</label>
                        <select id="supplier" class="form-control" required>
                            <option value="">Select Supplier</option>
                            <!-- Options will be inserted here by JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="invoice-ref">Invoice Reference</label>
                        <input type="text" id="invoice-ref" class="form-control" readonly required>
                        <small class="form-text text-muted">Auto-generated based on date</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="receive-date">Receive Date</label>
                        <input type="date" id="receive-date" class="form-control" required 
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div id="receiving-items">
                        <!-- Items will be added here dynamically -->
                        <div class="receiving-item">
                            <div class="form-group">
                                <label>Product</label>
                                <select class="form-control item-product" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" class="form-control item-quantity" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Unit Cost</label>
                                <input type="number" step="0.01" class="form-control item-cost" required>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" id="add-receiving-item">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Process Receipt
                    </button>

                    
                </form>
            </div>
            
            <!-- Quick Stats Panel -->
            <div class="panel quick-stats-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Quick Stats</h2>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= count($products) ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count(array_filter($products, fn($p) => $p['stock'] < 10)) ?></div>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count(array_filter($products, fn($p) => $p['stock'] <= 0)) ?></div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                </div>
            </div>
            
            
        </div>
    </main>
</div>

<!-- Stock Adjustment Modal -->
<div id="stock-modal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h2>Adjust Stock for <span id="modal-product-name"></span></h2>
        <form id="stock-form">
            <input type="hidden" id="product-id">
            <input type="hidden" id="batch-id">
            
            <div class="form-group">
                <label for="stock-change">Quantity Change</label>
                <div class="input-group">
                    <select id="change-type" class="form-control" style="max-width: 100px;">
                        <option value="+">Add</option>
                        <option value="-">Remove</option>
                    </select>
                    <input type="number" id="stock-change" class="form-control" min="1" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason">Reason</label>
                <select id="reason" class="form-control" required>
                    <option value="">Select Reason</option>
                    <option value="restock">Restock</option>
                    <option value="damage">Damage</option>
                    <option value="theft">Theft</option>
                    <option value="expired">Expired</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group" id="supplier-group" style="display: none;">
                <label for="supplier">Supplier</label>
                <select id="supplier" class="form-control">
                    <option value="">Select Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="cost-group" style="display: none;">
                <label for="unit-cost">Unit Cost</label>
                <input type="number" step="0.01" id="unit-cost" class="form-control">
            </div>
            
            <div class="form-group" id="invoice-group" style="display: none;">
                <label for="invoice-ref">Invoice Reference</label>
                <input type="text" id="invoice-ref" class="form-control">
            </div>
            
            <div class="form-group" id="other-reason-group" style="display: none;">
                <label for="other-reason">Specify Reason</label>
                <input type="text" id="other-reason" class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Adjustment</button>
        </form>
    </div>
</div>

<!-- Cost Price Edit Modal -->
<div id="cost-modal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeCostModal()">&times;</span>
        <h2>Edit Cost Price for <span id="cost-modal-product-name"></span></h2>
        <form id="cost-form">
            <input type="hidden" id="cost-product-id">
            
            <div class="form-group">
                <label for="new-cost">New Cost Price (KSh)</label>
                <input type="number" step="0.01" id="new-cost" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="cost-change-reason">Reason for Change</label>
                <input type="text" id="cost-change-reason" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Cost</button>
        </form>
    </div>
</div>


<script>
// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Chart
    initInventoryChart();

    // Load suppliers first
    fetchSuppliers();
    
    // Set up event listeners
    setupEventListeners();
    
    // Check if financial view should be shown
    if (localStorage.getItem('financialView') === 'true') {
        showFinancialColumns();
    }

    // Generate initial invoice number
    updateInvoiceNumber();

    console.log("Canvas exists:", !!document.getElementById('inventoryValueChart'));
    initInventoryChart();
});

// Initialize Inventory Value Chart
function initInventoryChart() {
    // Get chart canvas element safely
    const ctx = document.getElementById('inventoryValueChart');
    if (!ctx) {
        console.error("Chart container not found");
        return;
    }

    try {
        // Initialize the chart
        const chart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_values($categories)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($categoryValues)) ?>,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', 
                        '#ec4899', '#14b8a6', '#f97316', '#64748b', '#84cc16'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw.toLocaleString()} KSh`;
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        right: 10,
                        bottom: 10,
                        left: 10
                    }
                },
                // Added animation configuration
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Store chart reference for updates
        window.inventoryChart = chart;
        
    } catch (e) {
        console.error("Chart initialization failed:", e);
        // Create comprehensive fallback display
        ctx.innerHTML = `
            <div class="chart-fallback" style="
                text-align: center;
                padding: 20px;
                color: #666;
                background: #f8f9fa;
                border-radius: 8px;
                border: 1px dashed #ddd;
            ">
                <i class="fas fa-chart-pie" style="
                    font-size: 2rem;
                    margin-bottom: 10px;
                    color: #6c757d;
                "></i>
                <p style="margin: 5px 0; font-weight: 500;">Inventory Valuation Data Unavailable</p>
                <p style="margin: 5px 0;">Total Value: <strong><?= number_format($totalInventoryValue, 2) ?> KSh</strong></p>
                <small style="color: #999;">Chart cannot be displayed due to technical error</small>
            </div>
        `;
        
        // If Font Awesome fails to load
        if (typeof FontAwesome === 'undefined') {
            ctx.querySelector('i').style.display = 'none';
        }
    }
}

async function refreshValuation() {
    try {
        const response = await fetch('/pos/public/api/inventory/valuation');
        const data = await response.json();
        
        if (!data || !data.categories) throw new Error("Invalid data");
        
        // Update total display
        document.querySelector('.valuation-amount').textContent = 
            data.total.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' KSh';
        
        // Update chart if it exists
        if (window.inventoryChart) {
            window.inventoryChart.data.datasets[0].data = Object.values(data.categories);
            window.inventoryChart.data.labels = Object.keys(data.categories);
            window.inventoryChart.update();
        }
        
    } catch (error) {
        console.error("Failed to refresh valuation:", error);
    }
}

// Set up event listeners
function setupEventListeners() {
    // Receiving form
    document.getElementById('add-receiving-item').addEventListener('click', addReceivingItem);
    document.getElementById('receiving-form').addEventListener('submit', processReceiving);
    
    // Stock adjustment form
    document.getElementById('reason').addEventListener('change', function() {
        const isRestock = this.value === 'restock';
        document.getElementById('supplier-group').style.display = isRestock ? 'block' : 'none';
        document.getElementById('cost-group').style.display = isRestock ? 'block' : 'none';
        document.getElementById('invoice-group').style.display = isRestock ? 'block' : 'none';
        document.getElementById('other-reason-group').style.display = this.value === 'other' ? 'block' : 'none';
    });
    
    document.getElementById('stock-form').addEventListener('submit', submitStockAdjustment);
    document.getElementById('cost-form').addEventListener('submit', submitCostUpdate);
    
    // Remove item buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item') || 
            e.target.closest('.remove-item')) {
            const item = e.target.closest('.receiving-item');
            if (document.querySelectorAll('.receiving-item').length > 1) {
                item.remove();
            } else {
                item.querySelectorAll('input, select').forEach(el => el.value = '');
            }
        }
    });

    document.getElementById('receive-date').addEventListener('change', updateInvoiceNumber);
}

//function to update invoice number
function updateInvoiceNumber() {
    const receiveDate = document.getElementById('receive-date').value;
    
    const dateParam = receiveDate ? receiveDate.replace(/-/g, '') : '';
    
    fetch(`/pos/public/api/inventory/next-invoice?date=${dateParam}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('invoice-ref').value = data.invoiceNumber;
            } else {
                console.error('Failed to get invoice number:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching invoice number:', error);
        });
}

// Fetch suppliers when page loads
document.addEventListener('DOMContentLoaded', function() {
    // This runs when page loads
    fetch('/pos/public/api/suppliers')
        .then(response => response.json())
        .then(data => {
            const supplierSelect = document.getElementById('supplier');
            
            // Clear loading message
            supplierSelect.innerHTML = '<option value="">Select Supplier</option>';
            
            // Add each supplier as a dropdown option
            data.suppliers.forEach(supplier => {
                const option = document.createElement('option');
                option.value = supplier.id;
                option.textContent = supplier.name;
                supplierSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading suppliers:', error);
            document.getElementById('supplier').innerHTML = 
                '<option value="">Error loading suppliers</option>';
        });
});

function fetchSuppliers() {
    const supplierSelect = document.getElementById('supplier');
    supplierSelect.innerHTML = '<option value="">Loading suppliers...</option>';
    
    fetch('/pos/public/api/suppliers')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            supplierSelect.innerHTML = '<option value="">Select Supplier</option>';
            
            if (data.success && data.suppliers && data.suppliers.length > 0) {
                data.suppliers.forEach(supplier => {
                    const option = document.createElement('option');
                    option.value = supplier.id;
                    option.textContent = supplier.name;
                    supplierSelect.appendChild(option);
                });
            } else {
                supplierSelect.innerHTML = '<option value="">No suppliers found</option>';
            }
        })
        .catch(error => {
            console.error('Error loading suppliers:', error);
            supplierSelect.innerHTML = '<option value="">Error loading suppliers</option>';
        });
}



// Financial View Toggle
function toggleFinancialView() {
    if (localStorage.getItem('financialView') === 'true') {
        hideFinancialColumns();
        localStorage.setItem('financialView', 'false');
    } else {
        showFinancialColumns();
        localStorage.setItem('financialView', 'true');
    }
}

function showFinancialColumns() {
    document.querySelectorAll('.financial-column').forEach(col => {
        col.style.display = 'table-cell';
    });
    document.getElementById('financial-view-toggle').innerHTML = 
        '<i class="fas fa-dollar-sign"></i> Hide Financials';
}

function hideFinancialColumns() {
    document.querySelectorAll('.financial-column').forEach(col => {
        col.style.display = 'none';
    });
    document.getElementById('financial-view-toggle').innerHTML = 
        '<i class="fas fa-dollar-sign"></i> Financial View';
}

// Receiving Panel Functions
function showReceivingPanel() {
    document.getElementById('receiving-panel').style.display = 'block';
}

function hideReceivingPanel() {
    document.getElementById('receiving-panel').style.display = 'none';
}

function addReceivingItem() {
    const newItem = document.createElement('div');
    newItem.className = 'receiving-item';
    newItem.innerHTML = `
        <div class="form-group">
            <label>Product</label>
            <select class="form-control item-product" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" class="form-control item-quantity" min="1" required>
        </div>
        <div class="form-group">
            <label>Unit Cost</label>
            <input type="number" step="0.01" class="form-control item-cost" required>
        </div>
        <button type="button" class="btn btn-danger btn-sm remove-item">
            <i class="fas fa-trash"></i>
        </button>
    `;
    document.getElementById('receiving-items').appendChild(newItem);
}

function processReceiving(e) {
    e.preventDefault();
    
    const supplierId = document.getElementById('supplier').value;
    const invoiceRef = document.getElementById('invoice-ref').value;
    const receiveDate = document.getElementById('receive-date').value;
    
    const items = [];
    document.querySelectorAll('.receiving-item').forEach(item => {
        const productId = item.querySelector('.item-product').value;
        const quantity = item.querySelector('.item-quantity').value;
        const unitCost = item.querySelector('.item-cost').value;
        
        if (productId && quantity && unitCost) {
            items.push({
                product_id: productId,
                quantity: quantity,
                unit_cost: unitCost
            });
        }
    });
    
    if (items.length === 0) {
        alert('Please add at least one valid item');
        return;
    }
    
    // Send to server
    fetch('/pos/api/inventory/receive', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            supplier_id: supplierId,
            invoice_ref: invoiceRef,
            receive_date: receiveDate,
            items: items
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Inventory received successfully!');
            location.reload(); // Refresh to show new stock
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to process receipt');
    });
}

// Stock Adjustment Functions
function adjustStock(productId, productName) {
    document.getElementById('product-id').value = productId;
    document.getElementById('modal-product-name').textContent = productName;
    
    // Set the batch ID from the selected batch dropdown
    const batchSelect = document.querySelector(`.batch-select[data-product="${productId}"]`);
    if (batchSelect) {
        document.getElementById('batch-id').value = batchSelect.value;
    }
    
    document.getElementById('stock-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('stock-modal').style.display = 'none';
    document.getElementById('stock-form').reset();
    document.getElementById('supplier-group').style.display = 'none';
    document.getElementById('cost-group').style.display = 'none';
    document.getElementById('invoice-group').style.display = 'none';
    document.getElementById('other-reason-group').style.display = 'none';
}

function submitStockAdjustment(e) {
    e.preventDefault();
    
    const productId = document.getElementById('product-id').value;
    const batchId = document.getElementById('batch-id').value;
    const changeType = document.getElementById('change-type').value;
    const quantity = document.getElementById('stock-change').value;
    const reason = document.getElementById('reason').value;
    const otherReason = document.getElementById('other-reason').value;
    const supplierId = document.getElementById('supplier').value;
    const unitCost = document.getElementById('unit-cost').value;
    const invoiceRef = document.getElementById('invoice-ref').value;
    
    const adjustmentData = {
        product_id: productId,
        batch_id: batchId,
        change_type: changeType === '+' ? 'add' : 'remove',
        change_amount: quantity,
        reason: reason === 'other' ? otherReason : reason,
        submitted_by: <?= $clerk['id'] ?>,
        supplier_id: reason === 'restock' ? supplierId : null,
        unit_cost: reason === 'restock' ? unitCost : null,
        invoice_ref: reason === 'restock' ? invoiceRef : null
    };
    
    fetch('/pos/public/api/inventory/adjustment-request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(adjustmentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Stock adjustment request submitted for approval');
            closeModal();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit adjustment request');
    });
}

// Cost Price Edit Functions
function editCostPrice(e, productId) {
    e.stopPropagation();
    
    const productName = e.target.closest('tr').querySelector('td:first-child').textContent;
    const currentCost = e.target.closest('td').querySelector('.cost-price').textContent;
    
    document.getElementById('cost-product-id').value = productId;
    document.getElementById('cost-modal-product-name').textContent = productName;
    document.getElementById('new-cost').value = currentCost;
    
    document.getElementById('cost-modal').style.display = 'block';
}

function submitCostUpdate(e) {
    e.preventDefault();
    
    const productId = document.getElementById('cost-product-id').value;
    const newCost = document.getElementById('new-cost').value;
    const reason = document.getElementById('cost-change-reason').value;
    
    if (!productId || !newCost || !reason) {
        alert('Please fill all required fields');
        return;
    }
    
    fetch('/pos/public/api/inventory/cost-change-request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            new_cost: newCost,
            reason: reason,
            submitted_by: <?= $clerk['id'] ?>
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Cost change request submitted for approval');
            closeCostModal();
            location.reload(); // Refresh to show pending changes
        } else {
            throw new Error(data.message || 'Failed to submit request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}
function closeCostModal() {
    document.getElementById('cost-modal').style.display = 'none';
    document.getElementById('cost-form').reset();
}

function submitCostUpdate(e) {
    e.preventDefault();
    
    const productId = document.getElementById('cost-product-id').value;
    const newCost = document.getElementById('new-cost').value;
    const reason = document.getElementById('cost-change-reason').value;
    
    fetch('/pos/api/products/update-cost', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            new_cost: newCost,
            reason: reason,
            updated_by: <?= $clerk['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cost price updated successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update cost price');
    });
}

// Theme Toggle
function toggleTheme() {
    document.body.classList.toggle('dark-theme');
    const icon = document.querySelector('.btn-theme-toggle i');
    if (icon) {
        icon.classList.toggle('fa-moon');
        icon.classList.toggle('fa-sun');
    }
    localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
}

// Initialize theme
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-theme');
    const icon = document.querySelector('.btn-theme-toggle i');
    if (icon) {
        icon.classList.replace('fa-moon', 'fa-sun');
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal();
        closeCostModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
        closeCostModal();
    }
});
</script>
</body>
</html>