-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 02:00 PM
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
-- Database: `stkgear`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$0GzwYIZTcfblM1bdpxvbyOqOrB1Rp5lUoJHxdCAiK6G8bN5AtS3H.', '2026-05-16 03:35:42');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_code` varchar(30) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` varchar(255) NOT NULL,
  `customer_note` text DEFAULT NULL,
  `subtotal` int(10) UNSIGNED NOT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'Đang xét duyệt đơn hàng',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `customer_address`, `customer_note`, `subtotal`, `status`, `created_at`, `updated_at`) VALUES
(6, 'DH26051610DECC', 1, 'QA', '0912345678', 'HN', 'test', 950000, 'Đang vận chuyển', '2026-05-16 07:43:12', '2026-05-16 08:07:03'),
(7, 'DH26051688B997', 5, 'truong', '0123456789', '1', '1', 1750000, 'Đang vận chuyển', '2026-05-16 08:48:47', '2026-05-16 08:56:30'),
(8, 'DH26051696B2F8', 1, 'Runtime User', '0912345678', 'Ninh Binh', 'QA-final', 1890000, 'Đang xét duyệt đơn hàng', '2026-05-16 09:04:16', '2026-05-16 09:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_sku` varchar(30) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_color` varchar(80) DEFAULT NULL,
  `unit_price` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_sku`, `product_name`, `variant_color`, `unit_price`, `quantity`) VALUES
(6, 6, 1, 'DNT0001', 'Bàn phím cơ PMO P75', 'Den', 950000, 1),
(7, 7, 3, 'DNT0003', 'Chuột Mchose K7 Ultra Wireless', NULL, 1750000, 1),
(8, 8, 11, 'DNT0011', 'NUT108 - Bàn phím cơ Fullsize', 'Den', 1890000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(30) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(40) NOT NULL,
  `brand` varchar(80) NOT NULL DEFAULT 'STK Gears',
  `description` text DEFAULT NULL,
  `technical_specs` text DEFAULT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `old_price` int(10) UNSIGNED DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` text NOT NULL,
  `gallery_json` text DEFAULT NULL,
  `color_options` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `slug`, `name`, `category`, `brand`, `description`, `technical_specs`, `price`, `old_price`, `stock`, `image_url`, `gallery_json`, `color_options`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'DNT0001', 'pmo-p75', 'Switch outemu lime v2', 'accessory_keycap', 'OUTEMU', 'Outemu Lime V2 là switch cơ silent tactile, thiên về cảm giác gõ êm nhưng vẫn có khấc phản hồi nhẹ.\r\nThiết kế silent pad trên stem giúp giảm mạnh tiếng bottom-out và tiếng chạm housing khi gõ.\r\nPhiên bản V2 nâng cấp lên chuẩn 5-pin giúp switch ổn định hơn so với Lime V1 3-pin.\r\nSwitch được factory lubed sẵn, giảm scratch và cho cảm giác nhấn mượt hơn.\r\nPhù hợp môi trường cần yên tĩnh như văn phòng, ký túc xá hoặc setup làm việc ban đêm.', 'Loại switch:Silent Tactile\r\nMount:5-pin PCB Mount\r\nActuation force:~35g\r\nBottom-out:~50g\r\nPre-travel:~1.8mm\r\nTotal travel:~3.3mm\r\nStem:POM\r\nHousing:Nylon\r\nLubed:Factory lubed', 3000, 6000, 12, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/1-1768279991006.png?v=1768279995760', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/1-1768279991006.png?v=1768279995760\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/2-1768279993130.png?v=1768279997323\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/3-1768279995305.png?v=1768279999037\"]', 'Xanh lá', 1, '2026-05-16 03:35:42', '2026-05-16 11:37:07'),
