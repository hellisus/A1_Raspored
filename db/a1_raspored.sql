-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 06:39 PM
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
-- Database: `a1_raspored`
--

-- --------------------------------------------------------

--
-- Table structure for table `glavna_tabela`
--

CREATE TABLE `glavna_tabela` (
  `ID` int(11) NOT NULL,
  `Job` int(11) DEFAULT NULL,
  `Task name` varchar(100) DEFAULT NULL,
  `Scheduled to` varchar(100) DEFAULT NULL,
  `Accept date` datetime DEFAULT NULL,
  `Custom Workorder Status` varchar(100) DEFAULT NULL,
  `Assignees` varchar(100) DEFAULT NULL,
  `Accepted by` varchar(100) DEFAULT NULL,
  `Job Type` varchar(50) DEFAULT NULL,
  `Priority` varchar(50) DEFAULT NULL,
  `Create date` datetime DEFAULT NULL,
  `Created by` varchar(100) DEFAULT NULL,
  `Job Creation Date` datetime DEFAULT NULL,
  `Proposal (New) Amount` decimal(15,2) DEFAULT NULL,
  `Proposal (Rejected) Amount` decimal(15,2) DEFAULT NULL,
  `Proposal (Accepted) Amount` decimal(15,2) DEFAULT NULL,
  `Current state` varchar(50) DEFAULT NULL,
  `Scheduled by` varchar(100) DEFAULT NULL,
  `Region name` varchar(100) DEFAULT NULL,
  `Location name` varchar(100) DEFAULT NULL,
  `Is locked` tinyint(1) DEFAULT NULL,
  `Woid` int(11) DEFAULT NULL,
  `Related Woid` int(11) DEFAULT NULL,
  `Empty_Column_1` varchar(255) DEFAULT NULL,
  `Adapter ID` bigint(20) DEFAULT NULL,
  `CPE Serial Numbers` varchar(255) DEFAULT NULL,
  `Customer Name` varchar(200) DEFAULT NULL,
  `Contact Phone On Location` varchar(50) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `Address` varchar(200) DEFAULT NULL,
  `House Number` varchar(50) DEFAULT NULL,
  `WO_InstallationType` varchar(100) DEFAULT NULL,
  `Street` varchar(200) DEFAULT NULL,
  `Country Name` varchar(100) DEFAULT NULL,
  `Get Address` tinyint(1) DEFAULT NULL,
  `Comment` text DEFAULT NULL,
  `Lm Id` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Novi Task za tehničara mock` varchar(255) DEFAULT NULL,
  `WO_IDP` varchar(50) DEFAULT NULL,
  `WO_IDPExpireDate` varchar(100) DEFAULT NULL,
  `Scheduled start` datetime DEFAULT NULL,
  `Duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `glavna_tabela`
--
ALTER TABLE `glavna_tabela`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_job` (`Job`),
  ADD KEY `idx_woid` (`Woid`),
  ADD KEY `idx_scheduled_start` (`Scheduled start`),
  ADD KEY `idx_assignees` (`Assignees`),
  ADD KEY `idx_region` (`Region name`),
  ADD KEY `idx_city` (`City`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
