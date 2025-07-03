<?php
if (!defined('IN_CONTROLLER')) {
    header('Location: /pos/public/login');
    exit;
}

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
    <meta name="base-url" content="/pos/public">
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
            <span class="cashier-info"><?php echo htmlspecialchars($cashier['name']) . ' (ID: ' . htmlspecialchars($cashier['id']) . ')'; ?></span>
            <div class="header-right">
                <!-- <select class="language-select" onchange="changeLanguage(this.value)">
                    <option value="en">English</option>
                    <option value="sw">Kiswahili</option>
                </select> -->
                
                <div class="header-actions">
                    <!-- <button class="btn btn-theme-toggle" onclick="toggleTheme()" title="Toggle Theme" aria-label="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button> -->
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
            <!-- Cart Panel -->
            <div class="cart-panel">
                <h3>Cart</h3>
                <!-- Search Bar and Customer Lookup -->
                <div class="search-customer-container">
                    <div class="search-bar">
                        <form method="POST" action="/pos/public/sales/pos">
                            <div class="input-group">
                                <input type="text" name="barcode" id="product-search" class="form-control" placeholder="Scan barcode or search products..." autocomplete="off">
                                <button type="submit" name="add_to_cart" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                            </div>
                        </form>
                    </div>
                    <div class="customer-section">
                        <div class="customer-search">
                            <input type="text" id="customer-lookup" class="form-control" placeholder="Enter customer phone or email..." autocomplete="off">
                            <button class="btn btn-customer-lookup" onclick="lookupCustomer()">Lookup</button>
                            <button class="btn btn-new-customer" onclick="showNewCustomerModal()">New Customer</button>
                        </div>
                        <div class="autocomplete-dropdown" id="autocomplete-dropdown" style="display: none;"></div>
                    </div>
                </div>
                <!-- Filters -->
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

                <!-- Customer Details -->
                <div id="customer-details" class="customer-details" style="display: none;">
                    <div class="customer-info-header">
                        <h4>Customer Information</h4>
                        <button class="btn btn-edit-customer" onclick="editCustomer()">Edit</button>
                    </div>
                    <div class="customer-info-body">
                        <p><strong>Name:</strong> <span id="customer-name"></span></p>
                        <p><strong>Phone:</strong> <span id="customer-phone"></span></p>
                        <p><strong>Email:</strong> <span id="customer-email"></span></p>
                        <p><strong>Address:</strong> <span id="customer-address"></span></p>
                        <p><strong>Loyalty Points:</strong> <span id="customer-points"></span></p>
                        <p><strong>Last Purchase:</strong> <span id="customer-last-purchase"></span></p>
                    </div>
                    <div class="purchase-history">
                        <h5>Purchase History</h5>
                        <div class="history-items" id="purchase-history-items"></div>
                    </div>
                </div>

                <!-- Product List -->
                <div class="product-list" id="product-list">
                    <div class="loading">Loading products...</div>
                </div>

                <!-- New Customer Modal -->
                <div id="new-customer-modal" class="modal" style="display: none;" role="dialog" aria-labelledby="modal-title" aria-modal="true">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal()" aria-label="Close modal">×</span>
                        <h3 id="modal-title">New Customer</h3>
                        <form id="new-customer-form">
                            <input type="hidden" id="modal-customer-id" name="customer_id">
                            <div class="form-group">
                                <label for="new-customer-name">Name</label>
                                <input type="text" id="new-customer-name" class="form-control" required aria-required="true">
                            </div>
                            <div class="form-group">
                                <label for="new-customer-phone">Phone</label>
                                <input type="text" id="new-customer-phone" class="form-control" required aria-required="true">
                            </div>
                            <div class="form-group">
                                <label for="new-customer-email">Email</label>
                                <input type="email" id="new-customer-email" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="new-customer-address">Address</label>
                                <textarea id="new-customer-address" class="form-control"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Customer</button>
                        </form>
                    </div>
                </div>

                <!-- Cart Items -->
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Your cart is empty</p>
                    </div>
                <?php else: ?>
                    <div class="cart-items">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 33%;">Item</th>
                                    <th style="width: 20%;">Qty</th>
                                    <th style="width: 17%;">Price</th>
                                    <th style="width: 20%;">Total</th>
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
                                                <div class="quantity-control">
                                                    <button type="submit" name="decrease_qty" class="btn-quantity" title="Decrease Quantity" <?php echo $item['quantity'] <= 0 ? 'disabled' : ''; ?>><i class="fas fa-minus"></i></button>
                                                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0" max="<?php echo htmlspecialchars($item['stock']); ?>" class="quantity-input" readonly>
                                                    <button type="submit" name="increase_qty" class="btn-quantity" title="Increase Quantity" <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>><i class="fas fa-plus"></i></button>
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
                                                    <button type="submit" name="remove_from_cart" class="btn btn-remove" title="Remove Item"><i class="fas fa-trash-alt"></i></button>
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
                    
                    <input type="hidden" id="receipt_method" name="receipt_method" value="print">
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
                            <input type="hidden" id="payment_method" name="payment_method" value="cash">
                        </div>
                    </div>
                    <button type="button" class="btn btn-complete" onclick="openCheckoutModal()">Complete Sale</button>
                    <div class="action-toolbar">
                        <button class="btn btn-action" onclick="holdOrder()" title="Hold Order">Hold</button>
                        <button class="btn btn-action" onclick="recallOrder()" title="Recall Sale">Recall</button>
                        <button class="btn btn-action" onclick="returnItem()" title="Return Item">Return</button>
                        <button class="btn btn-action" onclick="cancelSale()" title="Cancel Sale">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Checkout Modal -->
        <div id="checkout-modal" class="modal-overlay">
            <div class="checkout-modal">
                <div class="modal-header">
                    <h3>Confirm Purchase</h3>
                    <button class="close-modal" onclick="closeCheckoutModal()">×</button>
                </div>
                <div class="modal-body">
                    <div id="checkout-summary"></div>
                    <div class="payment-section"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-cancel" onclick="closeCheckoutModal()">Cancel</button>
                    <button class="btn btn-confirm" onclick="confirmCheckout()">Confirm Payment</button>
                </div>
            </div>
        </div>

        <!-- Hidden Receipt Template -->
        <div id="receipt-template" style="display: none;">
            <div class="receipt">
                <h3>Receipt of Sale</h3>
                <p><strong>Sale ID:</strong> <span id="receipt-sale-id"></span></p>
                <p><strong>Date:</strong> <span id="receipt-date"></span></p>
                <div class="receipt-items"></div>
                <div class="receipt-totals">
                    <p><strong>Subtotal:</strong> <span id="receipt-subtotal"></span> KES</p>
                    <p><strong>Tax (16%):</strong> <span id="receipt-tax"></span> KES</p>
                    <p><strong>Discount:</strong> <span id="receipt-discount"></span> KES</p>
                    <p><strong>Total:</strong> <span id="receipt-total"></span> KES</p>
                    <p><strong>Payment:</strong> <span id="receipt-payment"></span></p>
                </div>
                <p class="receipt-footer">Thank you!</p>
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
                
                // Update client-side cart (Step 2)
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

        // Autocomplete for customer lookup---with client-side validation(for email and phone formarts).
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
                    id: data.customer_id || customerId, // Use customer_id from response for create
                    name: customerData.name,
                    phone: customerData.phone,
                    email: customerData.email || 'N/A',
                    address: customerData.address || 'N/A',
                    loyalty_points: 0 // Default to 0
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
            firstInput.focus(); // Set focus to the first input
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
            document.getElementById('customer-lookup').focus(); // Return focus to customer lookup
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

        function confirmCheckout() {
            if (validatePayment()) {
                const originalForm = document.getElementById('payment-form');
                const modalInputs = document.querySelectorAll('.payment-section input');
                modalInputs.forEach(input => {
                    const originalInput = originalForm.querySelector(`[name="${input.name}"]`);
                    if (originalInput) {
                        originalInput.value = input.value;
                    }
                });
                // Add user_id to the form
                const userId = '<?php echo htmlspecialchars($cashier['id']); ?>';
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                originalForm.appendChild(userIdInput);
                generateReceipt();
                originalForm.submit();
            }
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

        function generateReceipt() {
            const cart = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;
            const subtotal = <?php echo $subtotal ?? 0; ?>;
            const tax = subtotal * 0.16;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const total = subtotal + tax - discount;
            const paymentMethod = document.getElementById('payment_method').value;
            const saleId = 'SALE-' + Math.floor(Math.random() * 1000); // Placeholder, replace with actual sale ID
            const date = new Date().toLocaleString('en-US', {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            const receiptTemplate = document.getElementById('receipt-template').cloneNode(true);
            receiptTemplate.style.display = 'block';
            receiptTemplate.querySelector('#receipt-sale-id').textContent = saleId;
            receiptTemplate.querySelector('#receipt-date').textContent = date;
            receiptTemplate.querySelector('#receipt-subtotal').textContent = subtotal.toFixed(2);
            receiptTemplate.querySelector('#receipt-tax').textContent = tax.toFixed(2);
            receiptTemplate.querySelector('#receipt-discount').textContent = discount.toFixed(2);
            receiptTemplate.querySelector('#receipt-total').textContent = total.toFixed(2);
            receiptTemplate.querySelector('#receipt-payment').textContent = paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1);

            const itemsContainer = receiptTemplate.querySelector('.receipt-items');
            itemsContainer.innerHTML = '';
            for (const [id, item] of Object.entries(cart)) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'receipt-item';
                itemDiv.innerHTML = `
                    <span>${item.quantity}x ${item.name}</span>
                    <span>${(item.price * item.quantity).toFixed(2)} KES</span>
                `;
                itemsContainer.appendChild(itemDiv);
            }

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Receipt</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .receipt { max-width: 300px; margin: 0 auto; font-size: 14px; }
                            .receipt h3 { text-align: center; margin-bottom: 20px; }
                            .receipt p { margin: 5px 0; display: flex; justify-content: space-between; }
                            .receipt-items { margin: 15px 0; }
                            .receipt-item { display: flex; justify-content: space-between; margin: 5px 0; }
                            .receipt-totals { border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; }
                            .receipt-footer { text-align: center; margin-top: 20px; font-style: italic; }
                            @media print {
                                body { margin: 0; }
                                .receipt { max-width: 100%; }
                            }
                        </style>
                    </head>
                    <body onload="window.print(); window.close();">
                        ${receiptTemplate.outerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();
        }

        function trapFocus(e) {
            if (!document.getElementById('checkout-modal').contains(e.target)) {
                e.stopPropagation();
                document.getElementById('checkout-modal').focus();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateTotal();
            filterProducts('all'); // Load all products by default
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
            overflow-x: hidden;
        }
        .pos-container {
            max-width: 1320px;
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
            border-radius: 5px;
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
            color: white;
            border: none;
            padding: 6px;
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
        .btn-logout:hover { background-color:rgb(38, 96, 220); }
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
            position: relative;
            padding-right: 30px;
        }
        .alert-close {
            position: absolute;
            right: 10px;
            top: 10px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }
        .alert-success { background-color: #d1fae5; color: #065f46; }
        .alert-error { background-color: #fee2e2; color: #991b1b; }
        .alert-info { background-color: #dbeafe; color: #1e40af; }
        .pos-main {
            display: flex;
            gap: 7px;
        }
        .cart-panel, .payment-panel {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .cart-panel { flex: 0 0 65%; }
        .payment-panel { flex: 0 0 30%; }
        .search-customer-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 8px;
        }
        .search-bar {
            flex: 1;
        }
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
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .btn-primary i {
            font-size: 0.9rem;
        }
        .customer-section {
            flex: 1;
            position: relative;
        }
        .customer-search {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .customer-section input {
            border-radius: 6px;
            padding: 8px;
            font-size: 0.9rem;
            flex: 1;
        }
        .btn-customer-lookup, .btn-new-customer {
            background-color: #3b82f6;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .btn-customer-lookup:hover, .btn-new-customer:hover {
            background-color: #1e40af;
        }
        .autocomplete-dropdown {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            width: 100%;
            top: 100%;
            left: 0;
        }
        .autocomplete-dropdown div {
            padding: 8px;
            cursor: pointer;
        }
        .autocomplete-dropdown div:hover {
            background: #f5f5f5;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .btn-filter, .category-select {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .btn-filter:hover, .category-select:hover {
            background-color: #e5e7eb;
        }
        .btn-filter.active {
            background-color: #1e3a8a;
            color: #fff;
            border-color: #1e3a8a;
        }
        .category-select {
            padding: 7px;
        }
        .customer-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        .customer-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .customer-info-body p {
            margin: 5px 0;
        }
        .purchase-history {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        .history-item {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .history-item.empty {
            text-align: center;
            color: #6b7280;
            padding: 15px;
            font-style: italic;
        }
        .history-item:last-child {
            border-bottom: none;
        }
        .product-list {
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        .btn-add-product {
            background-color: #22c55e;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: background-color 0.3s;
        }
        .btn-add-product:hover {
            background-color: #16a34a;
        }
        .no-products {
            text-align: center;
            color: #6b7280;
            padding: 15px;
            font-style: italic;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }
                /* Ensures modal focus is visible */
        .modal-content:focus {
            outline: 3px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Dark mode styles for modal inputs */
        .dark-theme .modal-content .form-control {
            background-color: #1f2937;
            border-color: #6b7280;
            color: #d1d5db;
        }

        .dark-theme .modal-content .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 5px rgba(59,130,246,0.3);
        }

        /* Improved close button visibility */
        .close:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
        }
        .cart-panel h3, .payment-panel h3 {
            margin: 0 0 10px;
            font-size: 1.2rem;
            color: #374151;
        }
        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: #6b7280;
            text-align: center;
            padding: 30px;
            font-size: 1.2rem;
            animation: fadeIn 1s ease-in-out;
        }
        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #9ca3af;
            animation: cartPulse 2s infinite;
        }
        @keyframes cartPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .cart-items {
            flex: 1;
            max-height: 400px;
            overflow-y: auto;
        }
        .cart-items tbody tr { border-bottom: 1px solid #e5e7eb; }
        .cart-items tbody tr:last-child { border-bottom: none; }
        .quantity-form {
            display: flex;
            align-items: center;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
        }
        .btn-quantity {
            background-color: #f3f4f6;
            color: #374151;
            border: none;
            padding: 6px 10px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-quantity:hover {
            background-color: #e5e7eb;
        }
        .btn-quantity:disabled {
            background-color: #e5e7eb;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .quantity-input {
            width: 50px;
            padding: 6px 0;
            text-align: center;
            border: none;
            border-left: 1px solid #d1d5db;
            border-right: 1px solid #d1d5db;
            font-size: 0.9rem;
            background-color: #fff;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-remove {
            color: #ef4444; 
            background-color: transparent; 
            border: none;
            padding: 2px;
            border-radius: 6px;
            font-size: 0.8rem;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-discount {
            color: #000000; 
            background-color: transparent;
            border: none;
            padding: 2px;
            border-radius: 6px;
            font-size: 0.8rem;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
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
        .payment-methods {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .btn-payment {
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
        .btn-payment:hover, .btn-payment.active {
            background-color: #1e40af;
        }
        .action-toolbar {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
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
        .btn-action:hover {
            background-color: #4b5563;
        }
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
        .btn-complete:hover {
            background-color: #16a34a;
        }
        .training-mode .btn-complete, .training-mode .btn-action, .training-mode .btn-primary {
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
        .dark-theme .cart-panel, .dark-theme .payment-panel {
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
        .dark-theme .btn-filter, .dark-theme .category-select {
            background-color: #1f2937;
            border-color: #6b7280;
            color: #d1d5db;
        }
        .dark-theme .btn-filter:hover, .dark-theme .category-select:hover {
            background-color: #4b5563;
        }
        .dark-theme .btn-filter.active {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .dark-theme .quantity-control {
            border-color: #6b7280;
        }
        .dark-theme .btn-quantity {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .btn-quantity:hover:not(:disabled) {
            background-color: #4b5563;
        }
        .dark-theme .quantity-input {
            background-color: #1f2937;
            border-color: #6b7280;
            color: #d1d5db;
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
        .dark-theme .customer-details {
            background-color: #374151;
            border-color: #4b5563;
        }
        .dark-theme .purchase-history {
            border-color: #4b5563;
        }
        .dark-theme .history-item {
            border-color: #4b5563;
        }
        .dark-theme .modal-content {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .product-list {
            border-color: #4b5563;
        }
        .dark-theme .product-item {
            border-color: #4b5563;
        }
        .dark-theme .no-products {
            color: #9ca3af;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .modal-open {
            overflow: hidden;
        }
        .checkout-modal {
            width: 90%;
            max-width: 600px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
        }
        .modal-body .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .modal-body .form-group {
            margin-bottom: 15px;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .summary-totals {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .summary-totals div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .grand-total {
            font-weight: bold;
            font-size: 1.1em;
            margin-top: 10px;
        }
        .dark-theme .checkout-modal {
            background: #374151;
            color: #fff;
        }
        .dark-theme .modal-header, .dark-theme .modal-footer {
            border-color: #4b5563;
        }
        .dark-theme .summary-item {
            border-color: #4b5563;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #6b7280;
        }
        @media (max-width: 992px) {
            .pos-main {
                flex-direction: column;
            }
            .cart-panel, .payment-panel {
                flex: 1;
            }
            .search-customer-container {
                flex-direction: column;
                align-items: stretch;
            }
            .customer-section {
                flex: none;
            }
            .autocomplete-dropdown {
                width: 100%;
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