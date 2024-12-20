-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 20, 2024 at 11:15 AM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u438242536_cafe_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `archived_sales`
--

CREATE TABLE `archived_sales` (
  `archive_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` datetime(6) NOT NULL,
  `total_amount` float NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_status` varchar(255) NOT NULL,
  `discount_type` enum('senior','pwd') DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `original_amount` decimal(10,2) DEFAULT NULL,
  `archived_date` datetime(6) NOT NULL,
  `archived_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_sales`
--

INSERT INTO `archived_sales` (`archive_id`, `order_id`, `customer_id`, `order_date`, `total_amount`, `user_id`, `payment_status`, `discount_type`, `discount_amount`, `original_amount`, `archived_date`, `archived_by`) VALUES
(1, 43, 52, '2024-12-02 13:54:38.000000', 246, 1, 'paid', NULL, NULL, NULL, '2024-12-02 16:25:00.000000', 1),
(2, 33, 38, '2024-11-29 15:37:15.000000', 5713, 1, 'paid', NULL, NULL, NULL, '2024-12-06 03:37:14.000000', 1),
(3, 28, 32, '2024-11-19 08:48:35.000000', 15, 1, 'paid', NULL, NULL, NULL, '2024-12-06 03:37:14.000000', 1),
(4, 45, 54, '2024-12-05 03:29:50.000000', 223, 1, 'paid', NULL, NULL, NULL, '2024-12-06 03:37:38.000000', 1),
(5, 41, 50, '2024-12-02 10:34:15.000000', 100, 1, 'paid', NULL, NULL, NULL, '2024-12-06 03:38:08.000000', 1),
(6, 34, 39, '2024-11-29 15:53:03.000000', 5000, 1, 'paid', NULL, NULL, NULL, '2024-12-06 14:09:51.000000', 1),
(7, 52, 61, '2024-12-06 15:20:41.000000', 24, 1, 'paid', NULL, NULL, NULL, '2024-12-06 15:20:51.000000', 1),
(8, 71, 80, '2024-12-18 08:43:50.000000', 226, 1, 'paid', NULL, NULL, NULL, '2024-12-18 08:44:11.000000', 1),
(9, 84, 102, '2024-12-19 10:44:08.000000', 200, 1, 'paid', NULL, NULL, NULL, '2024-12-19 10:49:42.000000', 1),
(10, 92, 130, '2024-12-19 11:02:32.000000', 100, 1, 'paid', NULL, NULL, NULL, '2024-12-19 11:02:41.000000', 1),
(11, 94, 132, '2024-12-19 11:16:23.000000', 900, 1, 'paid', NULL, NULL, NULL, '2024-12-19 11:26:20.000000', 1),
(12, 63, 72, '2024-12-18 02:13:19.000000', 250, 1, 'paid', NULL, NULL, NULL, '2024-12-19 11:43:14.000000', 1),
(13, 101, 139, '2024-12-19 11:52:10.000000', 388, 1, 'paid', NULL, NULL, NULL, '2024-12-19 11:52:20.000000', 1),
(14, 102, 140, '2024-12-19 12:19:21.000000', 165, 4, 'paid', NULL, NULL, NULL, '2024-12-19 12:41:26.000000', 1),
(15, 100, 138, '2024-12-19 11:51:20.000000', 223, 1, 'paid', NULL, NULL, NULL, '2024-12-19 12:42:17.000000', 4),
(16, 99, 137, '2024-12-19 11:50:39.000000', 590.4, 1, 'paid', NULL, NULL, NULL, '2024-12-19 12:48:32.000000', 1),
(17, 97, 135, '2024-12-19 11:28:06.000000', 80, 1, 'paid', NULL, NULL, NULL, '2024-12-19 12:55:45.000000', 4),
(18, 96, 134, '2024-12-19 11:27:10.000000', 369, 1, 'paid', NULL, NULL, NULL, '2024-12-19 12:55:59.000000', 4),
(19, 95, 133, '2024-12-19 11:21:03.000000', 885.6, 1, 'paid', NULL, NULL, NULL, '2024-12-19 12:56:09.000000', 4),
(20, 93, 131, '2024-12-19 11:13:18.000000', 900, 1, 'paid', NULL, NULL, NULL, '2024-12-19 12:56:24.000000', 1),
(21, 91, 129, '2024-12-19 11:01:05.000000', 100, 1, 'paid', NULL, NULL, NULL, '2024-12-19 12:56:31.000000', 1),
(22, 74, 83, '2024-12-19 05:52:32.000000', 200, 15, 'paid', NULL, NULL, NULL, '2024-12-19 13:18:02.000000', 15),
(23, 122, 163, '2024-12-19 14:55:51.000000', 143.2, 1, 'Paid', NULL, NULL, NULL, '2024-12-19 15:05:43.000000', 1),
(24, 125, 166, '2024-12-19 15:02:41.000000', 143.2, 1, 'paid', NULL, NULL, NULL, '2024-12-19 15:33:59.000000', 1),
(25, 124, 165, '2024-12-19 14:57:45.000000', 143.2, 1, 'paid', NULL, NULL, NULL, '2024-12-19 15:42:22.000000', 1),
(26, 121, 162, '2024-12-19 14:53:48.000000', 165, 1, 'Paid', NULL, NULL, NULL, '2024-12-20 00:17:02.000000', 1),
(27, 120, 161, '2024-12-19 14:53:11.000000', 179, 1, 'Paid', NULL, NULL, NULL, '2024-12-20 00:17:02.000000', 1),
(28, 128, 169, '2024-12-19 21:00:05.000000', 80, 1, 'paid', NULL, NULL, NULL, '2024-12-20 00:17:23.000000', 1),
(29, 132, 173, '2024-12-19 21:20:46.000000', 80, 1, 'paid', NULL, NULL, NULL, '2024-12-20 09:44:59.000000', 1),
(30, 129, 170, '2024-12-19 21:10:10.000000', 320, 1, 'paid', NULL, NULL, NULL, '2024-12-20 09:45:19.000000', 1),
(31, 130, 171, '2024-12-19 21:16:52.000000', 286.4, 1, 'paid', NULL, NULL, NULL, '2024-12-20 10:06:10.000000', 1),
(32, 131, 172, '2024-12-19 21:20:06.000000', 80, 1, 'paid', 'pwd', 20.00, 100.00, '2024-12-20 10:13:25.000000', 1),
(33, 104, 142, '2024-12-19 13:16:13.000000', 143.2, 4, 'paid', NULL, NULL, NULL, '2024-12-20 10:55:05.000000', 4);

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
(4, 58, 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `total_amount` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `Name`, `total_amount`) VALUES
(22, 'test1', 100),
(23, 'HIZOLA, JOHN CARLO A.', 1090),
(24, 'HIZOLA, JOHN CARLO A.', 2000),
(25, 'test1', 400),
(26, 'test1', 400),
(27, 'test1', 400),
(29, 'Xyrell Dave', 20),
(30, 'Xyrell Dave', 20),
(31, 'Xyrell Dave', 20),
(32, 'Xyrell Dave', 20),
(33, 'Lauraa Kate Roa', 200),
(34, 'Lauraa Kate Roa', 10000),
(35, 'Lauraa Kate Roa', 50),
(36, 'John Carlo Arceo Hizola', 600),
(37, 'John Carlo Arceo Hizola', 300),
(38, 'hizz', 10000),
(39, 'kjhkjh', 6000),
(40, 'hizola, John Carlo A. JC', 500),
(41, 'dear', 500),
(42, 'test3', 300),
(43, 'test2', 1234),
(44, 'HIZOLA, JOHN CARLO A.', 400),
(45, 'test2', 20),
(46, 'test2', 200),
(47, 'HIZOLA, JOHN CARLO A.', 200),
(48, 'test2', 500),
(49, 'fitness roms', 400),
(50, 'HIZOLA, JOHN CARLO A.', 120),
(51, 'hizola, John Carlo A. JC', 400),
(52, 'fitness roms', 300),
(53, 'John Carlo Arceo Hizola', 666),
(54, 'fitness roms', 344),
(55, 'HIZOLA, JOHN CARLO A.', 234),
(56, 'Xyrell Dave', 50),
(57, 'CHRIS JEN IAN DAVA ROA', 200),
(58, 'John Carlo Arceo Hizola', 1324),
(59, 'HIZOLA, JOHN CARLO A.', 345),
(60, 'trg', 1333),
(61, '24', 24),
(62, 'hi hiz ', 700),
(63, 'checking', 1000),
(64, 'Chris', 1500),
(65, 'Laura', 250),
(66, 'Laura', 500),
(67, 'Bug', 500),
(68, 'Lee', 300),
(69, 'Hiz', 230),
(70, 'bug', 500),
(71, 'asda', 300),
(72, 'lee', 300),
(73, 'aj', 300),
(74, 'lee', 500),
(75, 'denz', 300),
(76, 'hiz', 300),
(77, 'jade', 300),
(78, 'bug', 500),
(79, 'sasa', 500),
(80, 'test', 1234),
(81, 'test', 313),
(82, 'Sir Loudel', 200),
(83, 'Sir Loudel', 200),
(84, 'Sir Loudel', 200),
(85, 'Sir Loudel', 200),
(86, 'Sir Loudel', 200),
(87, 'Sir Loudel', 200),
(88, 'Laura', 100),
(89, 'Laura', 1000),
(90, 'Laura', 10),
(91, 'Laura', 1000),
(92, 'Laura', 2000),
(93, 'HIZOLA, JOHN CARLO A.', 133),
(94, 'HIZOLA, JOHN CARLO A.', 133),
(95, 'HIZOLA, JOHN CARLO A.', 133),
(96, 'HIZOLA, JOHN CARLO A.', 133),
(97, 'HIZOLA, JOHN CARLO A.', 133),
(98, 'HIZOLA, JOHN CARLO A.', 133),
(99, 'HIZOLA, JOHN CARLO A.', 133),
(100, 'HIZOLA, JOHN CARLO A.', 133),
(101, 'HIZOLA, JOHN CARLO A.', 133),
(102, 'HIZOLA, JOHN CARLO A.', 500),
(103, 'test2', 5678),
(104, 'HIZOLA, JOHN CARLO A.', 34567),
(105, 'fitness roms', 1234),
(106, 'HIZOLA, JOHN CARLO A.', 2234),
(107, 'HIZOLA, JOHN CARLO A.', 2134),
(108, 'HIZOLA, JOHN CARLO A.', 2134),
(109, 'HIZOLA, JOHN CARLO A.', 2134),
(110, 'HIZOLA, JOHN CARLO A.', 2134),
(111, 'HIZOLA, JOHN CARLO A.', 2134),
(112, 'HIZOLA, JOHN CARLO A.', 2134),
(113, 'HIZOLA, JOHN CARLO A.', 2134),
(114, 'HIZOLA, JOHN CARLO A.', 2134),
(115, 'HIZOLA, JOHN CARLO A.', 2134),
(116, 'HIZOLA, JOHN CARLO A.', 2134),
(117, 'HIZOLA, JOHN CARLO A.', 2134),
(118, 'HIZOLA, JOHN CARLO A.', 2134),
(119, 'HIZOLA, JOHN CARLO A.', 2134),
(120, 'HIZOLA, JOHN CARLO A.', 2134),
(121, 'HIZOLA, JOHN CARLO A.', 2134),
(122, 'HIZOLA, JOHN CARLO A.', 2134),
(123, 'HIZOLA, JOHN CARLO A.', 2134),
(124, 'HIZOLA, JOHN CARLO A.', 2134),
(125, 'HIZOLA, JOHN CARLO A.', 2134),
(126, 'HIZOLA, JOHN CARLO A.', 2134),
(127, 'test2', 532),
(128, 'HIZOLA, JOHN CARLO A.', 231),
(129, '34', 253),
(130, '   _', 6446),
(131, 'test2', 1000),
(132, 'fitness roms', 1000),
(133, 'HIZOLA, JOHN CARLO A.', 1222),
(134, 'John Carlo Arceo Hizola', 2141),
(135, 'John Carlo Arceo Hizola', 1231),
(136, 'John Carlo Arceo Hizola', 214),
(137, 'HIZOLA, JOHN CARLO A.', 14456),
(138, 'fitness roms', 1121),
(139, 'HIZOLA, JOHN CARLO A.', 1345),
(140, 'fitness roms', 1424),
(141, 'Laura', 250),
(142, 'fitness roms', 1442),
(143, 'Hiz', 250),
(144, 'Laura', 250),
(145, 'Laura', 200),
(146, 'Laura', 200),
(147, 'Chris', 500),
(148, 'HIZ', 200),
(155, 'xy', 200),
(156, 'xy', 200),
(157, 'xy', 200),
(158, 'xy', 200),
(159, 'xy', 200),
(160, 'xy', 200),
(161, 'xy', 200),
(162, 'Hiz', 200),
(163, 'Hiz', 200),
(164, 'xy', 200),
(165, 'Chris', 200),
(166, 'xy', 200),
(167, 'HIZOLA, JOHN CARLO A.', 134),
(168, 'HIZOLA, JOHN CARLO A.', 134),
(169, 'HIZOLA, JOHN CARLO A.', 134),
(170, 'test2', 1355),
(171, 'fitness roms', 1111),
(172, 'HIZOLA, JOHN CARLO A.', 1442),
(173, 'John Carlo Arceo Hizola', 1442),
(174, 'fitness roms', 145);

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
  `unit_of_measure` varchar(50) DEFAULT 'pieces'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `stock_quantity`, `last_updated`, `user_id`, `item_name`, `unit_of_measure`) VALUES
(68, 0, '2024-12-19 13:36:32.000000', 0, 'Blueberry Cheesecake', 'pieces'),
(75, 48, '2024-12-19 14:55:51.000000', 0, 'Matcha  Cake', 'pieces'),
(76, 41, '2024-12-19 21:16:52.000000', 0, 'Hershey Cheesecake', 'pieces'),
(78, 49, '2024-12-19 14:57:45.000000', 0, 'Strawberry Cheesecake', 'pieces'),
(90, 490, '2024-12-20 10:06:00.000000', 0, 'Burger ', 'pieces'),
(91, 500, '2024-12-19 15:30:01.000000', 0, 'MilkTea', 'pieces'),
(92, 500, '2024-12-19 15:30:58.000000', 0, 'Fries', 'pieces');

--
-- Triggers `inventory`
--
DELIMITER $$
CREATE TRIGGER `update_ingredient_name` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
    UPDATE product_ingredients 
    SET ingredient_name = NEW.item_name 
    WHERE inventory_id = NEW.inventory_id;
END
$$
DELIMITER ;

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
  `payment_status` varchar(255) NOT NULL,
  `discount_type` enum('senior','pwd') DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `original_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`order_id`, `customer_id`, `order_date`, `total_amount`, `user_id`, `payment_status`, `discount_type`, `discount_amount`, `original_amount`) VALUES
