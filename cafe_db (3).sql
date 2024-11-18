-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2024 at 04:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cafe_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`user_id`, `product_id`, `quantity`) VALUES
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(2, 12, 1),
(2, 12, 1),
(2, 12, 1),
(2, 12, 1),
(2, 12, 1),
(2, 12, 1),
(2, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1),
(1, 12, 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `total_amount` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `Name`, `total_amount`) VALUES
(2, 'HIZOLA, JOHN CARLO A.', '120'),
(3, 'HIZOLA, JOHN CARLO A.', '120'),
(4, 'HIZOLA, JOHN CARLO A.', '40'),
(5, 'HIZOLA, JOHN CARLO A.', '120'),
(6, 'HIZOLA, JOHN CARLO A.', '120'),
(7, 'HIZOLA, JOHN CARLO A.', '20'),
(8, 'HIZOLA, JOHN CARLO A.', '120'),
(9, 'HIZOLA, JOHN CARLO A.', '120'),
(10, 'HIZOLA, JOHN CARLO A.', '120'),
(11, 'HIZOLA, JOHN CARLO A.', '120'),
(12, 'HIZOLA, JOHN CARLO A.', '120'),
(13, 'HIZOLA, JOHN CARLO A.', '20'),
(14, 'HIZOLA, JOHN CARLO A.', '120'),
(15, 'HIZOLA, JOHN CARLO A.', '120'),
(16, 'Lauraa Kate Roa', '20'),
(17, 'Lauraa Kate Roa', '20'),
(18, 'Lauraa Kate Roa', '20'),
(19, 'Lauraa Kate Roa', '20'),
(20, 'John Carlo Arceo Hizola', '50');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `last_updated` datetime(6) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `stock_quantity`, `last_updated`, `user_id`, `item_name`, `unit_of_measure`) VALUES
(18, 87, '2024-11-17 18:32:08.000000', 0, 'Oil', 'liters'),
(19, 11, '2024-11-17 18:32:08.000000', 0, 'Potato', 'pieces');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` datetime(6) NOT NULL,
  `total_amount` float NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`order_id`, `customer_id`, `order_date`, `total_amount`, `user_id`, `payment_status`) VALUES
(1, 3, '2024-11-17 11:58:15.000000', 43, 1, 'paid'),
(2, 4, '2024-11-17 12:00:18.000000', 32, 1, 'paid'),
(3, 5, '2024-11-17 12:12:23.000000', 0, 2, 'paid'),
(4, 6, '2024-11-17 12:15:37.000000', 42, 2, 'paid'),
(5, 7, '2024-11-17 12:16:33.000000', 10, 1, 'paid'),
(6, 10, '2024-11-17 12:24:29.000000', 66, 1, 'paid'),
(7, 11, '2024-11-17 12:28:35.000000', 10, 2, 'paid'),
(8, 12, '2024-11-17 12:30:10.000000', 10, 2, 'paid'),
(9, 13, '2024-11-17 12:49:54.000000', 10, 1, 'paid'),
(10, 14, '2024-11-17 12:52:50.000000', 10, 1, 'paid'),
(11, 15, '2024-11-17 13:00:31.000000', 5, 1, 'paid'),
(14, 18, '2024-11-17 18:14:52.000000', 11, 1, 'paid'),
(15, 19, '2024-11-17 18:31:37.000000', 11, 1, 'paid'),
(16, 20, '2024-11-17 18:32:08.000000', 11, 1, 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 12, 3, 11),
(2, 1, 13, 2, 5),
(3, 2, 12, 2, 11),
(4, 2, 13, 2, 5),
(5, 3, 12, 1, 11),
(6, 3, 13, 1, 5),
(7, 4, 13, 4, 5),
(8, 4, 12, 2, 11),
(9, 5, 13, 2, 5),
(10, 6, 12, 6, 11),
(11, 7, 13, 2, 5),
(12, 8, 13, 2, 5),
(13, 9, 13, 2, 5),
(14, 10, 13, 2, 5),
(15, 11, 13, 1, 5),
(18, 14, 12, 1, 11),
(19, 15, 12, 1, 11),
(20, 16, 12, 1, 11);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` float NOT NULL,
  `category` varchar(255) NOT NULL,
  `image` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `name`, `price`, `category`, `image`) VALUES
