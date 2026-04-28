-- phpMyAdmin SQL Dump
-- Solo Second Thrift — Database Schema
-- Host: 127.0.0.1 | Server: 10.4.32-MariaDB | PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- Database: `thrift`
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- Table: users
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         int(11)       NOT NULL AUTO_INCREMENT,
  `nama`       varchar(100)  NOT NULL,
  `email`      varchar(100)  NOT NULL,
  `password`   varchar(255)  NOT NULL,
  `role`       enum('owner','crew','content_creator') NOT NULL,
  `status`     enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `users` (`id`,`nama`,`email`,`password`,`role`,`status`) VALUES
(7, 'Owner',           'owner@thrift.com', '1111', 'owner',           'aktif'),
(8, 'Crew',            'crew@thrift.com',  '2222', 'crew',            'aktif'),
(9, 'Content Creator', 'cc@thrift.com',    '3333', 'content_creator', 'aktif');

ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

-- ────────────────────────────────────────────────────────────
-- Table: barang  (katalog produk)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `barang` (
  `id`          int(11)        NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(50)    NOT NULL,
  `nama_barang` varchar(150)   NOT NULL,
  `harga`       decimal(12,2)  NOT NULL DEFAULT 0.00,
  `stok`        int(11)        NOT NULL DEFAULT 0,
  `created_at`  timestamp      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_barang` (`kode_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `barang` (`kode_barang`,`nama_barang`,`harga`,`stok`) VALUES
('BRG001', 'Kaos Vintage Polo',    125000.00, 10),
('BRG002', 'Celana Jeans Retro',   200000.00,  5),
('BRG003', 'Jaket Denim Classic',  350000.00,  3),
('BRG004', 'Kemeja Flanel Kotak',  175000.00,  8);

-- ────────────────────────────────────────────────────────────
-- Table: transaksi  (header / nota penjualan)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id`              int(11)        NOT NULL AUTO_INCREMENT,
  `kode_transaksi`  varchar(50)    NOT NULL,
  `user_id`         int(11)        NOT NULL,
  `total_bayar`     decimal(12,2)  NOT NULL DEFAULT 0.00,
  `created_at`      timestamp      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `fk_transaksi_user` (`user_id`),
  CONSTRAINT `fk_transaksi_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
-- Table: transaksi_detail  (item per transaksi)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transaksi_detail` (
  `id`            int(11)        NOT NULL AUTO_INCREMENT,
  `transaksi_id`  int(11)        NOT NULL,
  `kode_barang`   varchar(50)    NOT NULL,
  `nama_barang`   varchar(150)   NOT NULL,
  `harga_satuan`  decimal(12,2)  NOT NULL,
  `qty`           int(11)        NOT NULL DEFAULT 1,
  `subtotal`      decimal(12,2)  NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detail_transaksi` (`transaksi_id`),
  CONSTRAINT `fk_detail_transaksi`
    FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
