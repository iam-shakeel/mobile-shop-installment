-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 12, 2026 at 09:38 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mobile_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnic` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone_number`, `cnic`, `address`, `email`, `created_at`) VALUES
(1, 'Shakeel Khan', '7654345678', '234567654', 'Hayatabad Phase 3 Chowk', 'shakeeldelooks@gmail.com', '2026-03-12 18:02:36'),
(2, 'Mudasir khan', '09878', '45678987', 'peshawar', '', '2026-03-12 19:11:28');

-- --------------------------------------------------------

--
-- Table structure for table `installment_payments`
--

DROP TABLE IF EXISTS `installment_payments`;
CREATE TABLE IF NOT EXISTS `installment_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `installment_plan_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payments_plan` (`installment_plan_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installment_payments`
--

INSERT INTO `installment_payments` (`id`, `installment_plan_id`, `amount`, `payment_date`) VALUES
(1, 1, 50000.00, '2026-03-12'),
(2, 1, 50000.00, '2026-03-12'),
(3, 1, 50000.00, '2026-03-12'),
(4, 4, 5000.00, '2026-03-12'),
(5, 4, 5000.00, '2026-03-12');

-- --------------------------------------------------------

--
-- Table structure for table `installment_plans`
--

DROP TABLE IF EXISTS `installment_plans`;
CREATE TABLE IF NOT EXISTS `installment_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `down_payment` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `duration_months` int NOT NULL DEFAULT '1',
  `monthly_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `start_date` date NOT NULL,
  `next_due_date` date NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `fk_installment_sale` (`sale_id`),
  KEY `idx_installment_due` (`next_due_date`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installment_plans`
--

INSERT INTO `installment_plans` (`id`, `sale_id`, `down_payment`, `total_amount`, `remaining_amount`, `duration_months`, `monthly_amount`, `start_date`, `next_due_date`, `status`) VALUES
(4, 4, 5000.00, 35000.00, 20000.00, 6, 5000.00, '2026-03-12', '2026-06-12', 'active'),
(3, 3, 5000.00, 36000.00, 31000.00, 6, 5167.00, '2026-03-12', '2026-04-12', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cost_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `specifications` text COLLATE utf8mb4_unicode_ci,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `model`, `description`, `price`, `cost_price`, `specifications`, `stock_quantity`, `image_url`, `created_at`) VALUES
(1, 'iphone 17 pro max', 'Apple', '17 Pro Max', '', 560000.00, 520000.00, '', 3, '', '2026-03-12 18:00:12'),
(2, 'Samsung a32', 'Samsung', 'a32', '', 35000.00, 30000.00, '', 1, '', '2026-03-12 19:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sales_customer` (`customer_id`),
  KEY `idx_sales_created_at` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `total_amount`, `paid_amount`, `status`, `payment_method`, `notes`, `created_at`) VALUES
(4, 2, 35000.00, 0.00, 'installment', 'installment', '', '2026-03-12 19:46:32');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_sale_items_sale` (`sale_id`),
  KEY `fk_sale_items_product` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 1, 570000.00, 570000.00),
(2, 2, 2, 1, 35000.00, 35000.00),
(3, 3, 2, 1, 36000.00, 36000.00),
(4, 4, 2, 1, 35000.00, 35000.00);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL,
  `business_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Business Name',
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Office Address',
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '+92 123 456 7890',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `website_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `business_name`, `logo_url`, `address`, `contact`, `updated_at`, `website_url`, `whatsapp_number`) VALUES
(1, 'Insaf Mobile Center', 'assets/uploads/logo_20260313_010504_ee1e9454.png', 'Charsadda', '+92 123 456 7890', '2026-03-13 01:05:04', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$frRXL4XJhmNmau1wbnRB7OBDlV9V1eJWmU1j01.xskGIn8ONaOnxG', '2026-03-12 21:03:39');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
