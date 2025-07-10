<?php

if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /pos/public/login');
    exit;
}

// Initialize variables with proper fallbacks
$title = $title ?? "Manage Inventory";
$products = $products ?? [];
$pendingProducts = $pendingProducts ?? []; // New variable for pending products
$total = $total ?? count($products); // Assume total is count if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = $perPage ?? 10;
$user = $_SESSION['user'] ?? ['name' => 'Admin', 'id' => '10001'];

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

// Group pending products (if any)
$pending_grouped = [];
foreach ($pendingProducts as $product) {
    $category = $product['category_name'] ?? 'Uncategorized';
    $category_id = $product['category_id'] ?? md5($category);
    if (!isset($pending_grouped[$category_id])) {
        $pending_grouped[$category_id] = [
            'name' => $category,
            'products' => []
        ];
    }
    $pending_grouped[$category_id]['products'][] = $product;
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
                <a href="/pos/public/dashboard" class="breadcrumb-link">Dashboard</a> 
                <span class="breadcrumb-separator" aria-hidden="true">></span>
                <span class="breadcrumb-current" aria-current="page">Inventory</span>
            </nav>
        </div>
        <div class="header-right">
            <span class="admin-info" aria-label="Admin information">
                <i class="fas fa-user-circle" aria-hidden="true"></i>
                <?= htmlspecialchars($user['name']) ?> (ID: <?= htmlspecialchars($user['id']) ?>)
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
                <div class="tabs">
                    <button class="tab active" onclick="showTab('approved-products')">Active Products</button>
                    <button class="tab" onclick="showTab('pending-products')">
                        Pending Products Approval
                        <?php if (count($pendingProducts) > 0): ?>
                            <span class="badge"><?= count($pendingProducts) ?></span>
                        <?php endif; ?>
                    </button>
                    
                    
                   
                </div>
                <div class="panel-actions">
                    <a href="/pos/public/products/create" class="btn btn-primary" aria-label="Add new product">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add Product
                    </a>
                    <button class="btn btn-export" onclick="exportProducts()" aria-label="Export products to CSV">
                        <i class="fas fa-file-export" aria-hidden="true"></i> Export
                    </button>
                </div>
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
                        <p>Get started by adding your first product</p>
                        <a href="/pos/public/products/create" class="btn btn-primary" 
                           aria-label="Add first product">
                            <i class="fas fa-plus" aria-hidden="true"></i> Add Product
                        </a>
                    </div>
                <?php else: ?>
                    <table class="products-table" aria-label="Product list">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTable(0)" aria-sort="none" aria-label="Sort by name">
                                    Name <i class="fas fa-sort" aria-hidden="true"></i>
                                </th>
                                <th aria-label="Category">Category</th>
                                <th class="sortable" onclick="sortTable(2)" aria-sort="none" aria-label="Sort by cost price">
                                    Buying Price <i class="fas fa-sort" aria-hidden="true"></i>
                                </th>
                                <th class="sortable" onclick="sortTable(3)" aria-sort="none" aria-label="Sort by selling price">
                                    Selling Price <i class="fas fa-sort" aria-hidden="true"></i>
                                </th>
                                <th aria-label="Barcode">Barcode</th>
                                <th class="sortable" onclick="sortTable(5)" aria-sort="none" aria-label="Sort by stock">
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
                                    <td>
                                        <span class="product-name"><?= htmlspecialchars($product['name']) ?></span>
                                        <input type="text" class="inline-edit" data-field="name" 
                                            data-id="<?= $product['id'] ?>" 
                                            value="<?= htmlspecialchars($product['name']) ?>" 
                                            style="display: none;" aria-label="Edit product name">
                                    </td>
                                    <td><?= htmlspecialchars($cat['name']) ?></td>
                                    <td class="cost-price-cell">
                                        <span class="product-cost-price"><?= number_format($product['cost_price'], 2) ?> KSh</span>
                                        <input type="number" step="0.01" class="inline-edit" data-field="cost_price" 
                                            data-id="<?= $product['id'] ?>" 
                                            value="<?= htmlspecialchars($product['cost_price']) ?>" 
                                            style="display: none;" aria-label="Edit cost price">
                                    </td>
                                    <td class="price-cell">
                                        <span class="product-price"><?= number_format($product['price'], 2) ?> KSh</span>
                                        <span class="profit-display" style="display: block; font-size: 0.8rem; margin-top: 3px;">
                                            <?php 
                                            $profit = $product['price'] - $product['cost_price'];
                                            $margin = $product['cost_price'] > 0 ? ($profit / $product['cost_price']) * 100 : 0;
                                            $color = $profit >= 0 ? '#10b981' : '#ef4444';
                                            ?>
                                            Margin: <?= number_format($margin, 2) ?>% (<?= number_format($profit, 2) ?> KSh)
                                        </span>
                                        <input type="number" step="0.01" class="inline-edit" data-field="price" 
                                            data-id="<?= $product['id'] ?>" 
                                            value="<?= htmlspecialchars($product['price']) ?>" 
                                            style="display: none;" aria-label="Edit selling price">
                                    </td>
                                    <td>
                                        <span class="barcode"><?= htmlspecialchars($product['barcode'] ?? 'N/A') ?></span>
                                    </td>
                                    <td class="stock-cell <?= $stock_class ?>">
                                        <span class="product-stock"><?= htmlspecialchars($product['stock']) ?></span>
                                        <input type="number" class="inline-edit" data-field="stock" 
                                            data-id="<?= $product['id'] ?>" 
                                            value="<?= htmlspecialchars($product['stock']) ?>" 
                                            style="display: none;" aria-label="Edit product stock">
                                    </td>
                                    <td class="action-buttons">
                                        <button class="btn btn-inline-edit" 
                                                onclick="toggleInlineEdit(this)" 
                                                title="Quick Edit" 
                                                aria-label="Toggle inline edit for product">
                                            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                                        </button>
                                        <a href="/pos/public/products/edit/<?= htmlspecialchars($product['id']) ?>" 
                                        class="btn btn-edit" aria-label="Edit product details">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </a>
                                        <button class="confirmDelete" onclick="confirmDelete(<?= $product['id'] ?>)" 
                                                title="Delete Product" 
                                                aria-label="Delete product">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

              <!-- pending approvals -->
            <div id="pending-products" class="tab-content" style="display: none;">
                <?php if (empty($pendingProducts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>No Pending Products</h3>
                        <p>All products have been reviewed</p>
                    </div>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Buying Price</th>
                                <th>Proposed Selling Price</th>
                                <th>Stock</th>
                                <th>Submitted By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingProducts as $product): ?>
                            <tr data-id="<?= $product['id'] ?>">
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                <td><?= number_format($product['cost_price'], 2) ?> KSh</td>
                                <td>
                                    <input type="number" step="0.01" class="selling-price-input" 
                                        data-id="<?= $product['id'] ?>" 
                                        value="<?= number_format($product['price'], 2) ?>"
                                        placeholder="Set selling price">
                                </td>
                                <td><?= $product['stock'] ?></td>
                                <td><?= htmlspecialchars($product['submitted_by_name']) ?></td>
                                <td>
                                    <button class="btn btn-approve" onclick="approveProduct(<?= $product['id'] ?>)">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-reject" onclick="rejectProduct(<?= $product['id'] ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            

            
             

            <!-- Existing products table - wrap in tab-content -->
            <div id="approved-products" class="tab-content">
                <!-- Your existing products table goes here -->
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
                        Next <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </main>
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
    
    // Update ARIA sort attributes
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
        if (column === 2 || column === 4) {
            aText = parseFloat(aText) || 0;
            bText = parseFloat(bText) || 0;
            return sortDirection * (aText - bText);
        }
        return sortDirection * aText.localeCompare(bText);
    });
    
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

// Inline editing
function toggleInlineEdit(button) {
    const row = button.closest('tr');
    const spans = row.querySelectorAll('span');
    const inputs = row.querySelectorAll('.inline-edit');
    const isEditing = button.classList.contains('editing');
    
    spans.forEach(span => span.style.display = isEditing ? '' : 'none');
    inputs.forEach(input => input.style.display = isEditing ? 'none' : 'block');
    
    if (isEditing) {
        inputs.forEach(input => {
            const field = input.dataset.field;
            const id = input.dataset.id;
            const value = input.value;
            saveInlineEdit(id, field, value, row);
        });
    }
    
    button.classList.toggle('editing');
    button.innerHTML = isEditing 
        ? '<i class="fas fa-pencil-alt" aria-hidden="true"></i>' 
        : '<i class="fas fa-save" aria-hidden="true"></i>';
    button.setAttribute('aria-label', isEditing ? 'Toggle inline edit for product' : 'Save inline edits');
}

// Save inline edit
function saveInlineEdit(id, field, value, row) {
    fetch('/pos/public/products/inline-update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ 
            id: id, 
            field: field, 
            value: value 
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
            const displayElement = row.querySelector(`.product-${field.replace('_', '-')}`);
            if (displayElement) {
                displayElement.textContent = 
                    (field === 'price' || field === 'cost_price') 
                    ? `${parseFloat(value).toFixed(2)} KSh` 
                    : value;
            }
            showAlert('success', `${field.charAt(0).toUpperCase() + field.slice(1).replace('_', ' ')} updated successfully.`);
            
            if (field === 'price' || field === 'cost_price') {
                updateProfitDisplay(row);
            }
        } else {
            showAlert('error', data.message || 'Update failed.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Network error.');
    });
}

