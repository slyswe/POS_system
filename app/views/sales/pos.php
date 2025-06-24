<?php
if (!defined('IN_CONTROLLER')) {
    header('Location: /pos/public/login');
    exit;
}
//session_start();

if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
    header('Location: /pos/public/login');
    exit;
}

$title = "Point of Sale";
$cashier = [
    'name' => $_SESSION['user']['name'] ?? 'Unknown',
    'id' => $_SESSION['user']['id'] ?? 'N/A'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>
<body>
    <div class="pos-container">
        <!-- Training Mode Banner -->
        <div class="training-mode-banner">Training Mode Active - No Transactions Will Be Saved</div>

        <!-- Header Section -->
        <header class="pos-header">
            <div class="header-left">
                <h2 class="business-name">Retail POS</h2>
            </div>
            <div class="header-center">
                <span id="current-time" class="time-display"><?php echo date('Y-m-d H:i:s'); ?></span>
                <span id="connectivity-status" class="connectivity-status">Online</span>
            </div>
            <div class="header-right">
                <select class="language-select" onchange="changeLanguage(this.value)">
                    <option value="en">English</option>
                    <option value="sw">Kiswahili</option>
                </select>
                <span class="cashier-info"><?php echo htmlspecialchars($cashier['name']) . ' (ID: ' . htmlspecialchars($cashier['id']) . ')'; ?></span>
                <div class="header-actions">
                    <button class="btn btn-theme-toggle" onclick="toggleTheme()" title="Toggle Theme" aria-label="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-training-mode" onclick="toggleTrainingMode()" title="Training Mode" aria-label="Training Mode">
                        <i class="fas fa-graduation-cap"></i>
                    </button>
                    <a href="/pos/public/login" class="btn btn-switch-user" title="Switch User" aria-label="Switch User"><i class="fas fa-user"></i></a>
                    <a href="/pos/public/logout" class="btn btn-logout" title="Logout" aria-label="Logout"><i class="fas fa-sign-out-alt"></i></a>
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

        <!-- Main Content -->
        <div class="pos-main">
            <!-- Product Panel -->
            <div class="product-panel">
                <div class="search-bar">
                    <form method="POST" action="/pos/public/sales/pos">
                        <div class="input-group">
                            <input type="text" name="barcode" id="product-search" class="form-control" placeholder="Scan barcode or search products..." autocomplete="off">
                            <button type="submit" name="add_to_cart" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </form>
                </div>
                <div class="filter-buttons">
                    <button class="btn btn-filter" onclick="filterProducts('all')">All</button>
                    <button class="btn btn-filter" onclick="filterProducts('frequent')">Frequent</button>
                    <button class="btn btn-filter" onclick="filterProducts('new')">New</button>
                    <select class="form-select category-select" onchange="filterProducts(this.value)">
                        <option value="">Category</option>
                        <?php if (!empty($categories) && is_array($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No categories available</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="category-grid">
                    <?php if (!empty($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <button class="btn btn-category" onclick="filterProducts('<?php echo htmlspecialchars($category['id']); ?>')">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No categories available.</p>
                    <?php endif; ?>
                </div>
                <div class="product-list">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Add</th>
                            </tr>
                        </thead>
                        <tbody id="product-table">
                            <?php if (!empty($products) && is_array($products)): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr data-category-id="<?php echo htmlspecialchars($product['category_id'] ?? ''); ?>">
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><span class="money"><?php echo number_format($product['price'], 2); ?></span> KES</td>
                                        <td>
                                            <span class="stock-badge <?php echo $product['stock'] > 10 ? 'in-stock' : ($product['stock'] > 0 ? 'low-stock' : 'out-stock'); ?>">
                                                <?php echo $product['stock'] > 10 ? 'In Stock' : ($product['stock'] > 0 ? 'Low Stock' : 'Out of Stock'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="/pos/public/sales/pos" class="add-to-cart-form">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                                <input type="number" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['stock']); ?>" class="form-control quantity-input">
                                                <button type="submit" name="add_to_cart" class="btn btn-add" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>><i class="fas fa-plus"></i> Add</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4">No products available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cart Panel -->
            <div class="cart-panel">
                <h3>Cart</h3>
                <div class="customer-section">
                    <input type="text" id="customer-lookup" class="form-control" placeholder="Enter customer phone/email...">
                    <button class="btn btn-customer-lookup" onclick="lookupCustomer()">Lookup</button>
                    <span id="customer-info"></span>
                    <span id="loyalty-points"></span>
                </div>
                <?php if (empty($_SESSION['cart'])): ?>
                    <p class="empty-cart">No items in cart.</p>
                <?php else: ?>
                    <div class="cart-items">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Item</th>
                                    <th style="width: 20%;">Qty</th>
                                    <th style="width: 15%;">Price</th>
                                    <th style="width: 15%;">Total</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $subtotal = 0; ?>
                                <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>
                                            <form method="POST" action="/pos/public/sales/pos" class="quantity-form">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                                                <div class="quantity-actions">
                                                    <button type="submit" name="decrease_qty" class="btn btn-quantity" title="Decrease Quantity" <?php echo $item['quantity'] <= 0 ? 'disabled' : ''; ?>><i class="fas fa-minus"></i></button>
                                                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0" max="<?php echo htmlspecialchars($item['stock']); ?>" class="form-control quantity-input">
                                                    <button type="submit" name="increase_qty" class="btn btn-quantity" title="Increase Quantity" <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>><i class="fas fa-plus"></i></button>
                                                </div>
                                            </form>
                                        </td>
                                        <td><span class="money"><?php echo number_format($item['price'], 2); ?></span> KES</td>
                                        <td><span class="money"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></span> KES</td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-discount" onclick="applyItemDiscount('<?php echo htmlspecialchars($product_id); ?>')" title="Apply Discount"><i class="fas fa-percentage"></i></button>
                                                <form method="POST" action="/pos/public/sales/pos" style="display:inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                                                    <button type="submit" name="remove_from_cart" class="btn btn-remove" title="Remove Item"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php $subtotal += $item['price'] * $item['quantity']; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span><span class="money" id="subtotal"><?php echo number_format($subtotal, 2); ?></span> KES</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (16%):</span>
                            <span><span class="money" id="tax"><?php echo number_format($subtotal * 0.16, 2); ?></span> KES</span>
                        </div>
                        <div class="summary-row">
                            <span>Discount:</span>
                            <span>-<span class="money" id="discount-amount">0.00</span> KES</span>
                        </div>
                        <div class="summary-row total">
                            <span>Grand Total:</span>
                            <span><span class="money" id="grand-total"><?php echo number_format($subtotal * 1.16, 2); ?></span> KES</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment Panel -->
            <div class="payment-panel">
                <h3>Payment</h3>
                <form method="POST" action="/pos/public/sales/checkout" id="payment-form">
                    <div class="form-group">
                        <label for="discount">Cart Discount (KES)</label>
                        <input type="number" step="0.01" class="form-control" id="discount" name="discount" value="0" onchange="updateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="amount_paid">Amount Paid (KES)</label>
                        <input type="number" step="0.01" class="form-control" id="amount_paid" name="amount_paid" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="payment-methods">
                            <button type="button" class="btn btn-payment active" onclick="selectPayment('cash')">Cash</button>
                            <button type="button" class="btn btn-payment" onclick="selectPayment('card')">Card</button>
                            <button type="button" class="btn btn-payment" onclick="selectPayment('mobile')">Mobile Money</button>
                            <button type="button" class="btn btn-payment" onclick="selectPayment('gift')">Gift Card</button>
                            <input type="hidden" id="payment_method" name="payment_method" value="cash">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Change Due:</label>
                        <span><span class="money" id="change-due">0.00</span> KES</span>
                    </div>
                    <div class="form-group receipt-options">
                        <label>Receipt</label>
                        <div class="receipt-methods">
                            <button type="button" class="btn btn-receipt active" onclick="selectReceipt('print')">Print</button>
                            <button type="button" class="btn btn-receipt" onclick="selectReceipt('email')">Email</button>
                            <button type="button" class="btn btn-receipt" onclick="selectReceipt('text')">Text</button>
                            <input type="hidden" id="receipt_method" name="receipt_method" value="print">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-complete">Complete Sale</button>
                </form>
            </div>
        </div>

        <!-- Action Toolbar -->
        <div class="action-toolbar">
            <button class="btn btn-action" onclick="holdOrder()" title="Hold Order">Hold</button>
            <button class="btn btn-action" onclick="recallOrder()" title="Recall Sale">Recall</button>
            <button class="btn btn-action" onclick="returnItem()" title="Return Item">Return</button>
            <button class="btn btn-action" onclick="cancelSale()" title="Cancel Sale">Cancel</button>
        </div>
    </div>

    <script>
        // Update date and time
        function updateTime() {
            try {
                const now = new Date();
                document.getElementById('current-time').textContent = now.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            } catch (e) {
                console.error('Error updating time:', e);
            }
        }
        setInterval(updateTime, 1000);

        // Check connectivity status
        function checkConnectivity() {
            try {
                const status = navigator.onLine ? 'Online' : 'Offline';
                document.getElementById('connectivity-status').textContent = status;
                document.getElementById('connectivity-status').className = `connectivity-status ${status.toLowerCase()}`;
            } catch (e) {
                console.error('Error checking connectivity:', e);
            }
        }
        window.addEventListener('online', checkConnectivity);
        window.addEventListener('offline', checkConnectivity);
        checkConnectivity();

        // Filter products
        function filterProducts(filter) {
            try {
                const rows = document.querySelectorAll('#product-table tr');
                rows.forEach(row => {
                    const categoryId = row.dataset.categoryId || '';
                    row.style.display = (filter === 'all' || filter === '' || categoryId === filter) ? '' : 'none';
                });
            } catch (e) {
                console.error('Error filtering products:', e);
            }
        }

        // Update total with discount
        function updateTotal() {
            try {
                const subtotal = <?php echo $subtotal ?? 0; ?>;
                const taxRate = 0.16;
                const tax = subtotal * taxRate;
                const discount = parseFloat(document.getElementById('discount').value) || 0;
                const grandTotal = Math.max(0, subtotal + tax - discount);

                document.getElementById('subtotal').textContent = subtotal.toFixed(2);
                document.getElementById('tax').textContent = tax.toFixed(2);
                document.getElementById('discount-amount').textContent = discount.toFixed(2);
                document.getElementById('grand-total').textContent = grandTotal.toFixed(2);

                const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
                const changeDue = amountPaid - grandTotal;
                document.getElementById('change-due').textContent = changeDue.toFixed(2);
            } catch (e) {
                console.error('Error updating total:', e);
            }
        }

        // Calculate change due
        document.getElementById('amount_paid').addEventListener('input', updateTotal);

        // Select payment method
        function selectPayment(method) {
            try {
                document.getElementById('payment_method').value = method;
                document.querySelectorAll('.btn-payment').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
            } catch (e) {
                console.error('Error selecting payment method:', e);
            }
        }

        // Select receipt method
        function selectReceipt(method) {
            try {
                document.getElementById('receipt_method').value = method;
                document.querySelectorAll('.btn-receipt').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
            } catch (e) {
                console.error('Error selecting receipt method:', e);
            }
        }

        // Customer lookup
        function lookupCustomer() {
            try {
                const query = document.getElementById('customer-lookup').value;
                if (query) {
                    // Placeholder AJAX call
                    document.getElementById('customer-info').textContent = 'Customer: Ken Wangwi';
                    document.getElementById('loyalty-points').textContent = 'Points: 150';
                } else {
                    document.getElementById('customer-info').textContent = '';
                    document.getElementById('loyalty-points').textContent = '';
                    showAlert('error', 'Please enter a customer phone or email.');
                }
            } catch (e) {
                console.error('Error in customer lookup:', e);
            }
        }

        // Apply item-specific discount
        function applyItemDiscount(productId) {
            try {
                const discount = parseFloat(prompt('Enter discount amount for item (KES):')) || 0;
                if (discount > 0) {
                    showAlert('success', `Discount of ${discount.toFixed(2)} KES applied to item.`);
                    updateTotal();
                } else if (discount < 0) {
                    showAlert('error', 'Discount cannot be negative.');
                }
            } catch (e) {
                console.error('Error applying discount:', e);
            }
        }

        // Action toolbar functions
        function holdOrder() {
            try {
                showAlert('info', 'Order held successfully.');
            } catch (e) {
                console.error('Error holding order:', e);
            }
        }

        function recallOrder() {
            try {
                showAlert('info', 'Order recalled successfully.');
            } catch (e) {
                console.error('Error recalling order:', e);
            }
        }

        function returnItem() {
            try {
                showAlert('info', 'Item return initiated.');
            } catch (e) {
                console.error('Error returning item:', e);
            }
        }

        function cancelSale() {
            try {
                if (confirm('Cancel this sale? All items will be removed.')) {
                    window.location.href = '/pos/public/sales/pos?cancel_sale=1';
                }
            } catch (e) {
                console.error('Error cancelling sale:', e);
            }
        }

        // Show alerts
        function showAlert(type, message) {
            try {
                const alertsDiv = document.getElementById('alerts');
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.textContent = message;
                alertsDiv.appendChild(alert);
                setTimeout(() => alert.remove(), 5000);
            } catch (e) {
                console.error('Error showing alert:', e);
            }
        }

        // Toggle theme
        function toggleTheme() {
            try {
                document.body.classList.toggle('dark-theme');
                const icon = document.querySelector('.btn-theme-toggle i');
                icon.classList.toggle('fa-moon');
                icon.classList.toggle('fa-sun');
            } catch (e) {
                console.error('Error toggling theme:', e);
            }
        }

        // Toggle training mode
        function toggleTrainingMode() {
            try {
                const pin = prompt('Enter manager PIN to toggle training mode:');
                if (pin === '1234') { // Replace with secure PIN validation
                    document.body.classList.toggle('training-mode');
                    const isTraining = document.body.classList.contains('training-mode');
                    showAlert('success', isTraining ? 'Training mode enabled.' : 'Training mode disabled.');
                } else {
                    showAlert('error', 'Invalid manager PIN.');
                }
            } catch (e) {
                console.error('Error toggling training mode:', e);
            }
        }

        // Change language
        function changeLanguage(lang) {
            try {
                const translations = {
                    en: {
                        'Point of Sale': 'Point of Sale',
                        'Scan barcode or search products...': 'Scan barcode or search products...',
                        'Enter customer phone/email...': 'Enter customer phone/email...',
                        'Complete Sale': 'Complete Sale'
                    },
                    sw: {
                        'Point of Sale': 'Sehemu ya Mauzo',
                        'Scan barcode or search products...': 'Piga skana barcode au tafuta bidhaa...',
                        'Enter customer phone/email...': 'Ingiza simu/barua pepe ya mteja...',
                        'Complete Sale': 'Kamilisha Mauzo'
                    }
                };
                document.title = translations[lang]['Point of Sale'] + ' - POS System';
                document.querySelector('#product-search').placeholder = translations[lang]['Scan barcode or search products...'];
                document.querySelector('#customer-lookup').placeholder = translations[lang]['Enter customer phone/email...'];
                document.querySelector('.btn-complete').textContent = translations[lang]['Complete Sale'];
            } catch (e) {
                console.error('Error changing language:', e);
            }
        }

        // Initialize totals
        document.addEventListener('DOMContentLoaded', () => {
            updateTotal();
            document.getElementById('product-search').focus();
            document.getElementById('product-search').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.target.closest('form').submit();
                }
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
        }
        .pos-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 10px;
            display: flex;
            flex-direction: column;
        }
        .pos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        .business-name {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 600;
        }
        .time-display, .connectivity-status {
            font-size: 1rem;
            background-color: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 6px;
            margin: 0 10px;
        }
        .connectivity-status.offline {
            background-color: #ef4444;
        }
        .cashier-info {
            font-size: 1rem;
            margin-right: 15px;
        }
        .language-select {
            background-color: #6b7280;
            color: #fff;
            border: none;
            padding: 5px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn-theme-toggle, .btn-training-mode, .btn-switch-user, .btn-logout {
            background-color: #6b7280;
            color: #fff;
            border: none;
            padding: 8px;
            border-radius: 6px;
            font-size: 1rem;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        .btn-training-mode { background-color: #10b981; }
        .btn-switch-user { background-color: #4b5563; }
        .btn-logout { background-color: #ef4444; }
        .btn-theme-toggle:hover, .btn-training-mode:hover, .btn-switch-user:hover { background-color: #374151; }
        .btn-logout:hover { background-color: #dc2626; }
        .alerts-section {
            margin-bottom: 10px;
        }
        .alerts {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .alert {
            padding: 8px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .alert-success { background-color: #d1fae5; color: #065f46; }
        .alert-error { background-color: #fee2e2; color: #991b1b; }
        .alert-info { background-color: #dbeafe; color: #1e40af; }
        .pos-main {
            display: flex;
            gap: 10px;
        }
        .product-panel, .cart-panel, .payment-panel {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .product-panel { flex: 0 0 40%; }
        .cart-panel, .payment-panel { flex: 0 0 30%; }
        .search-bar .input-group {
            display: flex;
            gap: 10px;
        }
        .search-bar input {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 8px;
            font-size: 1rem;
            flex: 1;
        }
        .search-bar input:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 5px rgba(30,58,138,0.3);
        }
        .filter-buttons, .category-grid {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .btn-filter, .btn-category, .category-select {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .btn-category {
            flex: 1 1 100px;
            text-align: center;
            font-weight: 500;
        }
        .btn-filter:hover, .btn-category:hover, .category-select:hover {
            background-color: #e5e7eb;
        }
        .category-select {
            padding: 7px;
        }
        .product-list {
            flex: 1;
            overflow-y: auto;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }
        .table th, .table td {
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        .stock-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        .in-stock { background-color: #22c55e; color: #fff; }
        .low-stock { background-color: #f59e0b; color: #fff; }
        .out-stock { background-color: #ef4444; color: #fff; }
        .add-to-cart-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            font-size: 0.9rem;
            border-radius: 6px;
            text-align: center;
        }
        .btn-add, .btn-discount, .btn-remove {
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 6px;
            border-radius: 6px;
            font-size: 0.8rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        .btn-discount { background-color: #8b5cf6; }
        .btn-remove { background-color: #ef4444; }
        .btn-add:hover { background-color: #1e40af; }
        .btn-discount:hover { background-color: #7c3aed; }
        .btn-remove:hover { background-color: #dc2626; }
        .customer-section {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        .customer-section input {
            flex: 1;
            border-radius: 6px;
            padding: 8px;
            font-size: 0.9rem;
        }
        .btn-customer-lookup {
            background-color: #3b82f6;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .btn-customer-lookup:hover {
            background-color: #1e40af;
        }
        #customer-info, #loyalty-points {
            font-size: 0.9rem;
            color: #374151;
        }
        .cart-panel h3, .payment-panel h3 {
            margin: 0 0 10px;
            font-size: 1.2rem;
            color: #374151;
        }
        .empty-cart {
            color: #6b7280;
            text-align: center;
            padding: 15px;
            font-size: 0.9rem;
        }
        .cart-items {
            flex: 1;
            max-height: 350px;
            overflow-y: auto;
        }
        .cart-items tbody tr { border-bottom: 1px solid #e5e7eb; }
        .cart-items tbody tr:last-child { border-bottom: none; }
        .quantity-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        .quantity-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .btn-quantity {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 6px;
            border-radius: 6px;
            font-size: 0.8rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-quantity:hover {
            background-color: #e5e7eb;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .cart-summary {
            margin-top: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.9rem;
        }
        .summary-row.total {
            font-weight: 700;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .money {
            font-weight: 500;
        }
        .payment-panel .form-group {
            margin-bottom: 10px;
        }
        .payment-methods, .receipt-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            
        }
        .btn-payment, .btn-receipt {
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        .btn-receipt { background-color: #6b7280; }
        .btn-payment:hover, .btn-payment.active, .btn-receipt:hover, .btn-receipt.active { background-color: #1e40af; }
        .btn-complete {
            background-color: #22c55e;
            color: #fff;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        .btn-complete:hover { background-color: #16a34a; }
        .action-toolbar {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: nowrap;
        }
        .btn-action {
            background-color: #6b7280;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        .btn-action:hover { background-color: #4b5563; }
        .training-mode .btn-complete, .training-mode .btn-action, .training-mode .btn-add {
            background-color: #9ca3af !important;
            cursor: not-allowed;
        }
        .training-mode-banner {
            background-color: #10b981;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            border-radius: 6px;
            margin-bottom: 10px;
            display: none;
        }
        .training-mode .training-mode-banner {
            display: block;
        }
        .dark-theme {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .pos-header {
            background: linear-gradient(90deg, #374151, #4b5563);
        }
        .dark-theme .product-panel, .dark-theme .cart-panel, .dark-theme .payment-panel {
            background-color: #374151;
            color: #d1d5db;
        }
        .dark-theme .table th {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .table tbody tr:hover {
            background-color: #4b5563;
        }
        .dark-theme .btn-filter, .dark-theme .btn-category, .dark-theme .category-select {
            background-color: #1f2937;
            border-color: #6b7280;
            color: #d1d5db;
        }
        .dark-theme .btn-filter:hover, .dark-theme .btn-category:hover, .dark-theme .category-select:hover {
            background-color: #4b5563;
        }
        .dark-theme .btn-quantity {
            background-color: #1f2937;
            border-color: #6b7280;
            color: #d1d5db;
        }
        .dark-theme .btn-quantity:hover {
            background-color: #4b5563;
        }
        .dark-theme .summary-row {
            border-color: #6b7280;
        }
        .dark-theme .search-bar input, .dark-theme .customer-section input {
            background-color: #1f2937;
            border-color: #6b7280;
            color: #d1d5db;
        }
        .dark-theme .search-bar input:focus, .dark-theme .customer-section input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 5px rgba(59,130,246,0.3);
        }
        @media (max-width: 992px) {
            .pos-main {
                flex-direction: column;
            }
            .product-panel, .cart-panel, .payment-panel {
                flex: 1;
            }
        }
        @media (max-width: 576px) {
            .pos-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .header-left, .header-center, .header-right {
                width: 100%;
                justify-content: space-between;
            }
            .action-toolbar {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>