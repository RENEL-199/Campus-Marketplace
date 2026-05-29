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
                p.*,
                c.category_name
            FROM products p
            LEFT JOIN categories c 
            ON p.category_id = c.category_id
            WHERE p.prod_stock > 0
        ");

        return $this->map($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /* =========================
       GET PRODUCTS BY USER
    ========================= */
    public function getByUser(int $user_id): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                c.category_name
            FROM products p
            LEFT JOIN categories c 
            ON p.category_id = c.category_id
            WHERE p.user_id = ?
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
                location,
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
                $row['prod_id'],
                $row['user_id'],
                $row['prod_name'],
                $row['prod_desc'],
                $row['prod_price'],
                $row['prod_image'],
                $row['prod_stock'],
                $row['location'],
                $row['prod_duration'],
                $row['category_id'],
                $row['prod_rate_type'] ?? null,
                $row['category_name'] ?? null
            );
        }

        return $products;
    }
}