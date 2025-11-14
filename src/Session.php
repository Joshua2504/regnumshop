<?php

class Session {
    private $auth;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->auth = new Auth();
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
            return false;
        }

        $user = $this->auth->validateSession($this->get('session_token'));
        return $user !== false;
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
        if (!$user || empty(ADMIN_FORUM_IDS)) {
            return false;
        }

        $forumId = isset($user['forum_user_id']) ? (int)$user['forum_user_id'] : 0;
        return $forumId > 0 && in_array($forumId, ADMIN_FORUM_IDS, true);
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
