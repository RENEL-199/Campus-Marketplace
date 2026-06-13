-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
--
-- NORMALIZED SCHEMA: 12 tables
-- Removed unused: lost_found_items, lost_found_messages
-- Merged: cart_item_rentals + order_item_rentals -> rental_details
-- Merged: cart_item_services + order_item_services -> service_details
-- Merged: cart_item_service_files + order_item_service_files -> service_files
-- Added: notifications (for order approve/reject inbox)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iskohub`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `stud_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `stud_id`, `password`, `remember_token`, `profile_pic`, `course`, `year_level`, `age`, `gender`, `birthday`, `address`, `contact_number`, `email`, `created_at`) VALUES
(1, 'Admin', 'Admin-00', '$2y$10$BlRgUJ/bKa.MkwNIGby/6OuBOy9JOTWfv8FEWgyYCuxVsacEMUvti', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-30 10:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_type` enum('product','rental','service','lost_found') NOT NULL DEFAULT 'product'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `category_type`) VALUES
(1, 'Electronics', 'product'),
(2, 'School Supplies', 'product'),
(3, 'Services', 'service'),
(4, 'Preloved', 'product'),
(5, 'Rentals', 'rental'),
(6, 'Lost & Found', 'lost_found');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `prod_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `prod_name` varchar(255) NOT NULL,
  `prod_desc` text NOT NULL,
  `prod_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prod_image` varchar(255) DEFAULT NULL,
  `prod_stock` int(11) NOT NULL DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `rate_type` enum('Per Piece','Per Day') DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`prod_id`, `user_id`, `category_id`, `prod_name`, `prod_desc`, `prod_price`, `prod_image`, `prod_stock`, `location`, `rate_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Printing Service', 'Our Printing Service provides fast, affordable, and high-quality printing solutions for students and campus members. Whether you need assignments, research papers, reviewers, handouts, reports, certificates, or other academic documents printed, we ensure clear and professional results. Simply upload your files, choose your preferred print option, and submit your request. We offer both black-and-white and colored printing with reliable service and quick turnaround times to help you meet your deadlines.', 10.00, 'uploads/1780138834_1780134383_ChatGPT Image May 30, 2026, 05_22_00 PM.png', 100, 'Inside PUP Campus', 'Per Piece', 'active', '2026-05-30 11:00:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `selected_for_checkout` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `item_type` enum('product','rental','service') NOT NULL DEFAULT 'product',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_details`
-- (merged from cart_item_rentals and order_item_rentals)
--

CREATE TABLE `rental_details` (
  `id` int(11) NOT NULL,
  `ref_type` enum('cart','order') NOT NULL,
  `ref_id` int(11) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `rental_days` int(11) NOT NULL DEFAULT 1,
  `borrower_name` varchar(255) NOT NULL,
  `student_no` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_details`
-- (merged from cart_item_services and order_item_services)
--

CREATE TABLE `service_details` (
  `id` int(11) NOT NULL,
  `ref_type` enum('cart','order') NOT NULL,
  `ref_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_no` varchar(100) NOT NULL,
  `print_type` enum('B&W','Colored') NOT NULL DEFAULT 'B&W',
  `file_count` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_files`
-- (merged from cart_item_service_files and order_item_service_files)
--

CREATE TABLE `service_files` (
  `service_file_id` int(11) NOT NULL,
  `ref_type` enum('cart','order') NOT NULL,
  `ref_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
-- (buyer inbox: order approved/rejected by seller)
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'buyer who receives the notification',
  `order_id` int(11) NOT NULL,
  `type` enum('approved','rejected') NOT NULL,
  `message` text DEFAULT NULL COMMENT 'optional message from seller',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost_items`
--

CREATE TABLE `lost_items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `social` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` enum('lost','found') NOT NULL DEFAULT 'lost',
  `status` enum('open','claimed') NOT NULL DEFAULT 'open',
  `claimed_claim_id` int(11) DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_claims`
--

CREATE TABLE `lost_found_claims` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('lost','found') NOT NULL,
  `claimant_name` varchar(255) NOT NULL,
  `claimant_program` varchar(255) DEFAULT NULL,
  `claimant_contact` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by_owner` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_claimant` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_name` (`user_name`),
  ADD UNIQUE KEY `stud_id` (`stud_id`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

ALTER TABLE `products`
  ADD PRIMARY KEY (`prod_id`),
  ADD KEY `fk_products_user` (`user_id`),
  ADD KEY `fk_products_category` (`category_id`);

ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `fk_cart_user` (`user_id`),
  ADD KEY `fk_cart_product` (`product_id`);

ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_user` (`user_id`);

ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

ALTER TABLE `rental_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ref` (`ref_type`, `ref_id`);

ALTER TABLE `service_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ref` (`ref_type`, `ref_id`);

ALTER TABLE `service_files`
  ADD PRIMARY KEY (`service_file_id`),
  ADD KEY `idx_ref` (`ref_type`, `ref_id`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_user` (`user_id`),
  ADD KEY `fk_notif_order` (`order_id`);

ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `lost_found_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `products`
  MODIFY `prod_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rental_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `service_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `service_files`
  MODIFY `service_file_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `lost_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `lost_found_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`prod_id`);

ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
