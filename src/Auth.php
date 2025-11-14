<?php

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Authenticate user via COR Forum API
     */
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username and password are required.'];
        }

        try {
            // Call COR Forum API
            $ch = curl_init(COR_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'X-API-Key: ' . COR_API_KEY,
                'User-Agent: RegnumShop/1.0'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => $username,
                'password' => $password
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => 'Invalid credentials.'];
            }

            $result = json_decode($response, true);

            if (!$result || !isset($result['success']) || $result['success'] !== true) {
                return ['success' => false, 'error' => 'Invalid credentials.'];
            }

            // Login successful, create or get user
            // Try to extract email from API response if available
            $email = $result['email'] ?? $result['user']['email'] ?? null;
            $user = $this->createOrGetUser($username, $email);

            if (!$user) {
                return ['success' => false, 'error' => 'Failed to create user session.'];
            }

            // Create session
            $session = $this->createSession($user['id']);

            return [
                'success' => true,
                'user' => $user,
                'session_token' => $session['session_token']
            ];

        } catch (Exception $e) {
            error_log('Auth error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Authentication failed.'];
        }
    }

    /**
     * Create or get existing user
     */
    private function createOrGetUser($username, $email = null) {
        $stmt = $this->db->query(
            'SELECT * FROM users WHERE username = ?',
            [$username]
        );

        $user = $stmt->fetch();

        if (!$user) {
            // Create new user
            $this->db->query(
                'INSERT INTO users (username, email) VALUES (?, ?)',
                [$username, $email]
            );

            $userId = $this->db->lastInsertId();

            return [
                'id' => $userId,
                'username' => $username,
                'email' => $email
            ];
        } else {
            // Update email if provided and different
            if ($email && $user['email'] !== $email) {
                $this->db->query(
                    'UPDATE users SET email = ? WHERE id = ?',
                    [$email, $user['id']]
                );
                $user['email'] = $email;
            }
        }

        return $user;
    }

    /**
     * Create user session
     */
    private function createSession($userId) {
        // Delete old sessions for this user
        $this->db->query('DELETE FROM sessions WHERE user_id = ?', [$userId]);

        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

        // Create session
        $this->db->query(
            'INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)',
            [$userId, $sessionToken, $expiresAt]
        );

        return [
            'session_token' => $sessionToken,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Validate session token
     */
    public function validateSession($sessionToken) {
        if (empty($sessionToken)) {
            return false;
        }

        $stmt = $this->db->query(
            'SELECT s.*, u.* FROM sessions s
             JOIN users u ON s.user_id = u.id
             WHERE s.session_token = ? AND s.expires_at > datetime("now")',
            [$sessionToken]
        );

        return $stmt->fetch();
    }

    /**
     * Destroy session
     */
    public function logout($sessionToken) {
        $this->db->query('DELETE FROM sessions WHERE session_token = ?', [$sessionToken]);
        return true;
    }

    /**
     * Admin login (simple username/password check)
     */
    public function adminLogin($username, $password) {
        // For simplicity, check against config values
        // In production, you should use database with hashed passwords
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            return true;
        }

        return false;
    }
}