(18, 22, '2024-11-17 16:55:08.000000', 54, 1, 'paid', NULL, NULL, NULL),
(19, 23, '2024-11-18 10:18:03.000000', 184, 1, 'paid', NULL, NULL, NULL),
(21, 25, '2024-11-18 17:29:08.000000', 246, 1, 'paid', NULL, NULL, NULL),
(22, 26, '2024-11-18 18:01:09.000000', 240, 1, 'paid', NULL, NULL, NULL),
(23, 27, '2024-11-18 18:07:10.000000', 243, 1, 'paid', NULL, NULL, NULL),
(35, 40, '2024-11-29 16:42:51.000000', 399, 1, 'paid', NULL, NULL, NULL),
(46, 55, '2024-12-06 06:22:28.000000', 226, 1, 'paid', NULL, NULL, NULL),
(47, 56, '2024-12-06 10:28:16.000000', 30, 1, 'paid', NULL, NULL, NULL),
(48, 57, '2024-12-06 11:44:00.000000', 130, 1, 'paid', NULL, NULL, NULL),
(49, 58, '2024-12-06 13:51:24.000000', 130, 1, 'paid', NULL, NULL, NULL),
(50, 59, '2024-12-06 14:11:12.000000', 130, 1, 'paid', NULL, NULL, NULL),
(51, 60, '2024-12-06 15:11:21.000000', 100, 1, 'paid', NULL, NULL, NULL),
(55, 64, '2024-12-13 04:20:45.000000', 1307, 1, 'paid', NULL, NULL, NULL),
(56, 65, '2024-12-13 06:19:07.000000', 250, 1, 'paid', NULL, NULL, NULL),
(57, 66, '2024-12-14 03:17:55.000000', 250, 1, 'paid', NULL, NULL, NULL),
(58, 67, '2024-12-15 11:54:42.000000', 476, 1, 'paid', NULL, NULL, NULL),
(59, 68, '2024-12-16 04:46:57.000000', 250, 1, 'paid', NULL, NULL, NULL),
(60, 69, '2024-12-16 04:49:39.000000', 226, 1, 'paid', NULL, NULL, NULL),
(61, 70, '2024-12-18 01:34:18.000000', 476, 1, 'paid', NULL, NULL, NULL),
(62, 71, '2024-12-18 01:59:57.000000', 250, 1, 'paid', NULL, NULL, NULL),
(64, 73, '2024-12-18 02:13:48.000000', 250, 1, 'paid', NULL, NULL, NULL),
(65, 74, '2024-12-18 02:14:16.000000', 250, 1, 'paid', NULL, NULL, NULL),
(66, 75, '2024-12-18 02:15:35.000000', 250, 1, 'paid', NULL, NULL, NULL),
(67, 76, '2024-12-18 02:15:55.000000', 226, 1, 'paid', NULL, NULL, NULL),
(68, 77, '2024-12-18 02:16:13.000000', 250, 1, 'paid', NULL, NULL, NULL),
(69, 78, '2024-12-18 02:16:55.000000', 449, 1, 'paid', NULL, NULL, NULL),
(70, 79, '2024-12-18 02:17:39.000000', 250, 1, 'paid', NULL, NULL, NULL),
(73, 82, '2024-12-19 05:52:30.000000', 200, 15, 'paid', NULL, NULL, NULL),
(75, 84, '2024-12-19 05:52:35.000000', 200, 15, 'paid', NULL, NULL, NULL),
(76, 85, '2024-12-19 05:52:35.000000', 200, 15, 'paid', NULL, NULL, NULL),
(77, 86, '2024-12-19 05:52:36.000000', 200, 15, 'paid', NULL, NULL, NULL),
(78, 87, '2024-12-19 05:52:36.000000', 200, 15, 'paid', NULL, NULL, NULL),
(79, 88, '2024-12-19 05:53:33.000000', 0, 15, 'paid', NULL, NULL, NULL),
(81, 90, '2024-12-19 06:01:14.000000', 0, 14, 'paid', NULL, NULL, NULL),
(82, 91, '2024-12-19 06:11:45.000000', 200, 15, 'paid', NULL, NULL, NULL),
(83, 92, '2024-12-19 06:15:21.000000', 1221, 14, 'paid', NULL, NULL, NULL),
(85, 103, '2024-12-19 10:44:55.000000', 199, 14, 'paid', NULL, NULL, NULL),
(86, 104, '2024-12-19 10:45:33.000000', 399, 14, 'paid', NULL, NULL, NULL),
(87, 105, '2024-12-19 10:46:12.000000', 0, 1, 'paid', NULL, NULL, NULL),
(88, 106, '2024-12-19 10:52:55.000000', 250, 1, 'paid', NULL, NULL, NULL),
(89, 127, '2024-12-19 10:56:27.000000', 0, 1, 'paid', NULL, NULL, NULL),
(90, 128, '2024-12-19 11:00:41.000000', 100, 1, 'paid', NULL, NULL, NULL),
(103, 141, '2024-12-19 13:08:59.000000', 250, 1, 'paid', NULL, NULL, NULL),
(105, 143, '2024-12-19 13:19:58.000000', 200, 1, 'paid', NULL, NULL, NULL),
(106, 144, '2024-12-19 13:36:32.000000', 250, 1, 'paid', NULL, NULL, NULL),
(107, 145, '2024-12-19 13:39:18.000000', 179, 1, 'paid', NULL, NULL, NULL),
(108, 146, '2024-12-19 13:40:03.000000', 179, 1, 'paid', NULL, NULL, NULL),
(109, 147, '2024-12-19 13:42:27.000000', 399, 1, 'paid', NULL, NULL, NULL),
(110, 148, '2024-12-19 14:11:39.000000', 98.4, 1, 'paid', NULL, NULL, NULL),
(117, 155, '2024-12-19 14:27:58.000000', 143.2, 1, 'paid', NULL, NULL, NULL),
(118, 156, '2024-12-19 14:38:51.000000', 98.4, 1, 'paid', NULL, NULL, NULL),
(119, 160, '2024-12-19 14:45:01.000000', 143.2, 1, 'paid', NULL, NULL, NULL),
(123, 164, '2024-12-19 14:57:09.000000', 143.2, 1, 'paid', NULL, NULL, NULL),
(126, 167, '2024-12-19 20:12:47.000000', 80, 1, 'paid', NULL, NULL, NULL),
(127, 168, '2024-12-19 20:52:02.000000', 80, 1, 'paid', NULL, NULL, NULL),
(133, 174, '2024-12-20 10:06:00.000000', 80, 1, 'paid', 'senior', 20.00, 100.00);

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
(66, 46, 59, 1, 226),
(68, 48, 51, 1, 130),
(69, 49, 51, 1, 130),
(70, 50, 51, 1, 130),
(76, 55, 51, 1, 1307),
(77, 56, 51, 1, 250),
(78, 57, 51, 1, 250),
(79, 58, 51, 1, 250),
(80, 58, 59, 1, 226),
(81, 59, 51, 1, 250),
(82, 60, 59, 1, 226),
(83, 61, 51, 1, 250),
(84, 61, 59, 1, 226),
(85, 62, 51, 1, 250),
(87, 64, 51, 1, 250),
(88, 65, 51, 1, 250),
(89, 66, 51, 1, 250),
(90, 67, 59, 1, 226),
(91, 68, 51, 1, 250),
(92, 69, 51, 1, 250),
(93, 69, 58, 1, 199),
(94, 70, 51, 1, 250),
(97, 73, 59, 2, 100),
(99, 75, 59, 2, 100),
(100, 76, 59, 2, 100),
(101, 77, 59, 2, 100),
(102, 78, 59, 2, 100),
(105, 82, 49, 1, 200),
(106, 83, 59, 2, 100),
(107, 83, 49, 2, 200),
(108, 83, 58, 1, 199),
(109, 83, 60, 1, 299),
(110, 83, 73, 1, 123),
(112, 85, 58, 1, 199),
(113, 86, 58, 1, 199),
(114, 86, 49, 1, 200),
(115, 88, 51, 1, 250),
(116, 90, 59, 1, 100),
(132, 103, 51, 1, 250),
(134, 105, 51, 1, 250),
(135, 106, 51, 1, 250),
(136, 107, 50, 1, 179),
(137, 108, 50, 1, 179),
(138, 109, 54, 1, 399),
(139, 110, 73, 1, 123),
(146, 117, 50, 1, 179),
(147, 118, 73, 1, 123),
(148, 119, 50, 1, 179),
(152, 123, 50, 1, 179),
(155, 126, 59, 1, 100),
(156, 127, 59, 1, 100),
(162, 133, 59, 1, 100);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` float NOT NULL,
  `category` varchar(255) NOT NULL,
  `image` blob NOT NULL,
  `size` varchar(50) DEFAULT 'base-size'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `name`, `price`, `category`, `image`, `size`) VALUES
(36, 'Pearl Black', 159, 'Drinks', 0x313733343235383931355f424c2e6a7067, 'Small'),
(38, 'Okinawa', 165, 'Drinks', 0x313733343235383531335f4f4b2e706e67, 'Small'),
(40, 'Wintermelon', 165, 'Drinks', 0x313733343236333833315f57542e706e67, 'Small'),
(42, 'Hokkaido', 165, 'Drinks', 0x313733343235383630395f484f6b2e6a666966, 'Small'),
(46, 'Matcha', 165, 'Drinks', 0x313733343235383633385f4d2e6a7067, 'Small'),
(48, 'Dark Choco', 179, 'Chocolate Series', 0x313733343235343939355f43482e6a7067, 'Standard'),
(49, 'Chocolate Cheesecake', 200, 'Chocolate Series', 0x313733343235343338395f5443532e6a7067, 'Small'),
(50, 'Hershey Cheesecake', 179, 'Chocolate Series', 0x313733343235353430385f48432e6a7067, 'Standard'),
(51, 'Blueberry Cheesecake', 250, 'Chocolate Series', 0x313733343235333437335f4242432e6a7067, 'Small'),
(52, 'Strawberry Cheesecake', 179, 'Chocolate Series', 0x313733343235353531325f53432e6a7067, 'Standard'),
(53, 'Matcha Cheesecake', 179, 'Chocolate Series', 0x313733343235383831315f4d432e6a706567, 'Standard'),
(54, 'Pepperoni Pizza', 399, 'Pizza', 0x313733343235393332365f507a2e6a666966, 'Small'),
(58, 'Classic Burger', 199, 'Burger & Fries', 0x313733343235343236395f434c422e6a7067, 'Standard'),
(59, 'Cheese Burger', 100, 'Burger & Fries', 0x313733343235333539385f43422e6a7067, 'Standard'),
(60, 'Double Cheese Burger', 299, 'Burger & Fries', 0x313733343235343434335f4343422e6a7067, 'Standard'),
(63, 'Nachos', 120, 'Nachos', 0x313733343235393137305f4e432e6a7067, 'Standard'),
(73, 'Fries', 123, 'Burger & Fries', 0x313733343235393030345f46522e6a7067, 'Standard');

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `product_ingredient_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `ingredient_name` varchar(255) DEFAULT NULL,
  `quantity_needed` float NOT NULL DEFAULT 1,
  `unit_of_measure` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`product_ingredient_id`, `product_id`, `inventory_id`, `ingredient_name`, `quantity_needed`, `unit_of_measure`) VALUES
