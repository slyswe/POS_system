<?php
namespace App\Models;

use Exception;

class UserModel
{
    private $conn;

    public function __construct()
    {
        require_once BASE_PATH . '../config/database.php';
        $this->conn = getDbConnection();
        if ($this->conn->connect_error) {
            throw new Exception("Database connection failed: " . $this->conn->connect_error);
        }
    }

    public function getUserByEmail($email)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, password_hash, role, status, phone, address, last_login 
                FROM users 
                WHERE email = ? 
                LIMIT 1
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user ?: null;
        } catch (Exception $e) {
            error_log("Error fetching user by email: " . $e->getMessage());
            return null;
        }
    }

    public function getAllUsers()
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, role, status, phone, last_login 
                FROM users
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $users;
        } catch (Exception $e) {
            error_log("Error fetching all users: " . $e->getMessage());
            return [];
        }
    }

    public function emailExists($email)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_row()[0];
            $stmt->close();
            return $count > 0;
        } catch (Exception $e) {
            error_log("Error checking email existence: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($data)
    {
        try {
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = in_array($data['role'], ['Admin', 'Cashier', 'Inventory Clerk']) ? $data['role'] : 'Cashier';
            $stmt = $this->conn->prepare("
                INSERT INTO users (name, email, password_hash, address, phone, status, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param(
                "sssssss",
                $data['name'],
                $data['email'],
                $password_hash,
                $data['address'],
                $data['phone'],
                $data['status'],
                $role
            );
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, password_hash, address, phone, status, role, last_login 
                FROM users 
                WHERE id = ?
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user ?: null;
        } catch (Exception $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return null;
        }
    }

    public function updateUser($id, $data)
    {
        try {
            $password_hash = isset($data['password']) && !empty($data['password'])
                ? password_hash($data['password'], PASSWORD_DEFAULT)
                : $this->getUserById($id)['password_hash'];
            $last_login = date('Y-m-d H:i:s');
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET name = ?, email = ?, password_hash = ?, address = ?, phone = ?, status = ?, role = ?, last_login = ? 
                WHERE id = ?
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param(
                "ssssssssi",
                $data['name'],
                $data['email'],
                $password_hash,
                $data['address'],
                $data['phone'],
                $data['status'],
                $data['role'],
                $last_login,
                $id
            );
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($id)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    public function updateLastLogin($userId)
    {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $last_login = date('Y-m-d H:i:s');
            $stmt->bind_param("si", $last_login, $userId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}