-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2026 at 05:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `lost_found_claims`
--

CREATE TABLE `lost_found_claims` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
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
-- Dumping data for table `lost_found_claims`
--

INSERT INTO `lost_found_claims` (`id`, `item_id`, `claimant_name`, `claimant_program`, `claimant_contact`, `message`, `user_id`, `created_at`, `deleted_by_owner`, `deleted_by_claimant`) VALUES
(1, 1, 'Grace Guiterrez', 'BSIT 2-2', 'Grace Ganda sa fb', 'sa nb', 2, '2026-06-14 03:21:58', 0, 0);

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

--
-- Dumping data for table `lost_items`
--

INSERT INTO `lost_items` (`id`, `item_name`, `description`, `owner_name`, `program`, `contact`, `social`, `image`, `user_id`, `created_at`, `type`, `status`, `claimed_claim_id`, `claimed_at`) VALUES
(1, 'Calculator', 'here sa nb', 'Sam Renly Cruzado', 'BSIT 2-2', '09123456789', 'Ren.el_X', 'uploads/lost_found/1781407298_ae1e2150.png', 1, '2026-06-14 03:21:38', 'lost', 'open', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(60) NOT NULL DEFAULT 'general',
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `related_order_item_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `related_order_item_id`, `is_read`, `created_at`) VALUES
(1, 1, 'rental', 'Rental approved: Laptop', 'Your payment for \"Laptop\" has been approved by Pau. Contact: Not provided. Program/Year: Not provided.', 1, 0, '2026-06-14 11:21:03'),
(2, 2, 'rental_sent', 'Rental approved: Laptop', 'You approved \"Laptop\" for buyer Sam Renly Cruzado (phone: 1213243413).', 1, 0, '2026-06-14 11:21:03');

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
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `fullname`, `address`, `phone`, `payment_method`, `total`, `status`, `payment_proof_path`, `created_at`) VALUES
(1, 1, 'Sam Renly Cruzado', 'BLOCK 20', '1213243413', 'Gcash', 200.00, 'confirmed', 'uploads/rental_receipts/1781407235_2758b20f.png', '2026-06-14 03:20:35');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_name_snapshot` varchar(255) NOT NULL,
  `product_image_snapshot` varchar(255) DEFAULT NULL,
  `rate_type_snapshot` varchar(20) DEFAULT NULL,
  `item_type` enum('product','rental','service') NOT NULL DEFAULT 'product',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `seller_id`, `product_name_snapshot`, `product_image_snapshot`, `rate_type_snapshot`, `item_type`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 2, 2, 'Laptop', 'uploads/1781407197_66390650.webp', 'Per Day', 'rental', 1, 200.00, 200.00);

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
  `rental_terms` text DEFAULT NULL,
  `seller_terms_accepted_at` datetime DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`prod_id`, `user_id`, `category_id`, `prod_name`, `prod_desc`, `prod_price`, `prod_image`, `prod_stock`, `location`, `rate_type`, `rental_terms`, `seller_terms_accepted_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Printing Service', 'We provide high-quality printing solutions for businesses, organizations, and individuals. Our services include digital printing, large-format printing, business cards, flyers, brochures, posters, banners, invitations, stickers, and customized promotional materials. We are committed to delivering sharp, vibrant prints, fast turnaround times, competitive pricing, and excellent customer service to help bring your ideas and branding to life.', 10.00, 'uploads/1781406934_185c3196.png', 1, '', NULL, NULL, NULL, 'active', '2026-06-14 03:15:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rental_details`
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
  `gender` varchar(50) DEFAULT NULL,
  `payment_status` varchar(60) DEFAULT NULL,
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `payment_verified_at` datetime DEFAULT NULL,
  `payment_verified_by` int(11) DEFAULT NULL,
  `payment_rejection_reason` text DEFAULT NULL,
  `reservation_status` varchar(60) DEFAULT NULL,
  `rental_terms_accepted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_details`
--

