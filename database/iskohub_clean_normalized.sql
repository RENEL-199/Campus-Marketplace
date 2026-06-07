-- IskoHub Clean Normalized Database
-- Compatible with MariaDB / MySQL in XAMPP
-- Main cleanup:
-- 1. Removed duplicate/unused lost_found_items and lost_found_messages tables.
-- 2. Added missing foreign keys.
-- 3. Kept order/order_item snapshot fields because they preserve receipt history.
-- 4. Kept table/column names close to your current PHP code to reduce code changes.

DROP DATABASE IF EXISTS iskohub_clean;
CREATE DATABASE iskohub_clean CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE iskohub_clean;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL UNIQUE,
    stud_id VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(255) NULL,
    profile_pic VARCHAR(255) NULL,
    course VARCHAR(100) NULL,
    year_level VARCHAR(50) NULL,
    age INT NULL,
    gender VARCHAR(20) NULL,
    birthday DATE NULL,
    address VARCHAR(255) NULL,
    contact_number VARCHAR(50) NULL,
    email VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_type ENUM('product','rental','service','lost_found') NOT NULL DEFAULT 'product'
) ENGINE=InnoDB;

CREATE TABLE products (
    prod_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    prod_name VARCHAR(255) NOT NULL,
    prod_desc TEXT NOT NULL,
    prod_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    prod_image VARCHAR(255) NULL,
    prod_stock INT NOT NULL DEFAULT 0,
    location VARCHAR(255) NULL,
    rate_type ENUM('Per Piece','Per Day') NULL,
    rental_terms TEXT NULL,
    seller_terms_accepted_at DATETIME NULL,
    status ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(category_id),
    INDEX idx_products_user (user_id),
    INDEX idx_products_category (category_id),
    INDEX idx_products_status (status)
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    selected_for_checkout TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(prod_id) ON DELETE CASCADE,
    INDEX idx_cart_user (user_id),
    INDEX idx_cart_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE cart_item_rentals (
    cart_item_id INT PRIMARY KEY,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    borrower_name VARCHAR(255) NOT NULL,
    student_no VARCHAR(100) NOT NULL,
    age INT NULL,
    gender VARCHAR(50) NULL,
    CONSTRAINT fk_cart_rental_item FOREIGN KEY (cart_item_id) REFERENCES cart_items(cart_item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_item_services (
    cart_item_id INT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    student_no VARCHAR(100) NOT NULL,
    print_type ENUM('B&W','Colored') NOT NULL DEFAULT 'B&W',
    CONSTRAINT fk_cart_service_item FOREIGN KEY (cart_item_id) REFERENCES cart_items(cart_item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_item_service_files (
    service_file_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_item_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_service_file FOREIGN KEY (cart_item_id) REFERENCES cart_items(cart_item_id) ON DELETE CASCADE,
    INDEX idx_cart_service_file_item (cart_item_id)
) ENGINE=InnoDB;

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    payment_method VARCHAR(100) NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    payment_proof_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (status)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    item_type ENUM('product','rental','service') NOT NULL DEFAULT 'product',
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(prod_id),
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE order_item_rentals (
    order_item_id INT PRIMARY KEY,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    rental_days INT NOT NULL DEFAULT 1,
    borrower_name VARCHAR(255) NOT NULL,
    student_no VARCHAR(100) NOT NULL,
    age INT NULL,
    gender VARCHAR(50) NULL,
    payment_status VARCHAR(60) NOT NULL DEFAULT 'Pending Payment',
    payment_proof_path VARCHAR(255) NULL,
    payment_verified_at DATETIME NULL,
    payment_verified_by INT NULL,
    payment_rejection_reason TEXT NULL,
    reservation_status VARCHAR(60) NOT NULL DEFAULT 'Pending Payment',
    rental_terms_accepted TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_order_rental_item FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id) ON DELETE CASCADE,
    CONSTRAINT fk_order_rental_verified_by FOREIGN KEY (payment_verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_item_services (
    order_item_id INT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    student_no VARCHAR(100) NOT NULL,
    print_type ENUM('B&W','Colored') NOT NULL DEFAULT 'B&W',
    file_count INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_order_service_item FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_item_service_files (
    service_file_id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    CONSTRAINT fk_order_service_file FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id) ON DELETE CASCADE,
    INDEX idx_order_service_file_item (order_item_id)
) ENGINE=InnoDB;

CREATE TABLE rental_payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    status_from VARCHAR(60) NOT NULL,
    status_to VARCHAR(60) NOT NULL,
    changed_by INT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note TEXT NULL,
    CONSTRAINT fk_rental_history_order_item FOREIGN KEY (order_item_id) REFERENCES order_item_rentals(order_item_id) ON DELETE CASCADE,
    CONSTRAINT fk_rental_history_user FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_rental_history_order_item (order_item_id)
) ENGINE=InnoDB;

CREATE TABLE rental_terms_acceptances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    terms_text TEXT NULL,
    user_id INT NULL,
    CONSTRAINT fk_rental_terms_order_item FOREIGN KEY (order_item_id) REFERENCES order_item_rentals(order_item_id) ON DELETE CASCADE,
    CONSTRAINT fk_rental_terms_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_rental_terms_order_item (order_item_id)
) ENGINE=InnoDB;

CREATE TABLE seller_terms_acceptances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT NULL,
    ip_address VARCHAR(100) NULL,
    CONSTRAINT fk_seller_terms_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lost_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    owner_name VARCHAR(255) NULL,
    program VARCHAR(255) NULL,
    contact VARCHAR(100) NULL,
    social VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    user_id INT NULL,
    type ENUM('lost','found') NOT NULL DEFAULT 'lost',
    status ENUM('open','claimed') NOT NULL DEFAULT 'open',
    claimed_claim_id INT NULL,
    claimed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lost_items_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_lost_items_user (user_id),
    INDEX idx_lost_items_type_status (type, status)
) ENGINE=InnoDB;

CREATE TABLE lost_found_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_type ENUM('lost','found') NOT NULL,
    claimant_name VARCHAR(255) NOT NULL,
    claimant_program VARCHAR(255) NULL,
    claimant_contact VARCHAR(100) NULL,
    message TEXT NULL,
    user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_by_owner TINYINT(1) NOT NULL DEFAULT 0,
    deleted_by_claimant TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_lost_claim_item FOREIGN KEY (item_id) REFERENCES lost_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_lost_claim_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_lost_claim_item (item_id),
    INDEX idx_lost_claim_user (user_id)
) ENGINE=InnoDB;

ALTER TABLE lost_items
    ADD CONSTRAINT fk_lost_items_claim FOREIGN KEY (claimed_claim_id) REFERENCES lost_found_claims(id) ON DELETE SET NULL;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(60) NOT NULL DEFAULT 'general',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    related_order_item_id INT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_order_item FOREIGN KEY (related_order_item_id) REFERENCES order_items(order_item_id) ON DELETE SET NULL,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_unread (user_id, is_read)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO categories (category_id, category_name, category_type) VALUES
(1, 'Electronics', 'product'),
(2, 'School Supplies', 'product'),
(3, 'Services', 'service'),
(4, 'Preloved', 'product'),
(5, 'Rentals', 'rental'),
(6, 'Others', 'product'),
(7, 'Lost & Found', 'lost_found');

-- Default admin account from your old dump.
-- Password is already hashed.
INSERT INTO users (user_id, user_name, stud_id, password) VALUES
(1, 'Admin', 'Admin-00', '$2y$10$BlRgUJ/bKa.MkwNIGby/6OuBOy9JOTWfv8FEWgyYCuxVsacEMUvti');
