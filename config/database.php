<?php
define('DB_HOST', '127.0.0.1:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pos');

function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>