(2, 'DNT0002', 'furycube-matcha', 'Bộ keycap Matcha XDA Nhật', 'accessory_keycap', 'TUT', 'Bộ keycap tone xanh matcha phối trắng kem mang phong cách nhẹ mắt, tối giản và clean setup.\r\nSử dụng profile XDA với bề mặt phẳng, chiều cao đồng đều giữa các hàng phím.\r\nChất liệu PBT dye-sub giúp hạn chế bóng dầu và giữ ký tự bền màu theo thời gian.\r\nBộ gồm khoảng 122–124 keycaps tùy phiên bản, hỗ trợ nhiều layout phổ biến.\r\nTương thích tốt với các layout 60%, 65%, 75%, TKL, 96%, 98% và fullsize.\r\nChỉ bao gồm keycap, không kèm bàn phím hay switch.', 'Profile:XDA\r\nChất liệu:PBT Dye-sub\r\nSố lượng phím:122–124\r\nCông nghệ in:Dye-sub\r\nTương thích:Hầu hết bàn phím cơ', 350000, 450000, 12, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/d592bbc0-81a5-44f4-87ae-dd629388ece3.jpg?v=1631000152987', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/d592bbc0-81a5-44f4-87ae-dd629388ece3.jpg?v=1631000152987\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/90c6210f-7964-4785-a6e6-b0dd22d17b3f.jpg?v=1631000153803\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/060be61f-4c67-426f-814a-7c6b06789736.jpg?v=1631000154857\"]', 'Xanh lá', 1, '2026-05-16 03:35:42', '2026-05-16 11:34:02'),
(3, 'DNT0003', 'mchose-k7-ultra', 'RAINY75 - Bàn phím cơ khung nhôm', 'keyboard_mech', 'WOBLAB', 'Bàn phím cơ layout 75% được xem là một trong các mẫu “quốc dân” nổi bật nhất phân khúc custom tầm trung.\r\nKhung nhôm CNC 6063 nguyên khối, trọng lượng nặng và hoàn thiện cao cấp hơn nhiều bàn phím gaming phổ thông.\r\nThiết kế gasket mount kết hợp nhiều lớp foam tiêu âm cho chất âm marbly/thocky đặc trưng.\r\nPCB hotswap 1.2mm flex-cut hỗ trợ thay switch không cần hàn.\r\nHỗ trợ 3 mode: USB-C, Bluetooth và wireless 2.4GHz.\r\nTương thích QMK/VIA để remap phím và macro trực tiếp.', 'Layout:75% (~81 phím)\r\nChất liệu:Nhôm CNC 6063\r\nMount:PCB Gasket Mount\r\nPCB:1.2mm Flex-cut Hotswap\r\nPlate:PP hoặc FR4\r\nKết nối:USB-C / Bluetooth / 2.4GHz\r\nPhần mềm:QMK / VIA\r\nKeycap:PBT Doubleshot Cherry\r\nPin:3500mAh hoặc 7000mAh\r\nTrọng lượng:~1.8kg – 2kg', 2100000, 2599000, 12, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/31-1702459953458-0c7c7350-4d57-4640-8252-411a8e07180b.png?v=1729429778933', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/31-1702459953458-0c7c7350-4d57-4640-8252-411a8e07180b.png?v=1729429778933\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/33-1702459955836-6002a039-f7d6-4fbd-8488-d63032a6bcc0.png?v=1729429779513\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/30-1702459951039-61d83103-cf6a-4925-bd45-7ead4c906966.png?v=1729429780313\"]', 'Xanh,Trắng,Hồng', 1, '2026-05-16 03:35:42', '2026-05-16 11:31:16'),
(4, 'DNT0004', 'deskmat-control-xl', 'Bàn phím cơ công thái học Record Alice 3 mode', 'keyboard_mech', 'WEIKAV', 'Bàn phím cơ công thái học layout Alice 66 phím, thiết kế tách nhẹ hai bên giúp đặt tay tự nhiên hơn khi gõ lâu.\r\nKhung nhôm CNC 6063 nguyên khối, hoàn thiện anodized hoặc e-coating tùy phiên bản.\r\nSử dụng Leaf-Spring Mount cho cảm giác gõ mềm, có độ nảy và giảm rung chấn.\r\nPCB hotswap 1.2mm flex-cut hỗ trợ thay switch không cần hàn.\r\nKết nối 3 chế độ: USB-C, Bluetooth và wireless 2.4GHz.\r\nHỗ trợ VIA keymap để remap phím và macro.\r\nTrang bị RGB từng phím cùng nhiều lớp foam tiêu âm cho âm gõ đầm và sạch tiếng.', 'Layout:Alice 66 phím\r\nChất liệu:Nhôm CNC 6063\r\nMount:Leaf-Spring Mount\r\nPCB:1.2mm Flex-cut Hotswap\r\nKết nối:USB-C / Bluetooth / 2.4GHz\r\nPlate:PC\r\nRGB:Per-key RGB\r\nHỗ trợ phần mềm:VIA\r\nTrọng lượng:Khoảng 1.3kg\r\nPin:3000–4000mAh', 1100000, 1500000, 2, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/5-min-1717851461088.png?v=1717851470647', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/5-min-1717851461088.png?v=1717851470647\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/2-min-1717851461060.png?v=1717851467060\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/3-min-1717851461069.png?v=1717851468030\"]', 'Trắng,Cam,Đỏ', 1, '2026-05-16 03:35:42', '2026-05-16 11:56:18'),
(5, 'DNT0005', 'qa-product-1778903711', 'Mchose G75 v2 - Bàn phím cơ 75%', 'keyboard_mech', 'MChose', 'Bàn phím cơ layout 75% hiện đại, giữ đầy đủ hàng F và cụm điều hướng nhưng vẫn gọn hơn TKL/fullsize.\r\nThiết kế gasket mount kết hợp nhiều lớp foam tiêu âm giúp âm gõ trầm, dày và hạn chế tiếng rỗng.\r\nHỗ trợ hotswap 5-pin, thay switch dễ dàng không cần hàn.\r\nKết nối 3 chế độ: USB-C, Bluetooth và wireless 2.4GHz.\r\nTích hợp núm xoay multimedia để chỉnh âm lượng và điều khiển nhanh.\r\nMạch south-facing tương thích tốt với keycap Cherry profile, hạn chế cấn switch.\r\nTrang bị RGB nhiều hiệu ứng và keycap PBT doubleshot bền màu.', 'Layout:75% (~80–81 phím)\r\nMounting:Gasket Mount\r\nKết nối:USB-C / Bluetooth / 2.4GHz\r\nPCB:Hotswap 5-pin, south-facing\r\nPlate:PP hoặc PC flex-cut\r\nKeycap:PBT doubleshot\r\nPin:8000–10000mAh\r\nRGB:16.8 triệu màu\r\nTương thích:Windows / macOS', 1150000, 1450000, 3, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/7-1760709085086.png?v=1760709098330', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/7-1760709085086.png?v=1760709098330\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/9-1760709085110.png?v=1760709098973\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/11-1772963662975.png?v=1772963677613\"]', 'Đen,Trắng,Hồng', 1, '2026-05-16 03:57:31', '2026-05-16 11:23:46'),
(11, 'DNT0011', 'qa-runtime-keyboard-20260516', 'NUT108 - Bàn phím cơ Fullsize', 'keyboard_mech', 'WEIKAV', 'Bàn phím cơ fullsize 108 phím hướng tới phân khúc custom cao cấp.\r\nKhung nhôm CNC nguyên khối 6063 xử lý Mesh 220, cho cảm giác cứng cáp, nặng tay và hoàn thiện cao.\r\nThiết kế gasket mount kết hợp full foam giúp âm gõ trầm, dày và giảm rỗng tiếng.\r\nHỗ trợ hotswap, thay switch không cần hàn.\r\nPCB flex-cut 1.2mm + plate FR4 tạo cảm giác gõ mềm và có độ nảy nhẹ.\r\nKết nối 3 mode: USB-C, Bluetooth và wireless 2.4GHz.\r\nPin 10.000mAh cho thời lượng sử dụng dài.\r\nHỗ trợ VIA/QMK để remap phím và macro.', 'Layout:Fullsize 108 phím\r\nMounting:Gasket\r\nChất liệu:Nhôm CNC 6063\r\nPCB:1.2mm Flex-cut Hotswap\r\nPlate:FR4\r\nKết nối:USB-C / Bluetooth / 2.4GHz\r\nPin:10.000mAh\r\nRGB:South-facing RGB + Double Light Bar\r\nPhần mềm:VIA / QMK\r\nTrọng lượng:Khoảng 2.4kg', 2690000, 3000000, 15, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/17-1774702180442.png?v=1774702216123', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/17-1774702180442.png?v=1774702216123\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/20-1774702175096.png?v=1774702216080\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/16-1774702183320.png?v=1774702216147\"]', 'Trắng, Đỏ, Hồng', 1, '2026-05-16 08:18:12', '2026-05-16 11:20:13'),
(12, 'DNT0012', 'switch-kailh-mx-bcp-linear-clacky', 'Switch Kailh MX BCP - Linear Clacky', 'accessory_switch', 'KailhBox', 'Kailh MX BCP (Black Cherry Pie) là switch linear thiên hướng clacky, nổi tiếng nhờ âm gõ cao, đanh và phản hồi rõ.\nThiết kế lấy cảm hứng từ cộng đồng “Frankenswitch” Black Cherry Pie nổi tiếng trong custom keyboard.\nSử dụng stem POM kiểu MX Cream kết hợp housing PA66 cho cảm giác trượt mượt và âm thanh sắc nét.\nSwitch được factory lubed sẵn, giảm scratch và tăng độ đồng đều khi sử dụng.\nChuẩn 5-pin PCB mount giúp độ ổn định tốt hơn trên kit hotswap.', 'Loại switch:Linear\nMount:5-pin PCB Mount\nLực nhấn:63.5g ±5gf\nLò xo:20mm\nPre-travel:1.4mm\nTotal travel:3.5mm\nStem:Dupont POM\nTop Housing:PA66\nBottom Housing:PA66\nLubed:Factory lubed', 8000, 10000, 0, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/1-1715924917634.png?v=1715924924880', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/1-1715924917634.png?v=1715924924880\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/2-1715924917651.png?v=1715924926070\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/4-1715924917678.png?v=1715924927753\"]', 'Đen', 1, '2026-05-16 11:39:04', '2026-05-16 11:39:04'),
(13, 'DNT0013', 'deskmat-cao-c-p-dps', 'Deskmat cao cấp DPS', 'pad_mouse', 'DPS Creation', 'Deskmat kích thước lớn dành cho setup gaming và workspace, đủ không gian đặt cả bàn phím và chuột.\nSử dụng bề mặt vải speed mịn, hỗ trợ di chuột nhanh và tracking ổn định.\nĐế cao su tự nhiên chống trượt, bám mặt bàn tốt khi gaming hoặc làm việc liên tục.\nViền được may bo kỹ giúp hạn chế bong mép và cong vênh sau thời gian dài sử dụng.\nHình in sắc nét bằng công nghệ chuyển nhiệt, chống phai màu tốt.', 'Thương hiệu:DPS Creation\nKích thước:900 × 400 × 4mm\nBề mặt:Vải trơn Speed\nĐế:Cao su chống trượt\nViền:May bo chống tưa\nKiểu sử dụng:Gaming / làm việc / setup', 320000, 350000, 12, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/o1cn01lhbfv427uuqadabqi-16807801-1669256814275.jpg?v=1669256845597', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/o1cn01lhbfv427uuqadabqi-16807801-1669256814275.jpg?v=1669256845597\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/4eb6606186005f5e0611-1669256810227.jpg?v=1669256845597\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/59ce050ee36f3a31637e-1669256806314.jpg?v=1669256845597\"]', 'Trắng', 1, '2026-05-16 11:41:57', '2026-05-16 11:41:57'),
(15, 'DNT0014', 'deskmat-fbb', 'Deskmat FBB', 'pad_mouse', 'FBB', 'Deskmat FBB là dòng lót chuột cỡ lớn thiên hướng tối giản, tập trung vào cảm giác bề mặt và tính thẩm mỹ setup.\r\nSử dụng chất liệu vải lanh pha cotton giúp bề mặt thoáng tay, ít bám mồ hôi và tạo cảm giác khác biệt so với deskmat vải speed thông thường.\r\nKích thước lớn đủ để đặt cả bàn phím và chuột trên cùng một bề mặt.\r\nĐế cao su tự nhiên chống trượt, bám bàn tốt khi gaming hoặc typing lâu.\r\nThiết kế đơn giản, màu trung tính như xám, beige, xanh navy giúp phối dễ với setup minimal hoặc workstation.', 'Kích thước:800×400mm hoặc 900×400mm\r\nĐộ dày:~3mm – 4mm\r\nBề mặt:Linen + Cotton\r\nĐế:Cao su thiên nhiên chống trượt\r\nKiểu pad:Deskmat full-size', 200000, 250000, 6, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/436/596/products/5-1749646537222.png?v=1749646546713', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/5-1749646537222.png?v=1749646546713\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/9-1749646537264.png?v=1749646548647\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/1024x1024\\/100\\/436\\/596\\/products\\/8-1749646537241.png?v=1749646547667\"]', 'Xanh lá,Xám', 1, '2026-05-16 11:44:32', '2026-05-16 11:56:06'),
(16, 'DNT0016', 'mchose-ace-68-air', 'MCHOSE Ace 68 Air', 'keyboard_hall', 'Mchose', 'MCHOSE Ace 68 Air là bàn phím gaming sử dụng switch từ Hall Effect (magnetic switch), tập trung vào tốc độ phản hồi cực thấp và khả năng tùy chỉnh actuation chuyên cho FPS/esports.\nLayout 68 phím dạng compact 65%, tiết kiệm diện tích nhưng vẫn giữ cụm phím điều hướng cơ bản.\nHỗ trợ Rapid Trigger, adjustable actuation và SOCD/Snap Tap kiểu Wooting.\nPolling rate lên tới 8000Hz, scan rate 128K cùng latency khoảng 0.08–0.1ms.\nSử dụng switch từ tính Kailh Magnetic God hoặc switch HE tùy phiên bản.\nThiết kế thiên gaming hiện đại với RGB lightbox và hiệu ứng LED động.', 'Layout:65% / 68 phím\nSwitch:Hall Effect Magnetic\nPolling Rate:8000Hz\nScan Rate:128K\nLatency:~0.08–0.1ms\nKết nối:Wired USB-C\nKeycap:PBT Cherry Profile\nRGB:Per-key RGB + Lightbox\nTính năng:Rapid Trigger, SOCD, Adjustable Actuation', 1600000, 1800000, 4, 'https://www.mchose.store/cdn/shop/files/mchose-official-mchose-ace-68-air-hall-effect-magnetic-switch-gaming-keyboard-1180847136.jpg?v=1767167476&width=800', '[\"https:\\/\\/www.mchose.store\\/cdn\\/shop\\/files\\/mchose-official-mchose-ace-68-air-hall-effect-magnetic-switch-gaming-keyboard-1180847136.jpg?v=1767167476&width=800\",\"https:\\/\\/www.mchose.store\\/cdn\\/shop\\/files\\/mchose-official-mchose-ace-68-air-hall-effect-magnetic-switch-gaming-keyboard-1180847135.jpg?v=1767167476&width=800\",\"https:\\/\\/www.mchose.store\\/cdn\\/shop\\/files\\/mchose-official-mchose-ace-68-air-hall-effect-magnetic-switch-gaming-keyboard-1180847141.jpg?v=1767167476&width=800\"]', 'Xanh,Hồng,Đen', 1, '2026-05-16 11:48:31', '2026-05-16 11:48:31'),
(17, 'DNT0017', 'mchose-jet-75', 'MCHOSE Jet 75', 'keyboard_hall', 'Mchose', 'MCHOSE Jet 75 là bàn phím gaming Hall Effect layout 75%, tập trung mạnh vào hiệu năng esports và tốc độ phản hồi cực thấp.\nSử dụng magnetic switch (Hall Effect switch) cho phép tùy chỉnh actuation theo từng phím và hỗ trợ Rapid Trigger.\nPolling rate lên tới 8000Hz cùng scan rate 128K giúp giảm input delay đáng kể trong FPS cạnh tranh.\nHỗ trợ các tính năng gaming cao cấp như SOCD Snap Tap, Dynamic Keystroke (DKS) và rapid reset tương tự Wooting.\nThiết kế gasket mount kết hợp foam tiêu âm giúp trải nghiệm gõ tốt hơn nhiều keyboard gaming truyền thống.\nCó nhiều phiên bản switch như Kailh Magnetic God hoặc Starburst Magnetic Switch.', 'Layout:75%\nSwitch:Hall Effect Magnetic\nPolling Rate:8000Hz\nScan Rate:128K\nLatency:~0.1ms\nActuation:0.1mm – 3.4/3.5mm\nKết nối:Wired USB-C\nMount:Gasket Mount\nKeycap:PBT shine-through\nRGB:Per-key RGB\nTrọng lượng	~1030g', 1700000, 2000000, 8, 'https://www.mchose.store/cdn/shop/files/mchose-official-white-starburst-magnetic-switch-mchose-jet-75-hall-effect-magnetic-switch-gaming-keyboard-1166639421.jpg?v=1773831262&width=800', '[\"https:\\/\\/www.mchose.store\\/cdn\\/shop\\/files\\/mchose-official-white-starburst-magnetic-switch-mchose-jet-75-hall-effect-magnetic-switch-gaming-keyboard-1166639421.jpg?v=1773831262&width=800\",\"https:\\/\\/www.mchose.store\\/cdn\\/shop\\/files\\/mchose-official-mchose-jet-75-hall-effect-magnetic-switch-gaming-keyboard-1166639417.jpg?v=1773831262&width=800\",\"https:\\/\\/www.mchose.store\\/cdn\\/shop\\/files\\/mchose-official-black-topographic-starburst-magnetic-switch-mchose-jet-75-hall-effect-magnetic-switch-gaming-keyboard-1166639422.jpg?v=1773831262&width=800\"]', 'Trắng,Đỏ,Đen', 1, '2026-05-16 11:50:57', '2026-05-16 11:50:57'),
(18, 'DNT0018', 'chu-t-mchose-k7-ultra-wireless', 'Chuột Mchose K7 Ultra Wireless', 'mouse_wireless', 'Mchose', 'Trang bị cảm biến PixArt PAW3950 flagship với DPI tối đa 42.000, tracking 750 IPS và gia tốc 50G.\nPolling rate lên tới 8000Hz cho cả wired và wireless, giảm input delay đáng kể khi chơi FPS.\nTrọng lượng khoảng 59g, phù hợp flick nhanh và tracking tốc độ cao.\nHỗ trợ kết nối 3 mode: USB-C wired, 2.4GHz và Bluetooth.\nĐi kèm dock sạc từ tính RGB kiêm receiver 8K, vừa tiện vừa mang tính thẩm mỹ setup.\nThiết kế đối xứng, form khá giống dòng Razer Viper V3 Pro theo nhiều reviewer cộng đồng.', 'Sensor:PixArt PAW3950\nDPI:Tối đa 42.000\nPolling Rate:8000Hz\nTracking Speed:750 IPS\nGia tốc:50G\nKết nối:Wired / 2.4GHz / Bluetooth\nTrọng lượng:~59g\nSwitch:Optical switch\nFeet:100% PTFE\nDock:Magnetic RGB Charging Dock', 1750000, 2000000, 4, 'https://bizweb.dktcdn.net/thumb/grande/100/534/572/products/0002-4164049171472614445-2.png?v=1761194061573', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/grande\\/100\\/534\\/572\\/products\\/0002-4164049171472614445-2.png?v=1761194061573\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/grande\\/100\\/534\\/572\\/products\\/hong-f808546e-ba01-4e4b-8b76-b44aba36bbe6.jpg?v=1770173793640\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/grande\\/100\\/534\\/572\\/products\\/0001-4164049171472614445-1.png?v=1770173793640\"]', 'Đen,Đỏ,Trắng', 1, '2026-05-16 11:54:56', '2026-05-16 11:54:56'),
(19, 'DNT0019', 'chu-t-wlmouse-beast-x-max-wireless-8khz', 'Chuột WLMouse Beast X Max Wireless 8KHz', 'mouse_wireless', 'WLMouse', 'Trọng lượng chỉ khoảng 42g dù dùng form cỡ trung/lớn.\nKhung magnesium alloy cho cảm giác cứng, nhẹ và premium hơn nhựa thông thường.\nSử dụng cảm biến PixArt PAW3950 HS flagship hỗ trợ tracking rất cao và polling rate wireless 8000Hz.\nClick latency cực thấp, đủ cho esports competitive.\nĐi kèm nhiều phụ kiện như extra feet, grip tape, receiver phụ và carrying case.\nThiết kế đối xứng, thiên claw grip và fingertip grip.', 'Sensor:PixArt PAW3950 HS\nPolling Rate:Wireless 8000Hz\nDPI:30.000–42.000\nTracking Speed:750 IPS\nGia tốc:50G–70G\nMCU:Nordic 52840\nKết nối:Wireless 2.4GHz / Wired USB-C\nTrọng lượng:~42g\nChất liệu:Magnesium Alloy\nBattery:300mAh', 3950000, 4150000, 6, 'https://bizweb.dktcdn.net/thumb/grande/100/534/572/products/screenshot-2024-12-30-105121.png?v=1739869306933', '[\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/grande\\/100\\/534\\/572\\/products\\/screenshot-2024-12-30-105121.png?v=1739869306933\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/grande\\/100\\/534\\/572\\/products\\/screenshot-2024-12-30-105255.png?v=1735530795800\",\"https:\\/\\/bizweb.dktcdn.net\\/thumb\\/grande\\/100\\/534\\/572\\/products\\/screenshot-2024-12-30-105000.png?v=1735530798263\"]', 'Xanh Hồng,Cam,Trắng', 1, '2026-05-16 11:59:11', '2026-05-16 11:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'customer', '$2y$10$LO4Me3yYN01jgV05n73zd.B7pOEYH0G1.D8wKnZEeBWq1hFit/hPS', '2026-05-16 03:35:42'),
(4, 'qauser1970', '$2y$10$7B3ig4xIxji8Y8MCE5sMsuIappc2TA6fRnAXuuvwT1whsVMHb0L1q', '2026-05-16 07:42:03'),
(5, 'truong', '$2y$10$/BoXsZPqUie1mPib3DBS.eNCgFy.z46gTp/DtSeBeVGor73vS6zh6', '2026-05-16 07:47:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_username` (`username`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_code` (`order_code`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_search` (`customer_name`,`customer_phone`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_slug` (`slug`),
  ADD UNIQUE KEY `uq_product_sku` (`sku`),
  ADD KEY `idx_products_category` (`category`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `fk_products_created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