(217, 50, 76, 'Hershey Cheesecake', 1, 'pieces'),
(218, 53, 75, 'Matcha  Cake', 1, 'pieces'),
(219, 52, 78, 'Strawberry Cheesecake', 1, 'pieces'),
(234, 51, 68, 'Blueberry Cheesecake', 1, 'pieces'),
(235, 58, 90, 'Burger ', 1, 'pieces'),
(236, 59, 90, 'Burger ', 1, 'pieces'),
(237, 60, 90, 'Burger ', 1, 'pieces'),
(238, 73, 92, 'Fries', 50, 'pieces'),
(239, 40, 91, 'MilkTea', 1, 'pieces'),
(240, 46, 91, 'MilkTea', 1, 'pieces'),
(241, 48, 91, 'MilkTea', 1, 'pieces'),
(242, 42, 91, 'MilkTea', 1, 'pieces'),
(243, 38, 91, 'MilkTea', 1, 'pieces'),
(244, 36, 91, 'MilkTea', 1, 'pieces');

-- --------------------------------------------------------

--
-- Table structure for table `receipt`
--

CREATE TABLE `receipt` (
  `receipt_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `generated_at` datetime(6) NOT NULL,
  `total_amount` float NOT NULL,
  `discount_applied` tinyint(1) NOT NULL DEFAULT 0,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `original_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`receipt_id`, `order_id`, `generated_at`, `total_amount`, `discount_applied`, `discount_amount`, `original_amount`) VALUES
