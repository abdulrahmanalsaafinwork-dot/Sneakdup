-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 10, 2026 at 03:21 PM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u191994003_sneakdup`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `date`, `time_in`, `time_out`) VALUES
(1, 3, '2026-02-20', '2026-02-20 14:49:17', '2026-02-20 14:49:40'),
(2, 3, '2026-02-23', '2026-02-23 01:25:02', '2026-02-23 01:26:44'),
(3, 11, '2026-02-23', '2026-02-23 11:42:21', '2026-02-23 17:59:25'),
(5, 3, '2026-03-10', '2026-03-10 08:30:23', '2026-03-10 08:31:11'),
(6, 3, '2026-06-03', '2026-06-02 18:44:59', '2026-06-02 18:46:14'),
(7, 3, '2026-06-04', '2026-06-04 07:25:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'Alexa', 'alexamaecabilogan@gmail.com', 'Feedback', 'Solid Shoes nyo!', '2026-02-22 16:01:15'),
(2, 'Cha', 'chanaliedajay@gmail.com', 'Feedback', 'penge sapatos', '2026-02-23 10:01:10');

-- --------------------------------------------------------

--
-- Table structure for table `employee_schedules`
--

CREATE TABLE `employee_schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Dumping data for table `employee_schedules`
--

INSERT INTO `employee_schedules` (`id`, `user_id`, `work_date`, `created_at`) VALUES
(1, 15, '2026-03-10', '2026-03-10 08:29:51'),
(2, 3, '2026-03-05', '2026-03-10 08:38:56'),
(3, 3, '2026-04-23', '2026-04-22 11:03:36'),
(4, 3, '2026-06-06', '2026-06-04 05:48:56'),
(5, 17, '2026-06-05', '2026-06-04 07:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_promo_eligible` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_popular` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `description`, `price`, `image`, `is_promo_eligible`, `created_at`, `is_popular`) VALUES
(5, 'New Balance', 'Shoes', 'New Balance 530 Black', 999.00, 'bllc.jpg', 1, '2026-02-13 02:45:28', 0),
(17, 'Nike Socks', 'Accessories', 'Available in different sizes! ', 200.00, 'NIKE-PERFORMANCE-LIGHTWEIGHT-CREW-SOCKS-WHITE-BLACK-SX4704-101-1_900x.webp', 0, '2026-06-01 06:35:45', 0),
(18, 'Air Jordan IX', 'Shoes', 'Air Jordan 4 \" Black Cat \"', 1500.00, 'sdsdwa.jpg', 0, '2026-06-02 04:57:09', 0),
(19, 'Nike Air Max Plus', 'Shoes', 'Nike Air Max Plus TN \" Triple Black \"', 1500.00, '60710576-5cf4-45bd-9061-b3b909d13c1b.jpg', 0, '2026-06-02 05:10:57', 0),
(20, 'New Balance', 'Shoes', 'New Balance 530 \" White Blue \"', 999.00, '0803-NEWMR530SGD00W11H-1.jpg', 1, '2026-06-02 05:41:07', 0),
(21, 'Nike Sabrina II', 'Shoes', 'Nike Sabrina II \" Black Label \"', 2000.00, 'hehea.jpg', 0, '2026-06-02 05:59:59', 0),
(23, 'Nike Book I', 'Shoes', 'Nike Book I \" Halloween \"', 2000.00, 'nike-book-1-halloween-fj4249-003-68e77ba56b72d.webp', 0, '2026-06-02 06:28:01', 0),
(24, 'CloudTec', 'Shoes', 'Cloudtilt Beige & Blue', 1500.00, 'cldlast.jpg', 0, '2026-06-02 07:08:52', 0),
(25, 'Adidas Puma Palermo', 'Shoes', 'Adidas Puma Palermo Tenis', 1500.00, 'pumasbl.jpg', 0, '2026-06-03 06:13:00', 0),
(26, 'Adidas Puma Palermo', 'Shoes', 'Adidas Puma Palermo White Green', 1500.00, 'pumsgren.jpg', 0, '2026-06-03 06:16:05', 0),
(27, 'Nike Kobe XIII', 'Shoes', 'Nike Kobe 8 Protro Venice ', 2000.00, 'nike-kobe-8-protro-venice-beach-2024-dropout-1.jpg', 0, '2026-06-03 06:20:54', 0),
(28, 'Nike Air Force I', 'Shoes', 'Nike Air Force I Low Kobe', 1500.00, 'NikeAirForce1LowKobeBryantForeverWhiteMen_s.webp', 0, '2026-06-03 06:26:00', 1),
(31, 'Nba Jersey', 'Clothes', 'Nba Jersey Victor Wembanyama', 1500.00, 'wb.jpg', 0, '2026-06-03 06:51:46', 0),
(33, 'Nba Jersey', 'Clothes', 'Nba Jersey Shai Gilgeous Alexander', 1500.00, 'download.jpg', 0, '2026-06-03 06:54:44', 0),
(34, 'Nike Dunk Low', 'Shoes', 'Nike Dunk Low University Blue', 999.00, '16815300_33840245_1000.jpg', 1, '2026-06-03 07:08:57', 1),
(35, 'Nike Socks', 'Accessories', 'Available in different sizes!', 200.00, 'blcksocs.jpg', 0, '2026-06-03 19:46:19', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `image`, `stock`) VALUES
(35, 18, '9.5', 'Black', 'j4blckcc.jpg', 5),
(36, 19, '9', 'Black', 'nktntr.jpg', 4),
(37, 20, '9.5', 'White', 'nbwyyt.jpg', 4),
(38, 21, '9.5', 'Black', 'nksbrstr.jpg', 5),
(41, 23, '9.5', 'Black', 'bookfb.jpg', 5),
(42, 24, '9.5', 'Beige-Blue', 'cdfb.jpg', 5),
(43, 5, '9.5', 'Black', 'nbblc.jpg', 3),
(44, 25, '9.5', 'Blue', 'pmms.jpg', 4),
(45, 26, '9.5', 'White-Green', '514269276_122204851358304776_2158554009243337184_n.jpg', 5),
(46, 27, '9.5', 'Venice Beach', 'kobevens.jpg', 5),
(47, 28, '9.5', 'White', '622866024_122221970462304776_4472407750743862479_n.jpg', 4),
(48, 33, 'Large', 'Home', 'download.jpg', 5),
(49, 31, 'Large', 'Home', 'wb.jpg', 5),
(52, 34, '9.5', 'Blue', '471165282_122156390438304776_5600807215983109097_n.jpg', 5),
(53, 18, '9.0', 'Black', 'j4blckcc.jpg', 5),
(54, 19, '9.5', 'Black', 'nktntr.jpg', 5),
(55, 20, '9', 'White', 'nbwyyt.jpg', 5),
(56, 21, '9', 'Black', 'nksbrstr.jpg', 5),
(57, 23, '9', 'Black', 'bookfb.jpg', 5),
(58, 24, '9', 'Beige-Blue', 'cdfb.jpg', 5),
(59, 5, '9', 'Black', 'nbblc.jpg', 5),
(60, 17, 'Small', 'White', 'NIKE-PERFORMANCE-LIGHTWEIGHT-CREW-SOCKS-WHITE-BLACK-SX4704-101-1_900x.webp', 5),
(61, 17, 'Medium', 'White', 'NIKE-PERFORMANCE-LIGHTWEIGHT-CREW-SOCKS-WHITE-BLACK-SX4704-101-1_900x.webp', 5),
(62, 17, 'Large', 'White', 'NIKE-PERFORMANCE-LIGHTWEIGHT-CREW-SOCKS-WHITE-BLACK-SX4704-101-1_900x.webp', 5),
(63, 35, 'Small', 'Black', 'blcksocs.jpg', 5),
(64, 35, 'Medium', 'Black', 'blcksocs.jpg', 5),
(65, 35, 'Large', 'Black', 'blcksocs.jpg', 5),
(66, 28, '9', 'White', NULL, 5);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `variant_info` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `receipt_no`, `user_id`, `product_name`, `variant_info`, `quantity`, `total_price`, `sale_date`) VALUES
(1, '', 2, 'Sabrina 1', '9 - white', 1, 1500.00, '2026-02-12 14:09:21'),
(2, '', 3, 'New Balance', '10 - White-Blue', 1, 2000.00, '2026-02-12 15:14:27'),
(3, '', 2, 'Kobe 8 Protro Venice Beach', '8.5 - Venice Beach', 1, 1700.00, '2026-02-20 05:23:32'),
(4, '', 3, 'New Balance', '9.5 - Black-White', 1, 999.00, '2026-02-20 05:49:53'),
(5, '', 2, 'Nike Book 1', '9.5 - Halloween', 1, 1500.00, '2026-02-20 06:28:21'),
(6, '', 3, 'Kobe 8 Protro Venice Beach', '8.5 - Venice Beach', 1, 1700.00, '2026-02-22 17:25:31'),
(7, '', 11, 'Kobe 8 Protro Venice Beach', '8.5 - Venice Beach', 1, 1700.00, '2026-02-23 03:42:54'),
(8, '', 2, 'Nike Zoom Vomero', '9.5 - Black-White', 1, 1500.00, '2026-02-23 09:51:49'),
(9, '', 2, 'Kobe 8 Protro Venice Beach', '8.5 - Venice Beach', 1, 1700.00, '2026-02-23 10:18:16'),
(10, '', 2, 'Bapesta', '9.5 - White', 1, 1500.00, '2026-02-23 10:27:20'),
(11, '', 2, 'Bapesta', '9.5 - White', 1, 1500.00, '2026-02-23 12:22:06'),
(12, '', 2, 'Sabrina', '9.5 - Black', 1, 2000.00, '2026-02-23 12:36:06'),
(13, '', 17, 'Kobe 8 Protro Venice Beach', '8.5 - Venice Beach', 1, 1700.00, '2026-02-23 12:39:59'),
(14, '', 2, 'Puma Palermo', '10 - White-Green', 1, 999.00, '2026-02-25 05:34:32'),
(15, 'REC-6CA743', 2, 'BAPESTA', '9.5 - White', 1, 1500.00, '2026-02-25 05:45:58'),
(16, 'REC-6CA743', 2, 'NIKE ZOOM VOMERO', '9.5 - Black-White', 1, 1500.00, '2026-02-25 05:45:58'),
(17, 'REC-F0A778', 2, 'BAPESTA', '9.5 - White', 2, 3000.00, '2026-03-10 09:01:03'),
(18, 'REC-838B0B', 2, 'BAPESTA', '9.5 - White', 1, 1500.00, '2026-03-10 09:04:08'),
(19, 'REC-8A7E83', 2, 'NIKE ZOOM VOMERO', '9.5 - Black-White', 1, 1500.00, '2026-03-10 09:07:20'),
(20, 'REC-5EE9CC', 2, 'KOBE 8 PROTRO VENICE BEACH', '8.5 - Venice Beach', 1, 1700.00, '2026-03-10 09:16:53'),
(21, 'REC-5EE9CC', 2, 'BAPESTA', '9.5 - White', 1, 1500.00, '2026-03-10 09:16:53'),
(22, 'REC-5EE9CC', 2, 'NEW BALANCE', '9.5 - Black-White', 1, 999.00, '2026-03-10 09:16:53'),
(23, 'REC-D13388', 2, 'NIKE AIR FORCE I', '9.5 - White', 1, 1500.00, '2026-06-04 07:10:53'),
(24, 'REC-2A1E77', 2, 'NEW BALANCE', '9.5 - Black', 1, 999.00, '2026-06-04 07:12:18'),
(25, 'REC-BAE375', 2, 'NEW BALANCE', '9.5 - Black', 1, 999.00, '2026-06-04 07:12:43'),
(26, 'REC-BAE375', 2, 'NEW BALANCE', '9.5 - White', 1, 999.00, '2026-06-04 07:12:43'),
(27, 'REC-DB1839', 2, 'ADIDAS PUMA PALERMO', '9.5 - Blue', 1, 1500.00, '2026-06-04 07:26:05'),
(28, 'REC-B63152', 3, 'NIKE AIR MAX PLUS', '9 - Black', 1, 1500.00, '2026-06-04 07:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','blocked') DEFAULT 'active',
  `verification_code` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `created_at`, `status`, `verification_code`) VALUES
