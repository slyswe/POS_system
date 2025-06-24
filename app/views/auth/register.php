<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <!-- <link rel="stylesheet" href="/pos/public/css/style.css"> -->
</head>
<body>
    <h2>Register</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" action="/pos/public/register">
        <label>Name: <input type="text" name="name" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <label>Address: <textarea name="address" required></textarea></label><br>
        <label>Phone: <input type="tel" name="phone" required></label><br>
        <label>Status: 
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </label><br>
        <label>Role: 
            <select name="role">
                <option value="cashier">Cashier</option>
                <option value="admin">Admin</option>
                <option value="inventory_clerk">Inventory Clerk</option>
            </select>
        </label><br>
        <button type="submit">Register</button>
    </form>
</body>
</html>