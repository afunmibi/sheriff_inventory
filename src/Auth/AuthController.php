<?php
namespace Auth;

use Core\Controller;
use Core\Database;
use Core\Logger;

class AuthController extends Controller {
    public function login(): void {
        $data = $this->getJsonInput();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->validationError('Email and password are required');
        }

        $user = Database::table('users')
            ->where('email', $email)
            ->where('is_active', 1)
            ->first();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Logger::warning("Failed login attempt for: $email");
            $this->error('Invalid email or password', null, 401);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = strtolower($user['role']);
        $_SESSION['last_activity'] = time();
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => strtolower($user['role']),
        ];

        Logger::info("User logged in: {$user['email']}", ['user_id' => $user['user_id']]);

        $this->success('Login successful', [
            'user' => [
                'id'    => $user['user_id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => strtolower($user['role']),
            ],
        ]);
    }

    public function logout(): void {
        $user = $this->requireAuth();
        Logger::info("User logged out: {$user['email']}", ['user_id' => $user['user_id']]);

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        $this->success('Logout successful');
    }

    public function me(): void {
        $user = $this->requireAuth();
        $this->success('Current user', ['user' => $user]);
    }
}
