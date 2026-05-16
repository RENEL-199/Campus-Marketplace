<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';

class ProductRepository {

    private PDO $pdo;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->pdo;
    }

    /* GET ALL PRODUCTS (MARKETPLACE) */
    public function getAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM products");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->map($rows);
    }

    /* GET PRODUCTS BY USER */
    public function getByUser(int $user_id): array {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE user_id=?");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->map($rows);
    }

    /* ADD PRODUCT */
    public function add(Product $product): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (user_id, name, description, price, image, category, stock)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $product->user_id,
            $product->name,
            $product->description,
            $product->price,
            $product->image,
            $product->category,
            $product->stock
        ]);
    }

    /* DELETE PRODUCT  */
    public function delete(int $id, int $user_id): void {
        $stmt = $this->pdo->prepare("
            DELETE FROM products WHERE id=? AND user_id=?
        ");
        $stmt->execute([$id, $user_id]);
    }

    /* MAP DATA */
    private function map(array $rows): array {
        $products = [];

        foreach ($rows as $row) {
            $products[] = new Product(
                $row['id'],
                $row['user_id'],
                $row['name'],
                $row['description'],
                $row['price'],
                $row['image'],
                $row['category'],
                $row['stock']
            );
        }

        return $products;
    }
}