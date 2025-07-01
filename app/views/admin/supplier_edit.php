<?php
$title = "Edit Supplier";
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
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
            <header class="dashboard-header">
                <div class="header-left">
                    <h1 class="dashboard-title">Edit Supplier</h1>
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

            <section class="supplier-section">
                <div class="section-header">
                    <h3 class="section-title">Edit Supplier</h3>
                </div>
                <form method="POST" action="/pos/public/suppliers/edit/<?php echo $supplier['id']; ?>">
                    <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_info">Contact Info</label>
                        <input type="text" name="contact_info" id="contact_info" value="<?php echo htmlspecialchars($supplier['contact_info']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description"><?php echo htmlspecialchars($supplier['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($supplier['category']); ?>">
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-exit" onclick="exitForm()">Exit</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
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

        function exitForm() {
            window.location.href = '/pos/public/suppliers';
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
        }
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .supplier-section {
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
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
        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
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
        .dark-theme .form-group input, .dark-theme .form-group textarea {
            background-color: #4b5563;
            color: #d1d5db;
            border-color: #6b7280;
        }
        .dark-theme .btn-primary {
            background-color: #3b82f6;
        }
        .dark-theme .btn-primary:hover {
            background-color: #1e40af;
        }
        .dark-theme .btn-exit {
            background-color: #6b7280;
        }
        .dark-theme .btn-exit:hover {
            background-color: #4b5563;
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
            .form-group input, .form-group textarea {
                font-size: 0.85rem;
            }
            .btn-primary, .btn-exit {
                width: 100%;
            }
            .form-footer {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</body>
</html>