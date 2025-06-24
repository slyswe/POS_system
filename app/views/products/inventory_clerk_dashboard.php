<?php
// Role check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Inventory Clerk') {
    header('Location: /pos/public/login');
    exit;
}

// Initialize variables with proper fallbacks
$title = $title ?? "Inventory Clerk Dashboard";
$products = $products ?? [];
$total = $total ?? 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = $perPage ?? 10;
$clerk = $_SESSION['clerk'] ?? ['name' => 'John Doe', 'id' => '12345'];

// Group products by category
$grouped_products = [];
$categories = [];
foreach ($products as $product) {
    $category = $product['category_name'] ?? 'Uncategorized';
    $category_id = $product['category_id'] ?? md5($category);
    if (!isset($grouped_products[$category_id])) {
        $grouped_products[$category_id] = [
            'name' => $category,
            'products' => []
        ];
        $categories[$category_id] = $category;
    }
    $grouped_products[$category_id]['products'][] = $product;
}
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="products-container" role="main">
    <!-- Header -->
    <header class="page-header">
        <div class="header-left">
            <h1 class="page-title"><?= htmlspecialchars($title) ?></h1>
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="/pos/public/products" class="breadcrumb-link">Dashboard</a> 
                <span class="breadcrumb-separator" aria-hidden="true">></span>
                <span class="breadcrumb-current" aria-current="page">Stock Management</span>
            </nav>
        </div>
        <div class="header-right">
            <span class="clerk-info" aria-label="Clerk information">
                <i class="fas fa-user-circle" aria-hidden="true"></i>
                <?= htmlspecialchars($clerk['name']) ?> (ID: <?= htmlspecialchars($clerk['id']) ?>)
            </span>
            <span id="current-time" class="time-display" aria-live="polite"><?= date('Y-m-d H:i') ?></span>
            <span class="connectivity-status" id="connectivity-status" aria-live="polite">
                <i class="fas fa-circle" aria-hidden="true"></i> Online
            </span>
            <button class="btn btn-theme-toggle" onclick="toggleTheme()" title="Toggle Theme" aria-label="Toggle theme">
                <i class="fas fa-moon" aria-hidden="true"></i>
            </button>
            <a href="/pos/public/logout" class="btn btn-logout" title="Logout" aria-label="Logout">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            </a>
        </div>
    </header>

    <!-- Alerts -->
    <div class="alerts-section" role="alert" aria-live="assertive">
        <div id="alerts" class="alerts">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']) ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="products-panel">
            <div class="panel-header">
                <h2 class="panel-title">Stock Management</h2>
            </div>
            
            <div class="filter-section">
                <div class="search-bar">
                    <i class="fas fa-search search-icon" aria-hidden="true"></i>
                    <input type="text" id="search-input" placeholder="Search products..." 
                           oninput="searchProducts()" aria-label="Search products">
                    <button class="btn btn-clear-search" onclick="clearSearch()" aria-label="Clear search">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="filter-group">
                    <select id="category-filter" class="form-select" onchange="filterProducts()" 
                            aria-label="Filter by category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $id => $name): ?>
                            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="stock-filter" class="form-select" onchange="filterProducts()" 
                            aria-label="Filter by stock level">
                        <option value="">All Stock Levels</option>
                        <option value="low">Low Stock (<10)</option>
                        <option value="out">Out of Stock</option>
                    </select>
                </div>
            </div>
            
            <div class="table-container">
                <?php if (empty($products)): ?>
                    <div class="empty-state" role="alert">
                        <i class="fas fa-box-open" aria-hidden="true"></i>
                        <h3>No Products Found</h3>
                        <p>Contact the admin to add products.</p>
                    </div>
                <?php else: ?>
                    <table class="products-table" aria-label="Product stock list">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTable(0)" 
                                    aria-sort="none" aria-label="Sort by name">
                                    Name <i class="fas fa-sort" aria-hidden="true"></i>
                                </th>
                                <th aria-label="Category">Category</th>
                                <th aria-label="Price">Price</th>
                                <th aria-label="Barcode">Barcode</th>
                                <th class="sortable" onclick="sortTable(4)" 
                                    aria-sort="none" aria-label="Sort by stock">
                                    Stock <i class="fas fa-sort" aria-hidden="true"></i>
                                </th>
                                <th aria-label="Actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="products-table-body">
                            <?php foreach ($grouped_products as $cat_id => $cat): ?>
                                <?php foreach ($cat['products'] as $product): 
                                    $stock_class = '';
                                    if ($product['stock'] <= 0) $stock_class = 'out-of-stock';
                                    elseif ($product['stock'] < 10) $stock_class = 'low-stock';
                                ?>
                                <tr data-category-id="<?= htmlspecialchars($cat_id) ?>" 
                                    data-stock-level="<?= $stock_class ?>" 
                                    data-id="<?= $product['id'] ?>">
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($cat['name']) ?></td>
                                    <td><?= number_format($product['price'], 2) ?> KSh</td>
                                    <td><?= htmlspecialchars($product['barcode'] ?? 'N/A') ?></td>
                                    <td class="stock-cell <?= $stock_class ?>">
                                        <span class="product-stock"><?= htmlspecialchars($product['stock']) ?></span>
                                    </td>
                                    <td class="action-buttons">
                                        <button class="btn btn-adjust-stock" 
                                                onclick="adjustStock(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" 
                                                title="Adjust Stock" 
                                                aria-label="Adjust stock for <?= htmlspecialchars($product['name']) ?>">
                                            <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="table-footer">
                <div class="table-summary">
                    Showing <span id="shown-count"><?= count($products) ?></span> of 
                    <span id="total-count"><?= $total ?></span> products
                </div>
                <div class="pagination" role="navigation" aria-label="Pagination">
                    <a href="?page=<?= max(1, $page - 1) ?>" 
                       class="btn btn-prev <?= $page <= 1 ? 'disabled' : '' ?>" 
                       aria-label="Previous page" 
                       <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>
                        <i class="fas fa-chevron-left" aria-hidden="true"></i> Previous
                    </a>
                    <span id="page-info" class="page-info" aria-live="polite">
                        Page <?= $page ?> of <?= ceil($total / $perPage) ?>
                    </span>
                    <a href="?page=<?= min(ceil($total / $perPage), $page + 1) ?>" 
                       class="btn btn-next <?= $page >= ceil($total / $perPage) ? 'disabled' : '' ?>" 
                       aria-label="Next page" 
                       <?= $page >= ceil($total / $perPage) ? 'aria-disabled="true"' : '' ?>>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i> Next
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Stock Adjustment Modal -->
<div id="stock-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()" aria-label="Close modal">×</span>
        <h2>Adjust Stock for <span id="modal-product-name"></span></h2>
        <form id="stock-form" onsubmit="submitStockAdjustment(event)">
            <input type="hidden" id="product-id">
            <div class="form-group">
                <label for="stock-change">Stock Change (Positive to add, negative to remove)</label>
                <input type="number" id="stock-change" required aria-label="Stock change amount">
            </div>
            <div class="form-group">
                <label for="reason">Reason</label>
                <select id="reason" required aria-label="Reason for stock adjustment">
                    <option value="">Select Reason</option>
                    <option value="restock">Restock</option>
                    <option value="damage">Damage</option>
                    <option value="theft">Theft</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group" id="other-reason-group" style="display: none;">
                <label for="other-reason">Specify Reason</label>
                <input type="text" id="other-reason" aria-label="Specify other reason">
            </div>
            <button type="submit" class="btn btn-primary" aria-label="Submit stock adjustment">Submit</button>
        </form>
    </div>
