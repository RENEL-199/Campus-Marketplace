<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';

class ProductRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? (new Database())->pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query("
            SELECT p.*, c.category_name, c.category_type, u.user_name AS seller_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE p.status = 'active' AND p.prod_stock > 0
            ORDER BY p.prod_id DESC
        ");
        return $this->map($stmt->fetchAll());
    }

    public function getById(int $prod_id): ?Product {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.category_name, c.category_type, u.user_name AS seller_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE p.prod_id = ? AND p.status <> 'deleted'
            LIMIT 1
        ");
        $stmt->execute([$prod_id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->map([$row])[0] ?? null;
    }

    public function getByUser(int $user_id): array {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.category_name, c.category_type, u.user_name AS seller_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE p.user_id = ? AND p.status <> 'deleted'
            ORDER BY p.prod_id DESC
        ");
        $stmt->execute([$user_id]);
        return $this->map($stmt->fetchAll());
    }

    public function add(Product $product): void {
        if ($product->user_id <= 0) {
            throw new InvalidArgumentException('Invalid user ID when adding product. Please log in again.');
        }
        $stmt = $this->pdo->prepare("
            INSERT INTO products
            (user_id, category_id, prod_name, prod_desc, prod_price, prod_image, prod_stock, location, rate_type, rental_terms, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $product->user_id,
            $product->category_id,
            $product->prod_name,
            $product->prod_desc,
            $product->prod_price,
            $product->prod_image,
            $product->prod_stock,
            $product->location,
            ($product->prod_rate_type !== '' ? $product->prod_rate_type : null),
            ($product->rental_terms !== '' ? $product->rental_terms : null)
        ]);
    }

    public function update(Product $product): void {
        $stmt = $this->pdo->prepare("
            UPDATE products SET
                category_id = ?, prod_name = ?, prod_desc = ?, prod_price = ?,
                prod_image = ?, prod_stock = ?, location = ?, rate_type = ?, rental_terms = ?
            WHERE prod_id = ? AND user_id = ?
        ");
        $stmt->execute([
            $product->category_id, $product->prod_name, $product->prod_desc, $product->prod_price,
            $product->prod_image, $product->prod_stock, $product->location, ($product->prod_rate_type !== '' ? $product->prod_rate_type : null), ($product->rental_terms !== '' ? $product->rental_terms : null),
            $product->prod_id, $product->user_id
        ]);
    }

    public function delete(int $prod_id, int $user_id): void {
        $stmt = $this->pdo->prepare("UPDATE products SET status='deleted' WHERE prod_id=? AND user_id=?");
        $stmt->execute([$prod_id, $user_id]);
    }

    private function map(array $rows): array {
        $products = [];
        foreach ($rows as $row) {
            $products[] = new Product(
                (int)$row['prod_id'],
                (int)$row['user_id'],
                (string)$row['prod_name'],
                (string)$row['prod_desc'],
                (float)$row['prod_price'],
                (string)($row['prod_image'] ?? 'uploads/default.png'),
                (int)$row['prod_stock'],
                $row['location'] ?? null,
                null,
                isset($row['category_id']) ? (int)$row['category_id'] : null,
                $row['rate_type'] ?? null,
                $row['category_name'] ?? null,
                $row['seller_name'] ?? null,
                $row['category_type'] ?? null,
                $row['rental_terms'] ?? null
            );
        }
        return $products;
    }
}
