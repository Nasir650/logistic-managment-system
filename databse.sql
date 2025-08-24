-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 24, 2025 at 06:37 AM
-- Server version: 10.6.22-MariaDB-cll-lve
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wildlife_logi`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room` varchar(64) NOT NULL,
  `shipment_id` int(11) DEFAULT NULL,
  `sender_user_id` int(11) NOT NULL,
  `sender_role` enum('admin','driver','customer') NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `room`, `shipment_id`, `sender_user_id`, `sender_role`, `body`, `created_at`) VALUES
(1, 'shipment:3', 3, 4, 'driver', 'nuguy', '2025-08-20 09:44:51'),
(2, 'shipment:3', 3, 4, 'driver', 'hy', '2025-08-20 09:44:51'),
(3, 'admin_driver:4', NULL, 7, 'admin', 'uyiuyiuyiu', '2025-08-20 09:51:45'),
(4, 'admin_driver:4', NULL, 7, 'admin', 'HY', '2025-08-20 09:51:45'),
(5, 'admin_driver:4', NULL, 7, 'admin', 'hy', '2025-08-20 09:56:31'),
(6, 'shipment:1', 1, 7, 'admin', 'hy customer', '2025-08-20 09:57:03'),
(7, 'shipment:3', 3, 4, 'driver', 'hy', '2025-08-20 10:01:06'),
(8, 'shipment:1', 1, 7, 'admin', 'message not going', '2025-08-22 14:39:22');

-- --------------------------------------------------------

--
-- Table structure for table `driver_profiles`
--

