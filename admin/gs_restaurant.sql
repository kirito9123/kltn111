-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th5 23, 2025 lúc 05:56 AM
-- Phiên bản máy phục vụ: 8.0.30
-- Phiên bản PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `gs_restaurant`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ban`
--

CREATE TABLE `ban` (
  `Id_ban` int NOT NULL,
  `id_vitri` int NOT NULL,
  `number_ban` varchar(5) NOT NULL,
  `ghichu` text,
  `image` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `ban`
--

INSERT INTO `ban` (`Id_ban`, `id_vitri`, `number_ban`, `ghichu`, `image`) VALUES
(1, 2, '01', NULL, 'silde2.jpg'),
(2, 2, '02', NULL, 'slide0.jpg'),
(3, 2, '03', NULL, 'slide1.jpg'),
(4, 2, '04', NULL, 'slide3.jpg'),
(5, 2, '05', NULL, 'restaurant-691397.jpg'),
(6, 1, 'Vip1', NULL, 'Vip1.JPG'),
(7, 1, 'Vip2', NULL, 'Vip2.JPG'),
(8, 1, 'Vip3', NULL, 'Vip3.JPG'),
(9, 1, 'Vip5', NULL, 'Vip5.JPG');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `cart_id` int NOT NULL,
  `id_mon` bigint NOT NULL,
  `sesid` varchar(255) NOT NULL,
  `name_mon` varchar(300) NOT NULL,
  `gia_mon` double NOT NULL,
  `soluong` int NOT NULL,
  `images` varchar(200) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `cart`
--

INSERT INTO `cart` (`cart_id`, `id_mon`, `sesid`, `name_mon`, `gia_mon`, `soluong`, `images`) VALUES
(125, 84, 'nchvoqcmt6t7l3km6d2nr0l4lf', 'Gỏi Bưởi Rong Sụn', 50000, 7, '198701dac7.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dichvu`
--

