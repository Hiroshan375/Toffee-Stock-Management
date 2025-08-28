-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 10:05 PM
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
-- Database: `toffee_stock`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `final_quantities`
-- (See below for the actual view)
--
CREATE TABLE `final_quantities` (
`id` int(11)
,`name` varchar(255)
,`original_quantity` int(11)
,`total_issued` decimal(32,0)
,`final_quantity` decimal(33,0)
,`price` decimal(10,2)
,`image_path` varchar(500)
);

-- --------------------------------------------------------

--
-- Table structure for table `issuing_details`
--

CREATE TABLE `issuing_details` (
  `id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `issued_quantity` int(11) NOT NULL,
  `issued_date` date NOT NULL,
  `issued_to` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `toffees`
--

CREATE TABLE `toffees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `issued_quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toffees`
--

INSERT INTO `toffees` (`id`, `name`, `quantity`, `price`, `image_path`, `created_at`, `updated_at`, `issued_quantity`) VALUES
(15, 'Center Fruit Strawberry 3.4g 220 Pcs Jar', 40, 1871.00, 'uploads/1754757295_new-product-500x500.jpeg', '2025-08-09 16:34:55', '2025-08-18 16:18:30', 0),
(16, 'Center Shock Assorted Peach and Apple 3.2g 220 Pcs Jar', 40, 1871.00, 'uploads/1754757536_sk.jpeg', '2025-08-09 16:38:56', '2025-08-18 16:18:30', 0),
(17, 'Alpenliebe Caramel 3.6g 200 Pcs Jar', 15, 1700.00, 'uploads/1754757706_51juSAkjrSL.jpeg', '2025-08-09 16:41:46', '2025-08-18 16:18:30', 0),
(18, 'Sour Marbles 1.1g 7 Pcs 48 Sachets Display ', 50, 816.00, 'uploads/1754763000_sm.jpeg', '2025-08-09 18:10:00', '2025-08-18 16:18:30', 0);

-- --------------------------------------------------------

--
-- Table structure for table `toffee_transactions`
--

CREATE TABLE `toffee_transactions` (
  `id` int(11) NOT NULL,
  `toffee_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `issue_quantity` int(11) DEFAULT 0,
  `load_quantity` int(11) DEFAULT 0,
  `current_quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toffee_transactions`
--

INSERT INTO `toffee_transactions` (`id`, `toffee_id`, `transaction_date`, `issue_quantity`, `load_quantity`, `current_quantity`, `notes`, `created_at`) VALUES
(1, 17, '2025-08-18', 5, 0, 15, '', '2025-08-18 16:18:30'),
(2, 15, '2025-08-18', 10, 0, 40, '', '2025-08-18 16:18:30'),
(3, 16, '2025-08-18', 10, 20, 40, '', '2025-08-18 16:18:30'),
(4, 18, '2025-08-18', 20, 0, 50, '', '2025-08-18 16:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin123');

-- --------------------------------------------------------

--
-- Structure for view `final_quantities`
--
DROP TABLE IF EXISTS `final_quantities`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `final_quantities`  AS SELECT `t`.`id` AS `id`, `t`.`name` AS `name`, `t`.`quantity` AS `original_quantity`, coalesce(sum(`i`.`issued_quantity`),0) AS `total_issued`, `t`.`quantity`- coalesce(sum(`i`.`issued_quantity`),0) AS `final_quantity`, `t`.`price` AS `price`, `t`.`image_path` AS `image_path` FROM (`toffees` `t` left join `issuing_details` `i` on(`t`.`id` = `i`.`stock_id`)) GROUP BY `t`.`id`, `t`.`name`, `t`.`quantity`, `t`.`price`, `t`.`image_path` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `issuing_details`
--
ALTER TABLE `issuing_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_id` (`stock_id`);

--
-- Indexes for table `toffees`
--
ALTER TABLE `toffees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `toffee_transactions`
--
ALTER TABLE `toffee_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `toffee_id` (`toffee_id`);

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
-- AUTO_INCREMENT for table `issuing_details`
--
ALTER TABLE `issuing_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `toffees`
--
ALTER TABLE `toffees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `toffee_transactions`
--
ALTER TABLE `toffee_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `issuing_details`
--
ALTER TABLE `issuing_details`
  ADD CONSTRAINT `issuing_details_ibfk_1` FOREIGN KEY (`stock_id`) REFERENCES `toffees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `toffee_transactions`
--
ALTER TABLE `toffee_transactions`
  ADD CONSTRAINT `toffee_transactions_ibfk_1` FOREIGN KEY (`toffee_id`) REFERENCES `toffees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
