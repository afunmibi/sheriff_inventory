<?php
/**
 * Auth Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends BaseController {
    private User $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function login(): void {
        $data = $this->getJsonInput();
        
        $errors = $this->validateRequired($data, ['email', 'password']);
        
        if (!empty($errors)) {
            $this->validationError('Validation failed', $errors);
        }
        
        if (!$this->validateEmail($data['email'])) {
            $this->validationError('Invalid email format', ['email' => 'Please enter a valid email']);
        }
        
        $user = $this->userModel->authenticate($data['email'], $data['password']);
        
        if (!$user) {
            $this->errorResponse('Invalid email or password');
        }
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        $this->log('info', 'User logged in', ['user_id' => $user['user_id']]);
        
        $this->successResponse('Login successful', [
            'user' => [
                'user_id' => $user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        session_unset();
        session_destroy();
        
        if ($userId) {
            $this->log('info', 'User logged out', ['user_id' => $userId]);
        }
        
        $this->successResponse('Logout successful');
    }

    public function me(): void {
        $user = $this->requireAuth();
        
        $this->successResponse('User retrieved', [
            'user_id' => $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
    }
}