</div>

<script>
// Check connectivity
function checkConnectivity() {
    const status = navigator.onLine ? 'Online' : 'Offline';
    const statusElement = document.getElementById('connectivity-status');
    statusElement.textContent = status;
    statusElement.className = `connectivity-status ${status.toLowerCase()}`;
}
window.addEventListener('online', checkConnectivity);
window.addEventListener('offline', checkConnectivity);
checkConnectivity();

// Update time every minute
function updateTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = now.toLocaleString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}
setInterval(updateTime, 60000);
updateTime();

// Toggle theme
function toggleTheme() {
    document.body.classList.toggle('dark-theme');
    const icon = document.querySelector('.btn-theme-toggle i');
    icon.classList.toggle('fa-moon');
    icon.classList.toggle('fa-sun');
    localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
}

// Initialize theme
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-theme');
    document.querySelector('.btn-theme-toggle i').classList.replace('fa-moon', 'fa-sun');
}

// Search products
function searchProducts() {
    const query = document.getElementById('search-input').value.toLowerCase();
    const rows = document.querySelectorAll('#products-table-body tr');
    let shownCount = 0;
    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const barcode = row.cells[3].textContent.toLowerCase();
        const matches = name.includes(query) || barcode.includes(query);
        row.style.display = matches ? '' : 'none';
        if (matches) shownCount++;
    });
    document.getElementById('shown-count').textContent = shownCount;
}

