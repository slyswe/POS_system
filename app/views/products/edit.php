<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3) . '/');
}

// Check if user is authorized
if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /pos/public/login');
    exit;
}

$title = "Edit Product";
$product = $product ?? [];
$categories = $categories ?? [];
$error = $error ?? '';
$admin = $_SESSION['user'] ?? ['name' => 'admin', 'id' => '01'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="products-container">
        <header class="products-header">
            <div class="header-left">
                <h2 class="page-title">Edit Product</h2>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <a href="/pos/public/dashboard" class="breadcrumb-link">Dashboard</a> 
                    <span class="breadcrumb-separator" aria-hidden="true">></span>
                    <a href="/pos/public/products" class="breadcrumb-link">Inventory</a>
                    <span class="breadcrumb-separator" aria-hidden="true">></span>
                    <span class="breadcrumb-current" aria-current="page">Edit Product</span>
                </nav>
            </div>
            <div class="header-center">
                <span id="current-time" class="time-display" aria-live="polite"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
            <div class="header-right">
                <span class="admin-info" aria-label="Admin information">
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($admin['name']) . ' (ID: ' . htmlspecialchars($admin['id']) . ')'; ?>
                </span>
                <div class="header-actions">
                    <button class="btn btn-theme-toggle" onclick="toggleTheme()" title="Toggle Theme" aria-label="Toggle theme">
                        <i class="fas fa-moon" aria-hidden="true"></i>
                    </button>
                    <a href="/pos/public/logout" class="btn btn-logout" title="Logout" aria-label="Logout">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="products-main">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <h3>Edit Product</h3>
                <form id="edit-product-form" method="POST" action="/pos/public/products/edit/<?php echo htmlspecialchars($product['id'] ?? ''); ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name" class="form-label">
                                <i class="fas fa-tag" aria-hidden="true"></i> Product Name
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" 
                                   required aria-required="true">
                        </div>
                        <div class="form-group">
                            <label for="category_id" class="form-label">
                                <i class="fas fa-list" aria-hidden="true"></i> Category
                            </label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['id']); ?>" 
                                            <?php echo $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cost_price">Buying Price</label>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" 
                                value="<?= htmlspecialchars($product['cost_price'] ?? '0.00') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Selling Price</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                value="<?= htmlspecialchars($product['price'] ?? '0.00') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="stock" class="form-label">
                                <i class="fas fa-boxes" aria-hidden="true"></i> Stock
                            </label>
                            <input type="number" class="form-control" id="stock" name="stock" 
                                   value="<?php echo htmlspecialchars($product['stock'] ?? '0'); ?>" 
                                   required aria-required="true">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="barcode" class="form-label">
                                <i class="fas fa-barcode" aria-hidden="true"></i> Barcode
                            </label>
                            <input type="text" class="form-control" id="barcode" name="barcode" 
                                   value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="action-row">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save" aria-hidden="true"></i> Update Product
                        </button>
                        <a href="/pos/public/products" class="btn btn-secondary">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Inventory
                        </a>
                    </div>
                </form>
            </div>
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
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        }

        // Initialize theme
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
            document.querySelector('.btn-theme-toggle i').classList.replace('fa-moon', 'fa-sun');
        }

        // Form validation
        document.getElementById('edit-product-form').addEventListener('submit', function(event) {
            const name = document.getElementById('name').value;
            const price = parseFloat(document.getElementById('price').value);
            const stock = parseInt(document.getElementById('stock').value);
            if (!name.trim()) {
                event.preventDefault();
                alert('Product name is required.');
            } else if (price <= 0) {
                event.preventDefault();
                alert('Price must be greater than 0.');
            } else if (stock < 0) {
                event.preventDefault();
                alert('Stock cannot be negative.');
            }
        });
    </script>

    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
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
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .header-left {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .page-title {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
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
        .breadcrumb-separator {
            margin: 0 5px;
            color: #dbeafe;
        }
        .breadcrumb-current {
            color: #fff;
        }
        .header-center {
            flex: 1;
            text-align: center;
        }
        .time-display {
            font-size: 1rem;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 6px;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .admin-info {
            font-size: 0.95rem;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn-theme-toggle, .btn-logout {
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
            transition: background-color 0.3s ease;
        }
        .btn-logout {
            background-color: #f56565;
        }
        .btn-theme-toggle:hover, .btn-theme-toggle:focus {
            background-color: #2d3748;
        }
        .btn-logout:hover, .btn-logout:focus {
            background-color: #e53e3e;
        }
        .products-main {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid transparent;
            border-image: linear-gradient(to right, #1e3a8a, #3b82f6) 1;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .form-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        .form-container h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin: 0 0 20px;
            text-align: center;
            position: relative;
        }
        .form-container h3::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            margin: 8px auto 0;
            border-radius: 2px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            position: relative;
        }
        .form-group-full {
            grid-column: 1 / -1;
        }
        .form-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .form-control {
            width: 80%;
            font-size: 0.95rem;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            background: #fafafa;
            transition: border-color 0.3s ease, transform 0.2s ease;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.2);
            outline: none;
            transform: scale(1.02);
        }
        .form-control:hover {
            border-color: #93c5fd;
        }
        .action-row {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 20px;
        }
        .btn-primary {
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
            outline: none;
        }
        .btn-secondary {
            background: linear-gradient(90deg, #4b5563, #6b7280);
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-secondary:hover, .btn-secondary:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(107, 114, 128, 0.3);
            outline: none;
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
        .dark-theme .form-container {
            background: #3c4a63;
            border-image: linear-gradient(to right, #4a5568, #718096) 1;
        }
        .dark-theme .form-container h3 {
            color: #edf2f7;
        }
        .dark-theme .form-container h3::after {
            background: linear-gradient(90deg, #4a5568, #718096);
        }
        .dark-theme .form-control {
            background-color: #4a5568;
            color: #edf2f7;
            border-color: #718096;
        }
        .dark-theme .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
        }
        .dark-theme .form-control:hover {
            border-color: #93c5fd;
        }
        .dark-theme .form-label {
            color: #edf2f7;
        }
        .dark-theme .alert-error {
            background-color: #feb2b2;
            color: #742a2a;
        }
        .dark-theme .alert-success {
            background-color: #b4f3e5;
            color: #285e61;
        }
        .dark-theme .btn-primary {
            background: linear-gradient(90deg, #3182ce, #60a5fa);
        }
        .dark-theme .btn-primary:hover, .dark-theme .btn-primary:focus {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
        }
        .dark-theme .btn-secondary {
            background: linear-gradient(90deg, #6b7280, #9ca3af);
        }
        .dark-theme .btn-secondary:hover, .dark-theme .btn-secondary:focus {
            background: linear-gradient(90deg, #4b5563, #6b7280);
        }
        @media (max-width: 768px) {
            .products-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .header-center {
                text-align: left;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group-full {
                grid-column: auto;
            }
            .action-row {
                flex-direction: column;
            }
        }
        @media (max-width: 576px) {
            .products-container {
                padding: 10px;
            }
            .form-container {
                padding: 15px;
            }
            .form-container h3 {
                font-size: 1.3rem;
            }
            .form-control {
                font-size: 0.9rem;
                padding: 8px;
            }
            .btn-primary, .btn-secondary {
                font-size: 0.9rem;
                padding: 10px 16px;
            }
        }
    </style>
</body>
</html>