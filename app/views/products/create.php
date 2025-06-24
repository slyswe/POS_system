<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3) . '/');
}

require_once BASE_PATH . 'app/controllers/ProductController.php';

session_start();

// Check if cashier data is in session, otherwise redirect or use fallback
if (isset($_SESSION['cashier']) && is_array($_SESSION['cashier']) && !empty($_SESSION['cashier']['name']) && !empty($_SESSION['cashier']['id'])) {
    $cashier = $_SESSION['cashier'];
} else {
    // Fallback or redirect to login (uncomment redirect for production)
    $cashier = ['name' => 'sample user', 'id' => '54321'];
    // header('Location: /pos/public/login');
    // exit;
}

// ProductController->create() sets $categories
$controller = new \App\Controllers\ProductController();
$categories = isset($categories) ? $categories : []; // Fallback if not set by controller
$controller->create();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


</head>
<body>
    <div class="products-container">
        <header class="products-header">
            <div class="header-left">
                <h2 class="page-title">Add Product</h2>
            </div>
            <div class="header-center">
                <span id="current-time" class="time-display"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
            <div class="header-right">
                <span class="cashier-info"><?php echo htmlspecialchars($cashier['name']) . ' (ID: ' . htmlspecialchars($cashier['id']) . ')'; ?></span>
                <div class="header-actions">
                    <button class="btn btn-theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="/pos/public/switch-user" class="btn btn-switch-user" title="Switch User">
                        <i class="fas fa-user"></i>
                    </a>
                    <a href="/pos/public/logout" class="btn btn-logout" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="products-main">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <div class="action-bar">
                <h3>Add New Product</h3>
            </div>
            <form id="add-product-form" method="POST" action="/pos/public/products/create">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <div class="category-input-group">
                        <select class="form-control" id="category_id" name="category_id">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" class="form-control" id="new_category" name="new_category" placeholder="Or type new category" style="display: none;">
                        <button type="button" class="btn btn-toggle-category" onclick="toggleCategoryInput()" title="Toggle Category Input">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price (KSH)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <div class="mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" class="form-control" id="stock" name="stock" required>
                </div>
                <div class="mb-3">
                    <label for="barcode" class="form-label">Barcode</label>
                    <input type="text" class="form-control" id="barcode" name="barcode">
                </div>
                <div class="action-row">
                    <button type="submit" class="btn btn-add"><i class="fas fa-plus"></i> Add Product</button>
                    <a href="/pos/public/inventory" class="btn btn-remove"><i class="fas fa-home"></i> Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        setInterval(updateTime, 1000);

        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const icon = document.querySelector('.btn-theme-toggle i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        }

        function toggleCategoryInput() {
            const categorySelect = document.getElementById('category_id');
            const newCategoryInput = document.getElementById('new_category');
            const buttonIcon = document.querySelector('.btn-toggle-category i');
            if (categorySelect.style.display === 'none') {
                categorySelect.style.display = 'block';
                newCategoryInput.style.display = 'none';
                categorySelect.value = '';
                newCategoryInput.value = '';
                buttonIcon.classList.remove('fa-list');
                buttonIcon.classList.add('fa-exchange-alt');
            } else {
                categorySelect.style.display = 'none';
                newCategoryInput.style.display = 'block';
                categorySelect.value = '';
                newCategoryInput.value = '';
                buttonIcon.classList.remove('fa-exchange-alt');
                buttonIcon.classList.add('fa-list');
            }
        }

        document.getElementById('add-product-form').addEventListener('submit', function(event) {
            const categoryId = document.getElementById('category_id').value;
            const newCategory = document.getElementById('new_category').value;
            if (!categoryId && !newCategory) {
                event.preventDefault();
                alert('Please select a category or enter a new one.');
            }
        });
    </script>
        <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f5f9;
            color: #2d3748;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .products-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px;
        }
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, #2b6cb0, #4299e1);
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .page-title {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        .time-display {
            font-size: 1rem;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 6px;
        }
        .cashier-info {
            font-size: 0.95rem;
            margin-right: 16px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn-theme-toggle, .btn-switch-user, .btn-logout {
            background-color: #4a5568;
            color: #fff;
            border: none;
            padding: 8px;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .btn-switch-user { background-color: #718096; }
        .btn-logout { background-color: #f56565; }
        .btn-theme-toggle:hover, .btn-switch-user:hover { background-color: #2d3748; }
        .btn-logout:hover { background-color: #e53e3e; }
        .products-main {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .alert-error {
            background-color: #fefcbf;
            color: #744210;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .alert-success {
            background-color: #e6fffa;
            color: #2f855a;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .action-bar h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            font-size: 0.95rem;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .form-control:focus {
            border-color: #4299e1;
            outline: none;
        }
        .form-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 6px;
            display: block;
        }
        .category-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn-toggle-category {
            background-color: #718096;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-toggle-category:hover {
            background-color: #2d3748;
        }
        .mb-3 {
            margin-bottom: 16px;
        }
        .action-row {
            display: flex;
            gap: 12px;
        }
        .btn-add {
            background-color: #2b6cb0;
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .btn-add:hover {
            background-color: #3182ce;
        }
        .btn-remove {
            background-color: #f56565;
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .btn-remove:hover {
            background-color: #e53e3e;
        }
        .dark-theme {
            background-color: #2d3748;
            color: #edf2f7;
        }
        .dark-theme .products-header {
            background: linear-gradient(90deg, #4a5568, #718096);
        }
        .dark-theme .products-main {
            background-color: #3c4a63;
        }
        .dark-theme .form-control {
            background-color: #4a5568;
            color: #edf2f7;
            border-color: #718096;
        }
        .dark-theme .form-label {
            color: #edf2f7;
        }
        .dark-theme .btn-add {
            background-color: #3182ce;
        }
        .dark-theme .btn-add:hover {
            background-color: #2b6cb0;
        }
        .dark-theme .btn-remove {
            background-color: #e53e3e;
        }
        .dark-theme .btn-remove:hover {
            background-color: #c53030;
        }
        .dark-theme .btn-toggle-category {
            background-color: #718096;
        }
        .dark-theme .btn-toggle-category:hover {
            background-color: #2d3748;
        }
        .dark-theme .alert-error {
            background-color: #feb2b2;
            color: #742a2a;
        }
        .dark-theme .alert-success {
            background-color: #b4f3e5;
            color: #285e61;
        }
    </style>
</body>
</html>