(33, 35, '2024-11-29 16:42:51.000000', 399, 0, 0.00, 0.00),
(34, 35, '2024-11-29 16:42:51.000000', 399, 0, 0.00, 0.00),
(47, 46, '2024-12-06 06:22:29.000000', 226, 0, 0.00, 0.00),
(48, 47, '2024-12-06 10:28:21.000000', 30, 0, 0.00, 0.00),
(49, 48, '2024-12-06 11:44:01.000000', 130, 0, 0.00, 0.00),
(50, 49, '2024-12-06 13:51:25.000000', 130, 0, 0.00, 0.00),
(51, 50, '2024-12-06 14:11:12.000000', 130, 0, 0.00, 0.00),
(52, 51, '2024-12-06 15:11:21.000000', 100, 0, 0.00, 0.00),
(56, 55, '2024-12-13 04:20:46.000000', 1307, 0, 0.00, 0.00),
(57, 56, '2024-12-13 06:19:07.000000', 250, 0, 0.00, 0.00),
(58, 57, '2024-12-14 03:17:56.000000', 250, 0, 0.00, 0.00),
(59, 58, '2024-12-15 11:54:42.000000', 476, 0, 0.00, 0.00),
(60, 59, '2024-12-16 04:46:57.000000', 250, 0, 0.00, 0.00),
(61, 60, '2024-12-16 04:49:40.000000', 226, 0, 0.00, 0.00),
(62, 61, '2024-12-18 01:34:19.000000', 476, 0, 0.00, 0.00),
(63, 62, '2024-12-18 01:59:57.000000', 250, 0, 0.00, 0.00),
(65, 64, '2024-12-18 02:13:49.000000', 250, 0, 0.00, 0.00),
(66, 65, '2024-12-18 02:14:16.000000', 250, 0, 0.00, 0.00),
(67, 66, '2024-12-18 02:15:35.000000', 250, 0, 0.00, 0.00),
(68, 67, '2024-12-18 02:15:55.000000', 226, 0, 0.00, 0.00),
(69, 68, '2024-12-18 02:16:13.000000', 250, 0, 0.00, 0.00),
(70, 69, '2024-12-18 02:16:55.000000', 449, 0, 0.00, 0.00),
(71, 70, '2024-12-18 02:17:39.000000', 250, 0, 0.00, 0.00),
(74, 73, '2024-12-19 05:52:33.000000', 200, 0, 0.00, 0.00),
(76, 75, '2024-12-19 05:52:36.000000', 0, 0, 0.00, 0.00),
(77, 76, '2024-12-19 05:52:36.000000', 0, 0, 0.00, 0.00),
(78, 77, '2024-12-19 05:52:36.000000', 0, 0, 0.00, 0.00),
(79, 78, '2024-12-19 05:52:36.000000', 0, 0, 0.00, 0.00),
(80, 79, '2024-12-19 05:53:33.000000', 0, 0, 0.00, 0.00),
(82, 81, '2024-12-19 06:01:14.000000', 0, 0, 0.00, 0.00),
(83, 82, '2024-12-19 06:11:46.000000', 200, 0, 0.00, 0.00),
(84, 83, '2024-12-19 06:15:21.000000', 1221, 0, 0.00, 0.00),
(86, 85, '2024-12-19 10:44:56.000000', 199, 0, 0.00, 0.00),
(87, 86, '2024-12-19 10:45:33.000000', 399, 0, 0.00, 0.00),
(88, 87, '2024-12-19 10:46:12.000000', 0, 0, 0.00, 0.00),
(89, 88, '2024-12-19 10:52:55.000000', 250, 0, 0.00, 0.00),
(90, 89, '2024-12-19 10:56:27.000000', 0, 0, 0.00, 0.00),
(91, 90, '2024-12-19 11:00:41.000000', 100, 0, 0.00, 0.00),
(104, 103, '2024-12-19 13:09:00.000000', 250, 0, 0.00, 0.00),
(106, 105, '2024-12-19 13:19:58.000000', 200, 0, 0.00, 0.00),
(107, 108, '2024-12-19 13:40:03.000000', 179, 0, 0.00, 0.00),
(108, 109, '2024-12-19 13:42:28.000000', 399, 0, 0.00, 0.00),
(109, 110, '2024-12-19 14:11:39.000000', 98.4, 0, 0.00, 0.00),
(110, 117, '2024-12-19 14:27:59.000000', 143.2, 0, 0.00, 0.00),
(111, 118, '2024-12-19 14:38:52.000000', 98.4, 0, 0.00, 0.00),
(112, 119, '2024-12-19 14:45:01.000000', 143.2, 0, 0.00, 0.00),
(116, 123, '2024-12-19 14:57:11.000000', 143.2, 0, 0.00, 0.00),
(119, 126, '2024-12-19 20:12:47.000000', 80, 0, 0.00, 0.00),
(120, 127, '2024-12-19 20:52:02.000000', 80, 0, 0.00, 0.00),
(125, 133, '2024-12-20 10:06:00.000000', 80, 1, 20.00, 100.00);

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
(24, 35, 399, '2024-11-29 16:42:51.000000', 1),
(35, 46, 226, '2024-12-06 06:22:29.000000', 1),
(36, 47, 30, '2024-12-06 10:28:19.000000', 1),
(37, 48, 130, '2024-12-06 11:44:01.000000', 1),
(38, 49, 130, '2024-12-06 13:51:24.000000', 1),
(39, 50, 130, '2024-12-06 14:11:12.000000', 1),
(40, 51, 100, '2024-12-06 15:11:21.000000', 1),
(44, 55, 1307, '2024-12-13 04:20:46.000000', 1),
(45, 56, 250, '2024-12-13 06:19:07.000000', 1),
(46, 57, 250, '2024-12-14 03:17:56.000000', 1),
(47, 58, 476, '2024-12-15 11:54:42.000000', 1),
(48, 59, 250, '2024-12-16 04:46:57.000000', 1),
(49, 60, 226, '2024-12-16 04:49:40.000000', 1),
(50, 61, 476, '2024-12-18 01:34:18.000000', 1),
(51, 62, 250, '2024-12-18 01:59:57.000000', 1),
(53, 64, 250, '2024-12-18 02:13:48.000000', 1),
(54, 65, 250, '2024-12-18 02:14:16.000000', 1),
(55, 66, 250, '2024-12-18 02:15:35.000000', 1),
(56, 67, 226, '2024-12-18 02:15:55.000000', 1),
(57, 68, 250, '2024-12-18 02:16:13.000000', 1),
(58, 69, 449, '2024-12-18 02:16:55.000000', 1),
(59, 70, 250, '2024-12-18 02:17:39.000000', 1),
(62, 73, 200, '2024-12-19 05:52:31.000000', 15),
(64, 75, 200, '2024-12-19 05:52:36.000000', 15),
(65, 76, 200, '2024-12-19 05:52:36.000000', 15),
(66, 77, 0, '2024-12-19 05:52:36.000000', 15),
(67, 78, 0, '2024-12-19 05:52:36.000000', 15),
(68, 79, 0, '2024-12-19 05:53:33.000000', 15),
(70, 81, 0, '2024-12-19 06:01:14.000000', 14),
(71, 82, 200, '2024-12-19 06:11:45.000000', 15),
(72, 83, 1221, '2024-12-19 06:15:21.000000', 14),
(74, 85, 199, '2024-12-19 10:44:55.000000', 14),
(75, 86, 399, '2024-12-19 10:45:33.000000', 14),
(76, 87, 0, '2024-12-19 10:46:12.000000', 1),
(77, 88, 250, '2024-12-19 10:52:55.000000', 1),
(78, 89, 0, '2024-12-19 10:56:27.000000', 1),
(79, 90, 100, '2024-12-19 11:00:41.000000', 1),
(92, 103, 250, '2024-12-19 13:09:00.000000', 1),
(94, 105, 200, '2024-12-19 13:19:58.000000', 1),
(95, 108, 179, '2024-12-19 13:40:03.000000', 1),
(96, 109, 399, '2024-12-19 13:42:28.000000', 1),
(97, 110, 98.4, '2024-12-19 14:11:39.000000', 1),
(98, 117, 143.2, '2024-12-19 14:27:59.000000', 1),
(99, 118, 98.4, '2024-12-19 14:38:51.000000', 1),
(100, 119, 143.2, '2024-12-19 14:45:01.000000', 1),
(104, 123, 143.2, '2024-12-19 14:57:10.000000', 1),
(107, 126, 80, '2024-12-19 20:12:47.000000', 1),
(108, 127, 80, '2024-12-19 20:52:02.000000', 1);

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
(2, 'admin1', '$2y$10$klQVrS4bOo.luLJyIGDUMu7ps6w24o7dZD039IvjXE6TbC/ISbHNm', 1),
(3, 'admin2', '$2y$10$g94zqr7GiGuf4AycQeWrUuxkmSQBR11vblOdl9nSeSiTqY.8hHcHO', 1),
(4, 'staff1', '$2y$10$dztoSfznH4AdUBGHq2V4pOVucey92OY.Sr2Gtrwi.XOmtwEB21KIG', 0),
(5, 'staff2', '$2y$10$zwXssga5L1NVVg4scxWGV.EKJ3bAj53mwAh5BExJfoP/jz7kgKge2', 0),
(14, 'CafeManager', '$2y$10$RU1iMBEfMFk3tGUm55zwdezmchHfgEWkMCUS5ktQZEJ9hU5t.lePa', 1),
(15, 'staff101', '$2y$10$tYGMD4HlI0ErCQcrnU1r5udujWQ7H/uS.Z/6BUepn7jvxfyiPDdfm', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archived_sales`
--
ALTER TABLE `archived_sales`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `archived_by` (`archived_by`);

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
  ADD KEY `inventory_ibfk_1` (`user_id`),
  ADD KEY `idx_item_name` (`item_name`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_ibfk_1` (`user_id`),
  ADD KEY `idx_discount_type` (`discount_type`),
  ADD KEY `idx_order_date` (`order_date`);

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
  ADD PRIMARY KEY (`product_ingredient_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `receipt`
--
ALTER TABLE `receipt`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_discount_applied` (`discount_applied`),
  ADD KEY `idx_generated_at` (`generated_at`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `sales_ibfk_1` (`user_id`);

--
-- Indexes for table `user_acc`
--
ALTER TABLE `user_acc`
  ADD PRIMARY KEY (`User_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `archived_sales`
--
ALTER TABLE `archived_sales`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  MODIFY `product_ingredient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT for table `receipt`
--
ALTER TABLE `receipt`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `user_acc`
--
ALTER TABLE `user_acc`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `archived_sales`
--
ALTER TABLE `archived_sales`
  ADD CONSTRAINT `archived_sales_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `user_acc` (`User_id`);

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
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_acc` (`User_id`),
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
  ADD CONSTRAINT `product_ingredients_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `product_ingredients_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
