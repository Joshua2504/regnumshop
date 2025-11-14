<?php

require_once __DIR__ . '/Auth.php';

class Session {
    private $auth;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            if (!session_start()) {
                error_log("Session start failed!");
                throw new Exception("Failed to start session");
            }
            error_log("Session started successfully. ID: " . session_id());
        } else {
            error_log("Session already active. ID: " . session_id());
        }
        
        try {
            $this->auth = new Auth();
            error_log("Auth object created successfully");
        } catch (Exception $e) {
            error_log("Failed to create Auth object: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Set session data
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session data
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     */
    public function remove($key) {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session
     */
    public function destroy() {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!$this->has('session_token')) {
            error_log("Session check: No session token found");
            return false;
        }

        $sessionToken = $this->get('session_token');
        error_log("Session check: Validating token: " . substr($sessionToken, 0, 10) . "...");
        
        $user = $this->auth->validateSession($sessionToken);
        $isValid = $user !== false;
        
        error_log("Session check: Token valid: " . ($isValid ? 'YES' : 'NO'));
        
        return $isValid;
    }

    /**
     * Get current user
     */
    public function getUser() {
        if (!$this->has('session_token')) {
            return null;
        }

        return $this->auth->validateSession($this->get('session_token'));
    }

    /**
     * Check if current user is allowed to access admin area.
     */
    public function isAdmin() {
        if ($this->get('is_admin', false) === true) {
            // Legacy flag for compatibility
            return true;
        }

        $user = $this->getUser();
        if (!$user || empty(ADMIN_USERNAMES)) {
            return false;
        }

        $username = strtolower($user['username'] ?? '');
        return $username !== '' && in_array($username, ADMIN_USERNAMES, true);
    }

    /**
     * Generate CSRF token
     */
    public function getCsrfToken() {
        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
        return $this->get('csrf_token');
    }

    /**
     * Validate CSRF token
     */
    public function validateCsrfToken($token) {
        return hash_equals($this->getCsrfToken(), $token);
    }
}