// Add this function to calculate and display profit
function updateProfitDisplay(row) {
    const costPrice = parseFloat(row.querySelector('.product-cost-price').textContent) || 0;
    const sellingPrice = parseFloat(row.querySelector('.product-price').textContent) || 0;
    const profit = sellingPrice - costPrice;
    const margin = costPrice > 0 ? ((profit / costPrice) * 100) : 0;
    
    const profitDisplay = row.querySelector('.profit-display');
    if (profitDisplay) {
        profitDisplay.textContent = `Margin: ${margin.toFixed(2)}% (${profit.toFixed(2)} KSh)`;
        profitDisplay.style.color = profit >= 0 ? '#10b981' : '#ef4444';
    }
}

// Delete product
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch('/pos/public/products/delete/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`tr[data-id="${id}"]`).remove();
                document.getElementById('shown-count').textContent = 
                    parseInt(document.getElementById('shown-count').textContent) - 1;
                document.getElementById('total-count').textContent = 
                    parseInt(document.getElementById('total-count').textContent) - 1;
                showAlert('success', 'Product deleted successfully.');
            } else {
                showAlert('error', data.message || 'Deletion failed.');
            }
        })
        .catch(() => showAlert('error', 'Network error.'));
    }
}

function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.getElementById(tabId).style.display = 'block';
    event.currentTarget.classList.add('active');
}

