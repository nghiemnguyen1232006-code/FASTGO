-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 29, 2026 lúc 06:01 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `fastgo`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `pickup_address` text NOT NULL,
  `delivery_address` text NOT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','assigned','picked_up','delivering','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `distance_km` decimal(8,2) DEFAULT 0.00 COMMENT 'Khoảng cách km',
  `package_type` varchar(20) DEFAULT 'small' COMMENT 'Loại hàng: document/small/medium/large/fragile/food',
  `note` text DEFAULT NULL COMMENT 'Ghi chú của khách',
  `goods_value` decimal(12,2) DEFAULT 0.00 COMMENT 'Giá trị hàng hóa',
  `shipping_fee` decimal(12,2) DEFAULT 0.00 COMMENT 'Phí ship hệ thống tính',
  `fee_approved` tinyint(1) DEFAULT 0 COMMENT '1 = nhân viên đã duyệt phí'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `driver_id`, `staff_id`, `pickup_address`, `delivery_address`, `weight`, `price`, `status`, `created_at`, `distance_km`, `package_type`, `note`, `goods_value`, `shipping_fee`, `fee_approved`) VALUES
(1, 6, 3, 2, 'gia lai', 'hcm', 3.00, 450000.00, 'completed', '2026-05-29 02:14:24', 0.00, 'small', NULL, 0.00, 0.00, 0),
(2, 6, NULL, NULL, 'gia lai', 'hcm', 5.00, 2520000.00, 'pending', '2026-05-29 04:01:05', 499.80, 'document', '', 0.00, 2520000.00, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','driver','customer') DEFAULT 'customer',
  `status` enum('active','locked') NOT NULL DEFAULT 'active',
  `cccd` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `education` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `hometown` varchar(255) DEFAULT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `license_type` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `role`, `status`, `cccd`, `birthdate`, `education`, `vehicle_plate`, `address`, `hometown`, `ethnicity`, `gender`, `nationality`, `religion`, `license_type`, `created_at`) VALUES
(1, 'Admin FASTGO', 'admin@gmail.com', '0900000000', '$2y$10$XrLEnZUQiA0h7Bb6KDw4jeTTeHijbnibIPo/5DjAb/RM1elrtvFOa', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-28 10:04:56'),
(2, 'Staff FASTGO', 'staff@gmail.com', '0900000001', '$2y$10$k1i2j/KP5ulp7qbwnMVAoOdDgGlJ51dXJdCICkhrCpnLKSHatcVF2', 'staff', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-28 10:04:56'),
(3, 'Nguyễn Minh Quân', 'quan.driver@gmail.com', '0901000001', '$2y$10$XrLEnZUQiA0h7Bb6KDw4jeTTeHijbnibIPo/5DjAb/RM1elrtvFOa', 'driver', 'active', '052204000001', '1999-05-12', 'Cao đẳng', '77A1-12345', 'Quy Nhơn, Bình Định', 'Bình Định', 'Kinh', 'Nam', 'Việt Nam', 'Không', 'A1', '2026-05-28 10:04:56'),
(4, 'Trần Quốc Bảo', 'bao.driver@gmail.com', '0901000002', '$2y$10$XrLEnZUQiA0h7Bb6KDw4jeTTeHijbnibIPo/5DjAb/RM1elrtvFOa', 'driver', 'active', '052204000002', '1998-08-20', 'Đại học', '77B1-45678', 'Tuy Phước, Bình Định', 'Quảng Ngãi', 'Kinh', 'Nam', 'Việt Nam', 'Phật giáo', 'A1', '2026-05-28 10:04:56'),
(5, 'Lê Hoàng Nam', 'nam.driver@gmail.com', '0901000003', '$2y$10$XrLEnZUQiA0h7Bb6KDw4jeTTeHijbnibIPo/5DjAb/RM1elrtvFOa', 'driver', 'active', '052204000003', '2000-01-15', 'THPT', '77C1-22222', 'An Nhơn, Bình Định', 'Bình Định', 'Kinh', 'Nam', 'Việt Nam', 'Không', 'A1', '2026-05-28 10:04:56'),
(6, 'Nguyễn Văn A', 'khachhang@gmail.com', '0123456789', '$2y$10$6SXmONTLjIEVHnMTRYwZquZld8AaWkW7vFBiCeAgsKBWshKK2cKhu', 'customer', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-29 02:13:46');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Chỉ mục cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
