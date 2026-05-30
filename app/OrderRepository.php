<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/CartRepository.php';

class OrderRepository {
    private PDO $pdo;
    private CartRepository $cartRepo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? (new Database())->pdo;
        $this->cartRepo = new CartRepository($this->pdo);
    }

    public function placeOrder(int $userId, array $cartItemIds, string $fullname, string $address, string $phone, string $paymentMethod): int {
        $items = $this->cartRepo->getSelectedItems($userId, $cartItemIds);
        if (empty($items)) {
            throw new Exception('No selected cart items found.');
        }

        $total = 0;
        foreach ($items as $item) {
            $total += CartRepository::subtotal($item);
        }

        $this->pdo->beginTransaction();
        try {
            $stockCheck = $this->pdo->prepare("SELECT prod_stock FROM products WHERE prod_id = ? FOR UPDATE");
            foreach ($items as $item) {
                $categoryType = strtolower($item['category_type'] ?? 'product');
                if ($categoryType === 'service') {
                    continue;
                }
                $stockCheck->execute([(int)$item['product_id']]);
                $stock = $stockCheck->fetchColumn();
                if ($stock === false) {
                    throw new Exception('Product not found.');
                }
                if ((int)$stock < (int)$item['quantity']) {
                    throw new Exception('Insufficient stock for ' . $item['prod_name']);
                }
            }

            $orderStmt = $this->pdo->prepare(" 
                INSERT INTO orders (user_id, fullname, address, phone, payment_method, total, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $orderStmt->execute([$userId, $fullname, $address, $phone, $paymentMethod, $total]);
            $orderId = (int)$this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare(" 
                INSERT INTO order_items (order_id, product_id, item_type, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stockUpdate = $this->pdo->prepare("UPDATE products SET prod_stock = prod_stock - ? WHERE prod_id = ?");

            foreach ($items as $item) {
                $categoryType = strtolower($item['category_type'] ?? 'product');
                if (!in_array($categoryType, ['product','rental','service'], true)) {
                    $categoryType = 'product';
                }
                $subtotal = CartRepository::subtotal($item);
                $itemStmt->execute([
                    $orderId,
                    (int)$item['product_id'],
                    $categoryType,
                    (int)$item['quantity'],
                    (float)$item['prod_price'],
                    $subtotal
                ]);
                $orderItemId = (int)$this->pdo->lastInsertId();

                if ($categoryType === 'rental') {
                    $this->copyRentalDetails($orderItemId, $item);
                    $stockUpdate->execute([(int)$item['quantity'], (int)$item['product_id']]);
                } elseif ($categoryType === 'service') {
                    $this->copyServiceDetails($orderItemId, $item);
                    $this->copyServiceFiles($orderItemId, (int)$item['cart_item_id']);
                } else {
                    $stockUpdate->execute([(int)$item['quantity'], (int)$item['product_id']]);
                }
            }

            $this->cartRepo->deleteItems($userId, $cartItemIds);
            $this->pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function copyRentalDetails(int $orderItemId, array $item): void {
        $stmt = $this->pdo->prepare(" 
            INSERT INTO order_item_rentals
            (order_item_id, date_from, date_to, rental_days, borrower_name, student_no, age, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderItemId,
            $item['date_from'],
            $item['date_to'],
            CartRepository::rentalDays($item['date_from'] ?? null, $item['date_to'] ?? null),
            $item['full_name'] ?? '',
            $item['student_no'] ?? '',
            $item['age'] ?? null,
            $item['gender'] ?? null
        ]);
    }

    private function copyServiceDetails(int $orderItemId, array $item): void {
        $stmt = $this->pdo->prepare(" 
            INSERT INTO order_item_services (order_item_id, full_name, student_no, print_type, file_count)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderItemId,
            $item['service_full_name'] ?? '',
            $item['service_student_no'] ?? '',
            $item['print_type'] ?? 'B&W',
            (int)$item['quantity']
        ]);
    }

    private function copyServiceFiles(int $orderItemId, int $cartItemId): void {
        $files = $this->pdo->prepare("SELECT original_filename, stored_filename, file_path FROM cart_item_service_files WHERE cart_item_id = ?");
        $files->execute([$cartItemId]);
        $insert = $this->pdo->prepare(" 
            INSERT INTO order_item_service_files (order_item_id, original_filename, stored_filename, file_path)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($files->fetchAll() as $file) {
            $insert->execute([$orderItemId, $file['original_filename'], $file['stored_filename'], $file['file_path']]);
        }
    }
}
