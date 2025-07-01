<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3) . '/');
}

require_once BASE_PATH . 'app/controllers/UserController.php';

//session_start();
$controller = new \App\Controllers\UserController();
$controller->index();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>
<body>
    <div class="manage-users-container">
        <header class="page-header">
            <div class="header-left">
                <h1 class="page-title">Manage Users</h1>
                <nav class="breadcrumbs">
                    <a href="/pos/public/dashboard" class="breadcrumb-link">Dashboard</a> >
                    <span>Users</span>
                </nav>
            </div>
            <div class="header-right">
                <select class="language-select" onchange="changeLanguage(this.value)">
                    <option value="en">English</option>
                    <option value="sw">Kiswahili</option>
                    <option value="es">Español</option>
                    <option value="fr">Français</option>
                </select>
                <span class="connectivity-status" id="connectivity-status">Online</span>
                <button class="btn btn-theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

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

        <main class="main-content">
            <div class="users-panel">
                <div class="panel-header">
                    <h2 class="panel-title">User List</h2>
                    <div class="panel-actions">
                        <a href="/pos/public/users/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add User
                        </a>
                    </div>
                </div>
                <div class="filter-section">
                    <div class="search-bar">
                        <input type="text" id="search-input" placeholder="Search by name or email..." oninput="searchUsers()">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select id="role-filter" onchange="filterUsers()">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="cashier">Cashier</option>
                        <option value="inventory_clerk">Inventory Clerk</option>
                    </select>
                    <select id="status-filter" onchange="filterUsers()">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
                                <th onclick="sortTable(1)">Name <i class="fas fa-sort"></i></th>
                                <th onclick="sortTable(2)">Role <i class="fas fa-sort"></i></th>
                                <th onclick="sortTable(3)">Email <i class="fas fa-sort"></i></th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <?php if (empty($users)): ?>
                                <tr><td colspan="8">No users found.</td></tr>
                            <?php else: foreach ($users as $user): ?>
                                <tr>
                                    <td><input type="checkbox" class="select-user" value="<?php echo htmlspecialchars($user['id']); ?>"></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($user['status']); ?>"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['last_login'] ?? 'N/A'); ?></td>
                                    <td class="action-buttons">
                                        <a href="/pos/public/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-edit" title="Edit User"><i class="fas fa-edit"></i></a>
                                        <a href="/pos/public/users/delete/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-delete" title="Delete User" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <div class="bulk-actions">
                        <select id="bulk-action" disabled>
                            <option value="">Bulk Actions</option>
                            <option value="delete">Delete Selected</option>
                            <option value="activate">Activate Selected</option>
                            <option value="deactivate">Deactivate Selected</option>
                        </select>
                        <button class="btn btn-action" onclick="applyBulkAction()" disabled id="bulk-action-btn">Apply</button>
                    </div>
                    <div class="pagination">
                        <button class="btn btn-page" onclick="changePage(-1)" disabled>Previous</button>
                        <span id="page-info">Page 1 of 1</span>
                        <button class="btn btn-page" onclick="changePage(1)" disabled>Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function checkConnectivity() {
            const status = navigator.onLine ? 'Online' : 'Offline';
            document.getElementById('connectivity-status').textContent = status;
            document.getElementById('connectivity-status').className = `connectivity-status ${status.toLowerCase()}`;
        }
        window.addEventListener('online', checkConnectivity);
        window.addEventListener('offline', checkConnectivity);
        checkConnectivity();

        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const icon = document.querySelector('.btn-theme-toggle i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        }

        function changeLanguage(lang) {
            console.log('Changing language to: ' + lang);
        }

        function searchUsers() {
            const query = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#users-table-body tr');
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[3].textContent.toLowerCase();
                row.style.display = (name.includes(query) || email.includes(query)) ? '' : 'none';
            });
        }

        function filterUsers() {
            const role = document.getElementById('role-filter').value;
            const status = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#users-table-body tr');
            rows.forEach(row => {
                const rowRole = row.cells[2].textContent;
                const rowStatus = row.cells[5].textContent.toLowerCase();
                const roleMatch = !role || rowRole === role;
                const statusMatch = !status || rowStatus === status;
                row.style.display = roleMatch && statusMatch ? '' : 'none';
            });
        }

        let sortDirection = 1;
        let lastSortedColumn = -1;
        function sortTable(column) {
            const tbody = document.getElementById('users-table-body');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            if (lastSortedColumn === column) {
                sortDirection *= -1;
            } else {
                sortDirection = 1;
            }
            lastSortedColumn = column;
            rows.sort((a, b) => {
                const aText = a.cells[column].textContent.toLowerCase();
                const bText = b.cells[column].textContent.toLowerCase();
                return sortDirection * aText.localeCompare(bText);
            });
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all').checked;
            document.querySelectorAll('.select-user').forEach(checkbox => {
                checkbox.checked = selectAll;
            });
            updateBulkActions();
        }

        function updateBulkActions() {
            const selected = document.querySelectorAll('.select-user:checked').length;
            const bulkAction = document.getElementById('bulk-action');
            const bulkButton = document.getElementById('bulk-action-btn');
            bulkAction.disabled = selected === 0;
            bulkButton.disabled = selected === 0;
        }

        function applyBulkAction() {
            const action = document.getElementById('bulk-action').value;
            const selected = Array.from(document.querySelectorAll('.select-user:checked')).map(cb => cb.value);
            if (!selected.length) return;
            // No PIN prompt; direct action (to be implemented with backend)
            console.log(`Applying ${action} to users: ${selected.join(', ')}`);
            // TODO: Implement AJAX or form submission to /pos/public/users/bulk-action
            showAlert('success', `${action.charAt(0).toUpperCase() + action.slice(1)} applied to ${selected.length} user(s).`);
        }

        function deleteUser(id) {
            // No PIN prompt; direct confirmation
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = `/pos/public/users/delete/${id}`;
            }
        }

        let currentPage = 1;
        const rowsPerPage = 10;
        function changePage(delta) {
            currentPage += delta;
            console.log(`Loading page ${currentPage}`);
            // TODO: Implement backend pagination
            updatePagination();
        }

        function updatePagination() {
            document.getElementById('page-info').textContent = `Page ${currentPage} of 1`; // Update with real total
            document.querySelectorAll('.btn-page').forEach(btn => {
                btn.disabled = true; // Update based on real data
            });
        }

        function showAlert(type, message) {
            const alertsDiv = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertsDiv.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.select-user').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });
            updatePagination();
        });
    </script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f1f3f5;
            color: #212529;
            margin: 0;
            padding: 20px;
        }
        .manage-users-container {
            max-width: 1280px;
            margin: 0 auto;
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
            border-radius: 8px;
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
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .alert-success { background-color: #d1fae5; color: #065f46; }
        .alert-error { background-color: #fee2e2; color: #991b1b; }
        .main-content {
            background-color: #f1f3f5;
        }
        .users-panel {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
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
        .btn-primary, .btn-action, .btn-page, .btn-edit, .btn-delete {
            padding: 8px 12px;
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
        .btn-action {
            background-color: #6b7280;
            color: #fff;
        }
        .btn-action:hover {
            background-color: #4b5563;
        }
        .btn-edit {
            /* background-color: #f59e0b; */
            color: blue;
            padding: 6px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-edit:hover {
            background-color: #d97706;
        }
        .btn-delete {
            
            color: #1e40af;
            padding: 6px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-delete:hover {
            background-color: #dc2626;
        }
        .btn-page {
            background-color: #f3f4f6;
            color: #374151;
        }
        .btn-page:hover:not(:disabled) {
            background-color: #e5e7eb;
        }
        .btn-page:disabled {
            background-color: #e5e7eb;
            cursor: not-allowed;
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
            min-width: 150px;
        }
        .search-bar input {
            width: 80%;
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
        .filter-section select {
            padding: 10px;
            font-size: 0.95rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background-color: #f3f4f6;
        }
        .table-container {
            overflow-x: auto;
        }
        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }
        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .users-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            cursor: pointer;
        }
        .users-table th:hover {
            background-color: #e5e7eb;
        }
        .users-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            color: #fff;
        }
        .status-badge.active {
            background-color: #22c55e;
        }
        .status-badge.inactive {
            background-color: #ef4444;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .bulk-actions {
            display: flex;
            gap: 10px;
        }
        .bulk-actions select, .bulk-actions button {
            font-size: 0.95rem;
        }
        .bulk-actions select:disabled, .bulk-actions button:disabled {
            background-color: #e5e7eb;
            cursor: not-allowed;
        }
        .pagination {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #page-info {
            font-size: 0.95rem;
            color: #374151;
        }
        .dark-theme {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .manage-users-container {
            background-color: #1f2937;
        }
        .dark-theme .page-header {
            background: linear-gradient(90deg, #374151, #4b5563);
        }
        .dark-theme .users-panel {
            background-color: #374151;
        }
        .dark-theme .panel-title {
            color: #d1d5db;
        }
        .dark-theme .btn-primary {
            background-color: #3b82f6;
        }
        .dark-theme .btn-primary:hover {
            background-color: #1e40af;
        }
        .dark-theme .btn-action, .dark-theme .btn-page {
            background-color: #6b7280;
        }
        .dark-theme .btn-action:hover, .dark-theme .btn-page:hover:not(:disabled) {
            background-color: #4b5563;
        }
        .dark-theme .btn-edit {
            background-color: #d97706;
        }
        .dark-theme .btn-edit:hover {
            background-color: #b45309;
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
        .dark-theme .search-icon {
            color: #9ca3af;
        }
        .dark-theme .users-table th {
            background-color: #1f2937;
            color: #d1d5db;
        }
        .dark-theme .users-table th:hover {
            background-color: #4b5563;
        }
        .dark-theme .users-table td {
            border-bottom-color: #6b7280;
        }
        .dark-theme .users-table tbody tr:hover {
            background-color: #4b5563;
        }
        .dark-theme .status-badge.active {
            background-color: #16a34a;
        }
        .dark-theme .status-badge.inactive {
            background-color: #f87171;
        }
        .dark-theme #page-info {
            color: #d1d5db;
        }
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
            }
            .table-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        @media (max-width: 576px) {
            .users-table th, .users-table td {
                padding: 8px;
                font-size: 0.9rem;
            }
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</body>
</html>