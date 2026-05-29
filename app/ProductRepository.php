<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';

class ProductRepository {

    private PDO $pdo;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->pdo;
    }

    /* =========================
       GET ALL PRODUCTS (WITH CATEGORY NAME)
    ========================= */
    public function getAll(): array {
        $stmt = $this->pdo->query(" 
            SELECT 
                p.prod_id,
                p.user_id,
                p.prod_name,
                p.prod_desc,
                p.prod_price,
                p.prod_image,
                p.prod_stock,
                p.prod_location AS location,
                p.prod_duration,
                p.prod_rate_type,
                p.category_id,
                c.category_name
            FROM products p
            LEFT JOIN categories c 
                ON p.category_id = c.category_id
            WHERE p.prod_stock > 0
            ORDER BY p.prod_id DESC
        ");

        return $this->map($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /* =========================
       GET ONE PRODUCT BY ID
    ========================= */
    public function getById(int $prod_id): ?Product {
        $stmt = $this->pdo->prepare(" 
            SELECT 
                p.prod_id,
                p.user_id,
                p.prod_name,
                p.prod_desc,
                p.prod_price,
                p.prod_image,
                p.prod_stock,
                p.prod_location AS location,
                p.prod_duration,
                p.prod_rate_type,
                p.category_id,
                c.category_name
            FROM products p
            LEFT JOIN categories c 
                ON p.category_id = c.category_id
            WHERE p.prod_id = ?
            LIMIT 1
        ");

        $stmt->execute([$prod_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $products = $this->map([$row]);
        return $products[0] ?? null;
    }

    /* =========================
       GET PRODUCTS BY USER
    ========================= */
    public function getByUser(int $user_id): array {
        $stmt = $this->pdo->prepare(" 
            SELECT 
                p.prod_id,
                p.user_id,
                p.prod_name,
                p.prod_desc,
                p.prod_price,
                p.prod_image,
                p.prod_stock,
                p.prod_location AS location,
                p.prod_duration,
                p.prod_rate_type,
                p.category_id,
                c.category_name
            FROM products p
            LEFT JOIN categories c 
                ON p.category_id = c.category_id
            WHERE p.user_id = ?
            ORDER BY p.prod_id DESC
        ");

        $stmt->execute([$user_id]);
        return $this->map($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /* =========================
       ADD PRODUCT
    ========================= */
    public function add(Product $product): void {
        if ($product->user_id <= 0) {
            throw new InvalidArgumentException('Invalid user ID when adding product. Please log in again.');
        }

        $stmt = $this->pdo->prepare(" 
            INSERT INTO products (
                user_id,
                prod_name,
                prod_desc,
                prod_price,
                prod_image,
                prod_stock,
                prod_location,
                prod_duration,
                prod_rate_type,
                category_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $product->user_id,
            $product->prod_name,
            $product->prod_desc,
            $product->prod_price,
            $product->prod_image,
            $product->prod_stock,
            $product->location,
            $product->prod_duration,
            $product->prod_rate_type,
            $product->category_id
        ]);
    }

    /* =========================
       DELETE PRODUCT
    ========================= */
    public function delete(int $prod_id, int $user_id): void {
        $stmt = $this->pdo->prepare(" 
            DELETE FROM products 
            WHERE prod_id = ? AND user_id = ?
        ");

        $stmt->execute([$prod_id, $user_id]);
    }

    /* =========================
       MAP DATABASE TO PRODUCT OBJECT
    ========================= */
    private function map(array $rows): array {
        $products = [];

        foreach ($rows as $row) {
            $products[] = new Product(
                (int)$row['prod_id'],
                (int)$row['user_id'],
                (string)$row['prod_name'],
                (string)$row['prod_desc'],
                (float)$row['prod_price'],
                (string)$row['prod_image'],
                (int)$row['prod_stock'],
                $row['location'] ?? null,
                $row['prod_duration'] ?? null,
                isset($row['category_id']) ? (int)$row['category_id'] : null,
                $row['prod_rate_type'] ?? null,
                $row['category_name'] ?? null
            );
        }

        return $products;
    }
}