// Clear search
function clearSearch() {
    document.getElementById('search-input').value = '';
    searchProducts();
}

// Filter products
function filterProducts() {
    const category = document.getElementById('category-filter').value;
    const stock = document.getElementById('stock-filter').value;
    const rows = document.querySelectorAll('#products-table-body tr');
    let shownCount = 0;
    rows.forEach(row => {
        const catId = row.dataset.categoryId;
        const stockLevel = row.dataset.stockLevel;
        const matchesCategory = !category || catId === category;
        const matchesStock = !stock || (stock === 'low' && stockLevel === 'low-stock') || 
                            (stock === 'out' && stockLevel === 'out-of-stock');
        row.style.display = matchesCategory && matchesStock ? '' : 'none';
        if (matchesCategory && matchesStock) shownCount++;
    });
    document.getElementById('shown-count').textContent = shownCount;
}

// Sort table
let sortDirection = 1;
let lastSortedColumn = -1;
function sortTable(column) {
    const tbody = document.getElementById('products-table-body');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const headers = document.querySelectorAll('.products-table th.sortable');
    
    headers.forEach((header, index) => {
        header.setAttribute('aria-sort', index === column && lastSortedColumn === column 
            ? (sortDirection === 1 ? 'ascending' : 'descending') 
            : 'none');
    });
    
    if (lastSortedColumn === column) {
        sortDirection *= -1;
    } else {
        sortDirection = 1;
    }
    lastSortedColumn = column;
    
    rows.sort((a, b) => {
        let aText = a.cells[column].textContent.toLowerCase();
        let bText = b.cells[column].textContent.toLowerCase();
        if (column === 4) {
            aText = parseInt(aText) || 0;
            bText = parseInt(bText) || 0;
            return sortDirection * (aText - bText);
        }
        return sortDirection * aText.localeCompare(bText);
    });
    
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

// Stock adjustment modal
function adjustStock(id, productName) {
    document.getElementById('product-id').value = id;
    document.getElementById('modal-product-name').textContent = productName;
    document.getElementById('stock-modal').style.display = 'block';
    document.getElementById('stock-change').focus();
}

function closeModal() {
    document.getElementById('stock-modal').style.display = 'none';
    document.getElementById('stock-form').reset();
    document.getElementById('other-reason-group').style.display = 'none';
}

function submitStockAdjustment(event) {
    event.preventDefault();
    const id = document.getElementById('product-id').value;
    const change = document.getElementById('stock-change').value;
    const reason = document.getElementById('reason').value;
    const otherReason = document.getElementById('other-reason').value;
    
    if (!reason) {
        showAlert('error', 'Please select a reason.');
        return;
    }
    if (reason === 'other' && !otherReason.trim()) {
        showAlert('error', 'Please specify a reason.');
        return;
    }
    
    fetch('/pos/public/products/adjust-stock', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id,
            change: parseInt(change),
            reason: reason === 'other' ? otherReason : reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                const stockCell = row.querySelector('.stock-cell');
                const stockSpan = row.querySelector('.product-stock');
                const newStock = data.newStock;
                stockSpan.textContent = newStock;
                stockCell.classList.remove('low-stock', 'out-of-stock');
                if (newStock <= 0) {
                    stockCell.classList.add('out-of-stock');
                    row.dataset.stockLevel = 'out-of-stock';
                } else if (newStock < 10) {
                    stockCell.classList.add('low-stock');
                    row.dataset.stockLevel = 'low-stock';
                } else {
                    row.dataset.stockLevel = '';
                }
                showAlert('success', 'Stock updated successfully.');
            }
            closeModal();
        } else {
            showAlert('error', data.message || 'Stock update failed.');
        }
    })
    .catch(() => showAlert('error', 'Network error.'));
}

