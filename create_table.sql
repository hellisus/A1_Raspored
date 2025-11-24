-- MySQL skripta za kreiranje tabele na osnovu Export_u_csv.csv
-- Baza podataka: A1_Raspored (ili promenite prema potrebi)

CREATE DATABASE IF NOT EXISTS A1_Raspored;
USE A1_Raspored;

DROP TABLE IF EXISTS glavna_tabela;

CREATE TABLE glavna_tabela (
    `ID` INT NOT NULL,
    `Job` INT NULL,
    `Task name` VARCHAR(100) NULL,
    `Scheduled to` VARCHAR(100) NULL,
    `Accept date` DATETIME NULL,
    `Custom Workorder Status` VARCHAR(100) NULL,
    `Assignees` VARCHAR(100) NULL,
    `Accepted by` VARCHAR(100) NULL,
    `Job Type` VARCHAR(50) NULL,
    `Priority` VARCHAR(50) NULL,
    `Create date` DATETIME NULL,
    `Created by` VARCHAR(100) NULL,
    `Job Creation Date` DATETIME NULL,
    `Proposal (New) Amount` DECIMAL(15,2) NULL,
    `Proposal (Rejected) Amount` DECIMAL(15,2) NULL,
    `Proposal (Accepted) Amount` DECIMAL(15,2) NULL,
    `Current state` VARCHAR(50) NULL,
    `Scheduled by` VARCHAR(100) NULL,
    `Region name` VARCHAR(100) NULL,
    `Location name` VARCHAR(100) NULL,
    `Is locked` BOOLEAN NULL,
    `Woid` INT NULL,
    `Related Woid` INT NULL,
    `Empty_Column_1` VARCHAR(255) NULL,
    `Adapter ID` BIGINT NULL,
    `CPE Serial Numbers` VARCHAR(255) NULL,
    `Customer Name` VARCHAR(200) NULL,
    `Contact Phone On Location` VARCHAR(50) NULL,
    `City` VARCHAR(100) NULL,
    `Address` VARCHAR(200) NULL,
    `House Number` VARCHAR(50) NULL,
    `WO_InstallationType` VARCHAR(100) NULL,
    `Street` VARCHAR(200) NULL,
    `Country Name` VARCHAR(100) NULL,
    `Get Address` BOOLEAN NULL,
    `Comment` TEXT NULL,
    `Lm Id` INT NULL,
    `Description` TEXT NULL,
    `Novi Task za tehničara mock` VARCHAR(255) NULL,
    `WO_IDP` VARCHAR(50) NULL,
    `WO_IDPExpireDate` VARCHAR(100) NULL,
    `Scheduled start` DATETIME NULL,
    `Duration` INT NULL,
    PRIMARY KEY (`ID`),
    INDEX `idx_job` (`Job`),
    INDEX `idx_woid` (`Woid`),
    INDEX `idx_scheduled_start` (`Scheduled start`),
    INDEX `idx_assignees` (`Assignees`),
    INDEX `idx_region` (`Region name`),
    INDEX `idx_city` (`City`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

