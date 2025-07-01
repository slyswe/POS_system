<?php
$title = "Suppliers Management";
$user_name = htmlspecialchars($_SESSION['user']['name'] ?? 'Admin User');
$role = htmlspecialchars($_SESSION['user']['role'] ?? 'admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Copied from Dashboard) -->
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
                <a href="/pos/public/sales/pos" class="nav-item">
                    <i class="fas fa-dollar-sign"></i> <span>POS</span>
                </a>
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <a href="/pos/public/users" class="nav-item">
                        <i class="fas fa-users"></i> <span>Users</span>
                    </a>
                    <a href="/pos/public/suppliers" class="nav-item active">
                        <i class="fas fa-truck"></i> <span>Suppliers</span>
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
                    <h1 class="dashboard-title">Suppliers Management</h1>
                    <span class="connectivity-status" id="connectivity-status">Online</span>
                </div>
                <div class="header-right">
                    <span class="last-updated">Last Updated: <span id="current-time"><?php echo date('Y-m-d H:i:s'); ?></span></span>
                    <div class="header-actions">
                        <button class="btn btn-theme-toggle" onclick="toggleTheme()" id="btn-theme-toggle" title="Toggle Theme" aria-label="Toggle Theme">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Alerts Section -->
            <div class="alerts-section">
                <div id="alerts" class="alerts">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); ?><?php unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?><?php unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Suppliers List -->
            <section class="suppliers-section">
                <div class="section-header">
                    <h3 class="section-title">Suppliers</h3>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="openSupplierModal('create')">Add Supplier</button>
                        <button class="btn btn-info" onclick="exportReport('pdf')">Export PDF</button>
                        <button class="btn btn-info" onclick="exportReport('excel')">Export Excel</button>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Category</th>
                            <th>Outstanding Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['contact_info']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['category']); ?></td>
                                <td><?php echo number_format($supplier['outstanding_balance'], 2); ?> KSh</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info" onclick="viewPurchaseHistory(<?php echo $supplier['id']; ?>)">History</button>
                                        <button class="btn btn-primary" onclick="openSupplierModal('edit', <?php echo json_encode($supplier); ?>)">Edit</button>
                                        <button class="btn btn-primary" onclick="openPurchaseModal(<?php echo $supplier['id']; ?>)">New Purchase</button>
                                        <button class="btn btn-primary" onclick="openPaymentModal(<?php echo $supplier['id']; ?>)">Record Payment</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- Supplier Modal -->
            <div class="modal-overlay" id="supplier-modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('supplier-modal')">×</span>
                    <h3 id="supplier-modal-title">Add Supplier</h3>
                    <form id="supplier-form">
                        <input type="hidden" name="id" id="supplier-id">
                        <div class="form-group">
                            <label for="supplier-name">Name</label>
                            <input type="text" id="supplier-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="supplier-contact">Contact Info</label>
                            <input type="text" id="supplier-contact" name="contact_info">
                        </div>
                        <div class="form-group">
                            <label for="supplier-description">Description</label>
                            <textarea id="supplier-description" name="description"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="supplier-category">Category</label>
                            <input type="text" id="supplier-category" name="category">
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <button type="button" class="btn btn-exit" onclick="closeModal('supplier-modal')">Exit</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Purchase Order Modal -->
            <div class="modal-overlay" id="purchase-modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('purchase-modal')">×</span>
                    <h3>New Purchase Order</h3>
                    <form id="purchase-form">
                        <input type="hidden" name="supplier_id" id="purchase-supplier-id">
                        <div class="form-group">
                            <label for="product-search">Search Products</label>
                            <input type="text" id="product-search" placeholder="Search products...">
                            <div class="autocomplete-dropdown" id="product-autocomplete"></div>
                        </div>
                        <div class="purchase-items">
                            <table class="table" id="purchase-items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Remove</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="form-group">
                            <label for="purchase-notes">Notes</label>
                            <textarea id="purchase-notes" name="notes"></textarea>
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">Create Purchase Order</button>
                            <button type="button" class="btn btn-exit" onclick="closeModal('purchase-modal')">Exit</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment Modal -->
            <div class="modal-overlay" id="payment-modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('payment-modal')">×</span>
                    <h3>Record Payment</h3>
                    <form id="payment-form">
                        <input type="hidden" name="supplier_id" id="payment-supplier-id">
                        <div class="form-group">
                            <label for="payment-purchase-order">Purchase Order (Optional)</label>
                            <select id="payment-purchase-order" name="purchase_order_id">
                                <option value="">Select Purchase Order</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment-amount">Amount</label>
                            <input type="number" id="payment-amount" name="amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="payment-method">Payment Method</label>
                            <select id="payment-method" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_payment">Mobile Payment</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment-notes">Notes</label>
                            <textarea id="payment-notes" name="notes"></textarea>
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">Record Payment</button>
                            <button type="button" class="btn btn-exit" onclick="closeModal('payment-modal')">Exit</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Purchase History Modal -->
            <div class="modal-overlay" id="history-modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('history-modal')">×</span>
                    <h3>Purchase History</h3>
                    <table class="table" id="history-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Delivery</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="form-footer">
                        <button type="button" class="btn btn-exit" onclick="closeModal('history-modal')">Exit</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let purchaseItems = [];

        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const icon = document.querySelector('.btn-theme-toggle i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }

        function showAlert(type, message) {
            const alertsDiv = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertsDiv.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        }

        function openSupplierModal(mode, supplier = {}) {
            const modal = document.getElementById('supplier-modal');
            const form = document.getElementById('supplier-form');
            const title = document.getElementById('supplier-modal-title');
            document.getElementById('supplier-id').value = supplier.id || '';
            document.getElementById('supplier-name').value = supplier.name || '';
            document.getElementById('supplier-contact').value = supplier.contact_info || '';
            document.getElementById('supplier-description').value = supplier.description || '';
            document.getElementById('supplier-category').value = supplier.category || '';
            title.textContent = mode === 'create' ? 'Add Supplier' : 'Edit Supplier';
            modal.style.display = 'flex';
            modal.classList.add('active');

            form.onsubmit = async (e) => {
                e.preventDefault();
                const data = {
                    name: form.name.value,
                    contact_info: form.contact_info.value,
                    description: form.description.value,
                    category: form.category.value
                };
                const url = mode === 'create' ? '/pos/public/suppliers/create' : `/pos/public/suppliers/edit/${form.id.value}`;
                try {
                    const response = await axios.post(url, data, { headers: { 'Content-Type': 'application/json' } });
                    if (response.data.success) {
                        showAlert('success', response.data.message || 'Supplier saved successfully.');
                        closeModal('supplier-modal');
                        location.reload();
                    } else {
                        showAlert('error', response.data.message || 'Failed to save supplier.');
                    }
                } catch (error) {
                    showAlert('error', 'Error saving supplier.');
                }
            };
        }

        function openPurchaseModal(supplierId) {
            purchaseItems = [];
            document.getElementById('purchase-supplier-id').value = supplierId;
            document.getElementById('product-search').value = '';
            document.getElementById('purchase-items-table').querySelector('tbody').innerHTML = '';
            document.getElementById('purchase-modal').style.display = 'flex';
            document.getElementById('purchase-modal').classList.add('active');

            const form = document.getElementById('purchase-form');
            form.onsubmit = async (e) => {
                e.preventDefault();
                const data = {
                    supplier_id: supplierId,
                    items: purchaseItems,
                    notes: form.notes.value
                };
                try {
                    const response = await axios.post('/pos/public/suppliers/purchase', data, { headers: { 'Content-Type': 'application/json' } });
                    if (response.data.success) {
                        showAlert('success', response.data.message || 'Purchase order created successfully.');
                        closeModal('purchase-modal');
                        location.reload();
                    } else {
                        showAlert('error', response.data.message || 'Failed to create purchase order.');
                    }
                } catch (error) {
                    showAlert('error', 'Error creating purchase order.');
                }
            };
        }

        function openPaymentModal(supplierId) {
            document.getElementById('payment-supplier-id').value = supplierId;
            const select = document.getElementById('payment-purchase-order');
            select.innerHTML = '<option value="">Select Purchase Order</option>';
            axios.get(`/pos/public/suppliers/history/${supplierId}`).then(response => {
                response.data.forEach(order => {
                    if (order.status !== 'cancelled') {
                        select.innerHTML += `<option value="${order.id}">#${order.id} - ${order.total_amount} KSh</option>`;
                    }
                });
            });
            document.getElementById('payment-modal').style.display = 'flex';
            document.getElementById('payment-modal').classList.add('active');

            const form = document.getElementById('payment-form');
            form.onsubmit = async (e) => {
                e.preventDefault();
                const data = {
                    supplier_id: supplierId,
                    purchase_order_id: form.purchase_order_id.value || null,
                    amount: parseFloat(form.amount.value),
                    payment_method: form.payment_method.value,
                    notes: form.notes.value
                };
                try {
                    const response = await axios.post('/pos/public/suppliers/payment', data, { headers: { 'Content-Type': 'application/json' } });
                    if (response.data.success) {
                        showAlert('success', 'Payment recorded successfully.');
                        closeModal('payment-modal');
                        location.reload();
                    } else {
                        showAlert('error', response.data.message || 'Failed to record payment.');
                    }
                } catch (error) {
                    showAlert('error', 'Error recording payment.');
                }
            };
        }

        function viewPurchaseHistory(supplierId) {
            const tbody = document.getElementById('history-table').querySelector('tbody');
            tbody.innerHTML = '';
            axios.get(`/pos/public/suppliers/history/${supplierId}`).then(response => {
                response.data.forEach(order => {
                    const balance = order.total_amount - order.paid_amount;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${order.id}</td>
                        <td>${new Date(order.order_date).toLocaleString()}</td>
                        <td>${order.total_amount.toFixed(2)} KSh</td>
                        <td>${order.paid_amount.toFixed(2)} KSh</td>
                        <td>${balance.toFixed(2)} KSh</td>
                        <td>${order.status}</td>
                        <td>
                            <select onchange="updateDeliveryStatus(${order.id}, this.value)">
                                <option value="pending" ${order.delivery_status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="delivered" ${order.delivery_status === 'delivered' ? 'selected' : ''}>Delivered</option>
                                <option value="partially_delivered" ${order.delivery_status === 'partially_delivered' ? 'selected' : ''}>Partially Delivered</option>
                            </select>
                        </td>
                        <td><button class="btn btn-info" onclick="viewOrderDetails(${order.id})">Details</button></td>
                    `;
                    tbody.appendChild(row);
                });
                document.getElementById('history-modal').style.display = 'flex';
                document.getElementById('history-modal').classList.add('active');
            });
        }

        function updateDeliveryStatus(orderId, status) {
            axios.post(`/pos/public/suppliers/delivery/${orderId}`, { delivery_status: status }, { headers: { 'Content-Type': 'application/json' } })
                .then(response => {
                    if (response.data.success) {
                        showAlert('success', 'Delivery status updated successfully.');
                    } else {
                        showAlert('error', response.data.message || 'Failed to update delivery status.');
                    }
                })
                .catch(() => showAlert('error', 'Error updating delivery status.'));
        }

        function exportReport(format) {
            window.location.href = `/pos/public/suppliers/export/${format}`;
        }

        // Product autocomplete
        document.getElementById('product-search').addEventListener('input', async (e) => {
            const query = e.target.value;
            if (query.length < 2) {
                document.getElementById('product-autocomplete').innerHTML = '';
                return;
            }
            const response = await axios.get(`/pos/public/products/filter?type=search&q=${encodeURIComponent(query)}`);
            const dropdown = document.getElementById('product-autocomplete');
            dropdown.innerHTML = '';
            response.data.products.forEach(product => {
                const div = document.createElement('div');
                div.textContent = `${product.name} (Stock: ${product.stock})`;
                div.onclick = () => addPurchaseItem(product);
                dropdown.appendChild(div);
            });
        });

        function addPurchaseItem(product) {
            const existing = purchaseItems.find(item => item.product_id === product.id);
            if (existing) {
                existing.quantity++;
            } else {
                purchaseItems.push({
                    product_id: product.id,
                    name: product.name,
                    quantity: 1,
                    unit_price: product.cost_price || product.price
                });
            }
            updatePurchaseTable();
            document.getElementById('product-search').value = '';
            document.getElementById('product-autocomplete').innerHTML = '';
        }

        function updatePurchaseTable() {
            const tbody = document.getElementById('purchase-items-table').querySelector('tbody');
            tbody.innerHTML = '';
            purchaseItems.forEach((item, index) => {
                const total = item.quantity * item.unit_price;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td><input type="number" value="${item.quantity}" min="1" onchange="updateItemQuantity(${index}, this.value)"></td>
                    <td><input type="number" value="${item.unit_price.toFixed(2)}" step="0.01" onchange="updateItemPrice(${index}, this.value)"></td>
                    <td>${total.toFixed(2)} KSh</td>
                    <td><button class="btn btn-remove" onclick="removePurchaseItem(${index})"><i class="fas fa-trash"></i></button></td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateItemQuantity(index, quantity) {
            purchaseItems[index].quantity = parseInt(quantity);
            updatePurchaseTable();
        }

        function updateItemPrice(index, price) {
            purchaseItems[index].unit_price = parseFloat(price);
            updatePurchaseTable();
        }

        function removePurchaseItem(index) {
            purchaseItems.splice(index, 1);
            updatePurchaseTable();
        }

        function viewOrderDetails(orderId) {
            showAlert('info', `Viewing details for Order #${orderId}`);
        }

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

        function checkConnectivity() {
            const status = navigator.onLine ? 'Online' : 'Offline';
            document.getElementById('connectivity-status').textContent = status;
            document.getElementById('connectivity-status').className = `connectivity-status ${status.toLowerCase()}`;
        }
        window.addEventListener('online', checkConnectivity);
        window.addEventListener('offline', checkConnectivity);
        checkConnectivity();
    </script>

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
            position: relative;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .suppliers-section {
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
        .section-actions {
            display: flex;
            gap: 10px;
        }
        .btn-primary {
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .btn-primary:hover {
            background-color: #1e40af;
        }
        .btn-info {
            background-color: #3b82f6;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .btn-info:hover {
            background-color: #1e40af;
        }
        .btn-exit {
            background-color: #6b7280;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .btn-exit:hover {
            background-color: #4b5563;
        }
        .btn-remove {
            color: #ef4444;
            border: none;
            padding: 6px;
            border-radius: 6px;
            font-size: 0.8rem;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            justify-content: flex-start;
        }
        .action-buttons .btn {
            padding: 6px 10px;
            font-size: 0.85rem;
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
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }
        .autocomplete-dropdown {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            width: calc(100% - 16px);
        }
        .autocomplete-dropdown div {
            padding: 8px;
            cursor: pointer;
        }
        .autocomplete-dropdown div:hover {
            background: #f5f5f5;
        }
        .purchase-items {
            margin-bottom: 20px;
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
        .dark-theme .table, .dark-theme .modal-content {
            background-color: #374151;
        }
        .dark-theme .table th {
            background-color: #4b5563;
            color: #d1d5db;
        }
        .dark-theme .btn-primary {
            background-color: #3b82f6;
        }
        .dark-theme .btn-primary:hover {
            background-color: #1e40af;
        }
        .dark-theme .btn-info {
            background-color: #3b82f6;
        }
        .dark-theme .btn-info:hover {
            background-color: #1e40af;
        }
        .dark-theme .btn-exit {
            background-color: #6b7280;
        }
        .dark-theme .btn-exit:hover {
            background-color: #4b5563;
        }
        .dark-theme .form-group input, .dark-theme .form-group textarea, .dark-theme .form-group select {
            background-color: #4b5563;
            color: #d1d5db;
            border-color: #6b7280;
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
            .table th, .table td {
                padding: 8px;
                font-size: 0.85rem;
            }
            .section-actions {
                flex-direction: column;
                gap: 5px;
            }
            .btn-primary, .btn-info, .btn-exit {
                width: 100%;
            }
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>