(12, 'Fries', 11, 'Burger & Fries', 0x313733313633373534335f6c6f676f2e706e67),
(13, 'Coffee', 5, 'Iced Coffee', 0x313733313633383832345f6c6f676f2e706e67);

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_needed` decimal(10,2) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`id`, `product_id`, `inventory_id`, `quantity_needed`, `unit_of_measure`) VALUES
(3, 12, 18, 4.00, 'liters'),
(4, 12, 19, 3.00, 'pieces');

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `size_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`size_id`, `product_id`, `size_name`, `price`) VALUES
(6, 12, 'Large', 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_size_ingredients`
--

CREATE TABLE `product_size_ingredients` (
  `size_id` int(11) NOT NULL,
  `inventory_id` int(50) NOT NULL,
  `quantity_needed` decimal(10,2) NOT NULL,
  `unit_of_measure` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt`
--

CREATE TABLE `receipt` (
  `receipt_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `generated_at` datetime(6) NOT NULL,
  `total_amount` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`receipt_id`, `order_id`, `generated_at`, `total_amount`) VALUES
(1, 10, '2024-11-17 12:52:50.000000', 10),
(2, 11, '2024-11-17 13:00:31.000000', 5),
(3, 14, '2024-11-17 18:14:52.000000', 11),
(4, 14, '2024-11-17 18:14:52.000000', 11),
(5, 15, '2024-11-17 18:31:37.000000', 11),
(6, 15, '2024-11-17 18:31:37.000000', 11),
(7, 16, '2024-11-17 18:32:08.000000', 11),
(8, 16, '2024-11-17 18:32:08.000000', 11);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `total_sales` float NOT NULL,
  `sales_date` datetime(6) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `order_id`, `total_sales`, `sales_date`, `user_id`) VALUES
(1, 8, 10, '2024-11-17 12:30:10.000000', 2),
(2, 9, 10, '2024-11-17 12:49:54.000000', 1),
(3, 10, 10, '2024-11-17 12:52:50.000000', 1),
(4, 11, 5, '2024-11-17 13:00:31.000000', 1),
(5, 14, 11, '2024-11-17 18:14:52.000000', 1),
(6, 15, 11, '2024-11-17 18:31:37.000000', 1),
(7, 16, 11, '2024-11-17 18:32:08.000000', 1);

-- --------------------------------------------------------

--
-- Table structure for table `size_ingredients`
--

CREATE TABLE `size_ingredients` (
  `size_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_acc`
--

CREATE TABLE `user_acc` (
  `User_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_acc`
--

INSERT INTO `user_acc` (`User_id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$/mZoUSDLsZRD/YXcvvlxS.7YQ5F.yt7mCtWjqlRZOb3BVuAbw13EG', 1),
(2, 'admin1', '$2y$10$klQVrS4bOo.luLJyIGDUMu7ps6w24o7dZD039IvjXE6TbC/ISbHNm', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `inventory_ibfk_1` (`user_id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_ibfk_1` (`user_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`size_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_size_ingredients`
--
ALTER TABLE `product_size_ingredients`
  ADD KEY `size_id` (`size_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `receipt`
--
ALTER TABLE `receipt`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `sales_ibfk_1` (`user_id`);

--
-- Indexes for table `size_ingredients`
--
ALTER TABLE `size_ingredients`
  ADD PRIMARY KEY (`size_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `user_acc`
--
ALTER TABLE `user_acc`
  ADD PRIMARY KEY (`User_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `size_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `receipt`
--
ALTER TABLE `receipt`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_acc`
--
ALTER TABLE `user_acc`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_acc` (`User_id`);

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD CONSTRAINT `product_ingredients_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`),
  ADD CONSTRAINT `product_ingredients_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `product_size_ingredients`
--
ALTER TABLE `product_size_ingredients`
  ADD CONSTRAINT `product_size_ingredients_ibfk_1` FOREIGN KEY (`size_id`) REFERENCES `product_sizes` (`size_id`),
  ADD CONSTRAINT `product_size_ingredients_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`);

--
-- Constraints for table `receipt`
--
ALTER TABLE `receipt`
  ADD CONSTRAINT `receipt_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_acc` (`User_id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`);

--
-- Constraints for table `size_ingredients`
--
ALTER TABLE `size_ingredients`
  ADD CONSTRAINT `size_ingredients_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`),
  ADD CONSTRAINT `size_ingredients_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `product_sizes` (`size_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