document.getElementById('reason').addEventListener('change', function() {
    document.getElementById('other-reason-group').style.display = 
        this.value === 'other' ? 'block' : 'none';
});

// Show alerts
function showAlert(type, message) {
    const alertsDiv = document.getElementById('alerts');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" aria-hidden="true"></i> ${message}`;
    alertsDiv.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('stock-modal').style.display === 'block') {
        closeModal();
    }
    if (e.key === 'Enter' && e.target.id === 'stock-change') {
        document.getElementById('reason').focus();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    searchProducts();
    filterProducts();
});
</script>

<style>
body {
    font-family: 'Roboto', Arial, sans-serif;
    background-color: #f1f3f5;
    color: #212529;
    margin: 0;
    padding: 0;
    line-height: 1.6;
}
.products-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 20px;
}
.page-header {
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
    flex-direction: column;
    gap: 5px;
}
.page-title {
    font-size: 1.8rem;
    margin: 0;
}
.breadcrumbs {
    font-size: 0.9rem;
}
.breadcrumb-link {
    color: #dbeafe;
    text-decoration: none;
}
.breadcrumb-link:hover, .breadcrumb-link:focus {
    color: #fff;
    outline: 2px solid #fff;
}
.header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}
.clerk-info, .time-display {
    font-size: 0.9rem;
    background-color: rgba(255,255,255,0.2);
    padding: 5px 10px;
    border-radius: 6px;
}
.connectivity-status {
    font-size: 0.9rem;
    background-color: rgba(255,255,255,0.2);
    padding: 5px 10px;
    border-radius: 6px;
}
.connectivity-status.offline {
    background-color: #ef4444;
}
.btn-theme-toggle, .btn-logout {
    background-color: #6b7280;
    color: #fff;
    border: none;
    padding: 8px;
    border-radius: 6px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.btn-theme-toggle:hover, .btn-theme-toggle:focus, 
.btn-logout:hover, .btn-logout:focus {
    background-color: #4b5563;
    outline: 2px solid #fff;
}
.btn-logout:hover, .btn-logout:focus {
    background-color: #dc2626;
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
    display: flex;
    align-items: center;
    gap: 8px;
}
.alert-error { background-color: #fee2e2; color: #991b1b; }
.alert-success { background-color: #d1fae5; color: #065f46; }
.main-content {
    background-color: #f1f3f5;
}
.products-panel {
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
    font-size: 1.5rem;
    color: #374151;
    margin: 0;
}
.filter-section {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.search-bar {
    position: relative;
    flex: 1;
    min-width: 200px;
}
.search-bar input {
    width: 100%;
    padding: 10px 10px 10px 35px;
    font-size: 0.95rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background-color: #fff;
}
.search-bar input:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 5px rgba(30,58,138,0.3);
    outline: none;
}
.search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}
.btn-clear-search {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
}
.filter-section select {
    padding: 10px;
    font-size: 0.95rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background-color: #f3f4f6;
}
.filter-section select:focus {
    border-color: #1e3a8a;
    outline: none;
}
.table-container {
    overflow-x: auto;
}
.products-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.95rem;
}
.products-table th, .products-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.products-table th {
    background-color: #f3f4f6;
    color: #374151;
    font-weight: 600;
}
.products-table th.sortable {
    cursor: pointer;
}
.products-table th.sortable:hover, .products-table th.sortable:focus {
    background-color: #e5e7eb;
    outline: 2px solid #1e3a8a;
}
.products-table tbody tr:hover {
    background-color: #f9fafb;
}
.low-stock { background-color: #fef3c7; color: #b45309; }
.out-of-stock { background-color: #fee2e2; color: #991b1b; }
.action-buttons {
    display: flex;
    gap: 8px;
}
.btn-adjust-stock {
    padding: 6px;
    border: none;
    border-radius: 6px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background-color: #10b981;
    cursor: pointer;
}
.btn-adjust-stock:hover, .btn-adjust-stock:focus {
    background-color: #059669;
    outline: 2px solid #059669;
}
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    flex-wrap: wrap;
    gap: 10px;
}
.table-summary {
    font-size: 0.95rem;
}
.pagination {
    display: flex;
    align-items: center;
    gap: 10px;
}
.btn-prev, .btn-next {
    background-color: #f3f4f6;
    color: #374151;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
}
.btn-prev:hover:not(.disabled), .btn-prev:focus:not(.disabled),
.btn-next:hover:not(.disabled), .btn-next:focus:not(.disabled) {
    background-color: #e5e7eb;
    outline: 2px solid #374151;
}
.btn-prev.disabled, .btn-next.disabled {
    cursor: not-allowed;
    opacity: 0.5;
}
.page-info {
    font-size: 0.95rem;
    color: #374151;
}
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
}
.modal-close {
    float: right;
    font-size: 1.5rem;
    cursor: pointer;
}
.modal-close:hover, .modal-close:focus {
    color: #dc2626;
    outline: 2px solid #dc2626;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
}
.form-group input, .form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}
.form-group input:focus, .form-group select:focus {
    border-color: #1e3a8a;
    outline: none;
}
.btn-primary {
    background-color: #1e3a8a;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 0.95rem;
    cursor: pointer;
}
.btn-primary:hover, .btn-primary:focus {
    background-color: #1e40af;
    outline: 2px solid #1e40af;
}
.dark-theme {
    background-color: #1f2937;
    color: #d1d5db;
}
.dark-theme .main-content, .dark-theme .products-container {
    background-color: #1f2937;
}
.dark-theme .page-header {
    background: linear-gradient(90deg, #374151, #4b5563);
}
.dark-theme .products-panel {
    background-color: #374151;
}
.dark-theme .panel-title {
    color: #d1d5db;
}
.dark-theme .btn-primary {
    background-color: #3b82f6;
}
.dark-theme .btn-primary:hover, .dark-theme .btn-primary:focus {
    background-color: #1e40af;
}
.dark-theme .btn-adjust-stock {
    background-color: #10b981;
}
.dark-theme .btn-adjust-stock:hover, .dark-theme .btn-adjust-stock:focus {
    background-color: #059669;
}
.dark-theme .search-bar input, .dark-theme .filter-section select {
    background-color: #4b5563;
    border-color: #6b7280;
    color: #d1d5db;
}
.dark-theme .search-bar input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 5px rgba(59,130,246,0.3);
}
.dark-theme .search-icon, .dark-theme .btn-clear-search {
    color: #9ca3af;
}
.dark-theme .products-table th {
    background-color: #1f2937;
    color: #d1d5db;
}
.dark-theme .products-table th.sortable:hover, 
.dark-theme .products-table th.sortable:focus {
    background-color: #4b5563;
}
.dark-theme .products-table td {
    border-bottom-color: #6b7280;
}
.dark-theme .products-table tbody tr:hover {
    background-color: #4b5563;
}
.dark-theme .page-info, .dark-theme .table-summary {
    color: #d1d5db;
}
.dark-theme .low-stock { background-color: #78350f; color: #fed7aa; }
.dark-theme .out-of-stock { background-color: #991b1b; color: #fecaca; }
.dark-theme .modal-content {
    background-color: #374151;
}
.dark-theme .form-group input, .dark-theme .form-group select {
    background-color: #4b5563;
    border-color: #6b7280;
    color: #d1d5db;
}
.dark-theme .form-group input:focus, .dark-theme .form-group select:focus {
    border-color: #3b82f6;
}
@media (max-width: 768px) {
    .filter-section {
        flex-direction: column;
    }
    .header-right {
        flex-wrap: wrap;
        gap: 10px;
    }
    .table-footer {
        flex-direction: column;
        align-items: flex-start;
    }
}
@media (max-width: 576px) {
    .products-table th, .products-table td {
        padding: 8px;
        font-size: 0.9rem;
    }
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
}
</style>