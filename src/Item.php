<?php

class Item {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all active items
     */
    public function getActiveItems() {
        $stmt = $this->db->query(
            'SELECT * FROM items WHERE active = 1 ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Get all items (including inactive)
     */
    public function getAllItems() {
        $stmt = $this->db->query(
            'SELECT * FROM items ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Get item by ID
     */
    public function getById($id) {
        $stmt = $this->db->query(
            'SELECT * FROM items WHERE id = ?',
            [$id]
        );
        return $stmt->fetch();
    }

    /**
     * Create new item
     */
    public function create($data) {
        $stmt = $this->db->query(
            'INSERT INTO items (name, description, price, image_url, stock, active)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['description'] ?? '',
                $data['price'],
                $data['image_url'] ?? '',
                $data['stock'] ?? 0,
                $data['active'] ?? 1
            ]
        );

        return $this->db->lastInsertId();
    }

    /**
     * Update item
     */
    public function update($id, $data) {
        $stmt = $this->db->query(
            'UPDATE items
             SET name = ?, description = ?, price = ?, image_url = ?, stock = ?, active = ?, updated_at = datetime("now")
             WHERE id = ?',
            [
                $data['name'],
                $data['description'] ?? '',
                $data['price'],
                $data['image_url'] ?? '',
                $data['stock'] ?? 0,
                $data['active'] ?? 1,
                $id
            ]
        );

        return $stmt !== false;
    }

    /**
     * Delete item
     */
    public function delete($id) {
        $stmt = $this->db->query('DELETE FROM items WHERE id = ?', [$id]);
        return $stmt !== false;
    }

    /**
     * Update stock
     */
    public function updateStock($id, $quantity) {
        $stmt = $this->db->query(
            'UPDATE items SET stock = stock - ? WHERE id = ?',
            [$quantity, $id]
        );
        return $stmt !== false;
    }

    /**
     * Check if item has enough stock
     */
    public function hasStock($id, $quantity) {
        $item = $this->getById($id);
        return $item && $item['stock'] >= $quantity;
    }
}
