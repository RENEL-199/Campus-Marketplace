<?php
class Product {
    public function __construct(
        public int $prod_id,
        public int $user_id,
        public string $prod_name,
        public string $prod_desc,
        public float $prod_price,
        public string $prod_image,
        public int $prod_stock,
        public ?string $location,
        public ?string $prod_duration,
        public ?int $category_id,
        public ?string $prod_rate_type = null,
        public ?string $category_name = null,
        public ?string $seller_name = null
    ) {}
}
