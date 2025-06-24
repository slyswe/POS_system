<?php
namespace App\Controllers;

use App\Models\UserModel;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2) . '/');
}

require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'app/models/UserModel.php';

class UserController {
    private $model;

    public function __construct() {
        //session_start();
        $this->model = new UserModel();

        // Admin-only access check
        //if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            //header('Location: /pos/public/login');
            //exit;
       // }
    }

    public function index() {
        $users = $this->model->getAllUsers();
        require_once BASE_PATH . 'app/views/users/list.php';
    }

    public function create() {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING),
                'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                'password' => $_POST['password'] ?? '',
                'address' => filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING),
                'phone' => filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING),
                'status' => filter_var($_POST['status'] ?? '', FILTER_SANITIZE_STRING),
                'role' => filter_var($_POST['role'] ?? '', FILTER_SANITIZE_STRING)
            ];

            if (!$data['name'] || !$data['email'] || !$data['password']) {
                $error = 'Name, email, and password are required';
            } elseif ($this->model->emailExists($data['email'])) {
                $error = 'Email already exists';
            } else {
                $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                if ($this->model->createUser($data)) {
                    $_SESSION['success'] = 'User created successfully';
                    header('Location: /pos/public/users');
                    exit;
                } else {
                    $error = 'Failed to create user';
                }
            }
        }
        require_once BASE_PATH . 'app/views/users/create.php';
    }

    public function edit($id) {
        $error = '';
        $user = $this->model->getUserById($id);
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /pos/public/users');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING),
                'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                'password' => $_POST['password'] ?? '',
                'address' => filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING),
                'phone' => filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING),
                'status' => filter_var($_POST['status'] ?? '', FILTER_SANITIZE_STRING),
                'role' => filter_var($_POST['role'] ?? '', FILTER_SANITIZE_STRING)
            ];

            if (!$data['name'] || !$data['email']) {
                $error = 'Name and email are required';
            } else {
                if ($data['password']) {
                    $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                } else {
                    $data['password_hash'] = $user['password_hash']; // Keep existing password if not changed
                }
                if ($this->model->updateUser($id, $data)) {
                    $_SESSION['success'] = 'User updated successfully';
                    header('Location: /pos/public/users');
                    exit;
                } else {
                    $error = 'Failed to update user';
                }
            }
        }
        // Pass user data to the view
        require_once BASE_PATH . 'app/views/users/edit.php';
    }

    public function delete($id) {
        if ($this->model->deleteUser($id)) {
            $_SESSION['success'] = 'User deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete user';
        }
        header('Location: /pos/public/users');
        exit;
    }
}