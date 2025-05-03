-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hostiteľ: 127.0.0.1
-- Čas generovania: So 26.Apr 2025, 16:18
-- Verzia serveru: 10.4.32-MariaDB
-- Verzia PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáza: `databaze`
--

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Sťahujem dáta pre tabuľku `classes`
--

INSERT INTO `classes` (`class_id`, `name`, `description`, `capacity`, `created_at`) VALUES
(6, 'asd', 'asdsa', 28, '2025-04-19 14:34:11'),
(7, 'asd', 'asd', 14, '2025-04-26 14:16:33');

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `class_reservations`
--

CREATE TABLE `class_reservations` (
  `reservation_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('admin','readonly','customer','verification') DEFAULT 'readonly'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Sťahujem dáta pre tabuľku `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `role`) VALUES
(1, 'asd', 'asd@asd.cz', '$2y$10$fVuM8PNA9RN9Dhgh4804ROK4L95xUXYlrNqmfLXDm8FsPd2KppIr6', '2025-04-11 09:09:27', 'readonly'),
(3, 'asd', 'asd@as.cz', '$2y$10$PTdRfjV0FDEX52kynH.i.uo6EkziOrSrpmFz27CYxHJ8ad3WP5oLu', '2025-04-11 09:15:30', 'readonly'),
(4, 'asd', 'asd@s.sk', '$2y$10$NR79X.GeD5cuW09/ZW9eduh3KHs8RhaFXZlRxc7dLQqgJqR1v5WEO', '2025-04-11 09:32:54', 'readonly'),
(5, 'aa', 'aa@aa.aa', 'aa', '0000-00-00 00:00:00', 'customer'),
(6, 'admin', 'aa@aa.aaa', '$2y$10$1tnNAuwkHh.SGGNSzn43CODBZ2es2XcCtDCiXcSPNxMXGHPDDNizy', '2025-04-11 09:35:48', 'admin'),
(8, 'asd', 'asd@asd.czs', '$2y$10$hkhZPHAKBNwp1qzyvmL5qOy3gubf0uFbhUd6w.B4XGyht9lu8qRP2', '2025-04-19 13:51:52', 'verification'),
(9, 'mario', 'm@m.m', '$2y$10$lF8Ys9pjcDlo/h8XJ2Kvv.JmAdhNWBayeivuITBdp.Aicu3jFu0w6', '2025-04-19 14:55:19', 'admin'),
(10, 'cc', 'cc@cc.cc', '$2y$10$6OC4iofGukBTFZRKrSHQnerfQRkax.dU3cS/mpBDsi4Pu9lhpLfGG', '2025-04-26 12:59:55', 'admin');

--
-- Kľúče pre exportované tabuľky
--

--
-- Indexy pre tabuľku `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`);

--
-- Indexy pre tabuľku `class_reservations`
--
ALTER TABLE `class_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `fk_class_reservations_user` (`user_id`),
  ADD KEY `idx_class_date` (`class_id`,`date`);

--
-- Indexy pre tabuľku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pre exportované tabuľky
--

--
-- AUTO_INCREMENT pre tabuľku `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pre tabuľku `class_reservations`
--
ALTER TABLE `class_reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pre tabuľku `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Obmedzenie pre exportované tabuľky
--

--
-- Obmedzenie pre tabuľku `class_reservations`
--
ALTER TABLE `class_reservations`
  ADD CONSTRAINT `class_reservations_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `class_reservations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_class_reservations_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
