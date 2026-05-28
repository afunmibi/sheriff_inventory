<?php
namespace Auth;

use Core\AuthenticationException;
use Core\AuthorizationException;
use Core\Config;

class AuthMiddleware {
    public static function authenticated(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) && isset($_SESSION['user']['user_id'])) {
            $_SESSION['user_id'] = $_SESSION['user']['user_id'];
            $_SESSION['user_email'] = $_SESSION['user']['email'] ?? '';
            $_SESSION['user_name'] = $_SESSION['user']['name'] ?? '';
            $_SESSION['user_role'] = strtolower($_SESSION['user']['role'] ?? '');
        }

        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Authentication required');
        }

        $timeout = Config::get('session.timeout', 900);
        $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
        if ($lastActivity > 0 && time() - $lastActivity > $timeout) {
            $_SESSION = [];
            session_destroy();
            throw new AuthenticationException('Session expired');
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function role(string $role): callable {
        return function () use ($role) {
            self::authenticated();
            if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
                throw new AuthorizationException('Insufficient permissions');
            }
            return true;
        };
    }

    public static function anyRole(array $roles): callable {
        return function () use ($roles) {
            self::authenticated();
            if (!in_array($_SESSION['user_role'], $roles) && $_SESSION['user_role'] !== 'admin') {
                throw new AuthorizationException('Insufficient permissions');
            }
            return true;
        };
    }
}
