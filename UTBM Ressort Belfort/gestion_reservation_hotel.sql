-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 07, 2025 at 02:31 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestion_reservation_hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `role` varchar(50) NOT NULL,
  `login` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Sample employee data (Administrator account)
-- Default credentials: login=admin.hotel, password=password123
--

INSERT INTO `employee` (`employee_id`, `last_name`, `first_name`, `email`, `phone`, `role`, `login`, `password`) VALUES
(1, 'Admin', 'System', 'admin@utbmresort.com', '0123456789', 'administrator', 'admin.hotel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------

--
-- Table structure for table `guest`
--

CREATE TABLE `guest` (
  `guest_id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `adress` varchar(100) DEFAULT NULL,
  `password` varchar(100) NOT NULL,
  `registration_date` date NOT NULL,
  `date_of_birth` date NOT NULL,
  `loyality_points` int(11) NOT NULL DEFAULT 0,
  `login` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Sample guest data for demonstration
--

INSERT INTO `guest` (`guest_id`, `last_name`, `first_name`, `email`, `phone`, `adress`, `password`, `registration_date`, `date_of_birth`, `loyality_points`, `login`) VALUES
(1, 'Dupont', 'Jean', 'jean.dupont@example.com', '0612345678', '123 Rue de la Paix, 75001 Paris, France', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-01-15', '1990-05-20', 0, NULL),
(2, 'Martin', 'Sophie', 'sophie.martin@example.com', '0687654321', '45 Avenue des Champs, 69001 Lyon, France', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-02-20', '1985-08-15', 150, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room_type`
--

CREATE TABLE `room_type` (
  `room_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `amenities` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Room types with pricing
--

INSERT INTO `room_type` (`room_type_id`, `type_name`, `description`, `base_price`, `capacity`, `amenities`) VALUES
(1, 'Deluxe Room', 'Spacious room with garden view', 200.00, 2, 'LCD TV, Free WiFi, Minibar, Air conditioning, Garden view'),
(2, 'Premium Room', 'Elegant room with balcony and pool view', 250.00, 2, 'LCD TV, Free WiFi, Minibar, Air conditioning, Balcony, Pool view'),
(3, 'Junior Suite', 'Comfortable suite with private living room', 350.00, 3, 'LCD TV, Free WiFi, Minibar, Air conditioning, Private living room, Balcony'),
(4, 'Senior Suite', 'Large suite with terrace and panoramic view', 450.00, 4, 'LCD TV, Free WiFi, Minibar, Air conditioning, Living room, Terrace, Panoramic view'),
(5, 'Family Room', 'Ideal for families with two double beds', 300.00, 4, 'LCD TV, Free WiFi, Minibar, Air conditioning, 2 double beds, Safe'),
(6, 'Luxury Suite', 'Prestigious suite with jacuzzi', 600.00, 2, 'LCD TV, Free WiFi, Minibar, Air conditioning, Living room, Balcony, Private jacuzzi, 24/7 room service');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int(11) NOT NULL,
  `room_number` int(10) NOT NULL,
  `status` varchar(100) NOT NULL,
  `room_type_id` int(10) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Available rooms inventory
--

INSERT INTO `room` (`room_id`, `room_number`, `status`, `room_type_id`, `price_per_night`) VALUES
(1, 101, 'available', 1, 200.00),
(2, 102, 'available', 1, 200.00),
(3, 103, 'available', 1, 200.00),
(4, 104, 'available', 1, 200.00),
(5, 201, 'available', 2, 250.00),
(6, 202, 'available', 2, 250.00),
(7, 203, 'available', 2, 250.00),
(8, 301, 'available', 3, 350.00),
(9, 302, 'available', 3, 350.00),
(10, 303, 'available', 3, 350.00),
(11, 401, 'available', 4, 450.00),
(12, 402, 'available', 4, 450.00),
(13, 403, 'available', 4, 450.00),
(14, 501, 'available', 5, 300.00),
(15, 502, 'available', 5, 300.00),
(16, 503, 'available', 5, 300.00),
(17, 601, 'available', 6, 600.00),
(18, 602, 'available', 6, 600.00);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Additional services available
--

INSERT INTO `services` (`service_id`, `service_name`, `price`, `description`) VALUES
(1, 'Breakfast', 15.00, 'Continental buffet breakfast'),
(2, 'Airport Shuttle', 35.00, 'One-way airport transfer'),
(3, 'Late Checkout', 20.00, 'Late departure until 2 PM'),
(4, 'Half Board', 35.00, 'Breakfast + one meal per day'),
(5, 'Full Board', 55.00, 'Breakfast + lunch + dinner'),
(6, 'Car Rental', 45.00, 'Daily car rental'),
(7, 'Laundry Service', 12.00, 'Washed and folded'),
(8, 'Dry Cleaning', 8.00, 'Per item dry cleaning');

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `id_reservation` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `number_of_guest` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(100) NOT NULL,
  `booking_date` date NOT NULL,
  `special_requests` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Sample reservations for demonstration
--

INSERT INTO `reservation` (`id_reservation`, `guest_id`, `room_id`, `check_in_date`, `check_out_date`, `number_of_guest`, `total_price`, `status`, `booking_date`, `special_requests`) VALUES
(1, 1, 2, '2025-12-15', '2025-12-18', 2, 650.00, 'confirmed', '2025-11-01', 'Late check-in requested'),
(2, 2, 14, '2025-12-20', '2025-12-23', 4, 945.00, 'pending', '2025-11-05', 'Extra bed needed'),
(3, 1, 5, '2025-11-01', '2025-11-03', 2, 500.00, 'completed', '2025-10-25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_service`
--

CREATE TABLE `reservation_service` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Services attached to reservations
--

INSERT INTO `reservation_service` (`id`, `reservation_id`, `service_id`, `price`) VALUES
(1, 1, 1, 15.00),
(2, 1, 2, 35.00),
(3, 2, 1, 15.00),
(4, 2, 4, 35.00);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` varchar(255) DEFAULT NULL,
  `date_posted` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(100) NOT NULL,
  `payment_date` date NOT NULL,
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(10) NOT NULL,
  `reservation_id` int(10) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `currency` varchar(100) NOT NULL DEFAULT 'EUR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `login` (`login`);

ALTER TABLE `guest`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

ALTER TABLE `room_type`
  ADD PRIMARY KEY (`room_type_id`);

ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `room_type_id` (`room_type_id`);

ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_resv_room_dates` (`room_id`,`check_in_date`,`check_out_date`),
  ADD KEY `idx_resv_status` (`status`);

ALTER TABLE `reservation_service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rs_reservation` (`reservation_id`),
  ADD KEY `fk_rs_service` (`service_id`);

ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `reservation_id` (`reservation_id`);

ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `guest_id` (`guest_id`);

ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `employee_id` (`employee_id`);

ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `guest`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `room_type`
  MODIFY `room_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `reservation`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `reservation_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `invoice`
  MODIFY `invoice_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

ALTER TABLE `room`
  ADD CONSTRAINT `room_ibfk_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_type` (`room_type_id`);

ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_guest` FOREIGN KEY (`guest_id`) REFERENCES `guest` (`guest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_ibfk_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`);

ALTER TABLE `reservation_service`
  ADD CONSTRAINT `fk_rs_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`id_reservation`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`id_reservation`),
  ADD CONSTRAINT `feedback_ibfk_guest` FOREIGN KEY (`guest_id`) REFERENCES `guest` (`guest_id`) ON DELETE CASCADE;

ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guest` (`guest_id`);

ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`),
  ADD CONSTRAINT `maintenance_ibfk_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE;

ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`id_reservation`),
  ADD CONSTRAINT `invoice_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
