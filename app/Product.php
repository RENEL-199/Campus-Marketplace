<?php
class Product {
    public int $prod_id;
    public int $user_id;
    public string $prod_name;
    public string $prod_desc;
    public float $prod_price;
    public string $prod_image;
    public int $prod_stock;
    public ?string $location;
    public ?string $prod_duration;
    public ?int $category_id;
    public ?string $prod_rate_type;
    public ?string $category_name;
    public ?string $seller_name;
    public ?string $category_type;
    public ?string $rental_terms;

    public function __construct(
        int $prod_id,
        int $user_id,
        string $prod_name,
        string $prod_desc,
        float $prod_price,
        string $prod_image,
        int $prod_stock,
        ?string $location,
        ?string $prod_duration,
        ?int $category_id,
        ?string $prod_rate_type = null,
        ?string $category_name = null,
        ?string $seller_name = null,
        ?string $category_type = null,
        ?string $rental_terms = null
    ) {
        $this->prod_id = $prod_id;
        $this->user_id = $user_id;
        $this->prod_name = $prod_name;
        $this->prod_desc = $prod_desc;
        $this->prod_price = $prod_price;
        $this->prod_image = $prod_image;
        $this->prod_stock = $prod_stock;
        $this->location = $location;
        $this->prod_duration = $prod_duration;
        $this->category_id = $category_id;
        $this->prod_rate_type = $prod_rate_type;
        $this->category_name = $category_name;
        $this->seller_name = $seller_name;
        $this->category_type = $category_type;
        $this->rental_terms = $rental_terms;
    }
}
