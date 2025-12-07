-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2025 at 11:56 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

USE food_db;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `food_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('super_admin','manager','staff') DEFAULT 'manager',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `fullname`, `phone`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', '123', 'admin@food.com', 'Quản trị viên chính', '0123456789', 'super_admin', 'active', '2025-11-12 08:56:32', '2025-10-11 09:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `customer_id`, `created_at`) VALUES
(3, 3, '2025-10-23 06:55:51'),
(4, 4, '2025-10-29 08:20:10'),
(5, 7, '2025-11-12 04:32:28'),
(6, 8, '2025-11-12 08:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `cart_id` int(10) UNSIGNED NOT NULL,
  `food_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `food_id`, `quantity`, `added_at`) VALUES
(27, 4, 41, 1, '2025-11-04 07:44:49'),
(28, 3, 30, 1, '2025-11-04 12:03:15'),
(29, 3, 20, 1, '2025-11-10 07:23:07'),
(30, 3, 32, 1, '2025-11-10 07:29:44'),
(42, 6, 42, 2, '2025-11-12 08:59:54'),
(44, 6, 41, 2, '2025-11-12 09:03:37');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Món chính','Hải sản','Đồ chiên','Lẩu','Đồ chay','Salad','Súp','Khai vị','Bánh ngọt','Đồ uống','Khác') DEFAULT 'Khác',
  `cuisine` enum('vietnamese','korean','western','japanese','chinese','thai','other') DEFAULT 'vietnamese',
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`, `cuisine`, `description`, `image`, `created_at`) VALUES
(1, 'Món chính', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(2, 'Hải sản', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(3, 'Đồ chiên', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(4, 'Lẩu', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(5, 'Đồ chay', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(6, 'Salad', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(7, 'Súp', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(8, 'Khai vị', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(9, 'Bánh ngọt', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(10, 'Đồ uống', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57'),
(11, 'Khác', 'Khác', 'vietnamese', NULL, NULL, '2025-10-09 17:27:57');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','viewed','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`, `responded_at`) VALUES
(1, 'Nhu Quynh', 'nhuquynhdothi4@gmail.com', NULL, NULL, 'ăn no', 'new', '2025-10-11 13:54:45', NULL),
(4, 'Hoang Long', '2431540078@vaa.edu', NULL, NULL, 'ăn chay\r\n', 'new', '2025-10-27 09:10:27', NULL),
(5, 'Hoang Long', '2431540078@vaa.edu', '0909972350', NULL, 'ăn món hàn', 'new', '2025-11-04 06:59:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `membership` enum('normal','silver','gold','vip') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `full_name`, `phone`, `address`, `membership`, `created_at`) VALUES
(3, 3, 'Hoang Long', '0909972350', '123 tân phú', 'normal', '2025-10-23 06:55:51'),
(4, 4, 'Khánh Trình', '12341234', 'tiền giang', 'normal', '2025-10-29 08:20:10'),
(7, 10, 'Minh Nhat', '12341234', 'cà mau', 'normal', '2025-11-12 04:32:28'),
(8, 6, 'Nhu Quỳnh', '0909972351', '234 gò vấp', 'normal', '2025-11-12 08:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `food_id` int(10) UNSIGNED NOT NULL,
  `reviewer_name` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `response` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `customer_id`, `food_id`, `reviewer_name`, `message`, `response`, `rating`, `created_at`, `responded_at`) VALUES
(1, 3, 20, NULL, 'ngon đáng để mua lại', 'cám ơn bạn', 5, '2025-11-10 07:22:02', '2025-11-10 02:43:56'),
(5, 7, 41, 'Minh Nhat', 'ngon đáng mua lại', NULL, 5, '2025-11-12 08:51:14', NULL),
(10, 8, 41, 'Nhu Quỳnh', 'Ngon tuyệt', NULL, 5, '2025-11-12 08:59:10', NULL),
(11, 8, 42, 'Nhu Quỳnh', 'Rất ngon', NULL, 5, '2025-11-12 09:00:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `type` enum('normal','new','bestseller') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `category_id`, `name`, `description`, `price`, `image`, `available`, `type`, `created_at`) VALUES
(20, 1, 'Beef Steak', 'Thịt bò mềm, sốt đỏ đậm vị Âu', 200000.00, 'beef.jpg', 1, 'bestseller', '2025-10-09 17:29:41'),
(21, 2, 'Cá Hồi Nướng', 'Thơm lừng, béo ngậy, giàu omega', 100000.00, 'cá hồi.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(22, 9, 'Bánh nướng dâu', 'Ngọt dịu, trái cây tươi, phủ sốt ngọt', 80000.00, 'bánh ngọt.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(23, 9, 'Flan', 'Mềm mịn, béo ngậy, ngọt ngào tan chảy', 30000.00, 'plan.jpg', 1, 'bestseller', '2025-10-09 17:29:41'),
(24, 6, 'Salad rau củ', 'Món khai vị tươi mát', 50000.00, 'rau.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(25, 1, 'Cơm gà xé', 'Thịt mềm, cơm dẻo, nước chấm đậm đà', 50000.00, 'cơm gà.jpg', 1, 'new', '2025-10-09 17:29:41'),
(26, 1, 'Mì vịt quay', 'Sợi mì dai, vịt quay vàng giòn', 60000.00, 'mì.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(27, 3, 'Gà rán', 'Vỏ giòn rụm, thịt mềm, ngon khó cưỡng', 40000.00, 'gà rán.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(28, 1, 'Cơm trộn Hàn Quốc', 'Cơm trộn, rau củ, sốt cay, trứng', 60000.00, 'cơm trộn.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(29, 9, 'Tiramisu', 'Hương vị cà phê, béo ngậy, mềm mịn', 35000.00, 'tira.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(30, 9, 'Brownnie', 'Đậm vị socola, ngọt ngào tan chảy', 50000.00, 'brownni.jpg', 1, 'new', '2025-10-09 17:29:41'),
(31, 9, 'Bánh phô mai dâu', 'Béo ngậy, vị chua ngọt', 50000.00, 'mouse.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(32, 9, 'Kem Caramel', 'Mát lạnh, dịu ngọt', 40000.00, 'kem caramel.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(33, 10, 'Sinh tố kem dâu', 'Dâu tươi mát, kem béo ngậy', 40000.00, 'sinh tố.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(34, 8, 'Đậu hũ lạnh', 'Thanh mát, mềm mịn, đậu nành nguyên vị', 30000.00, 'đậu.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(35, 6, 'Salad đào phô mai', 'Vị chua ngọt từ đào kết hợp với phô mai béo', 70000.00, 'traicay.jpg', 1, 'bestseller', '2025-10-09 17:29:41'),
(36, 6, 'Salad trộn dầu ôliu', 'Lựa chọn lành mạnh, giàu dưỡng chất', 70000.00, 'rau trộn.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(37, 7, 'Súp nấm thập cẩm', 'Thơm ngon, bổ dưỡng', 50000.00, 'súp.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(38, 1, 'Cơm cà ri', 'Thơm, cay bùng vị', 70000.00, 'cơm cà ri.jpg', 1, 'normal', '2025-10-09 17:29:41'),
(40, 1, 'Sushi', 'Tươi ngon,Hấp dẫn', 50000.00, 'foods/1760184284_sushi.jpg', 1, 'new', '2025-10-11 12:04:44'),
(41, 1, 'Gà Roti', 'Thơm ngon, khó cưỡng', 90000.00, 'foods/1760196153_gà.jpg', 1, 'bestseller', '2025-10-11 15:22:33'),
(42, 11, 'Tacos', 'Thơm, ngon, độc lạ', 40000.00, 'foods/1761558703_tacos.jpg', 1, 'new', '2025-10-27 09:51:43');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `shipping_address` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `total` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `shipping_address`, `status`, `total`, `created_at`) VALUES
(34, 7, 'cà mau', 'completed', 300000.00, '2025-11-12 07:25:55'),
(35, 8, '234 gò vấp', 'pending', 90000.00, '2025-11-12 08:58:54');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `food_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `food_id`, `quantity`, `price`) VALUES
(38, 34, 30, 6, 50000.00),
(39, 35, 41, 1, 90000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('cash','credit_card','momo','zalo_pay') DEFAULT 'cash',
  `status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `method`, `status`, `created_at`) VALUES
(20, 34, 300000.00, 'cash', 'paid', '2025-11-12 07:25:55'),
(21, 35, 90000.00, 'cash', 'pending', '2025-11-12 08:58:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `token` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `token`, `created_at`) VALUES
(3, 'Hoang Long', '$2y$10$BRoY53rtX80FNqQjEAxCKelnjUtbSHjC3bg7zwRZfUYmkWEJdX1He', '2431540078@vaa', NULL, '2025-10-23 06:54:13'),
(4, 'Khánh Trình', '$2y$10$NvTbVHZH6EU1KN6j09G5IOGjgY1cX9BoWP6g4qBBAzXY7dvpU2zhC', '2431540111@vaa.edu.vn', NULL, '2025-10-29 08:19:35'),
(6, 'Đỗ Thị Như Quỳnh', '$2y$10$qdiwDXggCPKnaBo1KAENjOEc9./3hqFjRfWXo.m5j0S0FxMoCu8T6', 'nhuquynhdothi4@gmail.com', NULL, '2025-11-11 06:57:53'),
(9, 'Lê Nguyễn Ngọc Tâm', '$2y$10$GrFO4SM7VBwpyOkZVmsmWOWAvKCf0jTwOdwXuazFsQv3QUu8qonBW', '2431540084@vaa.edu.vn', NULL, '2025-11-11 07:34:23'),
(10, 'Phạm Minh Nhật', '$2y$10$/e4ftLrbnL18WJWo7.nI7ube5iMOMes2xTgNeqlm1SfmmMheE3zy6', '2431540096@vaa.edu.vn', '00316465bb9ed1ed76341a37b2626b31', '2025-11-11 07:37:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