function approveProduct(productId) {
    const sellingPriceInput = document.querySelector(`.selling-price-input[data-id="${productId}"]`);
    const sellingPrice = sellingPriceInput ? parseFloat(sellingPriceInput.value) : 0;
    
    if (isNaN(sellingPrice)) {
        alert('Please enter a valid selling price');
        return;
    }
    
    fetch('/pos/public/products/approve/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            price: sellingPrice
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Product approved successfully');
            document.querySelector(`tr[data-id="${productId}"]`).remove();
            
            // Update counts
            const badge = document.querySelector('.tab .badge');
            if (badge) {
                const newCount = parseInt(badge.textContent) - 1;
                badge.textContent = newCount;
                if (newCount <= 0) {
                    badge.remove();
                }
            }
        } else {
            showAlert('error', data.message || 'Approval failed');
        }
    })
    .catch(error => {
        showAlert('error', 'Network error');
    });
}

function approveAdjustment(id) {
    const notes = prompt('Enter any approval notes (optional):');
    fetch(`/pos/public/api/inventory/approve-adjustment/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            notes: notes,
            approved_by: <?= $_SESSION['user']['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Adjustment approved successfully');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Approval failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to approve adjustment');
    });
}

function rejectAdjustment(id) {
    const reason = prompt('Enter reason for rejection:');
    if (!reason) return;
    
    fetch(`/pos/public/api/inventory/reject-adjustment/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            reason: reason,
            approved_by: <?= $_SESSION['user']['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Adjustment rejected');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Rejection failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to reject adjustment');
    });
}

function rejectProduct(productId) {
    const reason = prompt('Please enter reason for rejection:');
    if (reason === null) return;
    
    fetch('/pos/public/products/reject/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Product rejected');
            document.querySelector(`tr[data-id="${productId}"]`).remove();
            
            // Update counts
            const badge = document.querySelector('.tab .badge');
            if (badge) {
                const newCount = parseInt(badge.textContent) - 1;
                badge.textContent = newCount;
                if (newCount <= 0) {
                    badge.remove();
                }
            }
        } else {
            showAlert('error', data.message || 'Rejection failed');
        }
    })
    .catch(error => {
        showAlert('error', 'Network error');
    });
}



