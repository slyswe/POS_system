<?php
namespace App\Controllers; 
require_once __DIR__ . '/../models/CustomerModel.php';

use App\Models\CustomerModel;
use \Exception;

class CustomerController {
    protected $customerModel;

    public function __construct() {
        $this->customerModel = new CustomerModel();
    }

    public function lookup($searchTerm) {
        error_log("[Customer Lookup] Search term: " . $searchTerm);

    // Basic validation
    if (empty($searchTerm)) {
        return ['success' => false, 'message' => 'Search term is required'];
    }

    try {
        $customer = $this->customerModel->findCustomer($searchTerm);
        
        if (!$customer) {
            error_log("[Customer Lookup] No customer found for: " . $searchTerm);
            return ['success' => false, 'message' => 'Customer not found'];
        }

        $history = $this->customerModel->getPurchaseHistory($customer['id']);
        return [
            'success' => true,
            'customer' => $customer,
            'history' => $history
        ];
    } catch (Exception $e) {
        error_log("[Customer Lookup ERROR] " . $e->getMessage());
        return ['success' => false, 'message' => 'Server error'];
    }
    }

    public function create() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
        http_response_code(403);
        return ['success' => false, 'message' => 'Unauthorized'];
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['name']) || empty($data['phone'])) {
            return ['success' => false, 'message' => 'Name and phone are required'];
        }
        try {
            $customerId = $this->customerModel->createCustomer($data);
            error_log("Customer created: id=$customerId, user={$_SESSION['user']['id']}");
            return ['success' => true, 'customer_id' => $customerId];
        } catch (Exception $e) {
            error_log("Customer creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create customer: ' . $e->getMessage()];
        }
    }

    public function update($customerId) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
            http_response_code(403);
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['name']) || empty($data['phone'])) {
            return ['success' => false, 'message' => 'Name and phone are required'];
        }
        try {
            $this->customerModel->updateCustomer($customerId, $data);
            error_log("Customer updated: id=$customerId, user={$_SESSION['user']['id']}");
            return ['success' => true, 'customer_id' => $customerId];
        } catch (Exception $e) {
            error_log("Customer update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update customer: ' . $e->getMessage()];
        }
    }

    public function autocomplete($searchTerm) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
            http_response_code(403);
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            $suggestions = $this->customerModel->autocomplete($searchTerm);
            return [
                'success' => true,
                'suggestions' => $suggestions
            ];
        } catch (Exception $e) {
            error_log("Customer autocomplete failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Autocomplete failed'];
        }
    }
}