<?php

class Analytics {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Track an event
     */
    public function track($eventType, $metadata = []) {
        $user = null;
        $session = new Session();

        if ($session->isLoggedIn()) {
            $user = $session->getUser();
        }

        $data = [
            'event_type' => $eventType,
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => $user['id'] ?? null,
            'username' => $user['username'] ?? null,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'session_id' => session_id(),
            'metadata' => json_encode($metadata)
        ];

        $this->db->query(
            'INSERT INTO analytics_events (event_type, page_url, user_id, username, ip_address, user_agent, referrer, session_id, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['event_type'],
                $data['page_url'],
                $data['user_id'],
                $data['username'],
                $data['ip_address'],
                $data['user_agent'],
                $data['referrer'],
                $data['session_id'],
                $data['metadata']
            ]
        );

        // Update daily summary
        $this->updateDailySummary($eventType, $metadata);
    }

    /**
     * Track page view
     */
    public function trackPageView() {
        $this->track('page_view');
    }

    /**
     * Track login
     */
    public function trackLogin($username) {
        $this->track('login', ['username' => $username]);
    }

    /**
     * Track order
     */
    public function trackOrder($orderId, $amount) {
        $this->track('order', ['order_id' => $orderId, 'amount' => $amount]);
    }

    /**
     * Track add to cart
     */
    public function trackAddToCart($itemId, $itemName, $quantity) {
        $this->track('add_to_cart', [
            'item_id' => $itemId,
            'item_name' => $itemName,
            'quantity' => $quantity
        ]);
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    /**
     * Update daily summary
     */
    private function updateDailySummary($eventType, $metadata) {
        $today = date('Y-m-d');

        // Ensure today's record exists
        $this->db->query(
            'INSERT OR IGNORE INTO analytics_summary (date) VALUES (?)',
            [$today]
        );

        // Update based on event type
        switch ($eventType) {
            case 'page_view':
                $this->db->query(
                    'UPDATE analytics_summary SET page_views = page_views + 1 WHERE date = ?',
                    [$today]
                );
                break;

            case 'login':
                $this->db->query(
                    'UPDATE analytics_summary SET logins = logins + 1 WHERE date = ?',
                    [$today]
                );
                break;

            case 'order':
                $amount = $metadata['amount'] ?? 0;
                $this->db->query(
                    'UPDATE analytics_summary SET orders = orders + 1, revenue = revenue + ? WHERE date = ?',
                    [$amount, $today]
                );
                break;
        }

        // Update unique visitors
        $this->updateUniqueVisitors($today);
    }

    /**
     * Update unique visitors count
     */
    private function updateUniqueVisitors($date) {
        $stmt = $this->db->query(
            'SELECT COUNT(DISTINCT ip_address) as count
             FROM analytics_events
             WHERE DATE(created_at) = ?',
            [$date]
        );

        $result = $stmt->fetch();
        $count = $result['count'] ?? 0;

        $this->db->query(
            'UPDATE analytics_summary SET unique_visitors = ? WHERE date = ?',
            [$count, $date]
        );
    }

    /**
     * Get analytics summary for date range
     */
    public function getSummary($startDate = null, $endDate = null) {
        if (!$startDate) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }

        $stmt = $this->db->query(
            'SELECT * FROM analytics_summary
             WHERE date BETWEEN ? AND ?
             ORDER BY date DESC',
            [$startDate, $endDate]
        );

        return $stmt->fetchAll();
    }

    /**
     * Get today's stats
     */
    public function getTodayStats() {
        $today = date('Y-m-d');

        $stmt = $this->db->query(
            'SELECT * FROM analytics_summary WHERE date = ?',
            [$today]
        );

        $stats = $stmt->fetch();

        if (!$stats) {
            return [
                'page_views' => 0,
                'unique_visitors' => 0,
                'logins' => 0,
                'orders' => 0,
                'revenue' => 0
            ];
        }

        return $stats;
    }

    /**
     * Get top pages
     */
    public function getTopPages($limit = 10) {
        $stmt = $this->db->query(
            'SELECT page_url, COUNT(*) as views
             FROM analytics_events
             WHERE event_type = "page_view" AND DATE(created_at) >= DATE("now", "-30 days")
             GROUP BY page_url
             ORDER BY views DESC
             LIMIT ?',
            [$limit]
        );

        return $stmt->fetchAll();
    }

    /**
     * Get recent events
     */
    public function getRecentEvents($limit = 50) {
        $stmt = $this->db->query(
            'SELECT * FROM analytics_events
             ORDER BY created_at DESC
             LIMIT ?',
            [$limit]
        );

        return $stmt->fetchAll();
    }

    /**
     * Get total stats
     */
    public function getTotalStats() {
        $stmt = $this->db->query(
            'SELECT
                SUM(page_views) as total_page_views,
                SUM(logins) as total_logins,
                SUM(orders) as total_orders,
                SUM(revenue) as total_revenue
             FROM analytics_summary'
        );

        return $stmt->fetch();
    }
}