INSERT INTO `rental_details` (`id`, `ref_type`, `ref_id`, `date_from`, `date_to`, `rental_days`, `borrower_name`, `student_no`, `age`, `gender`, `payment_status`, `payment_proof_path`, `payment_verified_at`, `payment_verified_by`, `payment_rejection_reason`, `reservation_status`, `rental_terms_accepted`) VALUES
(2, 'order', 1, '2026-06-09', '2026-06-08', 1, 'baba', 'a', 1, 'Male', 'Reserved', 'uploads/rental_receipts/1781407235_2758b20f.png', '2026-06-14 11:21:03', 2, NULL, 'Reserved', 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_details`
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
-- Table structure for table `terms_acceptances`
--

CREATE TABLE `terms_acceptances` (
  `id` int(11) NOT NULL,
  `acceptance_type` enum('seller','rental') NOT NULL,
  `subject_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `accepted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `terms_text` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms_acceptances`
--

INSERT INTO `terms_acceptances` (`id`, `acceptance_type`, `subject_id`, `user_id`, `accepted_at`, `terms_text`, `user_agent`, `ip_address`) VALUES
(1, 'seller', 1, 1, '2026-06-14 11:15:34', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '::1'),
(3, 'seller', 2, 2, '2026-06-14 11:32:26', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '::1'),
(5, 'rental', 1, 1, '2026-06-14 11:20:35', NULL, NULL, NULL);

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
(1, 'Admin', 'Admin-00', '$2y$10$BlRgUJ/bKa.MkwNIGby/6OuBOy9JOTWfv8FEWgyYCuxVsacEMUvti', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 03:13:04'),
(2, 'Pau', 'ST-100-00', '$2y$10$Wzr.dPuYVU0VLuR3Tw3s1.1MnfUjz4RqE6xF5F3G3GSXgTYExSTdW', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 03:16:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `idx_cart_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `lost_found_claims`
--
ALTER TABLE `lost_found_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lost_claim_item` (`item_id`),
  ADD KEY `idx_lost_claim_user` (`user_id`);

--
-- Indexes for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lost_items_user` (`user_id`),
  ADD KEY `idx_lost_items_type_status` (`type`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_order_item` (`related_order_item_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_unread` (`user_id`,`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`),
  ADD KEY `fk_order_items_seller` (`seller_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`prod_id`),
  ADD KEY `idx_products_user` (`user_id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_status` (`status`);

--
-- Indexes for table `rental_details`
--
ALTER TABLE `rental_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rental_ref` (`ref_type`,`ref_id`),
  ADD KEY `fk_rental_verified_by` (`payment_verified_by`),
  ADD KEY `idx_rental_ref` (`ref_type`,`ref_id`);

--
-- Indexes for table `service_details`
--
ALTER TABLE `service_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_service_ref` (`ref_type`,`ref_id`),
  ADD KEY `idx_service_ref` (`ref_type`,`ref_id`);

--
-- Indexes for table `service_files`
--
ALTER TABLE `service_files`
  ADD PRIMARY KEY (`service_file_id`),
  ADD KEY `idx_service_file_ref` (`ref_type`,`ref_id`);

--
-- Indexes for table `terms_acceptances`
--
ALTER TABLE `terms_acceptances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_terms_acceptance` (`acceptance_type`,`subject_id`),
  ADD KEY `idx_terms_acceptance_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_name` (`user_name`),
  ADD UNIQUE KEY `stud_id` (`stud_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lost_found_claims`
--
ALTER TABLE `lost_found_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lost_items`
--
ALTER TABLE `lost_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `prod_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rental_details`
--
ALTER TABLE `rental_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_details`
--
ALTER TABLE `service_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_files`
--
ALTER TABLE `service_files`
  MODIFY `service_file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `terms_acceptances`
--
ALTER TABLE `terms_acceptances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `lost_found_claims`
--
ALTER TABLE `lost_found_claims`
  ADD CONSTRAINT `fk_lost_claim_item` FOREIGN KEY (`item_id`) REFERENCES `lost_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lost_claim_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD CONSTRAINT `fk_lost_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_order_item` FOREIGN KEY (`related_order_item_id`) REFERENCES `order_items` (`order_item_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_details`
--
ALTER TABLE `rental_details`
  ADD CONSTRAINT `fk_rental_verified_by` FOREIGN KEY (`payment_verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
