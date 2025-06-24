<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3) . '/');
}

require_once BASE_PATH . 'app/controllers/UserController.php';

session_start();
session_start();
// The $id and $user are now handled by UserController->edit()
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// $user is already set by the controller's edit method
$user = isset($user) ? $user : ['id' => 0, 'name' => '', 'email' => '', 'role' => 'cashier', 'phone' => '', 'address' => '', 'status' => 'active'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>
<body>
    <div class="edit-user-container">
        <!-- Header -->
        <header class="page-header">
            <div class="header-left">
                <h1 class="page-title">Edit User</h1>
                <nav class="breadcrumbs">
                    <a href="/pos/public/dashboard" class="breadcrumb-link">Dashboard</a> >
                    <a href="/pos/public/users" class="breadcrumb-link">Users</a> >
                    <span>Edit User</span>
                </nav>
            </div>
            <div class="header-right">
                <select class="language-select" onchange="changeLanguage(this.value)">
                    <option value="en">English</option>
                    <option value="es">Español</option>
                    <option value="fr">Français</option>
                </select>
                <span class="connectivity-status" id="connectivity-status">Online</span>
                <button class="btn btn-theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
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
        <main class="main-content">
            <div class="form-panel">
                <h2 class="form-title">Update User Details</h2>
                <form method="POST" action="/pos/public/users/edit/<?php echo htmlspecialchars($user['id']); ?>" id="edit-user-form" onsubmit="return confirmSubmit()">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <div class="form-group">
                        <label for="name" class="form-label">Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required aria-describedby="name-help">
                        <small id="name-help" class="form-help">Enter the user’s full name.</small>
                    </div>
                    <div class="form-group">
                        <label for="role" class="form-label">Role <span class="required">*</span></label>
                        <select class="form-control" id="role" name="role" required aria-describedby="role-help" onchange="checkRoleChange()">
                            <option value="cashier" <?php echo $user['role'] === 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="inventory_clerk" <?php echo $user['role'] === 'inventory_clerk' ? 'selected' : ''; ?>>Inventory Clerk</option>
                        </select>
                        <small id="role-help" class="form-help">Select the user’s role. Admins have full access.</small>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required aria-describedby="email-help" oninput="validateEmail()">
                        <small id="email-help" class="form-help">Enter a valid email address.</small>
                        <small id="email-error" class="form-error" style="display: none;">Invalid email format.</small>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="password" name="password" aria-describedby="password-help" oninput="validatePassword()">
                        <small id="password-help" class="form-help">Minimum 8 characters, including letters and numbers.</small>
                        <small id="password-error" class="form-error" style="display: none;">Password must be at least 8 characters with letters and numbers.</small>
                    </div>
                    <div class="form-group">
                        <label for="password-confirm" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="password-confirm" name="password-confirm" aria-describedby="password-confirm-help" oninput="validatePasswordConfirm()">
                        <small id="password-confirm-help" class="form-help">Re-enter the password to confirm.</small>
                        <small id="password-confirm-error" class="form-error" style="display: none;">Passwords do not match.</small>
                    </div>
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" aria-describedby="phone-help">
                        <small id="phone-help" class="form-help">Enter the user’s phone number (e.g., +254123456789).</small>
                    </div>
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" aria-describedby="address-help"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        <small id="address-help" class="form-help">Enter the user’s physical address.</small>
                    </div>
                    <div class="form-group">
                        <label for="status" class="form-label">Account Status</label>
                        <select class="form-control" id="status" name="status" aria-describedby="status-help">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <small id="status-help" class="form-help">Set the account status. Inactive users cannot log in.</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="/pos/public/users" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Check connectivity status
        function checkConnectivity() {
            const status = navigator.onLine ? 'Online' : 'Offline';
            document.getElementById('connectivity-status').textContent = status;
            document.getElementById('connectivity-status').className = `connectivity-status ${status.toLowerCase()}`;
        }
        window.addEventListener('online', checkConnectivity);
        window.addEventListener('offline', checkConnectivity);
        checkConnectivity();

        // Toggle theme
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const icon = document.querySelector('.btn-theme-toggle i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        }

        // Change language (placeholder)
        function changeLanguage(lang) {
            console.log('Changing language to: ' + lang);
        }

        // Real-time email validation
        function validateEmail() {
            const email = document.getElementById('email').value;
            const error = document.getElementById('email-error');
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regex.test(email) && email) {
                error.style.display = 'block';
            } else {
                error.style.display = 'none';
            }
        }

        // Real-time password validation
        function validatePassword() {
            const password = document.getElementById('password').value;
            const error = document.getElementById('password-error');
            const regex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
            if (!regex.test(password) && password) {
                error.style.display = 'block';
            } else {
                error.style.display = 'none';
            }
            validatePasswordConfirm();
        }

        // Real-time password confirmation validation
        function validatePasswordConfirm() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password-confirm').value;
            const error = document.getElementById('password-confirm-error');
            if (password !== confirm && confirm) {
                error.style.display = 'block';
            } else {
                error.style.display = 'none';
            }
        }

        // Confirm form submission
        function confirmSubmit() {
            if (confirm('Are you sure you want to update this user?')) {
                showAlert('success', 'User update request sent.');
                return true;
            }
            return false;
        }

        // Show alerts
        function showAlert(type, message) {
            const alertsDiv = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertsDiv.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        // Check role change (basic implementation)
        function checkRoleChange() {
            const currentRole = '<?php echo $user['role']; ?>';
            const newRole = document.getElementById('role').value;
            if (currentRole !== newRole && confirm('Changing the role may affect user permissions. Proceed?')) {
                showAlert('info', 'Role change confirmed.');
            }
        }
    </script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f1f3f5;
            color: #212529;
            margin: 0;
            padding: 0;
        }
        .edit-user-container {
            max-width: 800px;
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
        .breadcrumb-link:hover {
            color: #fff;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .language-select {
            background-color: #6b7280;
            color: #fff;
            border: none;
            padding: 5px;
            border-radius: 6px;
            font-size: 0.9rem;
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
        .alert-error { background-color: #fee2e2; color: #991b1b; }
        .alert-success { background-color: #d1fae5; color: #065f46; }
        .alert-info { background-color: #dbeafe; color: #1e40af; }
        .main-content {
            background-color: #f1f3f5;
        }
        .form-panel {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        .form-title {
            font-size: 1.3rem;
            color: #374151;
            margin: 0 0 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            font-size: 0.95rem;
            color: #374151;
            margin-bottom: 5px;
            display: block;
        }
        .required {
            color: #ef4444;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            font-size: 0.95rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background-color: #fff;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 5px rgba(30,58,138,0.3);
            outline: none;
        }
        .form-control[aria-invalid="true"] {
            border-color: #ef4444;
        }
        .form-help {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
            display: block;
        }
        .form-error {
            font-size: 0.85rem;
            color: #ef4444;
            margin-top: 5px;
            display: block;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #1e3a8a;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #1e40af;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        .dark-theme {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .main-content, .dark-theme .edit-user-container {
            background-color: #1f2937;
        }
        .dark-theme .page-header {
            background: linear-gradient(90deg, #374151, #4b5563);
        }
        .dark-theme .form-panel {
            background-color: #374151;
        }
        .dark-theme .form-title, .dark-theme .form-label {
            color: #d1d5db;
        }
        .dark-theme .form-control {
            background-color: #4b5563;
            border-color: #6b7280;
            color: #d1d5db;
        }
        .dark-theme .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 5px rgba(59,130,246,0.3);
        }
        .dark-theme .form-help {
            color: #9ca3af;
        }
        .dark-theme .form-error {
            color: #f87171;
        }
        .dark-theme .btn-primary {
            background-color: #3b82f6;
        }
        .dark-theme .btn-primary:hover {
            background-color: #1e40af;
        }
        .dark-theme .btn-secondary {
            background-color: #6b7280;
        }
        .dark-theme .btn-secondary:hover {
            background-color: #4b5563;
        }
        .dark-theme .connectivity-status {
            background-color: #4b5563;
        }
        .dark-theme .connectivity-status.offline {
            background-color: #f87171;
        }
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
        }
        @media (max-width: 576px) {
            .edit-user-container {
                padding: 15px;
            }
            .form-actions {
                flex-direction: column;
                gap: 8px;
            }
            .btn-primary, .btn-secondary {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</body>
</html>