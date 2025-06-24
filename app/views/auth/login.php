<?php
if (!defined('IN_CONTROLLER')) {
    die('Direct access not allowed');
}
$title = "Login - POS System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f1f3f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: #fff;
            padding: 15px;
            border-radius: 6px 6px 0 0;
            text-align: center;
            margin: -30px -30px 20px -30px;
        }
        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            color: #374151;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 5px rgba(30, 58, 138, 0.3);
        }
        .btn-login {
            width: 100%;
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-login:hover {
            background-color: #1e40af;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.9rem;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.9rem;
        }
        .dark-theme {
            background-color: #1f2937;
        }
        .dark-theme .login-container {
            background-color: #374151;
            color: #d1d5db;
        }
        .dark-theme .login-header {
            background: linear-gradient(90deg, #374151, #4b5563);
        }
        .dark-theme .form-group label {
            color: #d1d5db;
        }
        .dark-theme .form-group input {
            background-color: #4b5563;
            border-color: #6b7280;
            color: #d1d5db;
        }
        .dark-theme .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.3);
        }
        .dark-theme .btn-login {
            background-color: #3b82f6;
        }
        .dark-theme .btn-login:hover {
            background-color: #1e40af;
        }
        .dark-theme .alert-error {
            background-color: #7f1d1d;
            color: #f87171;
        }
        .dark-theme .alert-success {
            background-color: #064e3b;
            color: #6ee7b7;
        }
        @media (max-width: 576px) {
            .login-container {
                margin: 0 15px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Login into POS</h1>
        </div>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <form method="POST" action="/pos/public/login">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
    </div>
</body>
</html>