(2, 'Main Admin', 'admin@shoe.com', '$2y$10$vqpl6IeF7jDUF2do/N5Hr.U7nTDsGfN3od5TlC6Hf927Ko8tqigGa', 'admin', '2026-01-18 02:28:41', 'active', NULL),
(3, 'Dustin', 'dustin@gmail.com', '$2y$10$aAg9JflPUNzamckKXjY..eSPiQ43pYJp41VYcYmJ2JLpVwP9Fvo2C', 'employee', '2026-01-18 02:58:32', 'active', NULL),
(4, 'Dustin', 'tin@gmail.com', '$2y$10$1/4fjoDtg.0UQwANCnA2q.c1ig1xjPMiNp.I4p29BkRKlK4mBCDwS', 'customer', '2026-01-18 03:04:58', 'active', NULL),
(5, 'MOMSHIE BESMONTE', 'MB@YAHOO.COM', '$2y$10$0a22I3j4dKimrYdvP85Wdezv39u1Tx8jcWp6IvjubyPUNNdoYCMii', 'customer', '2026-01-29 10:52:31', 'active', NULL),
(6, 'John', 'john@gma', '$2y$10$lKvnmtRZ4jZeZu8LpzkjwOhbWO.T4CIv7ht/1aHNwdDj2u6bkyO2O', 'customer', '2026-02-09 16:26:12', 'active', NULL),
(7, 'Chanalyn', 'chanalyn@gmail.com', '$2y$10$ScxFMkz.9qf4uH.r51lNy.ZzCViMkkZIMueqBJBbiEqvGAgPtwrEe', 'customer', '2026-02-09 16:29:49', 'active', NULL),
(8, 'Dustin Pogi', 'dustinperrera10@gmail.com', '$2y$10$T4cFUXqDxyUaispqcfQnU.LbkjdNfjrGjH6ctQX9PdzuTynJZtvB.', 'customer', '2026-02-09 17:14:42', 'active', NULL),
(9, 'Dustin 2', 'dustinperrera1@gmail.com', '$2y$10$hU4gt8//FGNGH/W80sNXDu8Hml32jiQ2yQiqLqJX5utBQ3/Jhk3kG', 'customer', '2026-02-12 13:15:13', 'active', NULL),
(10, 'Alexa Cabilogan', 'alexamaecabilogan@gmail.com', '$2y$10$.70o8XlWIYD4PUtrWpf4tu25qA3I/XmaT/ohuHJz.L2qpqiyHccJK', 'customer', '2026-02-18 09:57:13', 'active', NULL),
(11, 'John Malsi', 'malsi@gmail.com', '$2y$10$rlfjdZJASfQ4Cr93VyTjfusjfM8QANIxgbyY9zz1bQJ882sLFAEN6', 'employee', '2026-02-20 07:12:55', 'active', NULL),
(13, 'Chanalie Dajay', 'chanaliedajay@gmail.com', '$2y$10$H/sclaeLtfSpAILi1m4SV.sj4b/nuI5ZVSEWx7df3NGzzd6vum2/.', 'customer', '2026-02-23 09:40:47', '', '181601'),
(14, 'john Marc', 'jmbesmonte11@gmail.com', '$2y$10$z1FrZJE0VvtY7Cci6Jygx.o/r3zLX77XSDv8ElUYf56xgprFYEU0a', 'customer', '2026-02-23 09:42:18', 'active', NULL),
(15, 'John Besmonte', 'johnbesmonte@gmail.com', '$2y$10$466Fp5WUgLS7iRUGvQkO2.sQyJDA/hl/4mlMJXqSahgjSN3Yulld.', 'employee', '2026-02-23 09:45:21', 'active', NULL),
(16, 'Lester Lagajino', 'lstrcaparos@gmail.com', '$2y$10$Rbv/Zb65qZmI1NA5OhKNmuNOFnq//HR5cz9hckeNQrVcG1rB9qsIG', 'customer', '2026-02-23 12:16:14', 'active', NULL),
(17, 'Lester Lagajino', 'lesterlagajino@gmail.com', '$2y$10$wtB1jndVjuMnmC3ZW0RwROdXEIumo8/CvnYrt9B6S7.1TpHAwz8Y.', 'employee', '2026-02-23 12:38:51', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`user_id`,`work_date`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
