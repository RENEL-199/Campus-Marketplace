<?php

class Product {
    public function __construct(
        public int $id,
        public int $user_id, // ✅ NEW
        public string $name,
        public string $description,
        public string $price,
        public string $image,
        public string $category,
        public int $stock
    ) {}
}