function approveProduct(productId) {
    const sellingPriceInput = document.querySelector(`.selling-price-input[data-id="${productId}"]`);
    const sellingPrice = sellingPriceInput ? parseFloat(sellingPriceInput.value) : 0;
    
    if (isNaN(sellingPrice) || sellingPrice <= 0) {
        alert('Please enter a valid selling price');
        return;
    }
    
    fetch('/pos/public/products/approve/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            price: sellingPrice
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Product approved successfully');
            document.querySelector(`tr[data-id="${productId}"]`).remove();
            
            // Update badge count
            const badge = document.querySelector('.tab .badge');
            if (badge) {
                const newCount = parseInt(badge.textContent) - 1;
                badge.textContent = newCount;
                if (newCount <= 0) {
                    badge.remove();
                }
            }
        } else {
            showAlert('error', data.message || 'Approval failed');
        }
    })
    .catch(error => {
        showAlert('error', 'Network error');
    });
}

function rejectProduct(productId) {
    const reason = prompt('Please enter reason for rejection:');
    if (reason === null) return;
    
    fetch('/pos/public/products/reject/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Product rejected');
            document.querySelector(`tr[data-id="${productId}"]`).remove();
            
            // Update badge count
            const badge = document.querySelector('.tab .badge');
            if (badge) {
                const newCount = parseInt(badge.textContent) - 1;
                badge.textContent = newCount;
                if (newCount <= 0) {
                    badge.remove();
                }
            }
        } else {
            showAlert('error', data.message || 'Rejection failed');
        }
    })
    .catch(error => {
        showAlert('error', 'Network error');
    });
}

function approveCostChange(id) {
    const notes = prompt('Enter any approval notes (optional):');
    if (notes === null) return; // User cancelled
    
    fetch(`/pos/public/api/inventory/approve-cost-change/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            notes: notes,
            approved_by: <?= $_SESSION['user']['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the row from the table
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) row.remove();
            
            // Update badge count
            updatePendingBadge('pending-cost-changes');
            
            showAlert('success', 'Cost change approved successfully');
        } else {
            showAlert('error', data.message || 'Approval failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Failed to approve cost change');
    });
}

function rejectCostChange(id) {
    const reason = prompt('Enter reason for rejection:');
    if (!reason) return;
    
    fetch(`/pos/public/api/inventory/reject-cost-change/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            reason: reason,
            approved_by: <?= $_SESSION['user']['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the row from the table
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) row.remove();
            
            // Update badge count
            updatePendingBadge('pending-cost-changes');
            
            showAlert('success', 'Cost change rejected');
        } else {
            showAlert('error', data.message || 'Rejection failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Failed to reject cost change');
    });
}

// Helper function to update badge counts
function updatePendingBadge(tabId) {
    const badge = document.querySelector(`.tab[onclick="showTab('${tabId}')"] .badge`);
    if (badge) {
        const newCount = parseInt(badge.textContent) - 1;
        if (newCount > 0) {
            badge.textContent = newCount;
        } else {
            badge.remove();
        }
    }
}

// Export products
function exportProducts() {
    window.location.href = '/pos/public/products/export';
}

// Show alerts
function showAlert(type, message) {
    const alertsDiv = document.getElementById('alerts');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" aria-hidden="true"></i> ${message}`;
    alertsDiv.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
}

