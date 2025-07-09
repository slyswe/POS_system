<?php
if (!defined('IN_CONTROLLER')) {
    header('Location: /pos/public/login');
    exit;
}

if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
    header('Location: /pos/public/login');
    exit;
}


if (isset($_SESSION['receipt_data'])) {
    // Output receipt data for JavaScript
    echo '<script>const initialReceiptData = ' . json_encode($_SESSION['receipt_data']) . ';</script>';
}

$title = "Point of Sale";
$cashier = [
    'name' => $_SESSION['user']['name'] ?? 'Unknown',
    'id' => $_SESSION['user']['id'] ?? 'N/A'
];

// Mock data for products and categories (replace with actual database queries)
$categories = $categories ?? [
    ['id' => 1, 'name' => 'Beverages'],
    ['id' => 2, 'name' => 'Bakery'],
    ['id' => 3, 'name' => 'Produce'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="/pos/public">
    <title><?php echo htmlspecialchars($title); ?> - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes scale-in {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-scale-in { animation: scale-in 0.3s ease-out forwards; }
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .animate-fade-in { animation: fade-in 0.5s ease-in-out forwards; }
        .font-sans { font-family: 'Inter', sans-serif; }
        .training-mode .training-mode-banner { display: block; }
        .training-mode .btn-primary, .training-mode .btn-action, .training-mode .btn-complete {
            background-color: #9ca3af !important;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="min-h-screen font-sans bg-slate-100 text-slate-800 transition-colors duration-300">
    <div class="max-w-7xl mx-auto p-4">
        <!-- Training Mode Banner -->
        <div class="training-mode-banner hidden bg-emerald-500 text-white text-center py-3 rounded-lg mb-4 font-semibold">
            Training Mode Active - No Transactions Will Be Saved
        </div>

        <!-- Header Section -->
        <header class="bg-white rounded-2xl shadow-lg p-6 mb-6 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <h1 class="text-3xl font-bold text-blue-600">Point of Sale</h1>
                <span class="px-3 py-1 text-sm font-medium bg-blue-100 text-blue-600 rounded-full" id="connectivity-status">Online</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-500"><?php echo htmlspecialchars($cashier['name']) . ' (ID: ' . htmlspecialchars($cashier['id']) . ')'; ?></span>
                <span class="text-sm text-slate-500">Last Updated: <span id="current-time"><?php echo date('Y-m-d H:i:s'); ?></span></span>
                <button class="p-3 text-slate-600 hover:bg-slate-200 rounded-full" onclick="toggleTrainingMode()" title="Training Mode" aria-label="Training Mode">
                    <i class="fas fa-graduation-cap"></i>
                </button>
                <a href="/pos/public/login" class="p-3 text-slate-600 hover:bg-slate-200 rounded-full" title="Switch User" aria-label="Switch User">
                    <i class="fas fa-user"></i>
                </a>
                <a href="/pos/public/logout" class="p-3 text-red-500 hover:bg-red-100 rounded-full" title="Logout" aria-label="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- Alerts Section -->
        <div id="alerts" class="mb-6 space-y-2">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="flex items-center p-4 rounded-lg bg-red-100 text-red-800">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                    <button class="ml-auto p-2 hover:bg-red-200 rounded-full" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="flex items-center p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                    <button class="ml-auto p-2 hover:bg-green-200 rounded-full" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cart Panel -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg flex flex-col">
                <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold">Current Sale</h2>
                </div>
                <!-- Search -->
                <div class="p-5">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                        <form method="POST" action="/pos/public/sales/pos">
                            <div class="flex gap-2">
                                <input type="text" name="barcode" id="product-search" placeholder="Scan barcode or search products..." class="w-full pl-12 pr-4 py-4 bg-white border border-transparent focus:ring-2 focus:ring-blue-500 focus:border-transparent rounded-xl shadow-sm transition">
                                <button type="submit" name="add_to_cart" class="px-4 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Filters -->
                <div class="p-5 flex flex-wrap gap-3 border-b border-slate-200">
                    <button class="btn-filter px-4 py-2 bg-slate-100 rounded-lg hover:bg-slate-200 transition active" onclick="filterProducts('all')">All</button>
                    <button class="btn-filter px-4 py-2 bg-slate-100 rounded-lg hover:bg-slate-200 transition" onclick="filterProducts('frequent')">Frequent</button>
                    <button class="btn-filter px-4 py-2 bg-slate-100 rounded-lg hover:bg-slate-200 transition" onclick="filterProducts('new')">New</button>
                    <select class="category-select px-4 py-2 bg-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 transition" onchange="filterProducts(this.value)">
                        <option value="">Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Product List -->
                <div class="p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" id="product-list">
                        <!-- <div class="text-center p-4 text-slate-500">Loading products...</div> -->
                    </div>
                </div>
                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-5" id="cart-items">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="h-full flex flex-col justify-center items-center text-center text-slate-400" id="empty-cart">
                            <i class="fas fa-shopping-cart text-6xl mb-4"></i>
                            <p class="text-lg font-medium">Your cart is empty</p>
                            <p class="text-sm">Add products using the search or popular items.</p>
                        </div>
                    <?php else: ?>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-slate-600">
                                    <th class="p-3">Item</th>
                                    <th class="p-3">Qty</th>
                                    <th class="p-3">Price</th>
                                    <th class="p-3">Total</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $subtotal = 0; ?>
                                <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                    <tr class="border-t border-slate-200">
                                        <td class="p-3"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="p-3">
                                            <form method="POST" action="/pos/public/sales/pos" class="quantity-form">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                                                <div class="flex items-center bg-slate-100 rounded-full">
                                                    <button type="submit" name="decrease_qty" class="p-2 text-slate-600 hover:text-red-500 transition" <?php echo $item['quantity'] <= 0 ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0" max="<?php echo htmlspecialchars($item['stock']); ?>" class="w-12 text-center bg-transparent border-none text-sm font-bold" readonly>
                                                    <button type="submit" name="increase_qty" class="p-2 text-slate-600 hover:text-green-500 transition" <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="p-3">KSh <?php echo number_format($item['price'], 2); ?></td>
                                        <td class="p-3">KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        <td class="p-3">
                                            <div class="flex gap-2">
                                                <button class="p-2 text-slate-600 hover:text-blue-500 transition" onclick="applyItemDiscount('<?php echo htmlspecialchars($product_id); ?>')">
                                                    <i class="fas fa-percentage"></i>
                                                </button>
                                                <form method="POST" action="/pos/public/sales/pos" style="display:inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                                                    <button type="submit" name="remove_from_cart" class="p-2 text-slate-600 hover:text-red-500 transition">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php $subtotal += $item['price'] * $item['quantity']; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <!-- Cart Summary -->
                <div class="p-5 border-t border-slate-200 space-y-3">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span class="font-medium" id="subtotal">KSh <?php echo number_format($subtotal ?? 0, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Tax (16%)</span>
                        <span class="font-medium" id="tax">KSh <?php echo number_format(($subtotal ?? 0) * 0.16, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Discount</span>
                        <span class="font-medium" id="discount-amount">KSh 0.00</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold text-slate-900">
                        <span>Total</span>
                        <span id="grand-total">KSh <?php echo number_format(($subtotal ?? 0) * 1.16, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Panel -->
            <div class="bg-white rounded-2xl shadow-lg p-5 flex flex-col">
                <h3 class="font-semibold text-lg mb-4">Payment</h3>
                <form method="POST" action="/pos/public/sales/checkout" id="payment-form">
                    <input type="hidden" id="payment_method" name="payment_method" value="cash">
                    <input type="hidden" id="receipt_method" name="receipt_method" value="print">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($cashier['id']); ?>">
                    <div class="mb-4">
                        <label for="discount" class="block text-sm font-medium text-slate-700 mb-2">Cart Discount (KSh)</label>
                        <input type="number" step="0.01" id="discount" name="discount" value="0" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500" onchange="updateTotal()">
                    </div>
                    <div class="mb-4">
                        <label for="amount_paid" class="block text-sm font-medium text-slate-700 mb-2">Amount Paid (KSh)</label>
                        <input type="number" step="0.01" id="amount_paid" name="amount_paid" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Payment Method</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" class="btn-payment px-4 py-3 rounded-lg text-sm font-semibold bg-blue-600 text-white shadow-md ring-2 ring-blue-300 transition" onclick="selectPayment('cash')" data-method="cash">Cash</button>
                            <button type="button" class="btn-payment px-4 py-3 rounded-lg text-sm font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 transition" onclick="selectPayment('card')" data-method="card">Card</button>
                            <button type="button" class="btn-payment px-4 py-3 rounded-lg text-sm font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 transition" onclick="selectPayment('mobile')" data-method="mobile">Mobile</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <button type="button" class="btn-action flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 p-3 rounded-lg transition" onclick="holdOrder()" title="Hold Order">
                            <i class="fas fa-clock"></i> Hold
                        </button>
                        <button type="button" class="btn-action flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 p-3 rounded-lg transition" onclick="recallOrder()" title="Recall Sale">
                            <i class="fas fa-undo"></i> Recall
                        </button>
                        <button type="button" class="btn-action flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 p-3 rounded-lg transition" onclick="returnItem()" title="Return Item">
                            <i class="fas fa-exchange-alt"></i> Return
                        </button>
                        <button type="button" class="btn-action flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 p-3 rounded-lg transition" onclick="cancelSale()" title="Cancel Sale">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                    <button type="button" class="btn-complete w-full text-center py-4 bg-green-600 text-white font-bold text-lg rounded-xl shadow-lg hover:bg-green-700 transition disabled:bg-slate-400 disabled:cursor-not-allowed" onclick="openCheckoutModal()" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>Complete Sale</button>
                </form>
            </div>
        </div>

        <!-- Checkout Modal -->
        
        <div id="checkout-modal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-40 transition-opacity duration-300 hidden">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md m-4 transform transition-all duration-300 animate-scale-in">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-2xl font-semibold text-slate-900">Confirm Purchase</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="text-center">
                        <p class="text-slate-500 text-lg">Total Amount Due</p>
                        <p class="text-5xl font-bold text-blue-600 tracking-tight" id="modal-total">KSh <?php echo number_format(($subtotal ?? 0) * 1.16, 2); ?></p>
                    </div>
                    
                    <!-- Checkout Summary -->
                    <div id="checkout-summary" class="space-y-4">
                        <div class="border-b border-slate-200 pb-4">
                            <h4 class="font-medium text-slate-700 mb-3">Items in Cart</h4>
                            <div class="space-y-3">
                                <?php foreach ($_SESSION['cart'] ?? [] as $product_id => $item): ?>
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                                            <span class="text-sm text-slate-500 block"><?php echo $item['quantity']; ?> × KSh <?php echo number_format($item['price'], 2); ?></span>
                                        </div>
                                        <span class="font-medium">KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-600">Subtotal</span>
                                <span class="font-medium" id="modal-subtotal">KSh <?php echo number_format($subtotal ?? 0, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Tax (16%)</span>
                                <span class="font-medium" id="modal-tax">KSh <?php echo number_format(($subtotal ?? 0) * 0.16, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Discount</span>
                                <span class="font-medium text-red-600" id="modal-discount-amount">-KSh 0.00</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-slate-200">
                                <span class="text-lg font-bold">Total</span>
                                <span class="text-lg font-bold" id="modal-grand-total">KSh <?php echo number_format(($subtotal ?? 0) * 1.16, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Inputs -->
                    <div class="payment-section space-y-4">
                        <div>
                            <label for="modal-amount-paid" class="block text-sm font-medium text-slate-700 mb-1">Amount Paid (KSh)</label>
                            <input type="number" step="0.01" id="modal-amount-paid" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="modal-discount" class="block text-sm font-medium text-slate-700 mb-1">Cart Discount (KSh)</label>
                            <input type="number" step="0.01" id="modal-discount" value="0" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500" onchange="updateModalTotal()">
                        </div>
                        <div>
                            <label for="modal-change" class="block text-sm font-medium text-slate-700 mb-1">Change Due (KSh)</label>
                            <input type="number" step="0.01" id="modal-change" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500" readonly>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-slate-50 rounded-b-2xl flex items-center justify-between">
                    <button onclick="closeCheckoutModal()" class="px-6 py-3 text-sm font-semibold text-slate-700 bg-transparent hover:bg-slate-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button onclick="return confirmCheckout(event)" class="px-8 py-3 text-base font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md focus:ring-2 focus:ring-green-500 transition">
                        Confirm & Print Receipt
                    </button>
                </div>
            </div>
        </div>

        <!-- Receipt Template (hidden until needed) -->
        <div id="receipt-template" class="hidden">
            <div class="receipt">
                <h3 class="text-center font-bold text-lg mb-2"><?php echo htmlspecialchars($title); ?></h3>
                <p class="text-center text-sm mb-4"><?php echo date('Y-m-d H:i:s'); ?></p>
                
                <div class="receipt-header border-b border-black pb-2 mb-2">
                    <p class="flex justify-between text-sm">
                        <span>Sale ID:</span>
                        <span id="receipt-sale-id">SALE-<?php echo rand(1000, 9999); ?></span>
                    </p>
                    <p class="flex justify-between text-sm">
                        <span>Cashier:</span>
                        <span><?php echo htmlspecialchars($cashier['name']); ?></span>
                    </p>
                    <p class="flex justify-between text-sm">
                        <span>Date:</span>
                        <span id="receipt-date"><?php echo date('Y-m-d H:i:s'); ?></span>
                    </p>
                </div>
                
                <div class="receipt-items my-4">
                    <?php foreach ($_SESSION['cart'] ?? [] as $product_id => $item): ?>
                        <div class="receipt-item flex justify-between py-1">
                            <div>
                                <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="text-xs block"><?php echo $item['quantity']; ?> × <?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <span><?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="receipt-totals border-t border-black pt-2">
                    <p class="flex justify-between">
                        <span>Subtotal:</span>
                        <span id="receipt-subtotal"><?php echo number_format($subtotal ?? 0, 2); ?></span>
                    </p>
                    <p class="flex justify-between">
                        <span>Tax (16%):</span>
                        <span id="receipt-tax"><?php echo number_format(($subtotal ?? 0) * 0.16, 2); ?></span>
                    </p>
                    <p class="flex justify-between">
                        <span>Discount:</span>
                        <span id="receipt-discount">0.00</span>
                    </p>
                    <p class="flex justify-between font-bold mt-2">
                        <span>Total:</span>
                        <span id="receipt-total"><?php echo number_format(($subtotal ?? 0) * 1.16, 2); ?></span>
                    </p>
                    <p class="flex justify-between text-sm mt-4">
                        <span>Payment Method:</span>
                        <span id="receipt-payment">Cash</span>
                    </p>
                    <p class="flex justify-between text-sm">
                        <span>Amount Paid:</span>
                        <span id="receipt-amount-paid"><?php echo number_format(($subtotal ?? 0) * 1.16, 2); ?></span>
                    </p>
                    <p class="flex justify-between text-sm">
                        <span>Change:</span>
                        <span id="receipt-change">0.00</span>
                    </p>
                </div>
                
                <div class="receipt-footer text-center text-xs mt-6">
                    <p>Thank you for shopping with us!</p>
                    <p>Items can be exchanged within 7 days with receipt</p>
                </div>
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
                const productList = document.getElementById('product-list');
                const baseUrl = document.querySelector('meta[name="base-url"]').content;

                console.log('Base URL:', baseUrl);
                console.log('Filter:', filter);

                // Highlight active filter
                document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
                if (filter === 'all' || filter === 'frequent' || filter === 'new') {
                    document.querySelector(`.btn-filter[onclick="filterProducts('${filter}')"]`).classList.add('active');
                }

                // Show loading state
                productList.innerHTML = '<div class="loading">Loading products...</div>';

                // Determine endpoint based on filter
                let url = `${baseUrl}/products/filter?type=${encodeURIComponent(filter)}`;
                if (filter && !['all', 'frequent', 'new'].includes(filter)) {
                    url = `${baseUrl}/products/filter?category_id=${encodeURIComponent(filter)}`;
                }

                console.log('Fetching products from:', url);

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status, 'OK:', response.ok, 'URL:', response.url);
                    // Capture response text for debugging
                    return response.text().then(text => {
                        console.log('Response text:', text.substring(0, 200)); // Log first 200 chars
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}, response: ${text.substring(0, 200)}`);
                        }
                        return JSON.parse(text);
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    productList.innerHTML = '';
                    if (data.success && data.products.length > 0) {
                        data.products.forEach(product => {
                            const productItem = document.createElement('div');
                            productItem.className = 'product-item';
                            productItem.innerHTML = `
                                <span>${product.name}</span>
                                <span>${product.price.toFixed(2)} KES</span>
                                <button class="btn btn-add-product" onclick="addProductToCart('${product.id}')"><i class="fas fa-plus"></i> Add</button>
                            `;
                            productList.appendChild(productItem);
                        });
                    } else {
                        productList.innerHTML = '<div class="no-products">No products found.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    productList.innerHTML = '<div class="no-products">Failed to load products.</div>';
                    showAlert('error', `Failed to load products: ${error.message}`);
                });
            } catch (e) {
                console.error('Error filtering products:', e);
                showAlert('error', 'An error occurred while filtering products.');
            }
        }

        // Handle product search form submission
        document.querySelector('.search-bar form').addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.getElementById('product-search').value.trim();
            
            if (!searchTerm) {
                showAlert('error', 'Please enter a product name or barcode');
                return;
            }

            addProductBySearch(searchTerm);
        });

        // Function to add product by search term
        async function addProductBySearch(searchTerm) {
            try {
                const baseUrl = document.querySelector('meta[name="base-url"]').content;
                const response = await fetch(`${baseUrl}/products/search?q=${encodeURIComponent(searchTerm)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success && data.products.length > 0) {
                    // Add the first matching product (or implement a selection UI for multiple matches)
                    addProductToCart(data.products[0].id);
                    document.getElementById('product-search').value = ''; // Clear the search field
                } else {
                    showAlert('error', 'No products found matching your search');
                }
            } catch (error) {
                console.error('Error searching products:', error);
                showAlert('error', 'Failed to search products');
            }
        }

        // Add product to cart
        function addProductToCart(productId) {
            try {
                const baseUrl = document.querySelector('meta[name="base-url"]').content;
                console.log('Adding product with ID:', productId);
                console.log('POST URL:', `${baseUrl}/sales/pos`);

                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('add_to_cart', 'true');

                fetch(`${baseUrl}/sales/pos`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status, 'OK:', response.ok);
                    return response.text().then(text => {
                        console.log('Response text:', text.substring(0, 200));
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}, response: ${text.substring(0, 200)}`);
                        }
                        return JSON.parse(text);
                    });
                })
                .then(data => {
                    console.log('Parsed response:', data);
                    if (data.success) {
                        // Update client-side cart
                        updateCartWithProduct(productId);
                    } else {
                        showAlert('error', data.message || 'Failed to add product to cart.');
                    }
                })
                .catch(error => {
                    console.error('Error adding product:', error);
                    showAlert('error', `Failed to add product to cart: ${error.message}`);
                });
            } catch (e) {
                console.error('Error in addProductToCart:', e);
                showAlert('error', 'An error occurred while adding product to cart.');
            }
        }

        // Initialize client-side cart from session
        let cart = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;

        function updateCartWithProduct(productId) {
            try {
                const baseUrl = document.querySelector('meta[name="base-url"]').content;
                fetch(`${baseUrl}/products/filter?type=all`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const product = data.products.find(p => p.id === parseInt(productId));
                        if (product) {
                            const existingItem = cart[productId];
                            if (existingItem) {
                                cart[productId] = { ...existingItem, quantity: existingItem.quantity + 1 };
                            } else {
                                cart[productId] = {
                                    name: product.name,
                                    price: product.price,
                                    stock: product.stock,
                                    quantity: 1
                                };
                            }
                            updateCartDisplay();
                            updateTotal();
                        } else {
                            showAlert('error', 'Product not found in client-side data.');
                        }
                    } else {
                        showAlert('error', 'Failed to fetch product details.');
                    }
                });
            } catch (e) {
                console.error('Error updating cart with product:', e);
                showAlert('error', 'An error occurred while updating cart.');
            }
        }

        function updateCartDisplay() {
            const cartItems = document.querySelector('.cart-items tbody') || document.createElement('tbody');
            const cartContainer = document.querySelector('.cart-items') || document.createElement('div');
            cartContainer.className = 'cart-items';
            const emptyCart = document.querySelector('.empty-cart');

            if (Object.keys(cart).length === 0) {
                emptyCart.style.display = 'flex';
                cartContainer.style.display = 'none';
                return;
            }

            emptyCart.style.display = 'none';
            cartContainer.style.display = 'block';
            cartItems.innerHTML = '';

            let subtotal = 0;
            for (const [id, item] of Object.entries(cart)) {
                const total = item.price * item.quantity;
                subtotal += total;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>
                        <form method="POST" action="/pos/public/sales/pos" class="quantity-form">
                            <input type="hidden" name="product_id" value="${id}">
                            <div class="quantity-control">
                                <button type="submit" name="decrease_qty" class="btn-quantity" ${item.quantity <= 1 ? 'disabled' : ''}><i class="fas fa-minus"></i></button>
                                <input type="number" name="quantity" value="${item.quantity}" min="0" max="${item.stock}" class="quantity-input" onchange="updateQuantity('${id}', this.value)">
                                <button type="submit" name="increase_qty" class="btn-quantity" ${item.quantity >= item.stock ? 'disabled' : ''}><i class="fas fa-plus"></i></button>
                            </div>
                        </form>
                    </td>
                    <td><span class="money">${item.price.toFixed(2)}</span> KES</td>
                    <td><span class="money">${total.toFixed(2)}</span> KES</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-discount" onclick="applyItemDiscount('${id}')"><i class="fas fa-percentage"></i></button>
                            <form method="POST" action="/pos/public/sales/pos" style="display:inline;">
                                <input type="hidden" name="product_id" value="${id}">
                                <button type="submit" name="remove_from_cart" class="btn btn-remove"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                `;
                cartItems.appendChild(row);
            }

            cartContainer.appendChild(cartItems);
            document.querySelector('.cart-panel').appendChild(cartContainer);
        }

        function updateQuantity(productId, newQuantity) {
            try {
                newQuantity = parseInt(newQuantity);
                if (isNaN(newQuantity) || newQuantity < 0) {
                    showAlert('error', 'Invalid quantity.');
                    return;
                }

                const baseUrl = document.querySelector('meta[name="base-url"]').content;
                fetch(`${baseUrl}/products/filter?type=all`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const product = data.products.find(p => p.id === parseInt(productId));
                            if (!product) {
                                showAlert('error', 'Product not found.');
                                return;
                            }
                            if (newQuantity > product.stock) {
                                showAlert('error', 'Not enough stock!');
                                return;
                            }

                            const formData = new FormData();
                            formData.append('product_id', productId);
                            formData.append('quantity', newQuantity);

                            fetch(`${baseUrl}/sales/pos`, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    if (newQuantity <= 0) {
                                        delete cart[productId];
                                    } else {
                                        cart[productId] = { ...cart[productId], quantity: newQuantity };
                                    }
                                    updateCartDisplay();
                                    updateTotal();
                                } else {
                                    showAlert('error', data.message || 'Failed to update quantity.');
                                }
                            });
                        }
                    })
                    .catch(e => {
                        console.error('Error updating quantity:', e);
                        showAlert('error', 'An error occurred while updating quantity.');
                    });
            } catch (e) {
                console.error('Error updating quantity:', e);
                showAlert('error', 'An error occurred while updating quantity.');
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

        // Customer lookup function with debouncing
        let debounceTimeout;
        function lookupCustomer() {
            const query = document.getElementById('customer-lookup').value.trim();
            if (!query) {
                showAlert('error', 'Please enter a phone or email');
                return;
            }

            fetch(`/pos/public/customer/lookup?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayCustomerDetails(data.customer, data.history);
                    } else {
                        showAlert('error', data.message || 'Customer not found');
                    }
                })
                .catch(error => {
                    console.error('Lookup failed:', error);
                    showAlert('error', 'Failed to fetch customer');
                });
        }

        // Autocomplete for customer lookup
        document.getElementById('new-customer-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const phone = document.getElementById('new-customer-phone').value.trim();
            const email = document.getElementById('new-customer-email').value.trim();
            const phoneRegex = /^[\d\s\-\+\(\)]{7,20}$/;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!phoneRegex.test(phone)) {
                showAlert('error', 'Invalid phone number format.');
                return;
            }
            if (email && !emailRegex.test(email)) {
                showAlert('error', 'Invalid email format.');
                return;
            }

            const baseUrl = document.querySelector('meta[name="base-url"]').content;
            const customerId = document.getElementById('modal-customer-id').value;
            const url = customerId ? `${baseUrl}/customer/update/${customerId}` : `${baseUrl}/customer/create`;
            const customerData = {
                name: document.getElementById('new-customer-name').value,
                phone: phone,
                email: email,
                address: document.getElementById('new-customer-address').value
            };

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(customerData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', customerId ? 'Customer updated successfully!' : 'Customer created successfully!');
                    closeModal();
                    document.getElementById('customer-lookup').value = customerData.phone;
                    lookupCustomer().catch(() => {
                        // Fallback: Display customer details from form data
                        displayCustomerDetails({
                            id: data.customer_id || customerId,
                            name: customerData.name,
                            phone: customerData.phone,
                            email: customerData.email || 'N/A',
                            address: customerData.address || 'N/A',
                            loyalty_points: 0
                        }, []);
                        showAlert('warning', 'Customer saved but lookup failed. Showing entered details.');
                    });
                } else {
                    showAlert('error', data.message || 'Failed to process customer.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Failed to process customer.');
            });
        });

        // Display customer details
        function displayCustomerDetails(customer, history) {
            const detailsDiv = document.getElementById('customer-details');
            document.getElementById('customer-name').textContent = customer.name;
            document.getElementById('customer-phone').textContent = customer.phone;
            document.getElementById('customer-email').textContent = customer.email || 'N/A';
            document.getElementById('customer-address').textContent = customer.address || 'N/A';
            document.getElementById('customer-points').textContent = customer.loyalty_points;
            document.getElementById('customer_id').value = customer.id;

            const lastPurchase = history.length > 0 ?
                new Date(history[0].created_at).toLocaleDateString() : 'Never';
            document.getElementById('customer-last-purchase').textContent = lastPurchase;

            const historyContainer = document.getElementById('purchase-history-items');
            historyContainer.innerHTML = '';
            if (history.length > 0) {
                history.forEach(purchase => {
                    const item = document.createElement('div');
                    item.className = 'history-item';
                    item.innerHTML = `
                        <p><strong>#${purchase.id}</strong> - ${new Date(purchase.created_at).toLocaleDateString()}</p>
                        <p>${purchase.items_count} items - ${purchase.total_amount.toFixed(2)} KES</p>
                    `;
                    historyContainer.appendChild(item);
                });
            } else {
                historyContainer.innerHTML = '<div class="history-item empty">No purchase history found.</div>';
            }

            detailsDiv.style.display = 'block';
        }

        // Edit customer
        function editCustomer() {
            const customerId = document.getElementById('customer_id').value;
            if (!customerId) {
                showAlert('error', 'No customer selected.');
                return;
            }

            const modal = document.getElementById('new-customer-modal');
            document.getElementById('modal-title').textContent = 'Edit Customer';
            document.getElementById('modal-customer-id').value = customerId;
            document.getElementById('new-customer-name').value = document.getElementById('customer-name').textContent;
            document.getElementById('new-customer-phone').value = document.getElementById('customer-phone').textContent;
            document.getElementById('new-customer-email').value = document.getElementById('customer-email').textContent === 'N/A' ? '' : document.getElementById('customer-email').textContent;
            document.getElementById('new-customer-address').value = document.getElementById('customer-address').textContent === 'N/A' ? '' : document.getElementById('customer-address').textContent;
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
            document.getElementById('new-customer-name').focus();
            document.addEventListener('focus', trapCustomerModalFocus, true);
            document.addEventListener('keydown', handleCustomerModalEscape);
        }

        // New customer modal functions
        function showNewCustomerModal() {
            document.getElementById('modal-title').textContent = 'New Customer';
            document.getElementById('new-customer-form').reset();
            document.getElementById('modal-customer-id').value = '';
            const modal = document.getElementById('new-customer-modal');
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
            const firstInput = document.getElementById('new-customer-name');
            firstInput.focus();
            document.addEventListener('focus', trapCustomerModalFocus, true);
            document.addEventListener('keydown', handleCustomerModalEscape);
        }

        function closeModal() {
            const modal = document.getElementById('new-customer-modal');
            modal.style.display = 'none';
            document.getElementById('autocomplete-dropdown').style.display = 'none';
            document.body.classList.remove('modal-open');
            document.removeEventListener('focus', trapCustomerModalFocus, true);
            document.removeEventListener('keydown', handleCustomerModalEscape);
            document.getElementById('customer-lookup').focus();
        }

        function trapCustomerModalFocus(e) {
            const modal = document.getElementById('new-customer-modal');
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (!modal.contains(e.target)) {
                e.preventDefault();
                firstElement.focus();
            }
        }

        function handleCustomerModalEscape(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        }

        // Handle new/edit customer form submission
        document.getElementById('new-customer-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const baseUrl = document.querySelector('meta[name="base-url"]').content;
            const customerId = document.getElementById('customer-id').value;
            const url = customerId ? `${baseUrl}/customer/update/${customerId}` : `${baseUrl}/customer/create`;

            const customerData = {
                name: document.getElementById('new-customer-name').value,
                phone: document.getElementById('new-customer-phone').value,
                email: document.getElementById('new-customer-email').value,
                address: document.getElementById('new-customer-address').value
            };

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(customerData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', customerId ? 'Customer updated successfully!' : 'Customer created successfully!');
                    closeModal();
                    document.getElementById('customer-lookup').value = customerData.phone;
                    lookupCustomer();
                } else {
                    showAlert('error', data.message || 'Failed to process customer.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Failed to process customer.');
            });
        });

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
                alert.innerHTML = `
                    ${message}
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                `;
                alertsDiv.appendChild(alert);
            } catch (e) {
                console.error('Error showing alert:', e);
            }
        }

        // Toggle training mode
        function toggleTrainingMode() {
            try {
                const pin = prompt('Enter manager PIN to toggle training mode:');
                if (pin === '1234') {
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
                        'Enter customer phone or email...': 'Enter customer phone or email...',
                        'Complete Sale': 'Complete Sale',
                        'Your cart is empty': 'Your cart is empty'
                    },
                    sw: {
                        'Point of Sale': 'Sehemu ya Mauzo',
                        'Scan barcode or search products...': 'Piga skana barcode au tafuta bidhaa...',
                        'Enter customer phone or email...': 'Ingiza simu/barua pepe ya mteja...',
                        'Complete Sale': 'Kamilisha Mauzo',
                        'Your cart is empty': 'Rukwama yako iko tupu'
                    }
                };
                document.title = translations[lang]['Point of Sale'] + ' - POS System';
                document.querySelector('#product-search').placeholder = translations[lang]['Scan barcode or search products...'];
                document.querySelector('#customer-lookup').placeholder = translations[lang]['Enter customer phone or email...'];
                document.querySelector('.btn-complete').textContent = translations[lang]['Complete Sale'];
                document.querySelector('.empty-cart p').textContent = translations[lang]['Your cart is empty'];
            } catch (e) {
                console.error('Error changing language:', e);
            }
        }

        // Update the modal total when discount changes
function updateModalTotal() {
    const subtotal = <?php echo $subtotal ?? 0; ?>;
    const tax = subtotal * 0.16;
    const discount = parseFloat(document.getElementById('modal-discount').value) || 0;
    const total = subtotal + tax - discount;
    const amountPaid = parseFloat(document.getElementById('modal-amount-paid').value) || 0;
    const change = Math.max(0, amountPaid - total);
    
    document.getElementById('modal-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('modal-tax').textContent = tax.toFixed(2);
    document.getElementById('modal-discount-amount').textContent = `-${discount.toFixed(2)}`;
    document.getElementById('modal-grand-total').textContent = total.toFixed(2);
    document.getElementById('modal-change').value = change.toFixed(2);
}
// Update the main payment form when modal inputs change
document.getElementById('modal-amount-paid').addEventListener('input', function() {
    document.getElementById('amount_paid').value = this.value;
    updateModalTotal();
});

document.getElementById('modal-discount').addEventListener('input', function() {
    document.getElementById('discount').value = this.value;
    updateModalTotal();
});


        // Checkout modal functions
        function openCheckoutModal() {
            const summaryHTML = generateCheckoutSummary();
            document.getElementById('checkout-summary').innerHTML = summaryHTML;
            const paymentForm = document.getElementById('payment-form');
            const paymentSection = document.querySelector('.payment-section');
            paymentSection.innerHTML = '';
            Array.from(paymentForm.elements).forEach(element => {
                if (element.type !== 'button' && element.type !== 'submit') {
                    const clone = element.cloneNode(true);
                    paymentSection.appendChild(clone);
                }
            });
            const modal = document.getElementById('checkout-modal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            document.body.classList.add('modal-open');
            document.addEventListener('focus', trapFocus, true);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeCheckoutModal();
            });
        }

        function closeCheckoutModal() {
            const modal = document.getElementById('checkout-modal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.classList.remove('modal-open');
            document.removeEventListener('focus', trapFocus, true);
        }

       function confirmCheckout(event) {
    event.preventDefault();
    
    // Get values from modal
    const amountPaid = parseFloat(document.getElementById('modal-amount-paid').value) || 0;
    const discount = parseFloat(document.getElementById('modal-discount').value) || 0;
    const total = parseFloat(document.getElementById('modal-grand-total').textContent.replace('KSh ', ''));

    // Validation
    if (!amountPaid) {
        showAlert('error', 'Please enter amount paid');
        return false;
    }
    
    if (amountPaid < total) {
        showAlert('error', 'Amount paid must cover the total');
        return false;
    }

    // Update main form values
    document.getElementById('amount_paid').value = amountPaid;
    document.getElementById('discount').value = discount;
    
    // Disable the confirm button to prevent multiple submissions
    const confirmButton = event.target;
    confirmButton.disabled = true;

    // Submit the form via AJAX
    const form = document.getElementById('payment-form');
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Generate receipt with the returned data
            generateReceipt(data.receipt_data);
            // Redirect after a delay to allow receipt printing
            setTimeout(() => {
                window.location.href = '/pos/public/sales/pos';
            }, 1500);
        } else {
            showAlert('error', data.message || 'Failed to process sale');
            confirmButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Checkout failed:', error);
        showAlert('error', 'Failed to process sale: Network error');
        confirmButton.disabled = false;
    });

    // Close the modal
    closeCheckoutModal();
}


        function validatePayment() {
            const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
            const total = parseFloat(document.getElementById('grand-total').textContent);
            if (amountPaid < total) {
                showAlert('error', 'Amount paid must be greater than or equal to total');
                return false;
            }
            return true;
        }

        function generateCheckoutSummary() {
            const cart = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;
            let html = '<div class="summary-items">';
            for (const [id, item] of Object.entries(cart)) {
                html += `
                    <div class="summary-item">
                        <span>${item.name} x ${item.quantity}</span>
                        <span>${(item.price * item.quantity).toFixed(2)} KES</span>
                    </div>`;
            }
            const subtotal = <?php echo $subtotal ?? 0; ?>;
            const tax = subtotal * 0.16;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const total = subtotal + tax - discount;
            html += `
                <div class="summary-totals">
                    <div><span>Subtotal:</span><span>${subtotal.toFixed(2)} KES</span></div>
                    <div><span>Tax (16%):</span><span>${tax.toFixed(2)} KES</span></div>
                    <div><span>Discount:</span><span>-${discount.toFixed(2)} KES</span></div>
                    <div class="grand-total"><span>Total:</span><span>${total.toFixed(2)} KES</span></div>
                </div>`;
            return html;
        }

        // Enhanced receipt generation
function generateReceipt(receiptData) {
    try {
        if (!receiptData || !receiptData.cart || Object.keys(receiptData.cart).length === 0) {
            throw new Error('No receipt data available. Cart is empty or sale was not processed.');
        }

        const subtotal = receiptData.subtotal;
        const tax = receiptData.tax;
        const discount = receiptData.discount;
        const total = receiptData.total;
        const amountPaid = receiptData.amount_paid;
        const change = Math.max(0, amountPaid - total);
        const paymentMethod = receiptData.payment_method;
        const cashierName = receiptData.cashier_name;
        const saleId = receiptData.sale_id;
        const timestamp = receiptData.timestamp;

        // Create a new window for printing
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            throw new Error('Popup blocked. Please allow popups for this site.');
        }
        
        // Build receipt HTML
        const receiptHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                    .receipt { width: 80mm; margin: 0 auto; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .bold { font-weight: bold; }
                    .mt-1 { margin-top: 0.25rem; }
                    .mt-2 { margin-top: 0.5rem; }
                    .mt-4 { margin-top: 1rem; }
                    .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
                    .border-bottom { border-bottom: 1px dashed #000; }
                    .border-top { border-top: 1px dashed #000; }
                    table { width: 100%; border-collapse: collapse; }
                    td { padding: 2px 0; }
                    td:last-child { text-align: right; }
                </style>
            </head>
            <body onload="window.print();window.close();">
                <div class="receipt">
                    <h2 class="text-center bold">Point of Sale</h2>
                    <p class="text-center mt-1">${timestamp}</p>
                    
                    <div class="border-bottom py-1 mt-2">
                        <p>Sale ID: ${saleId}</p>
                        <p>Cashier: ${cashierName}</p>
                        <p>Payment: ${paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1)}</p>
                    </div>
                    
                    <table class="mt-2">
                        ${Object.entries(receiptData.cart).map(([id, item]) => `
                            <tr>
                                <td>${item.name} x ${item.quantity}</td>
                                <td>${(item.price * item.quantity).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </table>
                    
                    <div class="border-top mt-2">
                        <table>
                            <tr><td>Subtotal:</td><td>${subtotal.toFixed(2)}</td></tr>
                            <tr><td>Tax (16%):</td><td>${tax.toFixed(2)}</td></tr>
                            <tr><td>Discount:</td><td>-${discount.toFixed(2)}</td></tr>
                            <tr class="bold"><td>Total:</td><td>${total.toFixed(2)}</td></tr>
                            <tr><td>Amount Paid:</td><td>${amountPaid.toFixed(2)}</td></tr>
                            <tr><td>Change:</td><td>${change.toFixed(2)}</td></tr>
                        </table>
                    </div>
                    
                    <p class="text-center mt-4">Thank you for shopping with us!</p>
                </div>
            </body>
            </html>
        `;
        
        printWindow.document.open();
        printWindow.document.write(receiptHTML);
        printWindow.document.close();
        
        showAlert('success', 'Receipt generated successfully.');
    } catch (error) {
        console.error('Error generating receipt:', error);
        showAlert('error', `Failed to generate receipt: ${error.message}`);
    }
}
// Move trapFocus outside of generateReceipt
function trapFocus(e) {
    if (!document.getElementById('checkout-modal').contains(e.target)) {
        e.stopPropagation();
        document.getElementById('checkout-modal').focus();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updateTotal();
    filterProducts('all');
    document.getElementById('product-search').focus();
    document.getElementById('product-search').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.target.closest('form').submit();
        }
    });

    if (typeof initialReceiptData !== 'undefined' && initialReceiptData && Object.keys(initialReceiptData.cart).length > 0) {
        generateReceipt();
    }
});
    </script>
</body>
</html>