<?php

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Item.php';

class Cart {
    private $session;
    private $itemModel;

    public function __construct() {
        $this->session = new Session();
        $this->itemModel = new Item();
    }

    /**
     * Get cart items
     */
    public function getItems() {
        return $this->session->get('cart', []);
    }

    /**
     * Add item to cart
     */
    public function addItem($itemId, $quantity = 1) {
        $item = $this->itemModel->getById($itemId);

        if (!$item || $item['active'] != 1) {
            return false;
        }

        $cart = $this->getItems();

        // Check if item already in cart
        if (isset($cart[$itemId])) {
            $cart[$itemId]['quantity'] += $quantity;
        } else {
            $cart[$itemId] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'image_url' => $item['image_url'],
                'quantity' => $quantity
            ];
        }

        // Check stock
        if (!$this->itemModel->hasStock($itemId, $cart[$itemId]['quantity'])) {
            return false;
        }

        $this->session->set('cart', $cart);
        return true;
    }

    /**
     * Update item quantity
     */
    public function updateQuantity($itemId, $quantity) {
        $cart = $this->getItems();

        if (!isset($cart[$itemId])) {
            return false;
        }

        if ($quantity <= 0) {
            unset($cart[$itemId]);
        } else {
            // Check stock
            if (!$this->itemModel->hasStock($itemId, $quantity)) {
                return false;
            }

            $cart[$itemId]['quantity'] = $quantity;
        }

        $this->session->set('cart', $cart);
        return true;
    }

    /**
     * Remove item from cart
     */
    public function removeItem($itemId) {
        $cart = $this->getItems();

        if (isset($cart[$itemId])) {
            unset($cart[$itemId]);
            $this->session->set('cart', $cart);
            return true;
        }

        return false;
    }

    /**
     * Clear cart
     */
    public function clear() {
        $this->session->remove('cart');
    }

    /**
     * Get cart total
     */
    public function getTotal() {
        $cart = $this->getItems();
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    /**
     * Get cart item count
     */
    public function getItemCount() {
        $cart = $this->getItems();
        $count = 0;

        foreach ($cart as $item) {
            $count += $item['quantity'];
        }

        return $count;
    }

    /**
     * Validate cart (check stock availability)
     */
    public function validate() {
        $cart = $this->getItems();

        foreach ($cart as $itemId => $item) {
            if (!$this->itemModel->hasStock($itemId, $item['quantity'])) {
                return false;
            }
        }

        return true;
    }
}