// Keyboard navigation for table
document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.classList.contains('inline-edit')) {
        const button = e.target.closest('tr').querySelector('.btn-inline-edit.editing');
        if (button) button.click();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    searchProducts();
    filterProducts();

    // Initialized profit displays for all rows
    document.querySelectorAll('#products-table-body tr').forEach(row => {
        updateProfitDisplay(row);
    });
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
.admin-info {
    font-size: 0.9rem;
    background-color: rgba(255,255,255,0.2);
    padding: 5px 10px;
    border-radius: 6px;
}
.time-display {
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
    /* background-color: #6b7280; */
    color: white;
    border: none;
    padding: 8px;
    border-radius: 6px;
    width: 20px;
    height: 20px;
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
.panel-actions {
    display: flex;
    gap: 10px;
}
.btn-primary, .btn-export {
    background-color: #1e3a8a;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    cursor: pointer;
}
.btn-primary:hover, .btn-primary:focus, 
.btn-export:hover, .btn-export:focus {
    background-color: #1e40af;
    outline: 2px solid #1e40af;
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
    background-color:rgb(224, 227, 232);
    color:rgb(40, 46, 56);
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
    background-color:rgb(236, 242, 244);
}
.inline-edit {
    width: 100%;
    padding: 5px;
    font-size: 0.95rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}
.inline-edit:focus {
    border-color: #1e3a8a;
    outline: none;
}
.cost-price-cell, .price-cell {
    text-align: right;
    min-width: 100px;
}

.profit-display {
    display: block;
    margin-top: 3px;
    font-size: 0.8rem;
    color: #6b7280;
}

.dark-theme .profit-display {
    color: #9ca3af;
}


.products-table th:nth-child(3),
.products-table td:nth-child(3),
.products-table th:nth-child(4),
.products-table td:nth-child(4) {
    width: 120px;
}
.tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 15px;
}

.tab {
    padding: 10px 15px;
    cursor: pointer;
    border: none;
    background: none;
    font-size: 0.95rem;
    position: relative;
    margin-right: 5px;
}

.tab.active {
    font-weight: bold;
    border-bottom: 2px solid #1e3a8a;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.badge {
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.8rem;
    margin-left: 5px;
}

.approval-actions {
    display: flex;
    gap: 5px;
}

.btn-approve {
    background-color: #10b981;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-reject {
    background-color: #ef4444;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.selling-price-input {
    width: 80px;
    padding: 5px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}

.dark-theme .tab {
    color: #d1d5db;
}

.dark-theme .tab.active {
    border-bottom-color: #3b82f6;
}

.dark-theme .selling-price-input {
    background-color: #4b5563;
    border-color: #6b7280;
    color: #d1d5db;
}

.action-buttons {
    display: flex;
    gap: 8px;
}
.btn-inline-edit, .btn-edit, .btn-delete {
    padding: 6px;
    border: none;
    border-radius: 6px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    cursor: pointer;
}
.btn-inline-edit { background-color: #3b82f6; }
.btn-inline-edit:hover, .btn-inline-edit:focus, 
.btn-inline-edit.editing { 
    background-color: #1e40af; 
    outline: 2px solid #1e40af;
}
.btn-edit { background-color: #f59e0b; }
.btn-edit:hover, .btn-edit:focus { 
    background-color: #d97706; 
    outline: 2px solid #d97706;
}
.btn-delete { background-color: #ef4444; }
.btn-delete:hover, .btn-delete:focus { 
    background-color: #dc2626; 
    outline: 2px solid #dc2626;
}
.low-stock { background-color: #fef3c7; color: #b45309; }
.out-of-stock { background-color: #fee2e2; color: #991b1b; }
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    flex-wrap: wrap;
    gap: 10px;
}
.table-footer .table-summary {
    font-size: 0.95rem;
}
.table-footer .pagination {
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
.dark-theme .btn-primary, .dark-theme .btn-export {
    background-color: #3b82f6;
}
.dark-theme .btn-primary:hover, .dark-theme .btn-primary:focus,
.dark-theme .btn-export:hover, .dark-theme .btn-export:focus {
    background-color: #1e40af;
}
.dark-theme .btn-inline-edit { background-color: #3b82f6; }
.dark-theme .btn-inline-edit:hover, .dark-theme .btn-inline-edit:focus,
.dark-theme .btn-inline-edit.editing { background-color: #1e40af; }
.dark-theme .btn-edit { background-color: #d97706; }
.dark-theme .btn-edit:hover, .dark-theme .btn-edit:focus { background-color: #b45309; }
.dark-theme .btn-delete { background-color: #dc2626; }
.dark-theme .btn-delete:hover, .dark-theme .btn-delete:focus { background-color: #b91c1c; }
.dark-theme .search-bar input, .dark-theme .filter-section select, 
.dark-theme .inline-edit {
    background-color: #4b5563;
    border-color: #6b7280;
    color: #d1d5db;
}
.dark-theme .search-bar input:focus, .dark-theme .inline-edit:focus {
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
.dark-theme .page-info, .dark-theme .table-total {
    color: #d1d5db;
}
.dark-theme .low-stock { background-color: #78350f; color: #fed7aa; }
.dark-theme .out-of-stock { background-color: #991b1b; color: #fecaca; }
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