CREATE TABLE `driver_profiles` (
  `user_id` int(11) NOT NULL,
  `license_number` varchar(60) NOT NULL,
  `vehicle_make` varchar(100) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(50) DEFAULT NULL,
  `capacity_lbs` int(11) DEFAULT NULL,
  `availability` tinyint(1) DEFAULT 1,
  `rating` decimal(2,1) DEFAULT 5.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `driver_profiles`
--

INSERT INTO `driver_profiles` (`user_id`, `license_number`, `vehicle_make`, `vehicle_model`, `vehicle_plate`, `capacity_lbs`, `availability`, `rating`) VALUES
(3, 'CDL-458796', 'Freightliner', 'M2', 'ABC-1234', 5000, 1, 4.8),
(4, 'kbk-456465', '2000', '23', '76576', 567, 1, 5.0);

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `cargo_type` varchar(100) DEFAULT NULL,
  `container_size` varchar(50) DEFAULT NULL,
  `weight_lbs` int(11) DEFAULT NULL,
  `volume_cuft` int(11) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `pickup_name` varchar(150) DEFAULT NULL,
  `pickup_address1` varchar(255) DEFAULT NULL,
  `pickup_city` varchar(100) DEFAULT NULL,
  `pickup_state` varchar(64) DEFAULT NULL,
  `pickup_zip` varchar(20) DEFAULT NULL,
  `pickup_contact` varchar(120) DEFAULT NULL,
  `pickup_phone` varchar(50) DEFAULT NULL,
  `pickup_datetime` datetime DEFAULT NULL,
  `delivery_name` varchar(150) DEFAULT NULL,
  `delivery_address1` varchar(255) DEFAULT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_state` varchar(64) DEFAULT NULL,
  `delivery_zip` varchar(20) DEFAULT NULL,
  `delivery_contact` varchar(120) DEFAULT NULL,
  `delivery_phone` varchar(50) DEFAULT NULL,
  `delivery_deadline` datetime DEFAULT NULL,
  `status` enum('pending','assigned','en_route_to_pickup','at_pickup','in_transit','delivered','cancelled') DEFAULT 'pending',
  `base_rate` decimal(10,2) DEFAULT 0.00,
  `distance_fee` decimal(10,2) DEFAULT 0.00,
  `surcharge` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `order_number`, `customer_id`, `driver_id`, `cargo_type`, `container_size`, `weight_lbs`, `volume_cuft`, `special_instructions`, `pickup_name`, `pickup_address1`, `pickup_city`, `pickup_state`, `pickup_zip`, `pickup_contact`, `pickup_phone`, `pickup_datetime`, `delivery_name`, `delivery_address1`, `delivery_city`, `delivery_state`, `delivery_zip`, `delivery_contact`, `delivery_phone`, `delivery_deadline`, `status`, `base_rate`, `distance_fee`, `surcharge`, `total_amount`, `paid`, `created_at`, `updated_at`) VALUES
(1, 'ORD-2025-74211', 5, 4, 'General Goods', 'Medium', 87, 6546, '', 'y', 'Df', 'Khushab', '—', '41000', 'Nasir Khan', '1233434355', '2025-08-30 10:33:00', 'hf', 'shawala khushab', 'khushab', '—', '41000', 'Nasir Khan', '1233434355', '2025-09-05 10:33:00', 'delivered', 250.00, 0.00, 0.00, 250.00, 0, '2025-08-19 06:33:29', '2025-08-19 06:55:44'),
(2, 'ORD-2025-93991', 5, 4, 'Oversized Load', 'Extra Large', 43, 3453, 'rte', 'y', 'Df', 'Lahore', 'lahore', '41000', 'Nasir Khan', '01233434355', '2025-08-19 11:05:00', 'hf', 'shawala khushab', 'Minawali', 'pakistan', '41000', 'Nasir Khan', '1233434355', '2025-08-28 11:06:00', 'delivered', 600.00, 0.00, 50.00, 650.00, 0, '2025-08-19 07:06:23', '2025-08-19 07:09:44'),
(3, 'ORD-2025-68208', 5, 4, 'General Goods', 'Medium', 45, 456, '6757', '564', 'fdhgf', '654', 'yutu', '6576', 'hgdfh', '657567', '2025-08-20 10:46:00', 'bfds', 'fdg', 'hfch', 'gdfhg', '5476547', 'bdh', 'r6576', '2025-08-29 10:46:00', 'assigned', 250.00, 0.00, 0.00, 250.00, 0, '2025-08-20 06:46:39', '2025-08-20 06:47:45');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_status_history`
--

CREATE TABLE `shipment_status_history` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipment_status_history`
--

INSERT INTO `shipment_status_history` (`id`, `shipment_id`, `user_id`, `status`, `note`, `created_at`) VALUES
(1, 1, 5, 'pending', 'Shipment created', '2025-08-19 06:33:29'),
(2, 1, 7, 'assigned', 'Admin assigned driver', '2025-08-19 06:41:22'),
(3, 1, 7, 'assigned', 'Admin assigned driver', '2025-08-19 06:44:24'),
(4, 1, 7, 'assigned', 'Admin assigned driver', '2025-08-19 06:50:35'),
(5, 1, 4, 'en_route_to_pickup', 'Driver accepted', '2025-08-19 06:51:43'),
(6, 1, 4, 'en_route_to_pickup', NULL, '2025-08-19 06:53:03'),
(7, 1, 4, 'at_pickup', NULL, '2025-08-19 06:53:09'),
(8, 1, 4, 'at_pickup', NULL, '2025-08-19 06:53:14'),
(9, 1, 4, 'delivered', NULL, '2025-08-19 06:55:44'),
(10, 2, 5, 'pending', 'Shipment created', '2025-08-19 07:06:23'),
(11, 2, 7, 'assigned', 'Admin assigned driver', '2025-08-19 07:06:55'),
(12, 2, 4, 'en_route_to_pickup', 'Driver accepted', '2025-08-19 07:07:25'),
(13, 2, 4, 'in_transit', NULL, '2025-08-19 07:08:15'),
(14, 2, 4, 'delivered', NULL, '2025-08-19 07:09:44'),
(15, 3, 5, 'pending', 'Shipment created', '2025-08-20 06:46:39'),
(16, 3, 7, 'assigned', 'Admin assigned driver', '2025-08-20 06:47:45');

-- --------------------------------------------------------

--
-- Table structure for table `tracking_points`
--

CREATE TABLE `tracking_points` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `speed_mph` decimal(5,2) DEFAULT NULL,
  `recorded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tracking_points`
--

INSERT INTO `tracking_points` (`id`, `shipment_id`, `driver_id`, `lat`, `lng`, `speed_mph`, `recorded_at`) VALUES
(1, 1, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 06:51:54'),
(2, 1, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 06:51:54'),
(3, 1, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 06:52:18'),
(4, 1, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 06:52:20'),
(5, 1, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 06:53:02'),
(6, 1, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 06:53:04'),
(7, 2, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 07:07:29'),
(8, 2, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 07:07:31'),
(9, 2, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 07:08:07'),
(10, 2, 4, 32.2863104, 72.3058688, NULL, '2025-08-19 07:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('admin','customer','driver') NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(120) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` enum('active','pending','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `email`, `password_hash`, `name`, `company`, `phone`, `status`, `email_verified`, `created_at`, `last_login`) VALUES
(2, 'customer', 'customer@acme.com', '$2y$10$7QzHyQm4KQdlT3qVfyfHPuCyd0mtxE9nWcTR0Zg84m8p2s0q3zabq', 'Acme Corporation', 'Acme Corporation', NULL, 'active', 1, '2025-08-19 05:34:21', NULL),
(3, 'driver', 'driver@demo.com', '6c99f96293f2cac5c5073b59e4b827ff', 'John Smith', NULL, NULL, 'active', 1, '2025-08-19 05:34:21', NULL),
(4, 'driver', 'nasirkhnbaloch@gmail.com', '$2y$10$vQJFD14xRqcnedPIx3r5iu9BRx1iHTD6wM9fpS.DwArsYhFjoxsR.', 'Nasir Khan', NULL, '1233434355', 'active', 0, '2025-08-19 06:10:18', '2025-08-20 09:42:17'),
(5, 'customer', 'c@gmail.com', '$2y$10$FzFtZvpbj/t92J/xrChN2.ohiRg9sbaz65uuh14UzDFHAjrH1pWV2', 'Nasir Khan', 'WYND', '1233434355', 'active', 0, '2025-08-19 06:29:23', '2025-08-22 14:36:34'),
(7, 'admin', 'a@gmail.com', '$2y$10$I7jP26AEoq6LKR0Mp.QmLO5w5MSYKwUuplUl55b6b/hjsagIccJQ.', 'System Administrator', NULL, NULL, 'active', 1, '2025-08-19 06:40:32', '2025-08-22 14:37:43'),
(8, 'admin', 'admin@logitrack.com', '$2y$10$AWKkQgOtlGWUxykW4Pr/OulJPcNKa7rRpuXkx92DM4cfwMXAm8OAO', 'System Administrator', NULL, NULL, 'active', 1, '2025-08-19 06:40:40', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_created` (`room`,`created_at`),
  ADD KEY `idx_ship` (`shipment_id`),
  ADD KEY `idx_sender` (`sender_user_id`);

--
-- Indexes for table `driver_profiles`
--
ALTER TABLE `driver_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tracking_points`
--
ALTER TABLE `tracking_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `driver_id` (`driver_id`);

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
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tracking_points`
--
ALTER TABLE `tracking_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `driver_profiles`
--
ALTER TABLE `driver_profiles`
  ADD CONSTRAINT `driver_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `shipments_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  ADD CONSTRAINT `shipment_status_history_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipment_status_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tracking_points`
--
ALTER TABLE `tracking_points`
  ADD CONSTRAINT `tracking_points_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tracking_points_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