CREATE TABLE `dichvu` (
  `id_dichvu` int NOT NULL,
  `Name_dichvu` varchar(50) NOT NULL,
  `Gia_dichvu` double NOT NULL,
  `ghichu` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `dichvu`
--

INSERT INTO `dichvu` (`id_dichvu`, `Name_dichvu`, `Gia_dichvu`, `ghichu`) VALUES
(1, 'Trang trí ', 0, NULL),
(2, 'Karaoke', 500000, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hopdong`
--

CREATE TABLE `hopdong` (
  `id` int NOT NULL,
  `sesis` varchar(255) NOT NULL,
  `id_mon` int NOT NULL,
  `name_mon` varchar(255) NOT NULL,
  `id_user` int NOT NULL,
  `dates` text NOT NULL,
  `tg` text NOT NULL,
  `soluong` int NOT NULL,
  `noidung` varchar(255) NOT NULL,
  `so_user` text NOT NULL,
  `gia` double NOT NULL,
  `thanhtien` double NOT NULL,
  `images` varchar(255) NOT NULL,
  `tinhtrang` int NOT NULL DEFAULT '0',
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_method` varchar(225) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'cash',
  `id_km` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `hopdong`
--

INSERT INTO `hopdong` (`id`, `sesis`, `id_mon`, `name_mon`, `id_user`, `dates`, `tg`, `soluong`, `noidung`, `so_user`, `gia`, `thanhtien`, `images`, `tinhtrang`, `payment_status`, `payment_method`, `id_km`, `created_at`) VALUES
(127, 'jk9m8o0nek15ajj754br22l192', 80, 'Chả Nấm Chiên - Sốt Chua Ngọt', 16, '5/5/2025', '12:12', 1, 'Birthday', '15-20', 40000, 40000, '23a7b9d56c.jpg', 0, 'pending', 'cash', NULL, '2025-05-22 11:51:35'),
(129, '1mrg827l2ncskaaklos7s259q9', 80, 'Chả Nấm Chiên - Sốt Chua Ngọt', 16, '5/19/2025', '12:12', 1, 'Birthday', '4', 40000, 40000, '23a7b9d56c.jpg', 0, 'pending', 'cash', NULL, '2025-05-22 17:18:44'),
(133, 'otapv3kj0e56a642ld35eckug7', 77, 'Bún Mì Vàng Phúc Kiến', 16, '5/12/2025', '11:11', 2, 'Birthday', '2', 100000, 200000, 'c14e8b749e.jpg', 1, 'completed', 'cash', NULL, '2025-05-22 17:32:36'),
(143, 'nchvoqcmt6t7l3km6d2nr0l4lf', 84, 'Gỏi Bưởi Rong Sụn', 16, '5/26/2025', '22:22', 7, 'Other', '20', 50000, 350000, '198701dac7.jpg', 1, 'completed', 'cash', NULL, '2025-05-23 05:37:26'),
(144, 'hkc6qsav4a0jcvosref1ffj2jo', 77, 'Bún Mì Vàng Phúc Kiến', 16, '2025-05-23', '22:22', 2, 'Birthday', '10', 100000, 200000, 'c14e8b749e.jpg', 0, 'completed', 'vietqr', 8, '2025-05-23 05:40:26'),
(145, 'hkc6qsav4a0jcvosref1ffj2jo', 78, 'Chả Giò Phô Mai', 16, '2025-05-23', '22:22', 2, 'Birthday', '10', 30000, 60000, 'a73e66a948.jpg', 0, 'completed', 'vietqr', 8, '2025-05-23 05:40:26'),
(146, 'hkc6qsav4a0jcvosref1ffj2jo', 79, 'Canh Củ Sen Nấm Bụng Dê', 16, '2025-05-23', '22:22', 2, 'Birthday', '10', 150000, 300000, '935d7252b1.jpg', 0, 'completed', 'vietqr', 8, '2025-05-23 05:40:26');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `id` int NOT NULL,
  `ten` varchar(255) NOT NULL,
  `email` varchar(225) NOT NULL,
  `sodienthoai` varchar(15) NOT NULL,
  `gioitinh` int NOT NULL,
  `solandat` int DEFAULT NULL,
  `ghichu` text,
  `passwords` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`id`, `ten`, `email`, `sodienthoai`, `gioitinh`, `solandat`, `ghichu`, `passwords`) VALUES
(10, 'Trung Kien', '', '0999999999', 1, NULL, NULL, 'e10adc3949ba59abbe56e057f20f883e'),
(15, 'Minh Trí', '', '0869387703', 1, 0, 'aaa', '202cb962ac59075b964b07152d234b70'),
(16, '1234', '', '0123456789', 1, NULL, NULL, '781e5e245d69b566979b86e28d23f2c7'),
(17, 'Trịnh Ngọc Tuấn Kiệt', '', '0385267380', 1, NULL, NULL, '202cb962ac59075b964b07152d234b70'),
(18, 'Chậu Lan Hồ Điệp Sang Trọng1', '', '0941518881', 1, NULL, NULL, '2c79605ded931f571a53f7e9300a6eb5'),
(19, 'sptesst', 'hieucv204@gmail.com', '0941518882', 1, NULL, NULL, '3e485bb318f9dd01363c6ff3ce9155bc');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khuyenmai`
--

CREATE TABLE `khuyenmai` (
  `id_km` int NOT NULL,
  `name_km` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time_star` datetime DEFAULT NULL,
  `time_end` datetime DEFAULT NULL,
  `discout` decimal(10,2) DEFAULT NULL,
  `ghichu` text,
  `images` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `khuyenmai`
--

INSERT INTO `khuyenmai` (`id_km`, `name_km`, `time_star`, `time_end`, `discout`, `ghichu`, `images`) VALUES
(7, '3', '2025-05-15 00:00:00', '2025-05-24 00:00:00', 2000.00, '32    ', '625f5a5d22.jpg'),
(8, '4', '2025-05-15 00:00:00', '2025-05-27 00:00:00', 20000.00, '20', '2d101e76a7.jpg'),
(9, '100000', '2025-05-23 12:31:00', '2025-05-24 12:31:00', 100000.00, '100000', '019549e4b8.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_mon`
--

CREATE TABLE `loai_mon` (
  `id_loai` int NOT NULL,
  `name_loai` varchar(255) NOT NULL,
  `ghichu` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `loai_mon`
--

INSERT INTO `loai_mon` (`id_loai`, `name_loai`, `ghichu`) VALUES
(18, 'Món khô', 'món khô'),
(19, 'Món nước', 'Món nước'),
(20, 'Món gỏi', 'Món gỏi'),
(21, 'Món lẩu', 'Món lẩu');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id_message` int NOT NULL,
  `id_user` int NOT NULL,
  `id_admin` int NOT NULL,
  `message_content` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sender_type` enum('user','admin') NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_status` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `messages`
--

INSERT INTO `messages` (`id_message`, `id_user`, `id_admin`, `message_content`, `timestamp`, `sender_type`, `is_read`, `read_status`) VALUES
(1, 10, 1, 'zxc', '2025-05-22 13:37:12', 'admin', 0, 0),
(2, 15, 1, 'zxc', '2025-05-22 13:38:26', 'user', 0, 0),
(3, 16, 1, '123', '2025-05-22 13:38:43', 'admin', 0, 0),
(4, 15, 1, 'zxc', '2025-05-22 13:43:06', 'user', 0, 0),
(5, 10, 1, 'zxc', '2025-05-22 13:46:21', 'admin', 0, 0),
(6, 15, 1, 'zxc', '2025-05-22 13:54:04', 'user', 0, 0),
(7, 15, 2, 'zxc', '2025-05-22 13:55:55', 'user', 0, 0),
(8, 16, 1, '123', '2025-05-22 13:56:34', 'admin', 0, 0),
(9, 16, 1, 'zxc', '2025-05-22 13:57:22', 'admin', 0, 0),
(10, 15, 2, '123', '2025-05-22 14:04:39', 'user', 0, 0),
(11, 16, 2, 'zxc', '2025-05-22 14:12:47', 'user', 0, 0),
(12, 16, 3, 'zcx', '2025-05-22 14:16:12', 'admin', 0, 0),
(13, 16, 1, 'm sao thế', '2025-05-22 14:17:03', 'user', 0, 0),
(14, 16, 3, 'tao ko sao', '2025-05-22 14:17:11', 'admin', 0, 0),
(15, 16, 3, 'ừ', '2025-05-22 14:19:11', 'admin', 0, 0),
(16, 16, 1, 'hả?', '2025-05-22 14:19:27', 'user', 0, 0),
(17, 16, 2, '2', '2025-05-22 14:21:37', 'user', 0, 0),
(18, 16, 2, '3', '2025-05-22 14:21:51', 'user', 0, 0),
(19, 16, 3, '123', '2025-05-22 14:23:58', 'admin', 0, 0),
(20, 16, 3, '123', '2025-05-22 14:24:04', 'user', 0, 1),
(21, 16, 3, '1234', '2025-05-22 14:24:13', 'user', 0, 1),
(22, 16, 3, 'admin xin chào', '2025-05-22 14:27:20', 'admin', 0, 0),
(23, 16, 3, 'user xin chào lại ', '2025-05-22 14:27:29', 'user', 0, 1),
(24, 16, 3, 'haha', '2025-05-22 14:31:09', 'admin', 0, 0),
(25, 16, 3, 'ha con c', '2025-05-22 14:31:27', 'user', 0, 1),
(26, 17, 3, 'kek', '2025-05-22 14:32:04', 'admin', 0, 0),
(27, 16, 3, 'áhiuashdiasjdasdjasd', '2025-05-23 12:42:28', 'user', 0, 1),
(28, 16, 3, 'gigf zxczxc', '2025-05-23 12:42:48', 'admin', 0, 0),
(29, 16, 3, 'zxczxc', '2025-05-23 12:42:49', 'admin', 0, 0),
(30, 16, 3, 'zxc', '2025-05-23 12:42:53', 'admin', 0, 0),
(31, 16, 3, 'zxcxzc', '2025-05-23 12:43:00', 'admin', 0, 0),
(32, 16, 3, 'zxczxc', '2025-05-23 12:43:29', 'admin', 0, 0),
(33, 16, 3, 'zxczxc', '2025-05-23 12:43:31', 'admin', 0, 0),
(34, 16, 3, 'zxc', '2025-05-23 12:43:34', 'user', 0, 1),
(35, 16, 3, 'hehe', '2025-05-23 12:52:03', 'admin', 0, 0),
(36, 16, 3, '123', '2025-05-23 12:52:14', 'user', 0, 0),
(37, 16, 3, '123', '2025-05-23 12:52:16', 'user', 0, 0),
(38, 16, 3, '123', '2025-05-23 12:52:16', 'user', 0, 0),
(39, 16, 3, '123', '2025-05-23 12:52:17', 'user', 0, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `monan`
--

CREATE TABLE `monan` (
  `id_mon` bigint NOT NULL,
  `name_mon` varchar(300) NOT NULL,
  `id_loai` int NOT NULL,
  `gia_mon` double NOT NULL,
  `ghichu_mon` text,
  `images` varchar(300) DEFAULT NULL,
  `tinhtrang` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `monan`
--

INSERT INTO `monan` (`id_mon`, `name_mon`, `id_loai`, `gia_mon`, `ghichu_mon`, `images`, `tinhtrang`) VALUES
(77, 'Bún Mì Vàng Phúc Kiến', 19, 100000, '', 'c14e8b749e.jpg', 1),
(78, 'Chả Giò Phô Mai', 18, 30000, '', 'a73e66a948.jpg', 1),
(79, 'Canh Củ Sen Nấm Bụng Dê', 19, 150000, '', '935d7252b1.jpg', 1),
(80, 'Chả Nấm Chiên - Sốt Chua Ngọt', 18, 40000, '', '23a7b9d56c.jpg', 1),
(81, 'Chả Ram An Duyên', 18, 45000, '', '6a29a37b44.jpg', 1),
(82, 'Cơm Chiên Ôliu Đen Triều Sơn', 18, 45000, '', '7a102353d7.jpg', 1),
(83, 'Súp Bắp Măng Tây Nấm Trùng Thảo', 19, 50000, '', '11f854f5d4.jpg', 1),
(84, 'Gỏi Bưởi Rong Sụn', 20, 50000, '', '198701dac7.jpg', 1),
(85, 'Gỏi Xoài Khô Mặn', 20, 50000, '', 'b64c3a4807.jpg', 1),
(86, 'Lẩu Sa Tế', 21, 100000, '', 'f0f8538562.jpg', 1),
(88, 'Lẩu Thập Bảo', 21, 150000, '', 'fe342619f2.jpg', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_emails`
--

CREATE TABLE `promotion_emails` (
  `id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `recipients` text NOT NULL,
  `sent_at` datetime NOT NULL,
  `admin_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `promotion_emails`
--

INSERT INTO `promotion_emails` (`id`, `subject`, `message`, `recipients`, `sent_at`, `admin_id`) VALUES
(1, 'hieucb204@gmail.com', 'hieucb204@gmail.com', 'hieucv204@gmail.com', '2025-05-23 05:07:57', 3),
(2, 'Thông tin KM', '🎉 Khuyến mãi hấp dẫn tại Nhà hàng GS! 🎉\r\nNhà hàng GS xin gửi đến quý khách hàng chuỗi chương trình khuyến mãi đặc biệt như một lời tri ân chân thành:\r\n\r\n🍽️ Giảm 10% tổng hóa đơn cho nhóm từ 4 người trở lên\r\n🥤 Tặng ngay 1 phần nước uống miễn phí cho mỗi khách hàng lần đầu đến dùng bữa\r\n🎂 Miễn phí bánh sinh nhật mini cho khách có ngày sinh trong tháng (áp dụng khi đặt bàn trước)\r\n💳 Giảm thêm 5% cho khách thanh toán qua VNPay hoặc VietQR\r\n\r\n📅 Thời gian áp dụng: từ 1/6/2025 đến 30/6/2025\r\n\r\nNhanh tay đặt bàn ngay hôm nay để cùng bạn bè và người thân thưởng thức những món ăn ngon trong không gian sang trọng và nhận về thật nhiều ưu đãi tại GS Restaurant!', 'hieucv204@gmail.com', '2025-05-23 05:08:51', 3),
(3, 'hieucb204@gmail.com', '🎉 Khuyến mãi hấp dẫn tại Nhà hàng GS! 🎉\r\nNhà hàng GS xin gửi đến quý khách hàng chuỗi chương trình khuyến mãi đặc biệt như một lời tri ân chân thành:\r\n\r\n🍽️ Giảm 10% tổng hóa đơn cho nhóm từ 4 người trở lên\r\n🥤 Tặng ngay 1 phần nước uống miễn phí cho mỗi khách hàng lần đầu đến dùng bữa\r\n🎂 Miễn phí bánh sinh nhật mini cho khách có ngày sinh trong tháng (áp dụng khi đặt bàn trước)\r\n💳 Giảm thêm 5% cho khách thanh toán qua VNPay hoặc VietQR\r\n\r\n📅 Thời gian áp dụng: từ 1/6/2025 đến 30/6/2025\r\n\r\nNhanh tay đặt bàn ngay hôm nay để cùng bạn bè và người thân thưởng thức những món ăn ngon trong không gian sang trọng và nhận về thật nhiều ưu đãi tại GS Restaurant!', 'hieucv204@gmail.com', '2025-05-23 05:41:20', 3);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tb_admin`
--

CREATE TABLE `tb_admin` (
  `id_admin` int NOT NULL,
  `Name_admin` varchar(255) NOT NULL,
  `adminuser` varchar(155) NOT NULL,
  `adminpass` varchar(255) NOT NULL,
  `level` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `tb_admin`
--

INSERT INTO `tb_admin` (`id_admin`, `Name_admin`, `adminuser`, `adminpass`, `level`) VALUES
(1, 'Ngan', 'Ngan', 'e10adc3949ba59abbe56e057f20f883e', 0),
(2, 'Nguyễn Minh Trí', 'tri123', 'e10adc3949ba59abbe56e057f20f883e', 0),
(3, 'Nguyễn Minh Trí', 'tri123', 'b85593ca6abda3f203e0af8239beb228', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vitri`
--

CREATE TABLE `vitri` (
  `id_vitri` int NOT NULL,
  `Name_vitri` varchar(5) NOT NULL,
  `Ghichu` text,
  `image` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Đang đổ dữ liệu cho bảng `vitri`
--

INSERT INTO `vitri` (`id_vitri`, `Name_vitri`, `Ghichu`, `image`) VALUES
(1, 'Vip', NULL, 'Vip3.JPG'),
(2, 'Sảnh ', NULL, 'silde2.jpg');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `ban`
--
ALTER TABLE `ban`
  ADD PRIMARY KEY (`Id_ban`),
  ADD KEY `id_vitri` (`id_vitri`);

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`);

--
-- Chỉ mục cho bảng `dichvu`
--
ALTER TABLE `dichvu`
  ADD PRIMARY KEY (`id_dichvu`);

--
-- Chỉ mục cho bảng `hopdong`
--
ALTER TABLE `hopdong`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hopdong_ibfk_km` (`id_km`);

--
-- Chỉ mục cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `khuyenmai`
--
ALTER TABLE `khuyenmai`
  ADD PRIMARY KEY (`id_km`);

--
-- Chỉ mục cho bảng `loai_mon`
--
ALTER TABLE `loai_mon`
  ADD PRIMARY KEY (`id_loai`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `id_user` (`id_user`);

--
-- Chỉ mục cho bảng `monan`
--
ALTER TABLE `monan`
  ADD PRIMARY KEY (`id_mon`),
  ADD KEY `id_loai` (`id_loai`);

--
-- Chỉ mục cho bảng `promotion_emails`
--
ALTER TABLE `promotion_emails`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `tb_admin`
--
ALTER TABLE `tb_admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Chỉ mục cho bảng `vitri`
--
ALTER TABLE `vitri`
  ADD PRIMARY KEY (`id_vitri`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `ban`
--
ALTER TABLE `ban`
  MODIFY `Id_ban` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT cho bảng `dichvu`
--
ALTER TABLE `dichvu`
  MODIFY `id_dichvu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `hopdong`
--
ALTER TABLE `hopdong`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `khuyenmai`
--
ALTER TABLE `khuyenmai`
  MODIFY `id_km` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `loai_mon`
--
ALTER TABLE `loai_mon`
  MODIFY `id_loai` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `messages`
--
ALTER TABLE `messages`
  MODIFY `id_message` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT cho bảng `monan`
--
ALTER TABLE `monan`
  MODIFY `id_mon` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT cho bảng `promotion_emails`
--
ALTER TABLE `promotion_emails`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `tb_admin`
--
ALTER TABLE `tb_admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `vitri`
--
ALTER TABLE `vitri`
  MODIFY `id_vitri` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ràng buộc đối với các bảng kết xuất
--

--
-- Ràng buộc cho bảng `ban`
--
ALTER TABLE `ban`
  ADD CONSTRAINT `ban_ibfk_1` FOREIGN KEY (`id_vitri`) REFERENCES `vitri` (`id_vitri`);

--
-- Ràng buộc cho bảng `hopdong`
--
ALTER TABLE `hopdong`
  ADD CONSTRAINT `hopdong_ibfk_km` FOREIGN KEY (`id_km`) REFERENCES `khuyenmai` (`id_km`);

--
-- Ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `khach_hang` (`id`);

--
-- Ràng buộc cho bảng `monan`
--
ALTER TABLE `monan`
  ADD CONSTRAINT `monan_ibfk_1` FOREIGN KEY (`id_loai`) REFERENCES `loai_mon` (`id_loai`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
