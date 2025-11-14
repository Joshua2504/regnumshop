<?php

class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create new order
     */
    public function create($userId, $cartItems, $paymentMethod) {
        try {
            $this->db->beginTransaction();

            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // Create order
            $stmt = $this->db->query(
                'INSERT INTO orders (user_id, total_amount, payment_method, payment_status, order_status)
                 VALUES (?, ?, ?, ?, ?)',
                [$userId, $total, $paymentMethod, 'pending', 'pending']
            );

            $orderId = $this->db->lastInsertId();

            // Add order items
            foreach ($cartItems as $item) {
                $this->db->query(
                    'INSERT INTO order_items (order_id, item_id, item_name, quantity, price)
                     VALUES (?, ?, ?, ?, ?)',
                    [$orderId, $item['id'], $item['name'], $item['quantity'], $item['price']]
                );

                // Update stock
                $itemModel = new Item();
                $itemModel->updateStock($item['id'], $item['quantity']);
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Order creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order by ID
     */
    public function getById($id) {
        $stmt = $this->db->query(
            'SELECT o.*, u.username
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ?',
            [$id]
        );
        return $stmt->fetch();
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId) {
        $stmt = $this->db->query(
            'SELECT * FROM order_items WHERE order_id = ?',
            [$orderId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Get orders by user ID
     */
    public function getByUserId($userId) {
        $stmt = $this->db->query(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Get all orders
     */
    public function getAll() {
        $stmt = $this->db->query(
            'SELECT o.*, u.username
             FROM orders o
             JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($id, $orderStatus) {
        $stmt = $this->db->query(
            'UPDATE orders SET order_status = ?, updated_at = datetime("now") WHERE id = ?',
            [$orderStatus, $id]
        );
        return $stmt !== false;
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $paymentStatus) {
        $stmt = $this->db->query(
            'UPDATE orders SET payment_status = ?, updated_at = datetime("now") WHERE id = ?',
            [$paymentStatus, $id]
        );
        return $stmt !== false;
    }

    /**
     * Add notes to order
     */
    public function addNotes($id, $notes) {
        $stmt = $this->db->query(
            'UPDATE orders SET notes = ?, updated_at = datetime("now") WHERE id = ?',
            [$notes, $id]
        );
        return $stmt !== false;
    }
}
