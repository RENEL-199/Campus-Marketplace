-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 03:14 AM
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
-- Database: `campus_market`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(41, 9, 23, 1, '2026-05-16 00:51:40');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fullname` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `created_at`, `fullname`, `address`, `phone`) VALUES
(1, 10, 20.00, '2026-05-01 10:54:54', 'Grace', 'Sto.Tomas Batangas', '09123456789'),
(18, 10, 500.00, '2026-05-01 10:55:57', 'Grace', 'Sto.Tomas Batangas', '09123456789'),
(19, 11, 80.00, '2026-05-01 11:10:05', 'Grace', 'Sto.Tomas Batangas', '09123456789'),
(20, 11, 100.00, '2026-05-01 11:11:27', 'Grace', 'Sto.Tomas Batangas', '09123456789'),
(21, 9, 45.00, '2026-05-03 06:28:27', 'Grace', 'Sto.Tomas Batangas', '09123456789'),
(22, 9, 500.00, '2026-05-06 06:16:23', 'Grace', 'Sto.Tomas Batangas', '09123456789'),
(23, 9, 1000.00, '2026-05-06 07:13:47', 'Grace', 'Sto.Tomas Batangas', '09123456789');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(14, 17, 20, 1, 20.00),
(15, 18, 19, 1, 500.00),
(16, 19, 24, 2, 40.00),
(17, 20, 32, 1, 100.00),
(18, 21, 21, 1, 45.00),
(19, 22, 19, 1, 500.00),
(20, 23, 33, 10, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` varchar(50) NOT NULL,
  `image` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` int(11) DEFAULT 1,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category`, `created_at`, `stock`, `user_id`) VALUES
(19, 'Casio Calculator', 'Reliable and easy-to-use Casio calculator perfect for students and professionals. Built for fast and accurate calculations, it supports essential math functions needed for school, exams, and daily tasks. Durable, compact, and designed for long-lasting performance.', '500', 'uploads/1777631927_Casio-Scientific-Calculator-PNG-Clipart.png', 'Electronics', '2026-05-01 10:38:47', 3, 9),
(20, 'Faber-Castell Ballpen', 'A durable and reliable ballpen from Faber-Castell, perfect for students and professionals. Provides smooth ink flow, comfortable grip, and clean writing for long study or work sessions. Ideal for everyday school use.', '20', 'uploads/1777632029_OIP.webp', 'School Supplies', '2026-05-01 10:40:29', 19, 9),
(21, 'Yello Paper Pad', 'High-quality yellow paper pad ideal for notes, assignments, and everyday writing. Smooth paper texture for easy writing with pens or pencils. Perfect for students, office use, and school activities.', '45', 'uploads/1777632186_OIP (1).webp', 'School Supplies', '2026-05-01 10:43:06', 2, 9),
(22, 'Sketch Pad', 'Gently used sketch pad ideal for drawing, sketching, and creative work. Pages are still in good condition and suitable for art students or hobbyists. Perfect for practicing illustrations, designs, and quick sketches at an affordable price.', '120', 'uploads/1777632341_OIP.jpg', 'Preloved', '2026-05-01 10:45:41', 1, 10),
(23, 'Lenovo Laptop', 'Reliable Lenovo laptop suitable for school, work, and everyday tasks. Offers smooth performance for browsing, documents, online classes, and light productivity work. Designed with durability and efficiency for students and professionals.', '30000', 'uploads/1777632495_OIP (2).webp', 'Electronics', '2026-05-01 10:48:15', 3, 10),
(24, 'Note Book', 'Notebook for sale! Brand new, Good for taking notes and all.\r\nColor: black\r\nPage: 80\r\n', '40', 'uploads/1777633334_OIP (1).jpg', 'School Supplies', '2026-05-01 11:02:14', 18, 11),
(26, 'Aqua Flask', 'Aqua Flask na blue, bilhin niyo na malinis naman to kahit gamit ko na', '200', 'uploads/1777633457_R.png', 'Preloved', '2026-05-01 11:04:17', 1, 11),
(27, 'Printing Service', 'Location: Near PUP\r\nFree Delivery\r\nPa print na kayo Mura lang\r\nColored: 15\r\nBlack and white: 10', '10', 'uploads/1777633545_OIP (2).jpg', 'Services', '2026-05-01 11:05:45', 100000, 11),
(28, 'Printer Ink', 'Printer Ink for sale! pang ink sa printer niyo', '100', 'uploads/1777633627_OIP (3).webp', 'Others', '2026-05-01 11:07:07', 19, 11),
(29, 'RJ45', 'Ito na RJ45 para di mahirap mag hanap ng bibilhan', '5', 'uploads/1777633664_OIP (4).webp', 'Electronics', '2026-05-01 11:07:44', 50, 11),
(30, 'UTP WIRE', 'Untwisted Wire for sale. Para di na rin kayo mag hanap\r\nper yard bentahan', '50', 'uploads/1777633724_OIP (3).jpg', 'Others', '2026-05-01 11:08:44', 20, 11),
(32, 'Blue MOUSE', 'BILHIN NIYO NA', '100', 'uploads/1777633866_OIP (4).jpg', 'Electronics', '2026-05-01 11:11:06', 0, 11);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `full_name` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'uploads/default.png',
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `full_name`, `student_id`, `course`, `year_level`, `age`, `gender`, `birthday`, `profile_pic`, `remember_token`) VALUES
(9, 'admin', '$2y$10$Nvjq95.T.G1BQLndeQIAo.SPsTC4J4hLvwmhaYbL/f7u/MRp0IXNG', '2026-05-01 18:33:48', '', '', '', '', 0, '', '0000-00-00', 'uploads/default.png', '94eeba5193a8a5e81021ac16b6759ed4cc8736eb1b9b389bfef4547d187bd9c1'),
(10, 'sam', '$2y$10$/jmwjy47wP8gBn9rN6.tMu6X/GG4wLdyELpuVlyx.IoJ.1rhM1uuK', '2026-05-01 18:43:28', 'Grace', 'ST-XXXX_XXX_X', 'BSIT', '2', 20, 'Female', '2006-12-12', 'uploads/1777632622_WIN_20260321_14_44_23_Pro.jpg', NULL),
(11, 'renly', '$2y$10$bDfC9caTwkue/W1/CIdWbO0PPGT0L.s6SpEByX7nYXqDyN5UhcNlS', '2026-05-01 19:00:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/default.png', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
