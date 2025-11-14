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
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log API call details for debugging
            error_log("COR API Call - URL: " . COR_API_URL);
            error_log("COR API Call - HTTP Code: " . $httpCode);
            error_log("COR API Call - Response: " . $response);
            if ($curlError) {
                error_log("COR API Call - cURL Error: " . $curlError);
                return ['success' => false, 'error' => 'Connection error to authentication server.'];
            }

            if ($httpCode !== 200) {
                error_log("COR API Call - HTTP Error: " . $httpCode);
                return ['success' => false, 'error' => 'Invalid credentials or server error.'];
            }

            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("COR API Call - JSON Error: " . json_last_error_msg());
                return ['success' => false, 'error' => 'Invalid response from authentication server.'];
            }

            if (!$result || !isset($result['success']) || $result['success'] !== true) {
                error_log("COR API Call - Auth Failed: " . print_r($result, true));
                return ['success' => false, 'error' => 'Invalid credentials.'];
            }

            // Login successful, create or get user
            $apiUser = $result['user'] ?? [];
            $email = $result['email'] ?? ($apiUser['email'] ?? null);
            $forumUserId = $this->extractForumUserId($result);
            $resolvedUsername = $apiUser['username'] ?? ($result['username'] ?? $username);
            $user = $this->createOrGetUser($resolvedUsername, $email, $forumUserId);

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
    private function createOrGetUser($username, $email = null, $forumUserId = null) {
        $user = null;

        if ($forumUserId) {
            $stmt = $this->db->query(
                'SELECT * FROM users WHERE forum_user_id = ? LIMIT 1',
                [$forumUserId]
            );
            if ($stmt) {
                $user = $stmt->fetch();
            }
        }

        if (!$user) {
            $stmt = $this->db->query(
                'SELECT * FROM users WHERE username = ? LIMIT 1',
                [$username]
            );

            if ($stmt) {
                $user = $stmt->fetch();
            }
        }

        if (!$user) {
            // Create new user
            $this->db->query(
                'INSERT INTO users (username, email, forum_user_id) VALUES (?, ?, ?)',
                [$username, $email, $forumUserId]
            );

            $userId = $this->db->lastInsertId();

            return [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'forum_user_id' => $forumUserId
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

            if ($forumUserId && (int)$user['forum_user_id'] !== (int)$forumUserId) {
                $this->db->query(
                    'UPDATE users SET forum_user_id = ? WHERE id = ?',
                    [$forumUserId, $user['id']]
                );
                $user['forum_user_id'] = $forumUserId;
            }

            if ($username && $user['username'] !== $username) {
                $this->db->query(
                    'UPDATE users SET username = ? WHERE id = ?',
                    [$username, $user['id']]
                );
                $user['username'] = $username;
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
            error_log("validateSession: Empty session token");
            return false;
        }

        $stmt = $this->db->query(
            'SELECT s.*, u.* FROM sessions s
             JOIN users u ON s.user_id = u.id
             WHERE s.session_token = ? AND s.expires_at > datetime("now")',
            [$sessionToken]
        );

        if (!$stmt) {
            error_log("validateSession: Database query failed");
            return false;
        }

        $user = $stmt->fetch();
        
        if ($user) {
            error_log("validateSession: Found valid session for user: " . $user['username']);
        } else {
            error_log("validateSession: No valid session found for token");
        }

        return $user;
    }

    /**
     * Destroy session
     */
    public function logout($sessionToken) {
        $this->db->query('DELETE FROM sessions WHERE session_token = ?', [$sessionToken]);
        return true;
    }

    /**
     * Extract COR forum user ID from API response.
     */
    private function extractForumUserId(array $result) {
        return $this->searchForUserId($result);
    }

    /**
     * Recursively search for a field that looks like a forum user ID.
     */
    private function searchForUserId($data) {
        if (!is_array($data)) {
            return null;
        }

        $acceptedKeys = [
            'userid',
            'useridentifier',
            'forumuserid',
            'memberid',
            'idmember',
            'uid'
        ];

        foreach ($data as $key => $value) {
            $normalizedKey = preg_replace('/[^a-z0-9]/', '', strtolower((string)$key));
            if (in_array($normalizedKey, $acceptedKeys, true) && is_numeric($value)) {
                $intVal = (int)$value;
                if ($intVal > 0) {
                    return $intVal;
                }
            }
        }

        if (isset($data['id']) && is_numeric($data['id'])) {
            $hasUserFields = isset($data['username']) || isset($data['name']) || isset($data['email']);
            if ($hasUserFields) {
                $intVal = (int)$data['id'];
                if ($intVal > 0) {
                    return $intVal;
                }
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $id = $this->searchForUserId($value);
                if ($id !== null) {
                    return $id;
                }
            }
        }

        return null;
    }
}
