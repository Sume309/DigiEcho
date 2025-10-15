-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2025 at 03:17 PM
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
-- Database: `digiecho`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','user','guest') DEFAULT 'admin',
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `type` enum('auth','user','product','order','category','brand','system','payment','inventory','settings') DEFAULT 'system',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_type`, `action`, `description`, `type`, `ip_address`, `user_agent`, `metadata`, `created_at`) VALUES
(1, 1, 'admin', 'Login', 'Admin logged into the system', 'auth', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"login_method\":\"form\"}', '2025-09-28 19:35:20'),
(2, 1, 'admin', 'User Profile Updated', 'Updated user profile information', 'user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"fields_updated\":[\"email\",\"phone\"]}', '2025-09-28 20:35:20'),
(3, 1, 'admin', 'Product Added', 'Added new product to inventory', 'product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"product_id\":123,\"category\":\"Electronics\"}', '2025-09-28 21:05:20'),
(4, 1, 'admin', 'Order Status Updated', 'Changed order status from pending to processing', 'order', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"order_id\":\"ORD-001\",\"old_status\":\"pending\",\"new_status\":\"processing\"}', '2025-09-28 21:20:20'),
(5, 1, 'admin', 'Category Created', 'Created new product category', 'category', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"category_name\":\"Home & Garden\"}', '2025-09-28 21:25:20'),
(6, 1, 'admin', 'Settings Updated', 'Updated site settings and configuration', 'settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '{\"settings_changed\":[\"site_name\",\"logo\"]}', '2025-09-28 21:30:20');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `page_type` enum('homepage','hot-deals') DEFAULT 'homepage',
  `status` enum('active','inactive') DEFAULT 'active',
  `sort_order` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `subtitle`, `description`, `image`, `button_text`, `button_link`, `page_type`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to DigiEcho', 'Your Trusted Online Marketplace', 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!', 'assets/images/banners/banner_1759093830_68d9a446ed50a.webp', 'Shop Now', '', 'homepage', 'active', 1, '2025-09-28 20:24:15', '2025-09-28 21:10:30'),
(2, 'Welcome to DigiEcho', 'Your Trusted Online Marketplace', 'Discover amazing products with great deals, quality assurance, \r\nand fast delivery. Shop with confidence!', 'assets/images/banners/banner_1759093004_68d9a10c24bcc.png', 'OK', '', 'homepage', 'active', 1, '2025-09-28 20:56:44', '2025-09-28 20:57:51'),
(3, 'Welcome to DigiEcho', 'Your Trusted Online Marketplace', 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!', 'assets/images/banners/banner_1759093141_68d9a19524a80.webp', 'OK', '', 'homepage', 'active', 1, '2025-09-28 20:59:01', '2025-09-28 20:59:01'),
(4, 'Welcome to DigiEcho', 'Your Trusted Online Marketplace', 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!', 'assets/images/banners/banner_1759093287_68d9a2272109c.webp', 'Shop Now', '', 'homepage', 'active', 1, '2025-09-28 21:01:27', '2025-09-28 21:01:27'),
(5, 'Welcome to DigiEcho', 'Your Trusted Online Marketplace', 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!', 'assets/images/banners/banner_1759093337_68d9a2598a40e.webp', 'Shop Now', '', 'homepage', 'active', 1, '2025-09-28 21:02:17', '2025-09-28 21:02:17'),
(6, 'Welcome to DigiEcho', 'Your Trusted Online Marketplace', 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!', 'assets/images/banners/banner_1759093875_68d9a4739b80b.webp', 'Shop Now', '', 'homepage', 'active', 1, '2025-09-28 21:11:15', '2025-09-28 21:11:15'),
(7, 'Welcome to DigiEcho', 'Your Trusted Online Marketplace', 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!', 'assets/images/banners/banner_1759120682_68da0d2a6402b.jpg', 'Shop Now', '', 'homepage', 'active', 1, '2025-09-29 04:38:02', '2025-09-29 04:38:02'),
(8, '.', 'Amazing Hot Deals', 'Don\'t miss out on these incredible offers!', 'assets/images/banners/banner_1759122767_68da154fcc3b1.webp', 'Shop Now', '', 'hot-deals', 'active', 4, '2025-09-29 05:12:47', '2025-09-29 05:21:52'),
(9, 'HOT DEALS', 'Amazing Hot Deals', 'Don\'t miss out on these incredible offers!', 'assets/images/banners/banner_1759122955_68da160b8d05d.webp', 'Shop Now', '', 'hot-deals', 'active', 1, '2025-09-29 05:15:55', '2025-09-29 05:15:55'),
(10, 'HOT DEALS', 'Amazing Hot Deals', 'Don\'t miss out on these incredible offers!', 'assets/images/banners/banner_1759123000_68da16388c626.png', 'Shop Now', '', 'hot-deals', 'active', 1, '2025-09-29 05:16:40', '2025-09-29 05:16:40'),
(11, 'Get your Favorite ', 'Your Trusted Online Marketplace', 'Don\'t miss out on these incredible offers!', 'assets/images/banners/banner_1759123298_68da176280ed2.webp', '', '', 'hot-deals', 'active', 1, '2025-09-29 05:21:38', '2025-09-29 05:21:38'),
(12, '.', '.', '.', 'assets/images/banners/banner_1759124027_68da1a3b04729.webp', '', '', 'hot-deals', 'active', 1, '2025-09-29 05:33:47', '2025-09-29 05:33:47'),
(13, '.', '.', '.', 'assets/images/banners/banner_1759124056_68da1a58259ea.webp', '', '', 'hot-deals', 'active', 1, '2025-09-29 05:34:16', '2025-09-29 05:34:16');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `slug` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `logo`, `created_at`, `slug`, `description`, `website`, `meta_title`, `meta_description`, `meta_keywords`, `sort_order`, `is_active`, `is_featured`, `updated_at`) VALUES
(1, 'Lenovo', '68ac9ea57d438_1756143269.png', '2025-06-23 23:48:34', 'lenovo', NULL, NULL, NULL, NULL, NULL, 0, 1, 0, '2025-09-17 20:19:22'),
(6, 'Walton', '1758143416_68cb23b804e74.jpg', '2025-07-01 23:41:12', 'walton', 'Walton is the latest multinational electrical, electronics, automobiles and other appliances brand with one of the largest well equipped R & I facilities in the world carried out its production through different subsidiaries under the banner of Walton group headquarters in Bangladesh. Today, Walton has a workforce of more than 30000+ in total 22 production bases under 700+ acres of factory area. The capacity of yearly production is 10 million units based on the market demands. Walton is the giant professional manufacturer in the relevant industry and has gained high reputation in terms of its unbeatable capability for producing Electrical and Electronics goods in the most competitive way in aspect of quality, cost, design and innovation', 'https://waltonbd.com/', 'walton', 'walton', 'walton', 0, 1, 1, '2025-10-08 05:58:49'),
(9, 'Asus', '68b1cd8e1c793_1756482958.png', '2025-08-29 15:55:46', 'asus', '', 'https://www.asus.com/', '', '', '', 0, 1, 0, '2025-09-17 21:19:29'),
(10, 'HP', '68b1cdb099f65_1756482992.png', '2025-08-29 15:55:58', 'hp', NULL, NULL, NULL, NULL, NULL, 0, 1, 0, '2025-09-17 20:19:22'),
(11, 'Acer', '68b1cdbeccaf5_1756483006.png', '2025-08-29 15:56:32', 'acer', '', 'https://www.acer.com/', 'Acer', 'Laptop', 'Laptop, acer', 0, 1, 1, '2025-09-17 20:56:09'),
(12, 'Smart', '68b1cdcb77e8f_1756483019.png', '2025-08-29 15:56:46', 'smart', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-09-17 21:15:37'),
(13, 'Dell', '68b1ce083f82c_1756483080.png', '2025-08-29 15:56:59', 'dell', '', 'https://www.dell.com/en-us', '', '', '', 0, 1, 0, '2025-09-17 21:23:01'),
(14, 'Microsoft', '68b1ce1a2581e_1756483098.png', '2025-08-29 15:58:00', 'microsoft', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-09-17 21:16:15'),
(15, 'Infinix', '68b1ce2b85f9d_1756483115.jfif', '2025-08-29 15:58:18', 'infinix', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-09-17 21:15:58'),
(16, 'Gigabyte', '68b1ce3b92353_1756483131.png', '2025-08-29 15:58:35', 'gigabyte', '', 'https://www.gigabyte.com/', '', '', '', 0, 0, 0, '2025-09-17 21:23:26'),
(17, 'Tecno', '68b1ce61908f4_1756483169.jfif', '2025-08-29 15:58:51', 'tecno', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-09-17 20:52:13'),
(18, 'Chuwi', '68b1ce935e1e9_1756483219.jfif', '2025-08-29 15:59:29', 'chuwi', '', 'https://www.chuwi.com/', '', '', '', 0, 0, 0, '2025-09-17 21:22:29'),
(19, 'AOC', '68b1cea48c4af_1756483236.png', '2025-08-29 16:00:19', 'aoc', '', 'https://aoc.com/uk/', '', '', '', 0, 0, 1, '2025-09-17 21:18:43'),
(20, 'Samsung', '1758143034_68cb223a3dadb.jpeg', '2025-09-17 21:02:01', 'samsung', 'Samsung Electronics constantly reinvents the future. We explore the unknown to discover technologies to help people all over the world lead happier, healthier lives.', 'https://www.samsung.com/', 'Samsung', 'Samsung', 'Samsung, smart phone', 0, 1, 1, '2025-09-17 21:03:54'),
(21, 'Apple', '1758691965_68d3827d144d5.png', '2025-09-24 05:32:45', 'apple', 'Apple’s Board of Directors oversees the CEO and other senior management in the competent and ethical operation of Apple on a day-to-day basis and ensures that the long-term interests of shareholders are being served. Our integrated approach means that decisions about environmental and social issues are reviewed at the highest levels of the company. Executive Team members regularly review each new product during its development, focusing on material and design choices, the supply chain, packaging, and product energy efficiency.', 'https://www.apple.com/', 'iphone', 'iphone', 'iphone, apple', 0, 1, 1, '2025-09-24 05:32:45'),
(22, 'Nokia', '1758699345_68d39f518aa0b.png', '2025-09-24 07:35:45', 'nokia', 'Nokia', 'https://www.nokia.com/', 'Nokia', 'Nokia', 'Noka', 0, 1, 0, '2025-09-24 07:35:45'),
(23, 'Oppo', '1758699386_68d39f7ac4724.png', '2025-09-24 07:36:26', 'oppo', 'Oppo', 'https://www.oppo.com/bd/', 'Oppo', 'Oppo', '', 0, 1, 1, '2025-09-24 07:36:26'),
(24, 'Itel', '1758699453_68d39fbd5ce50.png', '2025-09-24 07:37:33', 'itel', 'Hot Products · CITY 100 · Power 70 · S25 Ultra · A90 · S25. Tk 13,990 + VAT. News. itel launches CITY 100 smartphone with ai features and bold design for gen z ...', 'https://www.itel-life.com/bd/', 'Itel', 'Hot Products · CITY 100 · Power 70 · S25 Ultra · A90 · S25. Tk 13,990 + VAT. News. itel launches CITY 100 smartphone with ai features and bold design for gen z', '', 0, 1, 1, '2025-09-24 07:37:33'),
(25, 'HUAWEI', '1758699524_68d3a004b232f.png', '2025-09-24 07:38:44', 'huawei', 'Hot Products · CITY 100 · Power 70 · S25 Ultra · A90 · S25. Tk 13,990 + VAT. News. itel launches CITY 100 smartphone with ai features and bold design for gen z ...', 'https://www.huawei.com/en/', 'HUAWEI Phones', 'Hot Products · CITY 100 · Power 70 · S25 Ultra · A90 · S25. Tk 13,990 + VAT. News. itel launches CITY 100 smartphone with ai features and bold design for gen z', 'Huawi', 0, 1, 0, '2025-09-24 07:38:44'),
(26, 'Realme', '1758699610_68d3a05ab4a14.png', '2025-09-24 07:40:10', 'realme', 'Realme is a multinational Chinese consumer electronics manufacturer that specializes in smartphones, wearables, and other smart-home devices. It is known for offering performance and features at competitive prices, particularly in the budget and mid-range segments of the marke', 'https://www.realme.com/bd/', 'Realme', 'Realme is a multinational Chinese consumer electronics manufacturer that specializes in smartphones, wearables, and other smart-home devices. It is known for of', 'Realme phone', 0, 1, 1, '2025-09-24 07:40:10'),
(27, 'Symphony', '1758699739_68d3a0db1173c.png', '2025-09-24 07:42:19', 'symphony', 'Symphony Mobile is a Bangladeshi mobile phone brand and a subsidiary of the Edison Group. Though it initially rebranded and imported phones from China, the company now manufactures and assembles devices in its own factory in Bangladesh. It is known for its affordable and feature-packed smartphones and feature phone', 'https://www.oppo.com/bd/', 'Symphony', 'Symphony Mobile is a Bangladeshi mobile phone brand and a subsidiary of the Edison Group. Though it initially rebranded and imported phones from China, the comp', 'Symphony Phone', 0, 1, 1, '2025-09-24 07:42:19'),
(28, 'Vivo', '1758699806_68d3a11ed71d5.png', '2025-09-24 07:43:26', 'vivo', 'Vivo is a Chinese technology company founded in 2009 that specializes in designing and developing smartphones, smartphone accessories, software, and online services. Headquartered in Dongguan, Guangdong, Vivo is a major global smartphone brand operating in over 100 countrie', 'https://www.vivo.com/bd/', 'Vivo', 'Vivo is a Chinese technology company founded in 2009 that specializes in designing and developing smartphones, smartphone accessories, software, and online serv', 'Vivo phone', 0, 1, 0, '2025-09-24 07:43:26'),
(29, 'Xiaomi', '1758699863_68d3a157c689d.png', '2025-09-24 07:44:23', 'xiaomi', 'Xiaomi is a Chinese technology company that produces a wide range of consumer electronics, including smartphones, smart home devices, and electric vehicles. It is known for its competitive pricing, especially through its sub-brands such as Redmi and POCO', 'https://www.mi.com/global/', 'Xiaomi', 'Xiaomi is a Chinese technology company that produces a wide range of consumer electronics, including smartphones, smart home devices, and electric vehicles. It', 'Xiaomi', 0, 1, 1, '2025-09-24 07:44:23');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `parent_id` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meta_title` varchar(200) DEFAULT NULL COMMENT 'SEO meta title',
  `meta_description` text DEFAULT NULL COMMENT 'SEO meta description',
  `meta_keywords` text DEFAULT NULL COMMENT 'SEO meta keywords'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `is_active`, `sort_order`, `parent_id`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `meta_keywords`) VALUES
(5, 'Laptop', 'electronics', 'All Kind of Laptop Brand', '68acbf2271e26_1756151586.png', 1, 0, 0, '2025-06-04 06:02:20', '2025-10-04 19:51:36', NULL, NULL, NULL),
(14, 'DeskTop', 'desktop', 'All kind of Desktop brand', '68acc037e3fd3_1756151863.png', 0, 0, 0, '2025-08-25 19:57:43', '2025-10-04 19:52:23', NULL, NULL, NULL),
(15, 'Smart Phone', 'Mobile', 'All Kind of Smart Phone and Mobile brand', '68b1909e5e73d_1756467358.jfif', 1, 0, 0, '2025-08-29 11:35:58', '2025-09-17 20:14:10', NULL, NULL, NULL),
(16, 'Camera', 'Camera', 'DSLR\r\nDigital Camera\r\nHandycam \r\nCamera Lenses\r\nAction Camera\r\nMirrorless Camera\r\nVideo Camera\r\nDash Cam\r\nInstant Camera\r\nCamera Tripod\r\nCamera Accessories', '68b1943759396_1756468279.jfif', 1, 0, 0, '2025-08-29 11:51:19', '2025-09-21 09:00:50', NULL, NULL, NULL),
(17, 'Office Equipment', 'Office Equipment', 'ProjectorConference SystemPA SystemInteractive Flat PanelVideo WallSignageKioskPrinterLaser PrinterLarge Format PrinterID Card PrinterPOS PrinterLabel PrinterDot Matrix PrinterPhotocopierPrinter HeadTonerCartridgeInk BottleRibbonPrinter PaperPrinter DrumScannerBarcode ScannerCash DrawerFaxTelephone SetIP PhonePABX SystemMoney Counting MachinePaper ShredderLaminating MachineBinding Machine', '68b1adfdcd539_1756474877.jfif', 1, 0, 0, '2025-08-29 13:41:17', '2025-09-21 09:01:12', NULL, NULL, NULL),
(18, 'Computer Components', 'components', 'ptomaAcerBenQEpsonViewSonicBoxlightXiaomiAUNPhilipsHavitXINJIBlisbondProjection ScreenProjector MountVIVItekCheerluxInFocus', '68b1ae5acab24_1756474970.jfif', 1, 0, 0, '2025-08-29 13:42:50', '2025-09-16 17:42:43', NULL, NULL, NULL),
(19, 'Monitor', 'monitor', 'AOCLenovoCorsairDahuaValue-TopPC PowerHikvisionAIWANPCWaltonGigasonicTrendSonicThundeRobotTitan ArmyGaming MonitorCurved MonitorTouch Monitor4K MonitorMonitor ArmMSILGAsusHPDellSamsungGIGABYTEViewsonicAcerBenQPHILIPSXIAOMI', '68b1aea7d062e_1756475047.jfif', 1, 0, 0, '2025-08-29 13:44:07', '2025-10-04 19:50:32', NULL, NULL, NULL),
(20, 'Security', 'Security', 'Portable WiFi CameraIP CameraCC CameraPTZ CameraCC Camera PackageIP Camera PackageDVRNVRXVRCC Camera AccessoriesDoor LockSmart Door BellAccess ControlEntrance ControlDigital Locker & VaultKVM Switch', '68b1b07b4d3f3_1756475515.jfif', 1, 0, 0, '2025-08-29 13:51:55', '2025-08-29 13:51:55', NULL, NULL, NULL),
(21, 'Networking', 'Networking', 'StarlinkRouterPocket RouterAccess Point & Range ExtenderNetwork SwitchWiFi AdapterFirewallNetworking CableLAN CardPatch CordConnectorONUOLTSplicer MachineCrimping ToolOTDRPoE InjectorNetwork TransceiversFaceplatePatch PanelMedia ConverterCable Tester', '68b1b0edcf6bf_1756475629.jfif', 1, 0, 0, '2025-08-29 13:53:49', '2025-09-17 19:07:34', NULL, NULL, NULL),
(22, 'Server & Storage', 'Server & Storage', 'ServerGPU ServerWorkstationServer RackNAS StorageSAN StorageDAS StorageServer HDDServer HDD BayServer RAMServer SSDServer Power Supply', '68b1b1698cd48_1756475753.jfif', 1, 0, 0, '2025-08-29 13:55:53', '2025-08-29 13:55:53', NULL, NULL, NULL),
(23, 'Accessories', 'accessories', 'KeyboardMouseHeadphoneBluetooth HeadphoneMouse PadWrist RestHeadphone StandSpeaker & Home TheaterBluetooth SpeakersWebcamSoundbarCableConverterCard ReaderHubs & DocksMicrophoneDigital Voice RecorderPresenterMemory CardSound CardCapture CardPen DriveThermal PasteHDD-SSD EnclosurePower StripBluetooth AdapterPC Lighting & LED Strips', '68b1b1baf333b_1756475834.jfif', 1, 0, 14, '2025-08-29 13:57:14', '2025-09-29 04:52:08', NULL, NULL, NULL),
(24, 'Gadget', 'Gadget', 'Smart WatchSmart BandEarphoneEarbudsNeckbandSmart GlassesMini FanSmart RingPower BankTV BoxStudio EquipmentDronesGimbalDaily LifestyleCalculatorBlower MachineStream Deck', '68b1b73a98992_1756477242.jfif', 1, 0, 0, '2025-08-29 14:20:42', '2025-08-29 14:20:42', NULL, NULL, NULL),
(26, 'Appliance', 'Appliance', 'Appliance\r\nAC \r\nRefrigerator\r\nWashing Machine\r\nFan\r\nSewing Machine\r\nAir Purifier\r\nAir Cooler\r\nAir Fryer\r\nVacuum Cleaner\r\nOven\r\nBlender\r\nGeyser\r\nRoom Heater\r\nElectric Kettle\r\nCookerIron', '68b1b832f028c_1756477490.jfif', 1, 0, 0, '2025-08-29 14:24:50', '2025-09-21 09:00:39', NULL, NULL, NULL),
(27, 'Power', 'Power', 'UPSOnline UPSMini UPSPortable Power StationIPSUPS BatteryVoltage StabilizerSolar Panel', '68b1c5bb8a4d1_1756480955.png', 1, 0, 0, '2025-08-29 15:22:35', '2025-08-29 15:22:35', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `status` enum('active','closed','pending') NOT NULL DEFAULT 'active',
  `assigned_admin` int(11) DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_conversations`
--

INSERT INTO `chat_conversations` (`id`, `user_id`, `user_name`, `user_email`, `session_id`, `status`, `assigned_admin`, `last_message_at`, `created_at`, `updated_at`) VALUES
(2, NULL, 'Mrs Khadiza', 'khadiza@gmail.com', 'chat_1759054433155_iur7yb_1oi3dze6q_a311140c38dfea577a22dbfea6221146', 'active', NULL, '2025-09-28 16:14:51', '2025-09-28 16:13:53', '2025-09-28 16:14:51'),
(3, 13, 'Mr Shakib', 'mr@gmail.com', 'chat_1759055109148_iur7yb_bc70z06i1_94374442b8cd835609ed45ae3dc34924', 'active', NULL, '2025-09-29 00:21:07', '2025-09-28 16:25:09', '2025-09-29 00:21:07'),
(4, 18, 'Asad khan', 'asad@outlook.com', 'chat_1759055498747_iur7yb_urk7k0ghp_fcf6b33e75060a9a9743fa8e04d2a26e', 'active', NULL, '2025-09-28 16:32:03', '2025-09-28 16:31:38', '2025-09-28 16:32:03'),
(5, 7, 'Admin ', 'admin@gmail.com', 'chat_1759057138938_iur7yb_gd14znpwl_bcca190549e832eff9ddd70d7f94b22d', 'active', NULL, '2025-10-08 10:38:42', '2025-09-28 16:58:58', '2025-10-08 10:38:42'),
(6, 19, 'sajjad mia', 'sajjad@gmail.com', 'chat_1759057998120_iur7yb_ovypi2ttk_e09ae28193acbd28883527005b2b201f', 'closed', NULL, '2025-09-28 17:13:21', '2025-09-28 17:13:18', '2025-09-28 22:10:04'),
(7, 21, 'rayhan Khan', 'rayhan@gmail.com', 'chat_1759086938672_bofwyo_n1docrn08_ddbb66e30d264e49ae73c60f867d1358', 'active', NULL, '2025-09-29 01:15:41', '2025-09-29 01:15:38', '2025-09-29 01:15:41'),
(8, 17, 'Mrs Khadiza', 'khadiza@gmail.com', 'chat_1759087561400_bofwyo_ensg7zesd_57bf81ce4f4caa242c301400c926df0c', 'active', NULL, '2025-09-29 03:52:06', '2025-09-29 01:26:01', '2025-09-29 03:52:06'),
(9, 22, 's s', 'ss@gmail.com', 'chat_1759094517430_bofwyo_ea6jqowrx_f1ff7f8364166bff1a6ad9a76ab10ac6', 'active', NULL, '2025-09-29 03:28:28', '2025-09-29 03:21:57', '2025-09-29 03:28:28'),
(10, 23, 'shahin Hossen', 'shahin@gmail.com', 'chat_1759898984521_ut1f1e_af3eod9yh_0c4543ccc26b11ba6b361bfeae44580f', 'active', NULL, '2025-10-08 10:50:06', '2025-10-08 10:49:44', '2025-10-08 10:50:06');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','image','file','system') NOT NULL DEFAULT 'text',
  `attachment_path` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `conversation_id`, `sender_type`, `sender_id`, `sender_name`, `message`, `message_type`, `attachment_path`, `is_read`, `created_at`) VALUES
(9, 2, '', NULL, 'System', 'Hello Mrs Khadiza! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-28 16:13:53'),
(10, 2, 'user', NULL, 'Mrs Khadiza', 'hi', 'text', NULL, 1, '2025-09-28 16:13:56'),
(11, 2, 'user', NULL, 'Mrs Khadiza', 'Are you available ?', 'text', NULL, 1, '2025-09-28 16:14:06'),
(12, 2, 'admin', NULL, 'admin', 'Yes Mam', 'text', NULL, 0, '2025-09-28 16:14:34'),
(13, 2, 'admin', NULL, 'admin', 'How can i help u?', 'text', NULL, 0, '2025-09-28 16:14:51'),
(14, 3, '', NULL, 'System', 'Hello Mr Shakib! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-28 16:25:09'),
(15, 3, 'user', NULL, 'Mr Shakib', 'my i get support?', 'text', NULL, 1, '2025-09-28 16:25:23'),
(16, 3, 'admin', NULL, 'admin', 'Yes how can i help you.', 'text', NULL, 0, '2025-09-28 16:26:12'),
(17, 3, 'user', NULL, 'Mr Shakib', 'Have this product available yet?', 'text', NULL, 1, '2025-09-28 16:27:28'),
(18, 3, 'user', NULL, 'Mr Shakib', 'HP Pro Tower 280', 'text', NULL, 1, '2025-09-28 16:27:30'),
(19, 3, 'admin', NULL, 'admin', 'No sir', 'text', NULL, 0, '2025-09-28 16:28:35'),
(20, 3, 'admin', NULL, 'admin', 'Sorry', 'text', NULL, 0, '2025-09-28 16:28:36'),
(21, 3, 'admin', NULL, 'admin', 'Its not available right now.', 'text', NULL, 0, '2025-09-28 16:29:01'),
(22, 4, '', NULL, 'System', 'Hello Asad khan! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-28 16:31:38'),
(23, 4, 'user', NULL, 'Asad khan', 'hi', 'text', NULL, 1, '2025-09-28 16:31:46'),
(24, 4, 'user', NULL, 'Asad khan', 'Assalamuwalikum', 'text', NULL, 1, '2025-09-28 16:32:03'),
(25, 5, '', NULL, 'System', 'Hello admin admin! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-28 16:58:58'),
(26, 6, '', NULL, 'System', 'Hello sajjad mia! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-28 17:13:18'),
(27, 6, 'user', NULL, 'sajjad mia', 'hi', 'text', NULL, 1, '2025-09-28 17:13:21'),
(28, 5, 'user', NULL, 'admin admin', 'hi', 'text', NULL, 1, '2025-09-28 17:54:17'),
(29, 5, 'user', NULL, 'admin admin', 'hi', 'text', NULL, 1, '2025-09-28 20:19:47'),
(30, 3, 'user', NULL, 'Mr Shakib', 'Assalamuwalaikum', 'text', NULL, 1, '2025-09-29 00:21:07'),
(31, 7, '', NULL, 'System', 'Hello rayhan Khan! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-29 01:15:38'),
(32, 7, 'user', NULL, 'rayhan Khan', 'Hi', 'text', NULL, 1, '2025-09-29 01:15:41'),
(33, 8, '', NULL, 'System', 'Hello Mrs Khadiza! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-29 01:26:01'),
(34, 8, 'user', NULL, 'Mrs Khadiza', 'Hi', 'text', NULL, 1, '2025-09-29 01:26:05'),
(35, 8, 'user', NULL, 'Mrs Khadiza', 'Are you available', 'text', NULL, 1, '2025-09-29 01:26:11'),
(36, 8, 'user', NULL, 'Mrs Khadiza', 'I have successfully place a order please check', 'text', NULL, 1, '2025-09-29 01:26:38'),
(37, 5, 'user', NULL, 'Admin Shakib', 'Hlw', 'text', NULL, 1, '2025-09-29 01:51:20'),
(38, 9, '', NULL, 'System', 'Hello s s! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-09-29 03:21:57'),
(39, 9, 'user', NULL, 's s', 'Hlw!', 'text', NULL, 1, '2025-09-29 03:26:47'),
(40, 9, 'user', NULL, 's s', 'i am just place a successful order pleas let me know when i get it?', 'text', NULL, 1, '2025-09-29 03:27:23'),
(41, 9, 'admin', NULL, 'Admin', 'ASAP', 'text', NULL, 0, '2025-09-29 03:28:28'),
(42, 8, 'admin', NULL, 'Admin', 'Checked. please wait', 'text', NULL, 0, '2025-09-29 03:52:06'),
(43, 5, 'admin', NULL, 'Admin', 'Sir how can i help you?', 'text', NULL, 0, '2025-09-29 03:52:28'),
(44, 5, 'user', NULL, 'Admin ', 'Hello i am admin', 'text', NULL, 1, '2025-10-08 10:38:42'),
(45, 10, '', NULL, 'System', 'Hello shahin Hossen! Welcome to our live chat. How can we help you today?', 'system', NULL, 0, '2025-10-08 10:49:44'),
(46, 10, 'user', NULL, 'shahin Hossen', 'Hi this is my first order in your page', 'text', NULL, 1, '2025-10-08 10:50:06');

-- --------------------------------------------------------

--
-- Table structure for table `chat_typing_indicators`
--

CREATE TABLE `chat_typing_indicators` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_type` enum('user','admin') NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `is_typing` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('new','read','replied','archived') NOT NULL DEFAULT 'new',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `admin_notes` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `phone`, `ip_address`, `user_agent`, `status`, `priority`, `admin_notes`, `replied_at`, `replied_by`, `created_at`, `updated_at`) VALUES
(1, 'Mrs Khadiza', 'khadiza@gmail.com', 'Product Question', 'HP Pro Tower 280\r\nThere are a product that is already stock out. may i get this product?', '01998765432', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'read', 'normal', NULL, NULL, NULL, '2025-09-28 14:12:39', '2025-09-28 14:15:38'),
(3, 'mr', 'mr@gmail.com', 'Order Support', 'Need support', '0199876000', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'read', 'high', NULL, NULL, NULL, '2025-09-28 14:14:51', '2025-09-28 14:15:14'),
(4, 'Mrs Khadiza', 'khadiza@gmail.com', 'Technical Issue', 'Urgent need support', '01998765432', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'read', 'high', NULL, NULL, NULL, '2025-09-28 14:17:14', '2025-09-28 14:18:03'),
(7, 'Admin', 'admin@gmail.com', 'Technical Issue', 'ASAP', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'read', 'high', NULL, NULL, NULL, '2025-09-28 17:54:12', '2025-09-28 20:26:14'),
(8, 'Mr', 'admin@gmail.com', 'Order Support', 'Urgent need support', '+880 1234-567890', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'read', 'high', NULL, NULL, NULL, '2025-09-28 21:57:37', '2025-09-29 10:31:12'),
(9, 'Shahin Hossen', 'shahin@gmail.com', 'Order Support', 'I have successfully place order but not found my product yet', '01412345678', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'new', 'high', NULL, NULL, NULL, '2025-10-08 11:00:22', '2025-10-08 11:00:22'),
(10, 'Shahin Hossen', 'shahin@gmail.com', 'Technical Issue', 'I can\'t find my invoice', '01412345678', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'new', 'high', NULL, NULL, NULL, '2025-10-08 11:04:34', '2025-10-08 11:04:34'),
(11, 'Sultan', 'Sultan@outlook.com', 'Feedback', 'Good job', '01555555550', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'read', 'normal', NULL, NULL, NULL, '2025-10-08 11:07:21', '2025-10-08 11:09:15');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('fixed','percentage') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT 0.00,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `valid_until` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('billing','shipping') DEFAULT 'shipping',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line_1` varchar(200) NOT NULL,
  `address_line_2` varchar(200) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Bangladesh',
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales_summary`
--

CREATE TABLE `daily_sales_summary` (
  `sale_date` date DEFAULT NULL,
  `total_orders` bigint(21) NOT NULL,
  `total_sales` decimal(32,2) DEFAULT NULL,
  `average_order_value` decimal(14,6) DEFAULT NULL,
  `order_type` enum('online','pos') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_sales_summary`
--

INSERT INTO `daily_sales_summary` (`sale_date`, `total_orders`, `total_sales`, `average_order_value`, `order_type`) VALUES
('2025-07-01', 2, 760.00, 380.000000, 'online'),
('2025-07-01', 3, 1209.51, 403.170000, 'pos'),
('2025-07-02', 2, 19656.00, 9828.000000, 'pos'),
('2025-07-17', 3, 165700.00, 55233.333333, 'pos'),
('2025-09-24', 1, 55620.00, 55620.000000, 'pos'),
('2025-09-27', 3, 468498.50, 156166.166667, 'online'),
('2025-09-29', 3, 217347.70, 72449.233333, 'online'),
('2025-09-29', 1, 6762.96, 6762.960000, 'pos'),
('2025-10-05', 4, 363375.85, 90843.962500, 'online'),
('2025-10-06', 1, 163300.00, 163300.000000, 'online');

-- --------------------------------------------------------

--
-- Stand-in structure for view `low_stock_products`
-- (See below for the actual view)
--
CREATE TABLE `low_stock_products` (
`id` int(11)
,`name` varchar(200)
,`sku` varchar(100)
,`stock_quantity` int(11)
,`min_stock_level` int(11)
,`category_name` varchar(100)
,`subcategory_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `monthly_sales_summary` (
`sale_year` int(4)
,`sale_month` int(2)
,`total_orders` bigint(21)
,`total_sales` decimal(32,2)
,`average_order_value` decimal(14,6)
,`order_type` enum('online','pos')
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'new_order, low_stock, system, etc.',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` text DEFAULT NULL COMMENT 'JSON encoded data with additional information',
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `type`, `is_read`, `metadata`, `created_at`, `read_at`) VALUES
(116, 'Order Status Updated', 'Order #ORD-1759628097-7641 status changed to delivered: ok', 'order_update', 1, '{\"order_id\":157,\"order_number\":\"ORD-1759628097-7641\",\"status\":\"delivered\",\"details\":\"ok\"}', '2025-10-05 07:37:05', '2025-10-05 07:38:14'),
(117, 'Payment Paid', 'Payment of BDT 32763.50 via cash for Order #ORD-1759628097-7641 has been paid', 'payment', 1, '{\"order_id\":157,\"order_number\":\"ORD-1759628097-7641\",\"amount\":\"32763.50\",\"payment_method\":\"cash\",\"status\":\"paid\"}', '2025-10-05 07:37:13', '2025-10-05 07:38:14'),
(118, 'Product Activity', 'Product \'KAIMAN Z22 Walton\' updated', 'system', 1, '{\"product_id\":93,\"product_name\":\"KAIMAN Z22 Walton\",\"activity\":\"updated\",\"username\":\"Admin\"}', '2025-10-05 07:39:02', '2025-10-05 07:39:39'),
(119, 'User Activity', 'Mr Shakib logged in', 'user_activity', 1, '{\"user_id\":13,\"username\":\"Mr Shakib\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 07:39:57', '2025-10-05 08:17:14'),
(120, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 07:57:58', '2025-10-05 08:17:14'),
(121, 'Payment Paid', 'Payment of BDT 184000.00 via bKash for Order #ORD-1759621172-2005 has been paid', 'payment', 1, '{\"order_id\":156,\"order_number\":\"ORD-1759621172-2005\",\"amount\":\"184000.00\",\"payment_method\":\"bKash\",\"status\":\"paid\"}', '2025-10-05 07:58:19', '2025-10-05 08:17:14'),
(122, 'Order Status Updated', 'Order #ORD-1759621172-2005 status changed to delivered', 'order_update', 1, '{\"order_id\":156,\"order_number\":\"ORD-1759621172-2005\",\"status\":\"delivered\",\"details\":\"\"}', '2025-10-05 07:58:24', '2025-10-05 08:17:14'),
(123, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 00:02:56', '2025-10-05 00:07:34'),
(124, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 00:13:09', '2025-10-05 00:22:24'),
(125, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 23:09:09', '2025-10-05 23:35:36'),
(126, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 23:27:59', '2025-10-05 23:35:36'),
(127, 'Order Status Updated', 'Order #ORD-1759620419-1744 status changed to cancelled', 'order_update', 1, '{\"order_id\":155,\"order_number\":\"ORD-1759620419-1744\",\"status\":\"cancelled\",\"details\":\"\"}', '2025-10-05 23:40:49', '2025-10-06 00:05:11'),
(128, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 23:41:44', '2025-10-06 00:05:11'),
(129, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-05 23:54:55', '2025-10-06 00:05:11'),
(130, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-06 00:04:12', '2025-10-06 00:05:11'),
(131, 'New Order Received', 'Order #ORD-1759687797-3326 has been placed for BDT 163300', 'new_order', 1, '{\"order_id\":158,\"order_number\":\"ORD-1759687797-3326\",\"customer_id\":7,\"amount\":163300}', '2025-10-06 00:09:57', '2025-10-06 00:36:25'),
(132, 'Payment Pending', 'Payment of BDT 163300 via cod for Order #ORD-1759687797-3326 has been pending', 'payment', 1, '{\"order_id\":158,\"order_number\":\"ORD-1759687797-3326\",\"amount\":163300,\"payment_method\":\"cod\",\"status\":\"pending\"}', '2025-10-06 00:09:57', '2025-10-06 00:36:25'),
(133, 'High-Value Order', 'Order #ORD-1759687797-3326 is BDT 163300 (≥ BDT 10000)', 'high_value_order', 1, '{\"order_id\":158,\"order_number\":\"ORD-1759687797-3326\",\"amount\":163300,\"threshold\":10000}', '2025-10-06 00:09:57', '2025-10-06 00:36:25'),
(134, 'Order Status Updated', 'Order #ORD-1759687797-3326 status changed to delivered', 'order_update', 1, '{\"order_id\":158,\"order_number\":\"ORD-1759687797-3326\",\"status\":\"delivered\",\"details\":\"\"}', '2025-10-06 00:10:27', '2025-10-06 00:36:25'),
(135, 'Payment Paid', 'Payment of BDT 163300.00 via bKash for Order #ORD-1759687797-3326 has been paid', 'payment', 1, '{\"order_id\":158,\"order_number\":\"ORD-1759687797-3326\",\"amount\":\"163300.00\",\"payment_method\":\"bKash\",\"status\":\"paid\"}', '2025-10-06 00:10:34', '2025-10-06 00:36:25'),
(136, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-06 00:19:29', '2025-10-06 00:36:25'),
(137, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-06 21:59:31', '2025-10-06 21:59:36'),
(138, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-06 23:09:15', '2025-10-06 23:20:12'),
(139, 'Product Activity', 'Product \'HP Pro Tower 280 \' updated', 'system', 1, '{\"product_id\":67,\"product_name\":\"HP Pro Tower 280 \",\"activity\":\"updated\",\"username\":\"Admin\"}', '2025-10-06 23:26:34', '2025-10-06 23:42:45'),
(140, 'Product Activity', 'Product \'Lenovo\' updated', 'system', 1, '{\"product_id\":53,\"product_name\":\"Lenovo\",\"activity\":\"updated\",\"username\":\"Admin\"}', '2025-10-06 23:27:35', '2025-10-06 23:42:45'),
(141, 'Product Activity', 'Product \'Walton WNI-6A9-GMSD-DD\' updated', 'system', 1, '{\"product_id\":81,\"product_name\":\"Walton WNI-6A9-GMSD-DD\",\"activity\":\"updated\",\"username\":\"Admin\"}', '2025-10-06 23:28:13', '2025-10-06 23:42:45'),
(142, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-06 23:57:43', '2025-10-07 00:13:07'),
(143, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-07 00:03:19', '2025-10-07 00:13:07'),
(144, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-07 00:08:00', '2025-10-07 00:13:07'),
(145, 'User Management', 'User \'s s\' has been updated by Admin', 'user_activity', 1, '{\"target_user_id\":22,\"target_username\":\"s s\",\"action\":\"updated\",\"admin_user_id\":null,\"admin_username\":\"Admin\"}', '2025-10-07 00:13:03', '2025-10-07 00:13:07'),
(146, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-07 22:56:20', '2025-10-07 22:57:45'),
(147, 'Product Activity', 'Product \'XANON X1 Ultra\' created', 'system', 1, '{\"product_id\":94,\"product_name\":\"XANON X1 Ultra\",\"activity\":\"created\",\"username\":\"Admin\"}', '2025-10-07 23:30:28', '2025-10-08 09:39:55'),
(148, 'New Order Received', 'Order #ORD-1759858443-7269 has been placed for BDT 112697.7', 'new_order', 1, '{\"order_id\":159,\"order_number\":\"ORD-1759858443-7269\",\"customer_id\":7,\"amount\":112697.7}', '2025-10-07 23:34:03', '2025-10-08 09:39:55'),
(149, 'Payment Pending', 'Payment of BDT 112697.7 via cod for Order #ORD-1759858443-7269 has been pending', 'payment', 1, '{\"order_id\":159,\"order_number\":\"ORD-1759858443-7269\",\"amount\":112697.7,\"payment_method\":\"cod\",\"status\":\"pending\"}', '2025-10-07 23:34:03', '2025-10-08 09:39:55'),
(150, 'High-Value Order', 'Order #ORD-1759858443-7269 is BDT 112697.7 (≥ BDT 10000)', 'high_value_order', 1, '{\"order_id\":159,\"order_number\":\"ORD-1759858443-7269\",\"amount\":112697.7,\"threshold\":10000}', '2025-10-07 23:34:03', '2025-10-08 09:39:55'),
(151, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 09:38:19', '2025-10-08 09:39:55'),
(152, 'New Product Review', 'New review submitted for \'Walton WNI-6A9-GDNE-BD\' by Admin. Rating: 4 stars.', 'product_review', 1, '{\"product_id\":83,\"product_name\":\"Walton WNI-6A9-GDNE-BD\",\"review_id\":12,\"customer_name\":\"Admin\",\"customer_email\":\"admin@yahoo.com\",\"rating\":4,\"review_text\":\"Best product\"}', '2025-10-08 09:54:32', '2025-10-08 09:54:52'),
(153, 'New User Registration', 'New user shahin Hossen (shahin@gmail.com) has registered', 'user_registration', 1, '{\"user_id\":23,\"username\":\"shahin Hossen\",\"email\":\"shahin@gmail.com\"}', '2025-10-08 10:05:02', '2025-10-08 10:38:55'),
(154, 'User Activity', 'shahin Hossen logged in', 'user_activity', 1, '{\"user_id\":23,\"username\":\"shahin Hossen\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:05:12', '2025-10-08 10:38:55'),
(155, 'New Order Received', 'Order #ORD-1759896738-1343 has been placed for BDT 63813.5', 'new_order', 1, '{\"order_id\":160,\"order_number\":\"ORD-1759896738-1343\",\"customer_id\":23,\"amount\":63813.5}', '2025-10-08 10:12:18', '2025-10-08 10:38:55'),
(156, 'Payment Pending', 'Payment of BDT 63813.5 via cod for Order #ORD-1759896738-1343 has been pending', 'payment', 1, '{\"order_id\":160,\"order_number\":\"ORD-1759896738-1343\",\"amount\":63813.5,\"payment_method\":\"cod\",\"status\":\"pending\"}', '2025-10-08 10:12:18', '2025-10-08 10:38:55'),
(157, 'High-Value Order', 'Order #ORD-1759896738-1343 is BDT 63813.5 (≥ BDT 10000)', 'high_value_order', 1, '{\"order_id\":160,\"order_number\":\"ORD-1759896738-1343\",\"amount\":63813.5,\"threshold\":10000}', '2025-10-08 10:12:18', '2025-10-08 10:38:55'),
(158, 'New Product Review', 'New review submitted for \'Redmi Note 14\' by Shahin Hossen. Rating: 4 stars.', 'product_review', 1, '{\"product_id\":90,\"product_name\":\"Redmi Note 14\",\"review_id\":13,\"customer_name\":\"Shahin Hossen\",\"customer_email\":\"shahin@gmail.com\",\"rating\":4,\"review_text\":\"Excellent Product.\"}', '2025-10-08 10:23:46', '2025-10-08 10:38:55'),
(159, 'New Order Received', 'Order #ORD-1759898263-2542 has been placed for BDT 33808.85', 'new_order', 1, '{\"order_id\":161,\"order_number\":\"ORD-1759898263-2542\",\"customer_id\":23,\"amount\":33808.85}', '2025-10-08 10:37:43', '2025-10-08 10:38:09'),
(160, 'Payment Pending', 'Payment of BDT 33808.85 via cod for Order #ORD-1759898263-2542 has been pending', 'payment', 1, '{\"order_id\":161,\"order_number\":\"ORD-1759898263-2542\",\"amount\":33808.85,\"payment_method\":\"cod\",\"status\":\"pending\"}', '2025-10-08 10:37:43', '2025-10-08 10:38:55'),
(161, 'High-Value Order', 'Order #ORD-1759898263-2542 is BDT 33808.85 (≥ BDT 10000)', 'high_value_order', 1, '{\"order_id\":161,\"order_number\":\"ORD-1759898263-2542\",\"amount\":33808.85,\"threshold\":10000}', '2025-10-08 10:37:43', '2025-10-08 10:38:55'),
(162, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:37:52', '2025-10-08 10:38:07'),
(163, 'New Live Chat Message', 'New message from Admin  (admin@gmail.com): Hello i am admin', 'user_activity', 1, '{\"conversation_id\":\"5\",\"message_id\":\"44\",\"sender_name\":\"Admin \",\"sender_type\":\"user\",\"user_name\":\"Admin \",\"user_email\":\"admin@gmail.com\",\"message_preview\":\"Hello i am admin\"}', '2025-10-08 10:38:42', '2025-10-08 10:38:55'),
(164, 'User Activity', 'shahin Hossen logged in', 'user_activity', 1, '{\"user_id\":23,\"username\":\"shahin Hossen\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:40:50', '2025-10-08 10:42:38'),
(165, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:42:03', '2025-10-08 10:43:21'),
(166, 'User Activity', 'shahin Hossen logged in', 'user_activity', 1, '{\"user_id\":23,\"username\":\"shahin Hossen\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:45:01', '2025-10-08 10:49:02'),
(167, 'New Product Review', 'New review submitted for \'Lenovo\' by shahin Hossen. Rating: 3 stars.', 'product_review', 1, '{\"product_id\":53,\"product_name\":\"Lenovo\",\"review_id\":14,\"customer_name\":\"shahin Hossen\",\"customer_email\":\"shahin@gmail.com\",\"rating\":3,\"review_text\":\"user friendly and low budget price\"}', '2025-10-08 10:48:16', '2025-10-08 10:49:02'),
(168, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:48:23', '2025-10-08 10:48:36'),
(169, 'User Activity', 'shahin Hossen logged in', 'user_activity', 1, '{\"user_id\":23,\"username\":\"shahin Hossen\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:49:13', '2025-10-08 10:50:29'),
(170, 'New Live Chat Message', 'New message from shahin Hossen (shahin@gmail.com): Hi this is my first order in your page', 'user_activity', 1, '{\"conversation_id\":\"10\",\"message_id\":\"46\",\"sender_name\":\"shahin Hossen\",\"sender_type\":\"user\",\"user_name\":\"shahin Hossen\",\"user_email\":\"shahin@gmail.com\",\"message_preview\":\"Hi this is my first order in your page\"}', '2025-10-08 10:50:06', '2025-10-08 10:50:29'),
(171, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 10:50:20', '2025-10-08 10:50:29'),
(172, 'New Contact Message: Order Support', 'From: Shahin Hossen (shahin@gmail.com)\nSubject: Order Support\nMessage: I have successfully place order but not found my product yet...', 'contact_message', 1, '{\"message_id\":\"9\",\"sender_name\":\"Shahin Hossen\",\"sender_email\":\"shahin@gmail.com\",\"phone\":\"01412345678\",\"priority\":\"high\",\"subject\":\"Order Support\",\"type\":\"contact_message\"}', '2025-10-08 11:00:22', '2025-10-08 11:07:42'),
(173, 'New Contact Message: Technical Issue', 'From: Shahin Hossen (shahin@gmail.com)\nSubject: Technical Issue\nMessage: I can\'t find my invoice...', 'contact_message', 1, '{\"message_id\":\"10\",\"sender_name\":\"Shahin Hossen\",\"sender_email\":\"shahin@gmail.com\",\"phone\":\"01412345678\",\"priority\":\"high\",\"subject\":\"Technical Issue\",\"type\":\"contact_message\"}', '2025-10-08 11:04:34', '2025-10-08 11:07:42'),
(174, 'New Contact Message: Feedback', 'From: Sultan (Sultan@outlook.com)\nSubject: Feedback\nMessage: Good job...', 'contact_message', 1, '{\"message_id\":\"11\",\"sender_name\":\"Sultan\",\"sender_email\":\"Sultan@outlook.com\",\"phone\":\"01555555550\",\"priority\":\"normal\",\"subject\":\"Feedback\",\"type\":\"contact_message\"}', '2025-10-08 11:07:21', '2025-10-08 11:07:41'),
(175, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 11:07:27', '2025-10-08 11:07:40'),
(176, 'User Activity', 'Test User logged in', 'user_activity', 1, '{\"user_id\":1,\"username\":\"Test User\",\"activity\":\"logged in\"}', '2025-10-08 11:10:55', '2025-10-08 11:19:19'),
(177, 'New User Registration', 'New user New User (newuser@example.com) has registered', 'user_registration', 1, '{\"user_id\":2,\"username\":\"New User\",\"email\":\"newuser@example.com\"}', '2025-10-08 11:10:55', '2025-10-08 11:19:19'),
(178, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 11:27:28', '2025-10-08 11:52:50'),
(179, 'User Activity', 'shahin Hossen logged in', 'user_activity', 1, '{\"user_id\":23,\"username\":\"shahin Hossen\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 11:52:09', '2025-10-08 11:52:50'),
(180, 'User Activity', 'Admin logged in', 'user_activity', 1, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-08 11:52:45', '2025-10-08 11:52:50'),
(181, 'User Activity', 'Admin logged in', 'user_activity', 0, '{\"user_id\":7,\"username\":\"Admin\",\"activity\":\"logged in\",\"details\":{\"ip\":\"::1\",\"ua\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\"}}', '2025-10-12 18:23:21', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `old_daily_sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `old_daily_sales_summary` (
`sale_date` date
,`total_orders` bigint(21)
,`total_sales` decimal(32,2)
,`average_order_value` decimal(14,6)
,`order_type` enum('online','pos')
);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(30) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_type` enum('online','pos') NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_method` enum('bkash','nogod','cash') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `coupon_id` int(11) DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'BDT',
  `notes` text DEFAULT NULL,
  `billing_first_name` varchar(50) DEFAULT NULL,
  `billing_last_name` varchar(50) DEFAULT NULL,
  `billing_company` varchar(100) DEFAULT NULL,
  `billing_address_line_1` varchar(200) DEFAULT NULL,
  `billing_address_line_2` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_postal_code` varchar(20) DEFAULT NULL,
  `billing_country` varchar(100) DEFAULT NULL,
  `billing_phone` varchar(20) DEFAULT NULL,
  `shipping_first_name` varchar(50) DEFAULT NULL,
  `shipping_last_name` varchar(50) DEFAULT NULL,
  `shipping_company` varchar(100) DEFAULT NULL,
  `shipping_address_line_1` varchar(200) DEFAULT NULL,
  `shipping_address_line_2` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_postal_code` varchar(20) DEFAULT NULL,
  `shipping_country` varchar(100) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `order_type`, `status`, `payment_status`, `payment_method`, `transaction_id`, `subtotal`, `discount_amount`, `coupon_id`, `tax_amount`, `shipping_amount`, `total_amount`, `currency`, `notes`, `billing_first_name`, `billing_last_name`, `billing_company`, `billing_address_line_1`, `billing_address_line_2`, `billing_city`, `billing_state`, `billing_postal_code`, `billing_country`, `billing_phone`, `shipping_first_name`, `shipping_last_name`, `shipping_company`, `shipping_address_line_1`, `shipping_address_line_2`, `shipping_city`, `shipping_state`, `shipping_postal_code`, `shipping_country`, `shipping_phone`, `shipping_method`, `tracking_number`, `estimated_delivery`, `processed_by`, `processed_at`, `created_at`, `updated_at`) VALUES
(1, 'ORD-1751351897-7559', NULL, 'online', 'delivered', 'paid', 'cash', '123123123', 361.97, 0.07, NULL, 18.10, 0.00, 380.00, 'BDT', 'test', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:38:17', '2025-09-28 20:16:18'),
(2, 'ORD-1751352003-3662', NULL, 'online', 'delivered', 'paid', 'bkash', '123123123', 361.97, 0.07, NULL, 18.10, 0.00, 380.00, 'BDT', 'test', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:40:03', '2025-09-28 20:16:30'),
(3, 'ORD-1751352032-6555', NULL, 'online', 'delivered', 'pending', 'bkash', '123123123', 361.97, 0.07, NULL, 18.10, 0.00, 380.00, 'BDT', 'test', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:40:32', '2025-09-05 05:56:28'),
(4, 'ORD-1751352109-3696', NULL, 'online', 'delivered', 'pending', 'bkash', '123123123', 361.97, 0.07, NULL, 18.10, 0.00, 380.00, 'BDT', 'test', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'H', '123123123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:41:49', '2025-09-05 05:56:24'),
(5, 'ORD-1751352202-6597', NULL, 'online', 'delivered', 'pending', 'bkash', 'sadfdsf', 361.97, 0.07, NULL, 18.10, 0.00, 380.00, 'BDT', 'sdfasdf', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'bangladesh', '123123', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'bangladesh', '123123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:43:22', '2025-09-05 05:56:00'),
(6, 'ORD-1751352476-7252', NULL, 'online', 'delivered', 'pending', 'bkash', 'sadfdsf', 127.92, 0.07, NULL, 6.40, 0.00, 134.25, 'BDT', 'sdfasdf', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'bangladesh', '123123', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'bangladesh', '123123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:47:56', '2025-09-05 05:56:20'),
(7, 'ORD-1751352519-1216', NULL, 'online', 'delivered', 'pending', 'bkash', 'sadfdsf', 127.92, 0.07, NULL, 6.40, 0.00, 134.25, 'BDT', 'sdfasdf', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'bangladesh', '123123', 'A', 'B', 'C', 'D', 'E', 'F', 'G', '1216', 'bangladesh', '123123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:48:39', '2025-09-05 05:55:55'),
(8, 'ORD-1751352687-2426', NULL, 'online', 'delivered', 'pending', 'bkash', 'hhh', 127.92, 4.32, NULL, 6.40, 0.00, 130.00, 'BDT', 'hhh', 'hh', 'hh', 'hh', 'hh', 'hh', 'hh', 'hh', '1234', 'hh', '123', 'hh', 'hh', 'hh', 'hh', 'hh', 'hh', 'hh', '1234', 'hh', '123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 06:51:27', '2025-09-05 05:55:51'),
(9, 'POS-1751352758-8916', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 361.97, 0.00, NULL, 28.96, 0.00, 390.93, 'BDT', '', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-01 06:52:38', '2025-07-01 06:52:38', '2025-07-01 06:52:38'),
(10, 'POS-1751352994-4326', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 361.97, 0.00, NULL, 28.96, 0.00, 390.93, 'BDT', '', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-01 06:56:34', '2025-07-01 06:56:34', '2025-07-01 06:56:34'),
(11, 'POS-1751353171-5902', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 395.97, 0.00, NULL, 31.68, 0.00, 427.65, 'BDT', '', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-01 06:59:31', '2025-07-01 06:59:31', '2025-07-01 06:59:31'),
(12, 'ORD-1751428701-5358', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 15.99, 0.00, NULL, 0.80, 0.00, 16.79, 'BDT', '', 'abir', 'khan', '', 'mirpur', '', 'dhaka', 'dhaka', '1216', 'Bangladesh', '012321232', 'abir', 'khan', '', 'mirpur', '', 'dhaka', 'dhaka', '1216', 'bangladesh', '01232123', NULL, NULL, NULL, NULL, NULL, '2025-07-01 23:58:21', '2025-09-05 05:55:45'),
(13, 'ORD-1751429333-5317', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 50.00, 0.00, NULL, 2.50, 0.00, 52.50, 'BDT', 'dsdgfdgh', 'a', 'b', 'c', 'as', 'ac', 'z', 'x', '12', 'ewdf', '1335458555', 'a', 'b', 'c', 'as', 'ac', 'z', 'x', '12', 'ewdf', '1335458555', NULL, NULL, NULL, NULL, NULL, '2025-07-02 00:08:53', '2025-09-05 05:55:09'),
(14, 'POS-1751429584-3593', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 200.00, 0.00, NULL, 16.00, 0.00, 216.00, 'BDT', '', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-02 00:13:04', '2025-07-02 00:13:04', '2025-07-02 04:13:04'),
(15, 'ORD-1751429853-9739', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 25.00, 0.00, NULL, 1.25, 0.00, 26.25, 'BDT', '', 'a', 'b', 'c', 'as', 'ac', 'z', 'wrgr', '12', 'Bangladesh', '01335458555', 'a', 'b', 'c', 'as', 'ac', 'z', 'wrgr', '12', 'Bangladesh', '01335458555', NULL, NULL, NULL, NULL, NULL, '2025-07-02 00:17:33', '2025-09-05 05:55:20'),
(16, 'ORD-1751430476-2891', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 15.99, 0.00, NULL, 0.80, 0.00, 16.79, 'BDT', 'Bring safely', 'Sobuj', 'Hasan', 'IsDB', 'kafrul', '', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01401885646', 'Sobuj', 'Hasan', 'IsDB', 'kafrul', '', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01401885646', NULL, NULL, NULL, NULL, NULL, '2025-07-02 00:27:56', '2025-09-05 05:55:13'),
(17, 'ORD-1751430855-4626', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 120.00, 0.00, NULL, 6.00, 0.00, 126.00, 'BDT', '', 'hasan', 'mahmud', 'isdb', 'mirpur', '', 'dhaka', 'dhaka', '1216', 'bangladesh', '01401775566', 'hasan', 'mahmud', 'isdb', 'mirpur', '', 'dhaka', 'dhaka', '1216', 'bangladesh', '01401775566', NULL, NULL, NULL, NULL, NULL, '2025-07-02 00:34:15', '2025-09-05 05:55:16'),
(18, 'ORD-1751433945-2357', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 400.00, 0.00, NULL, 20.00, 0.00, 420.00, 'BDT', 'r5ft', 'a', 'b', 'c', 'as', 'ac', 'z', 'u', '12', 'Bangladesh', '01335458555', 'a', 'b', 'c', 'as', 'ac', 'z', 'u', '12', 'Bangladesh', '01335458555', NULL, NULL, NULL, NULL, NULL, '2025-07-02 01:25:45', '2025-09-05 05:55:25'),
(19, 'POS-1751434047-2280', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 18000.00, 0.00, NULL, 1440.00, 0.00, 19440.00, 'BDT', '', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-02 01:27:27', '2025-07-02 01:27:27', '2025-07-02 05:27:27'),
(20, 'ORD-1751437246-8160', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 419.99, 0.00, NULL, 21.00, 0.00, 440.99, 'BDT', '', 'a', 'b', 'c', 'as', 'ac', 'z', 'g', '12', 'Bangladesh', '01335458555', 'a', 'b', 'c', 'as', 'ac', 'z', 'g', '12', 'Bangladesh', '01335458555', NULL, NULL, NULL, NULL, NULL, '2025-07-02 02:20:46', '2025-09-05 05:55:29'),
(21, 'ORD-1752302433-4646', NULL, 'online', 'delivered', 'pending', 'cash', NULL, 129.99, 0.00, NULL, 6.50, 0.00, 136.49, 'BDT', 'fg', 'a', 'b', 'c', 'as', 'ac', 'z', 'g', '12', 'Bangladesh', '01335458555', 'a', 'b', 'c', 'as', 'ac', 'z', 'g', '12', 'Bangladesh', '01335458555', NULL, NULL, NULL, NULL, NULL, '2025-07-12 02:40:33', '2025-09-05 05:55:32'),
(22, 'ORD-1752768973-7692', 7, 'online', 'cancelled', 'pending', 'bkash', '123', 2175.00, 0.00, NULL, 326.25, 0.00, 2501.25, 'BDT', 'tttt', 'AA', 'BB', 'CC', 'DD', 'EE', 'FF', 'GG', '1234', 'Bangladesh', '123', 'AA', 'BB', 'CC', 'DD', 'EE', 'FF', 'GG', '1234', 'Bangladesh', '123456', NULL, NULL, NULL, NULL, NULL, '2025-07-17 12:16:13', '2025-08-25 20:20:57'),
(23, 'ORD-1752772560-5671', 7, 'online', 'delivered', 'pending', 'bkash', '3543545535', 51635.99, 0.00, NULL, 7745.40, 0.00, 59381.39, 'BDT', 'dsfdsffdg', 'Abu', 'Al-Mamun', 'GNSL', 'Nahin Tower', 'Arambag R/A', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Al-Mamun', 'GNSL', 'Nahin Tower', 'Arambag R/A', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-07-17 13:16:00', '2025-09-05 05:50:48'),
(24, 'POS-1752774764-5302', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 1640.99, 72.27, NULL, 131.28, 0.00, 1700.00, 'BDT', '', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-17 13:52:44', '2025-07-17 13:52:44', '2025-07-17 17:52:44'),
(25, 'POS-1752774931-7569', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 45360.00, 988.80, NULL, 3628.80, 0.00, 48000.00, 'BDT', 'almost 1k discount', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-17 13:55:31', '2025-07-17 13:55:31', '2025-07-17 17:55:31'),
(26, 'POS-1752775698-8415', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 107860.00, 488.80, NULL, 8628.80, 0.00, 116000.00, 'BDT', 'thank you mam', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-07-17 14:08:18', '2025-07-17 14:08:18', '2025-07-17 18:08:18'),
(27, 'ORD-1755752136-3976', 12, 'online', 'delivered', 'pending', 'cash', NULL, 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'fgd', 'df', 'df', 'dfgd', 'dgg', 'gddd', 'gddddddf', '12', 'Bangladesh', '12324334545', 'bvvvvvvv', 'vbbbbbb', 'fhhhhh', 'hffffffff', 'hggggggg', 'hhg', 'fgh', '13', 'Bangladesh', '1232423434', NULL, NULL, NULL, NULL, NULL, '2025-08-21 04:55:36', '2025-09-05 05:50:55'),
(28, 'ORD-1755752151-5304', 12, 'online', 'delivered', 'pending', 'bkash', '3', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'fgd', 'df', 'df', 'dfgd', 'dgg', 'gddd', 'gddddddf', '12', 'Bangladesh', '12324334545', 'bvvvvvvv', 'vbbbbbb', 'fhhhhh', 'hffffffff', 'hggggggg', 'hhg', 'fgh', '13', 'Bangladesh', '1232423434', NULL, NULL, NULL, NULL, NULL, '2025-08-21 04:55:51', '2025-09-05 05:50:59'),
(29, 'ORD-1755752174-6591', 12, 'online', 'delivered', 'pending', 'bkash', '3', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', 'sdffff', 'fgd', 'df', 'df', 'dfgd', 'dgg', 'gddd', 'gddddddf', '12', 'Bangladesh', '12324334545', 'bvvvvvvv', 'vbbbbbb', 'fhhhhh', 'hffffffff', 'hggggggg', 'hhg', 'fgh', '13', 'Bangladesh', '1232423434', NULL, NULL, NULL, NULL, NULL, '2025-08-21 04:56:14', '2025-09-05 05:51:01'),
(30, 'ORD-1755752933-5089', 12, 'online', 'delivered', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'f', 'g', 'hh', 'hgh', 'hgh', 'hh', 'fhf', '12', 'Bangladesh', '12132313', 'f', 'g', 'fhgfth', 'fhhfh', 'hfh', 'hfh', 'hfhg', '14', 'Bangladesh', '1233244', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:08:53', '2025-09-05 05:51:06'),
(31, 'ORD-1755753141-8162', 12, 'online', 'delivered', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:21', '2025-09-05 05:51:08'),
(32, 'ORD-1755753146-6661', 12, 'online', 'delivered', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:26', '2025-09-05 05:54:57'),
(33, 'ORD-1755753151-5869', 12, 'online', 'cancelled', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:31', '2025-08-25 18:27:04'),
(34, 'ORD-1755753155-2170', 12, 'online', 'cancelled', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:35', '2025-08-25 18:27:07'),
(35, 'ORD-1755753159-9924', 12, 'online', 'delivered', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:39', '2025-09-21 08:58:13'),
(36, 'ORD-1755753164-7059', 12, 'online', 'processing', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:44', '2025-09-21 08:58:08'),
(37, 'ORD-1755753168-2641', 12, 'online', 'delivered', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:48', '2025-09-05 05:49:37'),
(38, 'ORD-1755753172-4626', 12, 'online', 'delivered', 'pending', 'bkash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'hg', 'gh', 'gfh', 'fhfg', 'hghg', 'hghgh', 'hhjth', '15', 'Bangladesh', '1233', 'ghg', 'ghhghg', 'hggffg', 'ghhghg', 'hgfhg', 'dsf', 'dfdf', '16', 'Bangladesh', '1213344', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:12:52', '2025-09-05 05:49:34'),
(39, 'ORD-1755755619-2068', 12, 'online', 'shipped', 'pending', 'cash', '12', 350.00, 0.00, NULL, 52.50, 0.00, 402.50, 'BDT', '', 'ryt', 'yt', 'yt', 'ty', 'yt', 'yt', 'yty', '2', 'Bangladesh', '1133232', 'hj', 'hg', 'gh', 'jhjh', 'jgjh', 'hg', 'hghg', '12', 'Bangladesh', '1323', NULL, NULL, NULL, NULL, NULL, '2025-08-21 05:53:39', '2025-09-28 19:41:51'),
(40, 'ORD-1755756340-8466', 12, 'online', 'cancelled', 'pending', 'bkash', '12', 2500.00, 0.00, NULL, 375.00, 0.00, 2875.00, 'BDT', '', 'hf', 'g', 'gf', 'g', 'j', 'j', 'j', '2', 'Bangladesh', '123232', 'dhg', 'f', 'g', 'hgf', 'hg', 'fg', 'hf', '4', 'Bangladesh', '43545', NULL, NULL, NULL, NULL, NULL, '2025-08-21 06:05:40', '2025-09-05 05:49:24'),
(41, 'ORD-1757051313-2040', 7, 'online', 'processing', 'pending', 'bkash', 'jhj123213', 52500.00, 0.00, NULL, 7875.00, 0.00, 60375.00, 'BDT', '', 'Mr', 'Shakib', 'AKMU', 'Uttara', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '019123456', 'Mr', 'Shakib', 'AKMU', 'Uttara', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '019123456', NULL, NULL, NULL, NULL, NULL, '2025-09-05 05:48:33', '2025-09-05 05:49:20'),
(42, 'ORD-1757051315-8761', 7, 'online', 'delivered', 'pending', 'bkash', 'jhj123213', 52500.00, 0.00, NULL, 7875.00, 0.00, 60375.00, 'BDT', '', 'Mr', 'Shakib', 'AKMU', 'Uttara', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '019123456', 'Mr', 'Shakib', 'AKMU', 'Uttara', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '019123456', NULL, NULL, NULL, NULL, NULL, '2025-09-05 05:48:35', '2025-09-05 05:48:54'),
(43, 'ORD-1757054104-6105', 7, 'online', 'delivered', 'pending', 'bkash', 'jhj123213', 323500.00, 0.00, NULL, 48525.00, 0.00, 372025.00, 'BDT', '', 'Abu', 'siddik', '', 'Dhaka', 'Arambag R/A', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'siddik', '', 'Dhaka', 'Arambag R/A', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-05 06:35:04', '2025-09-05 06:36:21'),
(44, 'ORD-1757140329-1979', 13, 'online', 'cancelled', 'pending', 'cash', NULL, 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'I need it very urgent. plz deliver it as soon as possible within 2 days', 'Mr', '.', 'no', 'Rupnagar', 'Pallabi', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'no', 'Rupnagar', 'Pallabi', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:32:09', '2025-09-06 06:33:02'),
(45, 'ORD-1757140330-1626', 13, 'online', 'cancelled', 'pending', 'cash', NULL, 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'I need it very urgent. plz deliver it as soon as possible within 2 days', 'Mr', '.', 'no', 'Rupnagar', 'Pallabi', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'no', 'Rupnagar', 'Pallabi', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:32:10', '2025-09-06 06:33:07'),
(46, 'ORD-1757141192-4013', 13, 'online', 'cancelled', 'pending', 'bkash', 'TS66#K23213', 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'Please deliver me urgently as soon as possible within 2 days', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:46:32', '2025-09-17 22:19:41'),
(47, 'ORD-1757141200-4555', 13, 'online', 'cancelled', 'pending', 'bkash', 'TS66#K23213', 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'Please deliver me urgently as soon as possible within 2 days', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:46:40', '2025-09-17 22:19:50'),
(48, 'ORD-1757141206-4692', 13, 'online', 'cancelled', 'pending', 'bkash', 'TS66#K23213', 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'Please deliver me urgently as soon as possible within 2 days', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:46:46', '2025-09-17 22:19:36'),
(49, 'ORD-1757141212-8817', 13, 'online', 'cancelled', 'pending', 'bkash', 'TS66#K23213', 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'Please deliver me urgently as soon as possible within 2 days', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:46:52', '2025-09-17 22:19:32'),
(50, 'ORD-1757141219-1860', 13, 'online', 'cancelled', 'pending', 'bkash', 'TS66#K23213', 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'Please deliver me urgently as soon as possible within 2 days', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:46:59', '2025-09-17 22:19:28'),
(51, 'ORD-1757141225-5109', 13, 'online', 'delivered', 'pending', 'bkash', 'TS66#K23213', 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'Please deliver me urgently as soon as possible within 2 days', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', 'Mr', '.', 'Personal', 'Pallabi', 'Rupnagar', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01727272722', NULL, NULL, NULL, NULL, NULL, '2025-09-06 06:47:05', '2025-09-06 07:33:27'),
(84, 'ORD-1758263054-9807', 7, 'online', 'cancelled', 'pending', 'cash', 'LKd6#K23213', 123390.00, 0.00, NULL, 18508.50, 0.00, 141898.50, 'BDT', 'Ok', 'Siddik', 'Vai', 'OK', 'ok', 'ok', 'ok', 'ok', '1216', 'Bangladesh', '01911039525', 'Siddik', 'Siddik', 'OK', 'ok', 'ok', 'ok', 'ok', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 06:24:14', '2025-09-28 19:45:52'),
(85, 'ORD-1758280581-2830', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', 'ok', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:16:21', '2025-09-19 11:29:03'),
(86, 'ORD-1758280695-7731', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', 'ok', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:18:15', '2025-09-19 11:56:37'),
(87, 'ORD-1758281101-9091', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:25:01', '2025-09-19 11:56:33'),
(88, 'ORD-1758281124-6733', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:25:24', '2025-09-19 11:56:29'),
(89, 'ORD-1758281145-8808', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:25:45', '2025-09-19 11:56:23'),
(90, 'ORD-1758281167-6040', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:26:07', '2025-09-19 11:56:20'),
(91, 'ORD-1758281188-5584', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:26:28', '2025-09-19 11:56:17'),
(92, 'ORD-1758281209-7998', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:26:49', '2025-09-19 11:56:13'),
(93, 'ORD-1758281231-2925', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:27:11', '2025-09-19 11:56:10'),
(94, 'ORD-1758281252-4859', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:27:32', '2025-09-19 11:56:06'),
(95, 'ORD-1758281273-2056', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 166262.00, 0.00, NULL, 24939.30, 0.00, 191201.30, 'BDT', '', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'NoAB', 'No', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:27:53', '2025-09-19 11:56:01'),
(96, 'ORD-1758281389-9839', 16, 'online', 'cancelled', 'pending', 'cash', 'KHUH*jkhk$%j5#', 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', 'Urgent', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:29:49', '2025-09-28 19:41:51'),
(97, 'ORD-1758282066-2309', 16, 'online', 'cancelled', 'pending', 'bkash', '#ksiheoYGHJ', 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', 'OK', 'Ab', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Ab', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:41:06', '2025-09-19 11:55:53'),
(98, 'ORD-1758282368-6444', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 112500.00, 0.00, NULL, 16875.00, 0.00, 129375.00, 'BDT', 'ASAP', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'AB', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:46:08', '2025-09-19 11:55:48'),
(99, 'ORD-1758282433-4672', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 51500.00, 0.00, NULL, 7725.00, 0.00, 59225.00, 'BDT', '', 'Sakib', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Sakib', '.', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:47:13', '2025-09-19 11:55:37'),
(100, 'ORD-1758282471-9844', 16, 'online', 'cancelled', 'pending', 'cash', NULL, 109140.00, 0.00, NULL, 16371.00, 0.00, 125511.00, 'BDT', '', 'Mr ', 'Shakib', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Mr ', 'Shakib', 'no', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 11:47:51', '2025-09-19 11:55:33'),
(101, 'ORD-1758284415-5778', 16, 'online', 'cancelled', 'refunded', 'cash', NULL, 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', '', 'Mr', 'Shakib', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Mr', 'Shakib', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 12:20:15', '2025-09-20 00:38:31'),
(102, 'ORD-1758285486-3780', 7, 'online', 'delivered', 'pending', 'cash', 'WHG#jbbJHBV', 62390.00, 0.00, NULL, 9358.50, 0.00, 71748.50, 'BDT', 'ASAP', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 12:38:06', '2025-09-28 19:41:51'),
(103, 'ORD-1758286052-5690', 7, 'online', 'processing', 'pending', 'cash', NULL, 62390.00, 0.00, NULL, 9358.50, 0.00, 71748.50, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 12:47:32', '2025-09-21 08:57:02'),
(104, 'ORD-1758286251-3279', 7, 'online', 'shipped', 'pending', 'cash', 'MBKJN#jkbjkb', 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 12:50:51', '2025-09-28 19:41:51'),
(105, 'ORD-1758286582-9452', 7, 'online', 'delivered', 'pending', 'cash', NULL, 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 12:56:22', '2025-09-19 13:19:20'),
(106, 'ORD-1758294790-7928', 7, 'online', 'delivered', 'pending', 'cash', NULL, 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 15:13:10', '2025-09-21 08:56:44'),
(107, 'ORD-1758296005-7750', 7, 'online', 'cancelled', 'pending', 'cash', NULL, 62390.00, 0.00, NULL, 9358.50, 0.00, 71748.50, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-19 15:33:25', '2025-09-21 08:56:34'),
(108, 'ORD-1758349460-7638', 16, 'online', 'cancelled', 'pending', 'cash', 'HGWE#kjH&$', 109140.00, 0.00, NULL, 16371.00, 0.00, 125511.00, 'BDT', 'Urgent', 'Mr', 'Shakib', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Mr', 'Shakib', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-20 06:24:20', '2025-09-28 19:41:51'),
(109, 'ORD-1758351158-2436', 7, 'online', 'delivered', 'pending', 'bkash', 'KAJDBHK#kjnkj%jhvjg$', 62390.00, 0.00, NULL, 9358.50, 0.00, 71748.50, 'BDT', 'OK DONE', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-20 06:52:38', '2025-09-21 08:56:20'),
(110, 'ORD-1758364888-4202', 7, 'online', 'delivered', 'pending', 'cash', NULL, 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', 'ur', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-20 10:41:28', '2025-09-21 08:56:12'),
(111, 'ORD-1758442809-6007', 7, 'online', 'delivered', 'pending', 'cash', NULL, 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-21 08:20:09', '2025-09-21 08:56:00'),
(112, 'ORD-1758442910-9064', 7, 'online', 'delivered', 'pending', 'cash', NULL, 160000.00, 0.00, NULL, 24000.00, 0.00, 184000.00, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-21 08:21:50', '2025-09-21 08:55:50'),
(113, 'ORD-1758446816-6196', 7, 'online', 'delivered', 'pending', 'bkash', '#kjhHJHJJ&KJD%JH', 28490.00, 0.00, NULL, 4273.50, 0.00, 32763.50, 'BDT', 'ok', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-21 09:26:56', '2025-09-21 09:27:11'),
(114, 'ORD-1758447997-7176', 7, 'online', 'processing', 'pending', 'cash', NULL, 45000.00, 0.00, NULL, 6750.00, 0.00, 51750.00, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-21 09:46:37', '2025-09-21 09:46:59'),
(115, 'ORD-1758701122-9579', 16, 'online', 'delivered', 'pending', 'cash', NULL, 199999.00, 0.00, NULL, 29999.85, 0.00, 229998.85, 'BDT', '', 'Mr', 'Shakib', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Mr', 'Shakib', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-24 08:05:22', '2025-09-24 08:06:40'),
(116, 'POS-1758704423-1789', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 51500.00, 0.00, NULL, 4120.00, 0.00, 55620.00, 'BDT', 'ok', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-09-24 09:00:23', '2025-09-24 09:00:23', '2025-09-24 09:00:53'),
(117, 'ORD-1758704555-8620', 7, 'online', 'delivered', 'pending', 'cash', NULL, 20550.00, 0.00, NULL, 3082.50, 0.00, 23632.50, 'BDT', '', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-24 09:02:35', '2025-09-24 09:02:52'),
(118, 'ORD-1758704687-1849', 7, 'online', 'delivered', 'pending', 'cash', NULL, 194500.00, 0.00, NULL, 29175.00, 0.00, 223675.00, 'BDT', '', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-24 09:04:47', '2025-09-24 09:05:32'),
(119, 'ORD-1758800250-2860', 7, 'online', 'delivered', 'pending', 'cash', NULL, 112500.00, 0.00, NULL, 16875.00, 0.00, 129375.00, 'BDT', '', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-25 11:37:30', '2025-09-25 11:39:57'),
(120, 'ORD-1758912649-4933', 7, 'online', 'delivered', 'pending', 'bkash', '#Tsdfeff', 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', '', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-26 18:50:49', '2025-09-26 21:47:38'),
(121, 'ORD-1758913201-1563', 17, 'online', 'delivered', 'pending', 'cash', NULL, 389000.00, 0.00, NULL, 58350.00, 0.00, 447350.00, 'BDT', 'ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 19:00:01', '2025-09-28 19:41:51'),
(122, 'ORD-1758913419-4459', 17, 'online', 'delivered', 'pending', 'cash', NULL, 55490.00, 0.00, NULL, 8323.50, 0.00, 63813.50, 'BDT', 'Very Urgent', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 19:03:39', '2025-09-28 19:41:51'),
(123, 'ORD-1758913984-4908', 17, 'online', 'delivered', 'pending', 'cash', NULL, 28490.00, 0.00, NULL, 4273.50, 0.00, 32763.50, 'BDT', 'Ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 19:13:04', '2025-09-28 19:41:51'),
(124, 'ORD-1758914181-5580', 17, 'online', 'delivered', 'pending', 'cash', NULL, 51500.00, 0.00, NULL, 7725.00, 0.00, 59225.00, 'BDT', 'ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 19:16:21', '2025-09-28 19:41:51'),
(125, 'ORD-1758914529-5728', 17, 'online', 'delivered', 'pending', 'cash', NULL, 62390.00, 0.00, NULL, 9358.50, 0.00, 71748.50, 'BDT', 'ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 19:22:09', '2025-09-28 19:41:51'),
(126, 'ORD-1758921299-2676', 17, 'online', 'delivered', 'pending', 'cash', NULL, 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', 'ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:14:59', '2025-09-28 19:41:51'),
(127, 'ORD-1758921312-9303', 17, 'online', 'delivered', 'pending', 'cash', NULL, 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', 'ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:15:12', '2025-09-28 19:41:51'),
(128, 'ORD-1758921373-1097', 17, 'online', 'delivered', 'pending', 'cash', NULL, 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', 'ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:16:13', '2025-09-28 19:41:51'),
(129, 'ORD-1758921407-1708', 17, 'online', 'delivered', 'pending', 'cash', NULL, 6262.00, 0.00, NULL, 939.30, 0.00, 7201.30, 'BDT', 'ok', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Khadiza', 'Akter', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:16:47', '2025-09-28 19:41:51'),
(130, 'ORD-1758921573-7347', 7, 'online', 'delivered', 'pending', 'cash', NULL, 261762.00, 0.00, NULL, 39264.30, 0.00, 301026.30, 'BDT', 'ok', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:19:33', '2025-09-28 19:41:51'),
(131, 'ORD-1758921714-6671', 7, 'online', 'delivered', 'pending', 'cash', NULL, 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'ok', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:21:54', '2025-09-28 19:41:51'),
(132, 'ORD-1758921942-7956', 7, 'online', 'delivered', 'pending', 'cash', NULL, 194500.00, 0.00, NULL, 29175.00, 0.00, 223675.00, 'BDT', 'ok', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:25:42', '2025-09-28 19:41:51'),
(133, 'ORD-1758922282-7002', 7, 'online', 'delivered', 'paid', 'cash', NULL, 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', 'ok', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:31:22', '2025-09-28 19:41:51'),
(134, 'ORD-1758923190-6408', 7, 'online', 'delivered', 'paid', 'cash', '262+fgujyuytu', 142000.00, 0.00, NULL, 21300.00, 0.00, 163300.00, 'BDT', '', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:46:30', '2025-09-28 19:41:51'),
(135, 'ORD-1758923930-8998', 7, 'online', 'delivered', 'paid', 'bkash', NULL, 204390.00, 0.00, NULL, 30658.50, 0.00, 235048.50, 'BDT', '', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Abu', 'Bokkor', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-26 21:58:50', '2025-09-26 22:00:15'),
(136, 'ORD-1759058374-9422', 19, 'online', 'delivered', 'pending', 'cash', NULL, 239490.00, 0.00, NULL, 35923.50, 0.00, 275413.50, 'BDT', '', 'Sajjad', 'mia', 'No', 'Pallabi', '', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01712345678', 'Mrs', 'Khadiza', 'no', 'pallabi', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01712345678', NULL, NULL, NULL, NULL, NULL, '2025-09-28 11:19:34', '2025-09-28 19:41:51'),
(137, 'ORD-1759082509-6661', 7, 'online', 'delivered', 'paid', 'bkash', NULL, 11999.00, 0.00, NULL, 1799.85, 0.00, 13798.85, 'BDT', NULL, 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-28 18:01:49', '2025-09-28 21:50:56'),
(138, 'ORD-1759084737-7212', 7, 'online', 'cancelled', 'refunded', 'cash', NULL, 61000.00, 0.00, NULL, 9150.00, 0.00, 70150.00, 'BDT', NULL, 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-28 18:38:57', '2025-09-28 20:17:10'),
(139, 'ORD-1759087109-5473', 21, 'online', 'delivered', 'paid', 'bkash', NULL, 160000.00, 0.00, NULL, 24000.00, 0.00, 184000.00, 'BDT', NULL, 'Rayhan', 'Khan', 'Section 10', 'Senpara', 'parbota', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01612345678', 'Rayhan', 'Khan', 'Section 10', 'Senpara', 'parbota', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01612345678', NULL, NULL, NULL, NULL, NULL, '2025-09-28 19:18:29', '2025-09-28 20:16:53'),
(140, 'ORD-1759087556-3822', 17, 'online', 'processing', 'paid', 'bkash', '#JAJK%klLJNH323', 16999.00, 0.00, NULL, 2549.85, 0.00, 19548.85, 'BDT', 'OK', 'Mrs', 'Khadiza', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'Mrs', 'Khadiza', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', NULL, NULL, NULL, NULL, NULL, '2025-09-28 19:25:56', '2025-09-28 19:44:28'),
(141, 'ORD-1759090706-6751', 7, 'online', 'pending', 'pending', '', '#JAJK%klLJ0540', 20550.00, 0.00, NULL, 3082.50, 0.00, 23632.50, 'BDT', NULL, 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-28 20:18:26', '2025-09-28 20:18:26'),
(142, 'ORD-1759094787-4171', 22, 'online', 'pending', 'pending', '', NULL, 79999.00, 0.00, NULL, 11999.85, 0.00, 91998.85, 'BDT', NULL, 'SS', 'Mia', 'Section 12', 'Pallabi', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01421346581', 'SS', 'Mia', 'Section 12', 'Pallabi', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01421346581', NULL, NULL, NULL, NULL, NULL, '2025-09-28 21:26:27', '2025-09-28 21:26:27'),
(143, 'ORD-1759121632-5371', 7, 'online', 'delivered', 'pending', '', NULL, 194500.00, 0.00, NULL, 29175.00, 0.00, 223675.00, 'BDT', NULL, 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Shakib', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-09-29 04:53:52', '2025-10-03 02:37:09'),
(144, 'POS-1759135730-7888', 7, 'pos', 'delivered', 'paid', 'cash', NULL, 6262.00, 0.00, NULL, 500.96, 0.00, 6762.96, 'BDT', '', '', '', NULL, '', NULL, '', NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, '', '', '', NULL, NULL, NULL, 7, '2025-09-29 08:48:50', '2025-09-29 08:48:50', '2025-09-29 08:48:50'),
(145, 'ORD-1759591260-4987', 7, 'online', 'pending', 'pending', '', NULL, 142000.00, 0.00, NULL, 21300.00, 0.00, 163300.00, 'BDT', NULL, 'Admin', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 04:21:00', '2025-10-05 04:21:00'),
(146, 'ORD-1759592392-2333', 7, 'online', 'pending', 'pending', '', 'JKHG%Kjhk#124JH', 194500.00, 0.00, NULL, 29175.00, 0.00, 223675.00, 'BDT', NULL, 'Admin', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 04:39:52', '2025-10-05 04:39:52'),
(147, 'ORD-1759593355-7674', 7, 'online', 'delivered', 'paid', '', NULL, 79999.00, 0.00, NULL, 11999.85, 0.00, 91998.85, 'BDT', NULL, 'Mr', 'Admin', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Mr', 'Admin', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 04:55:55', '2025-10-05 05:07:44'),
(148, 'ORD-1759606275-2081', 7, 'online', 'pending', 'pending', '', NULL, 142000.00, 0.00, NULL, 21300.00, 0.00, 163300.00, 'BDT', NULL, 'Admin', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', '.', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 08:31:15', '2025-10-05 08:31:15'),
(149, 'ORD-1759606894-2959', 7, 'online', 'pending', 'pending', '', NULL, 181990.00, 0.00, NULL, 27298.50, 0.00, 209288.50, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 08:41:34', '2025-10-05 08:41:34'),
(150, 'ORD-1759607725-3447', 7, 'online', 'pending', 'pending', '', NULL, 164990.00, 0.00, NULL, 24748.50, 0.00, 189738.50, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 08:55:25', '2025-10-05 08:55:25'),
(151, 'ORD-1759609506-9556', 7, 'online', 'pending', 'pending', '', NULL, 28490.00, 0.00, NULL, 4273.50, 0.00, 32763.50, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 09:25:06', '2025-10-05 09:25:06'),
(152, 'ORD-1759615159-9814', 7, 'online', 'pending', 'pending', '', NULL, 108489.00, 0.00, NULL, 16273.35, 0.00, 124762.35, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 10:59:19', '2025-10-05 10:59:19'),
(153, 'ORD-1759617529-4426', 7, 'online', 'delivered', 'paid', 'bkash', NULL, 47490.00, 0.00, NULL, 7123.50, 0.00, 54613.50, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 11:38:49', '2025-10-05 11:54:30'),
(154, 'ORD-1759618584-4602', 7, 'online', 'pending', 'pending', '', NULL, 29399.00, 0.00, NULL, 4409.85, 0.00, 33808.85, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 11:56:24', '2025-10-05 11:56:24');
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `order_type`, `status`, `payment_status`, `payment_method`, `transaction_id`, `subtotal`, `discount_amount`, `coupon_id`, `tax_amount`, `shipping_amount`, `total_amount`, `currency`, `notes`, `billing_first_name`, `billing_last_name`, `billing_company`, `billing_address_line_1`, `billing_address_line_2`, `billing_city`, `billing_state`, `billing_postal_code`, `billing_country`, `billing_phone`, `shipping_first_name`, `shipping_last_name`, `shipping_company`, `shipping_address_line_1`, `shipping_address_line_2`, `shipping_city`, `shipping_state`, `shipping_postal_code`, `shipping_country`, `shipping_phone`, `shipping_method`, `tracking_number`, `estimated_delivery`, `processed_by`, `processed_at`, `created_at`, `updated_at`) VALUES
(155, 'ORD-1759620419-1744', 7, 'online', 'cancelled', 'pending', '', NULL, 160000.00, 0.00, NULL, 24000.00, 0.00, 184000.00, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 12:26:59', '2025-10-05 17:40:49'),
(156, 'ORD-1759621172-2005', 7, 'online', 'delivered', 'paid', 'bkash', NULL, 160000.00, 0.00, NULL, 24000.00, 0.00, 184000.00, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 12:39:32', '2025-10-05 14:58:24'),
(157, 'ORD-1759628097-7641', 13, 'online', 'delivered', 'paid', 'cash', NULL, 28490.00, 0.00, NULL, 4273.50, 0.00, 32763.50, 'BDT', 'ok', 'Mr', 'Shakib', 'DighoEcho User', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01312345678', 'Mr', 'Shakib', 'DighoEcho User', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01312345678', NULL, NULL, NULL, NULL, NULL, '2025-10-05 14:34:57', '2025-10-05 14:37:13'),
(158, 'ORD-1759687797-3326', 7, 'online', 'delivered', 'paid', 'bkash', NULL, 142000.00, 0.00, NULL, 21300.00, 0.00, 163300.00, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-05 18:09:57', '2025-10-05 18:10:34'),
(159, 'ORD-1759858443-7269', 7, 'online', 'pending', 'pending', '', NULL, 97998.00, 0.00, NULL, 14699.70, 0.00, 112697.70, 'BDT', NULL, 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'Admin', 'Bhai', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', NULL, NULL, NULL, NULL, NULL, '2025-10-07 17:34:03', '2025-10-07 17:34:03'),
(160, 'ORD-1759896738-1343', 23, 'online', 'pending', 'pending', '', NULL, 55490.00, 0.00, NULL, 8323.50, 0.00, 63813.50, 'BDT', NULL, 'Shahin', 'Hossen', 'Dighi Customer', 'House-12, Road-05, Block-B, Housing', 'Mirpur-11.5', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01412345678', 'Shahin', 'Hossen', 'Dighi Customer', 'House-12, Road-05, Block-B, Housing', 'Mirpur-11.5', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01412345678', NULL, NULL, NULL, NULL, NULL, '2025-10-08 04:12:18', '2025-10-08 04:12:18'),
(161, 'ORD-1759898263-2542', 23, 'online', 'pending', 'pending', '', NULL, 29399.00, 0.00, NULL, 4409.85, 0.00, 33808.85, 'BDT', NULL, 'Shahin', 'Hossen', 'Dighi Customer', 'House-12, Road-05, Block-B, Housing', 'Mirpur-11.5', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01412345678', 'Shahin', 'Hossen', 'Dighi Customer', 'House-12, Road-05, Block-B, Housing', 'Mirpur-11.5', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01412345678', NULL, NULL, NULL, NULL, NULL, '2025-10-08 04:37:43', '2025-10-08 04:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_sku` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_sku`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(97, 43, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-05 06:35:04'),
(99, 44, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:32:09'),
(100, 45, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:32:10'),
(101, 46, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:46:32'),
(102, 47, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:46:40'),
(103, 48, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:46:46'),
(104, 49, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:46:52'),
(105, 50, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:46:59'),
(106, 51, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-06 06:47:05'),
(161, 84, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-19 06:24:14'),
(162, 84, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-19 06:24:14'),
(163, 85, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:16:21'),
(164, 85, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:16:21'),
(165, 86, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:18:15'),
(166, 86, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:18:15'),
(167, 87, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:25:01'),
(168, 87, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:25:01'),
(169, 88, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:25:24'),
(170, 88, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:25:24'),
(171, 89, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:25:45'),
(172, 89, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:25:45'),
(173, 90, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:26:07'),
(174, 90, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:26:07'),
(175, 91, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:26:28'),
(176, 91, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:26:28'),
(177, 92, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:26:49'),
(178, 92, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:26:49'),
(179, 93, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:27:11'),
(180, 93, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:27:11'),
(181, 94, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:27:32'),
(182, 94, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:27:32'),
(183, 95, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-19 11:27:53'),
(184, 95, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:27:53'),
(185, 96, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:29:49'),
(186, 97, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 11:41:06'),
(187, 98, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-19 11:46:08'),
(188, 98, 51, 'Lenovo IdeaPad', 'Lenovo_IdeaPad-01', 1, 51500.00, 51500.00, '2025-09-19 11:46:08'),
(189, 99, 51, 'Lenovo IdeaPad', 'Lenovo_IdeaPad-01', 1, 51500.00, 51500.00, '2025-09-19 11:47:13'),
(190, 100, 70, 'Walton AMD Ryzen™ 3 Desktop AVIAN EX', 'Desktop-Walton-01', 1, 46750.00, 46750.00, '2025-09-19 11:47:51'),
(191, 100, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-19 11:47:51'),
(192, 101, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 12:20:15'),
(193, 102, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-19 12:38:06'),
(194, 103, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-19 12:47:32'),
(195, 104, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 12:50:51'),
(196, 105, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-19 12:56:22'),
(197, 106, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-19 15:13:10'),
(198, 107, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-19 15:33:25'),
(199, 108, 70, 'Walton AMD Ryzen™ 3 Desktop AVIAN EX', 'Desktop-Walton-01', 1, 46750.00, 46750.00, '2025-09-20 06:24:20'),
(200, 108, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-20 06:24:20'),
(201, 109, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-20 06:52:38'),
(202, 110, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-20 10:41:28'),
(203, 111, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-21 08:20:09'),
(204, 112, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-21 08:21:50'),
(205, 113, 77, 'WCF-1B5-GDEL-XX', 'WCF-1B5-GDEL-XX', 1, 28490.00, 28490.00, '2025-09-21 09:26:56'),
(206, 114, 80, 'WCG-2E5-EHLC-XX', 'Walton-Frige-13', 1, 45000.00, 45000.00, '2025-09-21 09:46:37'),
(207, 115, 85, 'Phone 16 Pro', 'iPhone 16 pro', 1, 183000.00, 183000.00, '2025-09-24 08:05:22'),
(208, 115, 92, 'Vivo Y19s Pro', 'Vivo Y19s Pro', 1, 16999.00, 16999.00, '2025-09-24 08:05:22'),
(209, 116, 51, 'Lenovo IdeaPad', 'Lenovo_IdeaPad-01', 1, 51500.00, 51500.00, '2025-09-24 09:00:23'),
(210, 117, 89, 'Xiaomi Redmi Note 14', 'Xiaomi Redmi Note 14', 1, 20550.00, 20550.00, '2025-09-24 09:02:35'),
(211, 118, 86, 'iPhone 16 Pro Max', 'iPhone 16 Pro Max', 1, 194500.00, 194500.00, '2025-09-24 09:04:47'),
(212, 119, 51, 'Lenovo IdeaPad', 'Lenovo_IdeaPad-01', 1, 51500.00, 51500.00, '2025-09-25 11:37:30'),
(213, 119, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-25 11:37:30'),
(214, 120, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-26 18:50:49'),
(215, 121, 86, 'iPhone 16 Pro Max', 'iPhone 16 Pro Max', 2, 194500.00, 389000.00, '2025-09-26 19:00:01'),
(216, 122, 79, 'WCG-2G0-CGXX-XX', 'WCG-2G0-CGXX-XX', 1, 55490.00, 55490.00, '2025-09-26 19:03:39'),
(217, 123, 77, 'WCF-1B5-GDEL-XX', 'WCF-1B5-GDEL-XX', 1, 28490.00, 28490.00, '2025-09-26 19:13:04'),
(218, 124, 51, 'Lenovo IdeaPad', 'Lenovo_IdeaPad-01', 1, 51500.00, 51500.00, '2025-09-26 19:16:21'),
(219, 125, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-26 19:22:09'),
(220, 126, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-26 21:14:59'),
(221, 127, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-26 21:15:12'),
(222, 128, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-26 21:16:13'),
(223, 129, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-26 21:16:47'),
(224, 130, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-26 21:19:33'),
(225, 130, 86, 'iPhone 16 Pro Max', 'iPhone 16 Pro Max', 1, 194500.00, 194500.00, '2025-09-26 21:19:33'),
(226, 130, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-26 21:19:33'),
(227, 131, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-26 21:21:54'),
(228, 132, 86, 'iPhone 16 Pro Max', 'iPhone 16 Pro Max', 1, 194500.00, 194500.00, '2025-09-26 21:25:42'),
(229, 133, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-26 21:31:22'),
(230, 134, 84, 'iPhone 16', 'iPhone 16 ', 1, 142000.00, 142000.00, '2025-09-26 21:46:30'),
(231, 135, 68, 'DELL OptiPlex 7010 ', 'Desktop-Dell-02', 1, 62390.00, 62390.00, '2025-09-26 21:58:50'),
(232, 135, 84, 'iPhone 16', 'iPhone 16 ', 1, 142000.00, 142000.00, '2025-09-26 21:58:50'),
(233, 136, 86, 'iPhone 16 Pro Max', 'iPhone 16 Pro Max', 1, 194500.00, 194500.00, '2025-09-28 11:19:34'),
(234, 136, 76, 'WFE-2N5-GDXX-XX', 'WFE-2N5-GDXX-XX', 1, 44990.00, 44990.00, '2025-09-28 11:19:34'),
(235, 137, 91, 'Vivo Y04', 'Vivo Y04', 1, 11999.00, 11999.00, '2025-09-28 18:01:49'),
(236, 138, 53, 'Lenovo', 'Lenovo_IdeaPad-02', 1, 61000.00, 61000.00, '2025-09-28 18:38:57'),
(237, 139, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-09-28 19:18:29'),
(238, 140, 92, 'Vivo Y19s Pro', 'Vivo Y19s Pro', 1, 16999.00, 16999.00, '2025-09-28 19:25:56'),
(239, 141, 89, 'Xiaomi Redmi Note 14', 'Xiaomi Redmi Note 14', 1, 20550.00, 20550.00, '2025-09-28 20:18:26'),
(240, 142, 93, 'KAIMAN Z22 Walton', 'KAIMAN Z22', 1, 79999.00, 79999.00, '2025-09-28 21:26:27'),
(241, 143, 86, 'iPhone 16 Pro Max', 'iPhone 16 Pro Max', 1, 194500.00, 194500.00, '2025-09-29 04:53:52'),
(242, 144, 67, 'HP Pro Tower 280 ', 'Desktop-HP-01', 1, 6262.00, 6262.00, '2025-09-29 08:48:50'),
(243, 145, 84, 'iPhone 16', 'iPhone 16 ', 1, 142000.00, 142000.00, '2025-10-05 04:21:00'),
(244, 146, 86, 'iPhone 16 Pro Max', 'iPhone 16 Pro Max', 1, 194500.00, 194500.00, '2025-10-05 04:39:52'),
(245, 147, 93, 'KAIMAN Z22 Walton', 'KAIMAN Z22', 1, 79999.00, 79999.00, '2025-10-05 04:55:55'),
(246, 148, 84, 'iPhone 16', 'iPhone 16 ', 1, 142000.00, 142000.00, '2025-10-05 08:31:15'),
(247, 149, 82, 'Walton WNR-6F0-SCRC-CO', 'WNR-6F0-SCRC-CO', 1, 181990.00, 181990.00, '2025-10-05 08:41:34'),
(248, 150, 83, 'Walton WNI-6A9-GDNE-BD', 'WNI-6A9-GDNE-BD', 1, 164990.00, 164990.00, '2025-10-05 08:55:25'),
(249, 151, 77, 'WCF-1B5-GDEL-XX', 'WCF-1B5-GDEL-XX', 1, 28490.00, 28490.00, '2025-10-05 09:25:06'),
(250, 152, 93, 'KAIMAN Z22 Walton', 'KAIMAN Z22', 1, 79999.00, 79999.00, '2025-10-05 10:59:19'),
(251, 152, 77, 'WCF-1B5-GDEL-XX', 'WCF-1B5-GDEL-XX', 1, 28490.00, 28490.00, '2025-10-05 10:59:19'),
(252, 153, 75, 'WFC-3A7-GDXX-XX', 'WFC-3A7-GDXX-XX', 1, 47490.00, 47490.00, '2025-10-05 11:38:49'),
(253, 154, 90, 'Redmi Note 14', 'Redmi Note 14', 1, 29399.00, 29399.00, '2025-10-05 11:56:24'),
(254, 155, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-10-05 12:26:59'),
(255, 156, 72, 'lenovo-ideapad', 'Laptop-Lenovo-05', 1, 160000.00, 160000.00, '2025-10-05 12:39:32'),
(256, 157, 77, 'WCF-1B5-GDEL-XX', 'WCF-1B5-GDEL-XX', 1, 28490.00, 28490.00, '2025-10-05 14:34:57'),
(257, 158, 84, 'iPhone 16', 'iPhone 16 ', 1, 142000.00, 142000.00, '2025-10-05 18:09:57'),
(258, 159, 93, 'KAIMAN Z22 Walton', 'KAIMAN Z22', 1, 79999.00, 79999.00, '2025-10-07 17:34:03'),
(259, 159, 94, 'XANON X1 Ultra', 'XANON X1 Ultra', 1, 17999.00, 17999.00, '2025-10-07 17:34:03'),
(260, 160, 79, 'WCG-2G0-CGXX-XX', 'WCG-2G0-CGXX-XX', 1, 55490.00, 55490.00, '2025-10-08 04:12:18'),
(261, 161, 90, 'Redmi Note 14', 'Redmi Note 14', 1, 29399.00, 29399.00, '2025-10-08 04:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `payment_method` enum('bkash','nogod','cash') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed','cancelled') DEFAULT 'pending',
  `gateway_response` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`id`, `order_id`, `transaction_id`, `payment_method`, `amount`, `status`, `gateway_response`, `processed_at`, `created_at`) VALUES
(1, 1, '123123123', 'bkash', 380.00, 'pending', NULL, NULL, '2025-07-01 06:38:17'),
(2, 2, '123123123', 'bkash', 380.00, 'pending', NULL, NULL, '2025-07-01 06:40:03'),
(3, 3, '123123123', 'bkash', 380.00, 'pending', NULL, NULL, '2025-07-01 06:40:32'),
(4, 4, '123123123', 'bkash', 380.00, 'pending', NULL, NULL, '2025-07-01 06:41:49'),
(5, 5, 'sadfdsf', 'bkash', 380.00, 'pending', NULL, NULL, '2025-07-01 06:43:22'),
(6, 6, 'sadfdsf', 'bkash', 134.25, 'pending', NULL, NULL, '2025-07-01 06:47:56'),
(7, 7, 'sadfdsf', 'bkash', 134.25, 'pending', NULL, NULL, '2025-07-01 06:48:39'),
(8, 8, 'hhh', 'bkash', 130.00, 'pending', NULL, NULL, '2025-07-01 06:51:27'),
(9, 9, 'POS-1751352758-8916', 'cash', 390.93, 'success', NULL, '2025-07-01 06:52:38', '2025-07-01 06:52:38'),
(10, 10, 'POS-1751352994-4326', 'cash', 390.93, 'success', NULL, '2025-07-01 06:56:34', '2025-07-01 06:56:34'),
(11, 11, 'POS-1751353171-5902', 'cash', 427.65, 'success', NULL, '2025-07-01 06:59:31', '2025-07-01 06:59:31'),
(12, 12, 'ORD-1751428701-5358', 'cash', 16.79, 'pending', NULL, NULL, '2025-07-01 23:58:21'),
(13, 13, 'ORD-1751429333-5317', 'cash', 52.50, 'pending', NULL, NULL, '2025-07-02 00:08:53'),
(14, 14, 'POS-1751429584-3593', 'cash', 216.00, 'success', NULL, '2025-07-02 00:13:04', '2025-07-02 00:13:04'),
(15, 15, 'ORD-1751429853-9739', 'cash', 26.25, 'pending', NULL, NULL, '2025-07-02 00:17:33'),
(16, 16, 'ORD-1751430476-2891', 'cash', 16.79, 'pending', NULL, NULL, '2025-07-02 00:27:56'),
(17, 17, 'ORD-1751430855-4626', 'cash', 126.00, 'pending', NULL, NULL, '2025-07-02 00:34:15'),
(18, 18, 'ORD-1751433945-2357', 'cash', 420.00, 'pending', NULL, NULL, '2025-07-02 01:25:45'),
(19, 19, 'POS-1751434047-2280', 'cash', 19440.00, 'success', NULL, '2025-07-02 01:27:27', '2025-07-02 01:27:27'),
(20, 20, 'ORD-1751437246-8160', 'cash', 440.99, 'pending', NULL, NULL, '2025-07-02 02:20:46'),
(21, 21, 'ORD-1752302433-4646', 'cash', 136.49, 'pending', NULL, NULL, '2025-07-12 02:40:33'),
(22, 22, '123', 'bkash', 2501.25, 'cancelled', NULL, '2025-08-25 20:20:57', '2025-07-17 12:16:16'),
(23, 23, '3543545535', 'bkash', 59381.39, 'cancelled', NULL, '2025-08-25 20:20:52', '2025-07-17 13:16:00'),
(24, 24, 'POS-1752774764-5302', 'cash', 1700.00, 'success', NULL, '2025-07-17 13:52:46', '2025-07-17 13:52:46'),
(25, 25, 'POS-1752774931-7569', 'cash', 48000.00, 'success', NULL, '2025-07-17 13:55:31', '2025-07-17 13:55:31'),
(26, 26, 'POS-1752775698-8415', 'cash', 116000.00, 'success', NULL, '2025-07-17 14:08:21', '2025-07-17 14:08:21'),
(27, 27, 'ORD-1755752136-3976', 'cash', 402.50, 'pending', NULL, NULL, '2025-08-21 04:55:36'),
(28, 28, '3', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 04:55:51'),
(29, 29, '3', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 04:56:14'),
(30, 30, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:08:53'),
(31, 31, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:21'),
(32, 32, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:26'),
(33, 33, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:31'),
(34, 34, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:35'),
(35, 35, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:39'),
(36, 36, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:44'),
(37, 37, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:48'),
(38, 38, '12', 'bkash', 402.50, 'pending', NULL, NULL, '2025-08-21 05:12:52'),
(39, 39, '12', '', 402.50, 'cancelled', NULL, '2025-08-21 05:54:53', '2025-08-21 05:53:39'),
(40, 40, '12', 'bkash', 2875.00, 'pending', NULL, NULL, '2025-08-21 06:05:40'),
(41, 41, 'jhj123213', 'bkash', 60375.00, 'pending', NULL, NULL, '2025-09-05 05:48:33'),
(42, 42, 'jhj123213', 'bkash', 60375.00, 'pending', NULL, NULL, '2025-09-05 05:48:35'),
(43, 43, 'jhj123213', 'bkash', 372025.00, 'pending', NULL, NULL, '2025-09-05 06:35:04'),
(44, 44, 'ORD-1757140329-1979', 'cash', 70150.00, 'cancelled', NULL, '2025-09-06 06:33:02', '2025-09-06 06:32:09'),
(45, 45, 'ORD-1757140330-1626', 'cash', 70150.00, 'cancelled', NULL, '2025-09-06 06:33:07', '2025-09-06 06:32:10'),
(46, 46, 'TS66#K23213', 'bkash', 70150.00, 'cancelled', NULL, '2025-09-17 22:19:41', '2025-09-06 06:46:32'),
(47, 47, 'TS66#K23213', 'bkash', 70150.00, 'cancelled', NULL, '2025-09-17 22:19:50', '2025-09-06 06:46:40'),
(48, 48, 'TS66#K23213', 'bkash', 70150.00, 'cancelled', NULL, '2025-09-17 22:19:36', '2025-09-06 06:46:46'),
(49, 49, 'TS66#K23213', 'bkash', 70150.00, 'cancelled', NULL, '2025-09-17 22:19:32', '2025-09-06 06:46:52'),
(50, 50, 'TS66#K23213', 'bkash', 70150.00, 'cancelled', NULL, '2025-09-17 22:19:28', '2025-09-06 06:46:59'),
(51, 51, 'TS66#K23213', 'bkash', 70150.00, 'pending', NULL, NULL, '2025-09-06 06:47:05'),
(78, 84, 'LKd6#K23213', '', 141898.50, 'cancelled', NULL, '2025-09-28 19:45:52', '2025-09-19 06:24:14'),
(79, 85, 'ORD-1758280581-2830', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:29:03', '2025-09-19 11:16:21'),
(80, 86, 'ORD-1758280695-7731', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:37', '2025-09-19 11:18:15'),
(81, 87, 'ORD-1758281101-9091', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:33', '2025-09-19 11:25:01'),
(82, 88, 'ORD-1758281124-6733', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:29', '2025-09-19 11:25:24'),
(83, 89, 'ORD-1758281145-8808', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:23', '2025-09-19 11:25:45'),
(84, 90, 'ORD-1758281167-6040', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:20', '2025-09-19 11:26:07'),
(85, 91, 'ORD-1758281188-5584', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:17', '2025-09-19 11:26:28'),
(86, 92, 'ORD-1758281209-7998', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:13', '2025-09-19 11:26:49'),
(87, 93, 'ORD-1758281231-2925', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:10', '2025-09-19 11:27:11'),
(88, 94, 'ORD-1758281252-4859', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:06', '2025-09-19 11:27:32'),
(89, 95, 'ORD-1758281273-2056', 'cash', 191201.30, 'cancelled', NULL, '2025-09-19 11:56:01', '2025-09-19 11:27:53'),
(90, 96, 'KHUH*jkhk$%j5#', '', 7201.30, 'cancelled', NULL, '2025-09-19 11:55:57', '2025-09-19 11:29:49'),
(91, 97, '#ksiheoYGHJ', 'bkash', 7201.30, 'cancelled', NULL, '2025-09-19 11:55:53', '2025-09-19 11:41:06'),
(92, 98, 'ORD-1758282368-6444', 'cash', 129375.00, 'cancelled', NULL, '2025-09-19 11:55:48', '2025-09-19 11:46:08'),
(93, 99, 'ORD-1758282433-4672', 'cash', 59225.00, 'cancelled', NULL, '2025-09-19 11:55:37', '2025-09-19 11:47:13'),
(94, 100, 'ORD-1758282471-9844', 'cash', 125511.00, 'cancelled', NULL, '2025-09-19 11:55:33', '2025-09-19 11:47:51'),
(95, 101, 'ORD-1758284415-5778', 'cash', 7201.30, 'pending', NULL, NULL, '2025-09-19 12:20:15'),
(96, 102, 'WHG#jbbJHBV', '', 71748.50, 'pending', NULL, NULL, '2025-09-19 12:38:06'),
(97, 103, 'ORD-1758286052-5690', 'cash', 71748.50, 'pending', NULL, NULL, '2025-09-19 12:47:32'),
(98, 104, 'MBKJN#jkbjkb', '', 7201.30, 'pending', NULL, NULL, '2025-09-19 12:50:51'),
(99, 105, 'ORD-1758286582-9452', 'cash', 70150.00, 'pending', NULL, NULL, '2025-09-19 12:56:22'),
(100, 106, 'ORD-1758294790-7928', 'cash', 7201.30, 'pending', NULL, NULL, '2025-09-19 15:13:10'),
(101, 107, 'ORD-1758296005-7750', 'cash', 71748.50, 'pending', NULL, NULL, '2025-09-19 15:33:25'),
(102, 108, 'HGWE#kjH&$', '', 125511.00, 'pending', NULL, NULL, '2025-09-20 06:24:20'),
(103, 109, 'KAJDBHK#kjnkj%jhvjg$', 'bkash', 71748.50, 'pending', NULL, NULL, '2025-09-20 06:52:38'),
(104, 110, 'ORD-1758364888-4202', 'cash', 7201.30, 'pending', NULL, NULL, '2025-09-20 10:41:28'),
(105, 111, 'ORD-1758442809-6007', 'cash', 70150.00, 'pending', NULL, NULL, '2025-09-21 08:20:09'),
(106, 112, 'ORD-1758442910-9064', 'cash', 184000.00, 'pending', NULL, NULL, '2025-09-21 08:21:50'),
(107, 113, '#kjhHJHJJ&KJD%JH', 'bkash', 32763.50, 'pending', NULL, NULL, '2025-09-21 09:26:56'),
(108, 114, 'ORD-1758447997-7176', 'cash', 51750.00, 'pending', NULL, NULL, '2025-09-21 09:46:37'),
(109, 115, 'ORD-1758701122-9579', 'cash', 229998.85, 'pending', NULL, NULL, '2025-09-24 08:05:22'),
(110, 116, 'POS-1758704423-1789', 'cash', 55620.00, 'success', NULL, '2025-09-24 09:00:23', '2025-09-24 09:00:23'),
(111, 117, 'ORD-1758704555-8620', 'cash', 23632.50, 'pending', NULL, NULL, '2025-09-24 09:02:35'),
(112, 118, 'ORD-1758704687-1849', 'cash', 223675.00, 'pending', NULL, NULL, '2025-09-24 09:04:47'),
(113, 119, 'ORD-1758800250-2860', 'cash', 129375.00, 'pending', NULL, NULL, '2025-09-25 11:37:30'),
(114, 120, '#Tsdfeff', 'bkash', 7201.30, 'pending', NULL, NULL, '2025-09-26 18:50:49'),
(115, 121, 'ORD-1758913201-1563', '', 447350.00, 'pending', NULL, NULL, '2025-09-26 19:00:01'),
(116, 122, 'ORD-1758913419-4459', '', 63813.50, 'pending', NULL, NULL, '2025-09-26 19:03:39'),
(117, 123, 'ORD-1758913984-4908', '', 32763.50, 'pending', NULL, NULL, '2025-09-26 19:13:04'),
(118, 124, 'ORD-1758914181-5580', '', 59225.00, 'pending', NULL, NULL, '2025-09-26 19:16:21'),
(119, 125, 'ORD-1758914529-5728', '', 71748.50, 'pending', NULL, NULL, '2025-09-26 19:22:09'),
(120, 126, 'ORD-1758921299-2676', '', 7201.30, 'pending', NULL, NULL, '2025-09-26 21:14:59'),
(121, 127, 'ORD-1758921312-9303', '', 7201.30, 'pending', NULL, NULL, '2025-09-26 21:15:12'),
(122, 128, 'ORD-1758921373-1097', '', 7201.30, 'pending', NULL, NULL, '2025-09-26 21:16:13'),
(123, 129, 'ORD-1758921407-1708', '', 7201.30, 'pending', NULL, NULL, '2025-09-26 21:16:47'),
(124, 130, 'ORD-1758921573-7347', '', 301026.30, 'pending', NULL, NULL, '2025-09-26 21:19:33'),
(125, 131, 'ORD-1758921714-6671', '', 70150.00, 'pending', NULL, NULL, '2025-09-26 21:21:54'),
(126, 132, 'ORD-1758921942-7956', '', 223675.00, 'pending', NULL, NULL, '2025-09-26 21:25:42'),
(127, 133, 'ORD-1758922282-7002', '', 70150.00, 'pending', NULL, NULL, '2025-09-26 21:31:22'),
(128, 134, 'ORD-1758923190-6408', '', 163300.00, 'pending', NULL, NULL, '2025-09-26 21:46:30'),
(129, 135, 'ORD-1758923930-8998', '', 235048.50, 'pending', NULL, NULL, '2025-09-26 21:58:50'),
(130, 136, 'ORD-1759058374-9422', '', 275413.50, 'pending', NULL, NULL, '2025-09-28 11:19:34'),
(131, 137, 'ORD-1759082509-6661', '', 13798.85, 'pending', NULL, NULL, '2025-09-28 18:01:49'),
(132, 138, 'ORD-1759084737-7212', '', 70150.00, 'cancelled', NULL, '2025-09-28 19:45:46', '2025-09-28 18:38:57'),
(133, 139, 'ORD-1759087109-5473', '', 184000.00, 'pending', NULL, NULL, '2025-09-28 19:18:29'),
(134, 140, '#JAJK%klLJNH323', '', 19548.85, 'pending', NULL, NULL, '2025-09-28 19:25:56'),
(135, 141, '#JAJK%klLJ0540', '', 23632.50, 'pending', NULL, NULL, '2025-09-28 20:18:26'),
(136, 142, 'ORD-1759094787-4171', '', 91998.85, 'pending', NULL, NULL, '2025-09-28 21:26:27'),
(137, 143, 'ORD-1759121632-5371', '', 223675.00, 'pending', NULL, NULL, '2025-09-29 04:53:52'),
(138, 144, 'POS-1759135730-7888', 'cash', 6762.96, 'success', NULL, '2025-09-29 08:48:50', '2025-09-29 08:48:50'),
(139, 145, 'ORD-1759591260-4987', '', 163300.00, 'pending', NULL, NULL, '2025-10-05 04:21:00'),
(140, 146, 'JKHG%Kjhk#124JH', '', 223675.00, 'pending', NULL, NULL, '2025-10-05 04:39:52'),
(141, 147, 'ORD-1759593355-7674', '', 91998.85, 'pending', NULL, NULL, '2025-10-05 04:55:55'),
(142, 148, 'ORD-1759606275-2081', '', 163300.00, 'pending', NULL, NULL, '2025-10-05 08:31:15'),
(143, 149, 'ORD-1759606894-2959', '', 209288.50, 'pending', NULL, NULL, '2025-10-05 08:41:34'),
(144, 150, 'ORD-1759607725-3447', '', 189738.50, 'pending', NULL, NULL, '2025-10-05 08:55:25'),
(145, 151, 'ORD-1759609506-9556', '', 32763.50, 'pending', NULL, NULL, '2025-10-05 09:25:06'),
(146, 152, 'ORD-1759615159-9814', '', 124762.35, 'pending', NULL, NULL, '2025-10-05 10:59:19'),
(147, 153, 'ORD-1759617529-4426', '', 54613.50, 'pending', NULL, NULL, '2025-10-05 11:38:49'),
(148, 154, 'ORD-1759618584-4602', '', 33808.85, 'pending', NULL, NULL, '2025-10-05 11:56:24'),
(149, 155, 'ORD-1759620419-1744', '', 184000.00, 'pending', NULL, NULL, '2025-10-05 12:26:59'),
(150, 156, 'ORD-1759621172-2005', '', 184000.00, 'pending', NULL, NULL, '2025-10-05 12:39:32'),
(151, 157, 'ORD-1759628097-7641', '', 32763.50, 'pending', NULL, NULL, '2025-10-05 14:34:57'),
(152, 158, 'ORD-1759687797-3326', '', 163300.00, 'pending', NULL, NULL, '2025-10-05 18:09:57'),
(153, 159, 'ORD-1759858443-7269', '', 112697.70, 'pending', NULL, NULL, '2025-10-07 17:34:03'),
(154, 160, 'ORD-1759896738-1343', '', 63813.50, 'pending', NULL, NULL, '2025-10-08 04:12:18'),
(155, 161, 'ORD-1759898263-2542', '', 33808.85, 'pending', NULL, NULL, '2025-10-08 04:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `markup_percentage` decimal(5,2) DEFAULT 0.00,
  `pricing_method` enum('manual','cost_plus','market_based') DEFAULT 'manual',
  `auto_update_price` tinyint(1) DEFAULT 0,
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `image` varchar(255) DEFAULT NULL,
  `is_hot_item` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `brand` int(11) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL COMMENT 'SEO meta title',
  `meta_description` text DEFAULT NULL COMMENT 'SEO meta description',
  `meta_keywords` text DEFAULT NULL COMMENT 'SEO meta keywords',
  `discount_price` decimal(10,2) DEFAULT NULL COMMENT 'Discounted selling price',
  `discount_start_date` date DEFAULT NULL COMMENT 'Discount start date',
  `discount_end_date` date DEFAULT NULL COMMENT 'Discount end date',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Featured product flag',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Display sort order',
  `views` int(11) DEFAULT 0 COMMENT 'Product view count',
  `sales_count` int(11) DEFAULT 0 COMMENT 'Total sales count',
  `rating_average` decimal(3,2) DEFAULT 0.00 COMMENT 'Average rating (0-5)',
  `rating_count` int(11) DEFAULT 0 COMMENT 'Total ratings count',
  `gallery_images` text DEFAULT NULL COMMENT 'JSON array of additional images',
  `tags` text DEFAULT NULL COMMENT 'Product tags separated by commas',
  `status` enum('draft','active','inactive','out_of_stock') DEFAULT 'draft' COMMENT 'Product status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `subcategory_id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `selling_price`, `cost_price`, `markup_percentage`, `pricing_method`, `auto_update_price`, `stock_quantity`, `min_stock_level`, `image`, `is_hot_item`, `is_active`, `weight`, `dimensions`, `created_at`, `updated_at`, `brand`, `meta_title`, `meta_description`, `meta_keywords`, `discount_price`, `discount_start_date`, `discount_end_date`, `is_featured`, `sort_order`, `views`, `sales_count`, `rating_average`, `rating_count`, `gallery_images`, `tags`, `status`) VALUES
(51, 5, 21, 'Lenovo IdeaPad', 'lenovo-ideapad', 'Key Features\r\nMPN: 82XQ00S4LK\r\nModel: IdeaPad Slim 3 15AMN8\r\nProcessor: AMD Ryzen 3 7320U (4C / 8T, 2.4 / 4.1GHz, 2MB L2 / 4MB L3)\r\nRam: 8GB DDR5, Storage: 512GB SSD\r\nDisplay: 15.6\" FHD (1920x1080) IPS 300nits Anti-glare\r\nFeatures: Mil-Std-810h Military Grade, Wi-Fi 6, BT5.2', 'Lenovo IdeaPad Slim 3 15AMN8 Ryzen 3 7320U 15.6\" FHD Laptop\r\n', 'Lenovo_IdeaPad-01', 'lenovo#01', 51500.00, NULL, 0.00, 'manual', 0, 17, 4, '68cc5864c1194_1758222436.jpg', 1, 1, 3.00, '20x25x20', '2025-08-29 17:27:40', '2025-09-26 19:16:21', 1, '', '', '', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cc5864c1465_1758222436_0.jpg\"]', '', 'active'),
(53, 5, 21, 'Lenovo', 'lenovo', 'Key Features\r\nMPN: 82XM014FLK\r\nModel: IdeaPad Slim 3 15ABR8\r\nProcessor: AMD Ryzen 5 5625U (6-core/12-thread, 16MB cache, up to 4.3 GHz)\r\nRam: 16GB DDR4, Storage: 512GB SSD\r\nDisplay: 15.6\" FHD (1920x1080)\r\nFeatures: Camera privacy shutter, Mil-Std-810h Military Grade', 'Lenovo IdeaPad Slim 3 15ABR8 Ryzen 5 5625U 15.6\" Laptop\r\n', 'Lenovo_IdeaPad-02', '', 61000.00, 0.00, 0.00, 'manual', 0, 1, 5, '68cc5855997d4_1758222421.jpg', 1, 1, NULL, '', '2025-08-29 17:56:09', '2025-10-08 04:48:46', 1, '', '', '', NULL, NULL, NULL, 0, 0, 0, 0, 3.00, 1, '[\"68cc58559992d_1758222421_0.jpg\"]', '', 'active'),
(67, 14, 23, 'HP Pro Tower 280 ', 'hp-pro-tower-280', 'Key Features\r\nMPN: B7NZ1PT\r\nModel: Pro Tower 280 G9\r\nProcessor: Intel Core i7-14700 (33MB L3 Cache, up to 5.4GHz)\r\nRAM: 8 GB DDR5-5600 MT/s, Storage: 512 GB PCIe NVMe M.2 SSD\r\nGraphics: Integrated, Intel UHD Graphics 770\r\nHP USB Wired Keyboard & Mouse', 'HP Pro Tower 280 G9 Core i7 14th Gen Brand PC', 'Desktop-HP-01', '00000000', 6262.00, NULL, 0.00, 'manual', 0, 4, 5, '68cc57d48cb22_1758222292.webp', 1, 1, 15.00, '10x10x10', '2025-09-18 18:24:40', '2025-10-06 17:26:34', 10, 'ok', 'ok', 'ok', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cc57d48cc2e_1758222292_0.webp\"]', 'ok', 'active'),
(68, 14, 25, 'DELL OptiPlex 7010 ', 'dell-optiplex-7010-', 'Key Features\r\nModel: OptiPlex 7010\r\nProcessor: Intel Core i3-13100 (12MB cache, 3.40 GHz to 4.50 GHz turbo)\r\nRAM: 8GB DDR4, Storage: 512GB SSD\r\nGraphics: Intel UHD Graphics 730\r\nDELL Mouse & Keyboard', 'DELL OptiPlex 7010 Core i3 13th Gen 8GB RAM Desktop PC', 'Desktop-Dell-02', '000000000', 62390.00, NULL, 0.00, 'manual', 0, 13, 5, '68cc5748bc359_1758222152.webp', 1, 1, 12.00, '10x10x10', '2025-09-18 18:56:58', '2025-09-28 19:45:52', 13, 'Dell', 'Dell', 'Dell, Desktop', 57500.00, '2025-09-19', '2025-09-30', 1, 0, 0, 0, 0.00, 0, '[\"68cc5748bc47d_1758222152_0.webp\"]', 'Dell', 'active'),
(70, 14, 26, 'Walton AMD Ryzen™ 3 Desktop AVIAN EX', 'wdpc320g12', 'Details of Walton AMD Ryzen™ 3 Desktop AVIAN EX\r\nBasic Information\r\nBrand\r\nWalton\r\nseries name\r\nAvian\r\nmodel\r\nWDPC320G12\r\nprocessor type\r\nAMD\r\nprocessor model\r\nAMD Ryzen™ 3\r\nRAM\r\n8 GB\r\nROM\r\n1 TB\r\n256 GB\r\noperating system\r\nWindows 10 Supported\r\nchipset\r\nAMD B450 Chipset\r\nDetailed Information\r\nmemory\r\nWalton Desktop RAM 8GB 3200 RGB\r\n\r\ncache memory\r\n6MB Cache Memory\r\nclock speed\r\nBase Clock: 3.60 GHz, Max. Boost Clock: Up to 4.0GHz\r\ngraphics\r\nRadeon™ Vega 8 Graphics\r\n\r\nstorage\r\n1TB SATA 3.5\" 7200 RPM HDD\r\n\r\n256GB M.2 2280 SATA III SSD\r\n\r\nprocessor\r\nAMD Ryzen 3 3200G Processor - No of cores 4 and Threads 4\r\nIO Ports/Interface\r\n1 x PS/2 keyboard port\r\n\r\n1 x PS/2 mouse Port\r\n\r\n1 x VGA port\r\n\r\n1 x DVI-D Port\r\n\r\n1 x HDMI Port\r\n\r\n4 x USB 3.1 Gen 1 Ports\r\n\r\n2 x USB 2.0/1.1 Ports\r\n\r\n1 x RJ-45 Port\r\n\r\n3 x audio Jacks\r\n\r\nnetwork & communication\r\nRealtek® GbE LAN Chip\r\n\r\npower supply\r\n300W 80+ Bronze\r\n\r\nchassis\r\nBlack, Mid Tower\r\nSpecial Feature\r\nWarranty Information\r\nwarranty\r\nProcessor: 3 Years\r\n\r\nMotherboard: 3 Years\r\n\r\nHard Disk, SSD & RAM: 3 Years\r\n\r\nAC Cord: 6 Months\r\n\r\nOthers Parts: 3 Years\r\n\r\nTerms & Conditions\r\nWarranty Terms & Conditions\r\nThis warranty does not cover any damage due to accident, electricity fault, natural causes, or negligence. And Authority keeps the power to change, expand, correct, stop or cancel the warranty period without any prior notice.', 'Walton AMD Ryzen™ 3 Desktop AVIAN EX\r\nWDPC320G12', 'Desktop-Walton-01', '0000000', 46750.00, NULL, 0.00, 'manual', 0, 9, 5, '68cc5c2153fd3_1758223393.png', 0, 1, 10.00, '10x10x10', '2025-09-18 19:23:13', '2025-09-24 08:12:04', NULL, 'Walton', 'Walton', 'Walton', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cc5c21541de_1758223393_0.png\"]', 'Walton', 'active'),
(72, 5, 21, 'lenovo-ideapad', 'lenovo-ideapad-slim-5-16akp10-ryzen-ai-7-350-16-28k-laptop', ' Key Features\r\n•	MPN: 83HY003EIN\r\n•	Model: IdeaPad Slim 5 16AKP10\r\n•	Processor: AMD Ryzen AI 7 350 (8C / 16T, Up to 5.0GHz) \r\n•	RAM: 32GB DDR5 5600MHz; Storage: 1TB SSD \r\n•	Display: 16\" 2.8K (2880x1800) OLED 500nits Glossy \r\n•	Features: Backlit Keyboard, MIL-STD-810H Military Grade,Copilot+ PC\r\n', 'Lenovo IdeaPad Slim 5 16AKP10 Ryzen AI 7 350 16\" 2.8K Laptop', 'Laptop-Lenovo-05', '00000', 160000.00, NULL, 0.00, 'manual', 0, 16, 5, '68ccdd92db82b_1758256530.jpg', 1, 1, 5.00, '10x10x10', '2025-09-19 04:35:30', '2025-10-04 23:39:32', 1, 'lenovo', 'Lenovo', 'Lenovo', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68ccdd92db9c1_1758256530_0.jpg\"]', 'Lenovo', 'active'),
(73, 26, 30, 'WFK-3G0-GDEL-XX', 'wfk-3g0-gdel-xx', 'Type: Direct Cool\r\n   - Door: Glass Door\r\n   - Gross Volume: 370 Ltr\r\n   - Net Volume: 367 Ltr\r\n   - Special Technology: Nano Healthcare\r\n   - Refrigerant: R600a\r\n', 'Type: Direct Cool\r\n   - Door: Glass Door\r\n   - Gross Volume: 370 Ltr\r\n   - Net Volume: 367 Ltr\r\n   - Special Technology: Nano Healthcare\r\n   - Refrigerant: R600a', 'WFK-3G0-GDEL-XX', 'WFK-3G0-GDEL-XX', 54490.00, NULL, 0.00, 'manual', 0, 10, 5, '68cfc1b91f1ca_1758446009.jpg', 1, 1, 38.00, '20x25x20', '2025-09-21 09:13:29', '2025-09-21 09:13:29', 6, 'walton-frige', 'walton-frige', 'walton-frige', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cfc1b91f4fe_1758446009_0.jpg\"]', 'walton-frige', 'active'),
(74, 26, 30, 'WFE-3B0-GDEL-XX', 'wfe-3b0-gdel-xx', '   - Type: Direct Cool\r\n   - Gross Volume: 341 Ltr\r\n   - Net Volume: 320 Ltr\r\n   - Refrigerant:  R600a', 'WFE-3B0-GDEL-XX\r\n   - Type: Direct Cool\r\n   - Gross Volume: 341 Ltr\r\n   - Net Volume: 320 Ltr\r\n   - Refrigerant:  R600a', 'WFE-3B0-GDEL-XX', 'WFE-3B0-GDEL-XX', 48990.00, NULL, 0.00, 'manual', 0, 10, 5, '68cfc23766119_1758446135.jpg', 1, 1, 40.00, '35x25x35', '2025-09-21 09:15:35', '2025-09-21 09:15:35', 6, 'walton-frige', 'walton-frige', 'walton, frige', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cfc23766480_1758446135_0.jpg\"]', 'walton, frige', 'active'),
(75, 26, 30, 'WFC-3A7-GDXX-XX', 'wfc-3a7-gdxx-xx', '   - Type: Direct Cool\r\n   - Door: Glass Door\r\n   - Gross Volume: 337 Ltr\r\n   - Net Volume: 317 Ltr\r\n   - Refrigerant: R600a\r\n   - Wide Voltage Design (150V-260V)\r\n', '   - Type: Direct Cool\r\n   - Door: Glass Door\r\n   - Gross Volume: 337 Ltr\r\n   - Net Volume: 317 Ltr\r\n   - Refrigerant: R600a\r\n   - Wide Voltage Design (150V-260V)\r\n', 'WFC-3A7-GDXX-XX', 'WFC-3A7-GDXX-XX', 47490.00, NULL, 0.00, 'manual', 0, 9, 5, '68cfc2d868700_1758446296.jpg', 1, 1, 42.00, '35x25x35', '2025-09-21 09:18:16', '2025-10-04 22:38:49', 6, 'walton frige', 'walton frige', 'walton frige', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cfc2d868a0a_1758446296_0.jpg\"]', 'walton frige', 'active'),
(76, 26, 30, 'WFE-2N5-GDXX-XX', 'wfe-2n5-gdxx-xx', '- Type: Direct Cool\r\n   - Gross Volume: 316 Ltr\r\n   - Net Volume: 295 Ltr\r\n   - Refrigerant: R600a\r\n', '- Type: Direct Cool\r\n   - Gross Volume: 316 Ltr\r\n   - Net Volume: 295 Ltr\r\n   - Refrigerant: R600a\r\n', 'WFE-2N5-GDXX-XX', 'WFE-2N5-GDXX-XX', 44990.00, NULL, 0.00, 'manual', 0, 4, 2, '68cfc35666abe_1758446422.jpg', 1, 1, 40.00, '20x25x20', '2025-09-21 09:20:22', '2025-09-28 11:19:34', 6, 'walton-frige', '- Type: Direct Cool\r\n   - Gross Volume: 316 Ltr\r\n   - Net Volume: 295 Ltr\r\n   - Refrigerant: R600a\r\n', 'walton frige', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cfc35666dfc_1758446422_0.jpg\"]', '', 'active'),
(77, 26, 30, 'WCF-1B5-GDEL-XX', 'wcf-1b5-gdel-xx', 'WCF-1B5-GDEL-XX\r\n   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 125 L\r\n   - Net Volume: 125 L\r\n   - Refrigerant: V.0101-R600a', 'WCF-1B5-GDEL-XX\r\n   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 125 L\r\n   - Net Volume: 125 L\r\n   - Refrigerant: V.0101-R600a', 'WCF-1B5-GDEL-XX', 'WCF-1B5-GDEL-XX', 28490.00, NULL, 0.00, 'manual', 0, 0, 1, '68cfc45760dd7_1758446679.jpg', 1, 1, 50.00, '20x10x20x10', '2025-09-21 09:24:39', '2025-10-05 01:34:57', 6, 'WCF-1B5-GDEL-XX', 'WCF-1B5-GDEL-XX', 'walton frige', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cfc457610cc_1758446679_0.jpg\"]', '', 'active'),
(78, 26, 30, 'WCF-2T5-GDEL-GX', 'wcf-2t5-gdel-gx', 'WCF-2T5-GDEL-GX\r\n   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 205 Ltr\r\n   - Net Volume: 205 Ltr\r\n   - Refrigerant: R600a\r\n', '   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 205 Ltr\r\n   - Net Volume: 205 Ltr\r\n   - Refrigerant: R600a', 'WCF-2T5-GDEL-GX', 'WCF-2T5-GDEL-GX', 37590.00, NULL, 0.00, 'manual', 0, 4, 1, '68cfc6b22f40a_1758447282.jpg', 0, 1, 60.00, '35x15x35X15', '2025-09-21 09:34:42', '2025-09-21 09:34:42', 6, 'walton-frige', '   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 205 Ltr\r\n   - Net Volume: 205 Ltr\r\n   - Refrigerant: R600a\r\n', 'Walton, Frige', NULL, NULL, NULL, 0, 2, 0, 0, 0.00, 0, '[\"68cfc6b22f759_1758447282_0.jpg\"]', 'Walton, Frige', 'active'),
(79, 26, 30, 'WCG-2G0-CGXX-XX', 'wcg-2g0-cgxx-xx', 'WCG-2G0-CGXX-XX\r\n   - Type: DECS\r\n   - Category: Ice Cream Freezer\r\n   - Gross Volume: 270 Ltr\r\n   - Net Volume: 270 Ltr', 'WCG-2G0-CGXX-XX\r\n   - Type: DECS\r\n   - Category: Ice Cream Freezer\r\n   - Gross Volume: 270 Ltr\r\n   - Net Volume: 270 Ltr', 'WCG-2G0-CGXX-XX', 'WCG-2G0-CGXX-XX', 55490.00, NULL, 0.00, 'manual', 0, 3, 1, '68cfc829689f3_1758447657.jpg', 1, 1, 55.00, '40x20x40x20', '2025-09-21 09:40:57', '2025-10-08 04:12:18', 6, 'walton-frige', 'Walton Frige\r\n   - Type: DECS\r\n   - Category: Ice Cream Freezer\r\n   - Gross Volume: 270 Ltr\r\n   - Net Volume: 270 Ltr\r\n', 'Walton Frige', NULL, NULL, NULL, 1, 3, 0, 0, 0.00, 0, '[\"68cfc82969061_1758447657_0.jpg\"]', 'walton frige', 'active'),
(80, 26, 30, 'WCG-2E5-EHLC-XX', 'wcg-2e5-ehlc-xx', '   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 255 Ltr\r\n   - Net Volume: 255 Ltr\r\n   - Refrigerant: R600a\r\n', '   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 255 Ltr\r\n   - Net Volume: 255 Ltr\r\n   - Refrigerant: R600a\r\n', 'Walton-Frige-13', 'WCG-2E5-EHLC-XX', 45000.00, NULL, 0.00, 'manual', 0, 19, 3, '68cfc950f16db_1758447952.jpg', 1, 1, 50.00, '20x10x20x10', '2025-09-21 09:45:52', '2025-09-21 09:46:37', 6, 'WCG-2E5-EHLC-XX', '   - Type: Direct Cool\r\n   - Category: Frost\r\n   - Gross Volume: 255 Ltr\r\n   - Net Volume: 255 Ltr\r\n   - Refrigerant: R600a\r\n', 'Walton Frige', 38990.00, '2025-09-21', '2026-02-21', 1, 0, 0, 0, 0.00, 0, '[\"68cfc950f1954_1758447952_0.jpg\"]', '', 'active'),
(81, 26, 30, 'Walton WNI-6A9-GMSD-DD', 'walton-wni-6a9-gmsd-dd', '   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 619 Ltr\r\n   - Net Volume: 582 Ltr\r\n   - CFC Free: R600a\r\n   - Using Latest MSO INVERTER technology\r\n   - No need to use Voltage Stabilizer\r\n', '   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 619 Ltr\r\n   - Net Volume: 582 Ltr\r\n   - CFC Free: R600a\r\n   - Using Latest MSO INVERTER technology\r\n   - No need to use Voltage Stabilizer\r\n', 'WNI-6A9-GMSD-DD', 'WNI-6A9-GMSD-DD', 129990.00, NULL, 0.00, 'manual', 0, 1, 2, '68cfcb1852bed_1758448408.jpg', 1, 1, 80.00, '40x05x40x05', '2025-09-21 09:53:28', '2025-10-06 17:28:13', 6, 'walton-frige', '   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 619 Ltr\r\n   - Net Volume: 582 Ltr\r\n   - CFC Free: R600a\r\n   - Using Latest MSO INVERTER technology\r\n   - No need to use Voltage Stabilizer', 'walton frige', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68cfcb1852ec9_1758448408_0.jpg\"]', '', 'active'),
(82, 26, 30, 'Walton WNR-6F0-SCRC-CO', 'wnr-6f0-scrc-co', 'WNR-6F0-SCRC-CO\r\n   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 660 Ltr \r\n   - Net Volume: 613 Ltr\r\n   - CFC Free: R600a', 'WNR-6F0-SCRC-CO\r\n   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 660 Ltr \r\n   - Net Volume: 613 Ltr\r\n   - CFC Free: R600a', 'WNR-6F0-SCRC-CO', 'WNR-6F0-SCRC-CO', 181990.00, NULL, 0.00, 'manual', 0, 4, 1, '68cfcd7d07cf5_1758449021.jpg', 1, 1, 85.00, '40x20x40x20', '2025-09-21 10:03:41', '2025-10-06 17:17:44', 6, 'walton-frige', 'WNR-6F0-SCRC-CO\r\n   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 660 Ltr \r\n   - Net Volume: 613 Ltr\r\n   - CFC Free: R600a\r\n', 'Walton Frige', NULL, NULL, NULL, 1, 0, 0, 0, 3.00, 1, '[\"68cfcd7d080ab_1758449021_0.jpg\"]', '', 'active'),
(83, 26, 30, 'Walton WNI-6A9-GDNE-BD', 'wni-6a9-gdne-bd', ' WNI-6A9-GDNE-BD\r\n   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 591 Ltr\r\n   - Net Volume: 548 Ltr\r\n   - CFC Free: R600a\r\n   - Using Latest MSO INVERTER technology\r\n   - No need to use Voltage Stabilizer\r\n', ' WNI-6A9-GDNE-BD\r\n   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 591 Ltr\r\n   - Net Volume: 548 Ltr\r\n   - CFC Free: R600a\r\n   - Using Latest MSO INVERTER technology\r\n   - No need to use Voltage Stabilizer\r\n', 'WNI-6A9-GDNE-BD', 'WNI-6A9-GDNE-BD', 164990.00, NULL, 0.00, 'manual', 0, 4, 1, '68cfce107cce2_1758449168.jpg', 1, 1, 90.00, '50x35x50x35', '2025-09-21 10:06:08', '2025-10-08 03:55:03', 6, 'walton-frige', 'Walton\r\n WNI-6A9-GDNE-BD\r\n   - Type: No-Frost\r\n   - HCFC free : Cyclopentane\r\n   - Gross Volume: 591 Ltr\r\n   - Net Volume: 548 Ltr\r\n   - CFC Free: R600a\r\n   - Using Latest MSO INVERTER technology\r\n   - No need to use Voltage Stabilizer\r\n', 'Walton Frige', NULL, NULL, NULL, 1, 0, 0, 0, 4.00, 1, NULL, '', 'active'),
(84, 15, 24, 'iPhone 16', 'iphone-16', 'Key Features\r\n•	MPN: MYEE3X/A/ MYEH3X/A/ MYEG3X/A\r\n•	Model: iPhone 16 (A3287)\r\n•	Display: 6.1\" Super Retina XDR OLED Display \r\n•	Processor: A18 Bionic Chip (3nm), Storage: 128GB, 256GB \r\n•	Camera: Dual 48MP Fusion + 12MP Ultra Wide on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'Key Features\r\n•	MPN: MYEE3X/A/ MYEH3X/A/ MYEG3X/A\r\n•	Model: iPhone 16 (A3287)\r\n•	Display: 6.1\" Super Retina XDR OLED Display \r\n•	Processor: A18 Bionic Chip (3nm), Storage: 128GB, 256GB \r\n•	Camera: Dual 48MP Fusion + 12MP Ultra Wide on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'iPhone 16 ', 'apple-iphone-1', 142000.00, NULL, 0.00, 'manual', 0, 5, 5, '68d3836943e25_1758692201.jpg', 1, 1, 0.30, '05x10x10', '2025-09-24 05:36:41', '2025-10-05 18:09:57', 21, 'i-phone', 'Key Features\r\n•	MPN: MYEE3X/A/ MYEH3X/A/ MYEG3X/A\r\n•	Model: iPhone 16 (A3287)\r\n•	Display: 6.1\" Super Retina XDR OLED Display \r\n•	Processor: A18 Bionic Chip (3nm), Storage: 128GB, 256GB \r\n•	Camera: Dual 48MP Fusion + 12MP Ultra Wide on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'iphone, apple', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68d383694422c_1758692201_0.jpg\"]', 'apple, iphone', 'active'),
(85, 15, 24, 'Phone 16 Pro', 'phone-16-pro', 'Key Features\r\n•	MPN: MYNH3X/A/ MYNK3X/A/ MYNL3X/A/ MYNJ3X/A\r\n•	Model: iPhone 16 Pro (A293)\r\n•	Display: 6.3\" Super Retina XDR OLED Display \r\n•	Processor: A18 Pro Chip (3nm), Storage: 128GB, 256GB \r\n•	Camera: Triple 48MP Fusion + 48MP Ultra Wide + 12MP Telephoto on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'Key Features\r\n•	MPN: MYNH3X/A/ MYNK3X/A/ MYNL3X/A/ MYNJ3X/A\r\n•	Model: iPhone 16 Pro (A293)\r\n•	Display: 6.3\" Super Retina XDR OLED Display \r\n•	Processor: A18 Pro Chip (3nm), Storage: 128GB, 256GB \r\n•	Camera: Triple 48MP Fusion + 48MP Ultra Wide + 12MP Telephoto on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'iPhone 16 pro', 'ipone-02', 183000.00, NULL, 0.00, 'manual', 0, 9, 5, '68d395a44df45_1758696868.jpg', 1, 1, 0.20, '10x10x10', '2025-09-24 06:54:28', '2025-10-06 17:24:20', 21, 'i-phone', 'Key Features\r\n•	MPN: MYNH3X/A/ MYNK3X/A/ MYNL3X/A/ MYNJ3X/A\r\n•	Model: iPhone 16 Pro (A293)\r\n•	Display: 6.3\" Super Retina XDR OLED Display \r\n•	Processor: A18 Pro Chip (3nm), Storage: 128GB, 256GB \r\n•	Camera: Triple 48MP Fusion + 48MP Ultra Wide + 12MP Telephoto on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'iphone, apple', NULL, NULL, NULL, 1, 0, 0, 0, 4.00, 1, '[\"68d395a44e68a_1758696868_0.jpg\"]', '', 'active'),
(86, 15, 24, 'iPhone 16 Pro Max', 'iphone-16-pro-max', 'Key Features\r\n•	MPN: MYWX3X/A/ MYWY3X/A/ MYWW3X/A/ MYWV3X/A\r\n•	Model: iPhone 16 Pro Max (A3296)\r\n•	Display: 6.9\" Super Retina XDR OLED Display \r\n•	Processor: A18 Pro Chip (3nm), Storage: 256GB, 512GB, 1TB \r\n•	Camera: Triple 48MP Fusion + 48MP Ultra Wide + 12MP Telephoto on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', '\r\nKey Features\r\n•	MPN: MYWX3X/A/ MYWY3X/A/ MYWW3X/A/ MYWV3X/A\r\n•	Model: iPhone 16 Pro Max (A3296)\r\n•	Display: 6.9\" Super Retina XDR OLED Display \r\n•	Processor: A18 Pro Chip (3nm), Storage: 256GB, 512GB, 1TB \r\n•	Camera: Triple 48MP Fusion + 48MP Ultra Wide + 12MP Telephoto on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'iPhone 16 Pro Max', 'ipone-03', 194500.00, NULL, 0.00, 'manual', 0, 2, 1, '68d396274708d_1758696999.jpg', 1, 1, 0.20, '', '2025-09-24 06:56:39', '2025-10-06 17:30:45', 21, 'iPhone 16 Pro Max', 'Key Features\r\n•	MPN: MYWX3X/A/ MYWY3X/A/ MYWW3X/A/ MYWV3X/A\r\n•	Model: iPhone 16 Pro Max (A3296)\r\n•	Display: 6.9\" Super Retina XDR OLED Display \r\n•	Processor: A18 Pro Chip (3nm), Storage: 256GB, 512GB, 1TB \r\n•	Camera: Triple 48MP Fusion + 48MP Ultra Wide + 12MP Telephoto on Back, 12MP on Front \r\n•	Features: New \"Camera Control Button\", Face ID, USB Type-C, Spatial Video Support\r\n', 'iphone, apple', NULL, NULL, NULL, 1, 0, 0, 0, 5.00, 1, '[\"68d3962747327_1758696999_0.jpg\"]', '', 'active'),
(87, 15, 31, 'Xiaomi Redmi A5', 'xiaomi-redmi-a5', 'Key Features\r\n•	Model: Redmi A5\r\n•	Display: 6.88\" HD+ 120Hz IPS Display \r\n•	Processor: Unisoc T7250 (12 nm) \r\n•	Camera: 32MP+Auxiliary on Rear, 8MP Selfie \r\n•	Features: Side Fingerprint, 5200mAh Battery\r\n', 'Key Features\r\n•	Model: Redmi A5\r\n•	Display: 6.88\" HD+ 120Hz IPS Display \r\n•	Processor: Unisoc T7250 (12 nm) \r\n•	Camera: 32MP+Auxiliary on Rear, 8MP Selfie \r\n•	Features: Side Fingerprint, 5200mAh Battery\r\n', 'Xiaomi Redmi A5', 'x', 10850.00, NULL, 0.00, 'manual', 0, 1, 5, '68d3a354ee67a_1758700372.jpg', 1, 0, 0.30, '10x10x10', '2025-09-24 07:52:52', '2025-09-27 17:48:35', 29, 'xiaomi', 'xiaomi', 'xiaomi', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68d3a354ee9fa_1758700372_0.jpg\"]', 'xiaomi-phone', 'inactive'),
(88, 15, 31, 'Xiaomi Redmi 13 (6/128GB)', 'xiaomi-redmi-13-6128gb', 'Key Features\r\n•	Model: Redmi 13\r\n•	Display: 6.79\" FHD+ 90Hz IPS Dot Display \r\n•	Processor: MediaTek Helio G91-Ultra (12nm) \r\n•	Camera: Dual 108+2MP on Rear, 13MP Selfie \r\n•	Features: Side Fingerprint, 33W Fast Charging\r\n', '\r\nKey Features\r\n•	Model: Redmi 13\r\n•	Display: 6.79\" FHD+ 90Hz IPS Dot Display \r\n•	Processor: MediaTek Helio G91-Ultra (12nm) \r\n•	Camera: Dual 108+2MP on Rear, 13MP Selfie \r\n•	Features: Side Fingerprint, 33W Fast Charging\r\n', 'Xiaomi Redmi 13 (6/128GB)', 'Xiaomi Redmi 13 (6/128GB)', 16490.00, NULL, 0.00, 'manual', 0, 25, 5, '68d3a3bb99584_1758700475.jpg', 0, 1, 0.20, '10x10x10', '2025-09-24 07:54:35', '2025-09-24 07:54:35', 29, 'xiaomi', '', '', NULL, NULL, NULL, 0, 0, 0, 0, 0.00, 0, NULL, '', 'active'),
(89, 15, 31, 'Xiaomi Redmi Note 14', 'xiaomi-redmi-note-14', 'Key Features\r\n•	Model: Redmi Note 14\r\n•	Display: 6.67\" FHD+ AMOLED 120Hz Display \r\n•	Processor: MediaTek Helio G99-Ultra (6nm) \r\n•	Camera: Triple 108+2+2MP on Rear, 20MP Selfie \r\n•	Features: IP54, Under In-Fingerprint, 33W Fast Charging\r\n', 'Key Features\r\n•	Model: Redmi Note 14\r\n•	Display: 6.67\" FHD+ AMOLED 120Hz Display \r\n•	Processor: MediaTek Helio G99-Ultra (6nm) \r\n•	Camera: Triple 108+2+2MP on Rear, 20MP Selfie \r\n•	Features: IP54, Under In-Fingerprint, 33W Fast Charging\r\n', 'Xiaomi Redmi Note 14', 'Xiaomi Redmi Note 14', 20550.00, NULL, 0.00, 'manual', 0, 33, 5, '68d3a42ee95a4_1758700590.jpg', 1, 1, NULL, '', '2025-09-24 07:56:30', '2025-09-28 20:18:26', 29, 'Xiaomi Redmi Note 14', 'Key Features\r\n•	Model: Redmi Note 14\r\n•	Display: 6.67\" FHD+ AMOLED 120Hz Display \r\n•	Processor: MediaTek Helio G99-Ultra (6nm) \r\n•	Camera: Triple 108+2+2MP on Rear, 20MP Selfie \r\n•	Features: IP54, Under In-Fingerprint, 33W Fast Charging\r\n', 'Xiaomi Redmi Note 14', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68d3a42ee9930_1758700590_0.jpg\"]', 'xiaomi-phone', 'active'),
(90, 15, 31, 'Redmi Note 14', 'redmi-note-14', 'Key Features\r\n•	Model: Redmi Note 14 Pro\r\n•	Display: 6.67\" FHD+ AMOLED 120Hz Display \r\n•	Processor: Helio G100 Ultra (6nm) \r\n•	Camera: Triple 200+8+2MP on Rear 32MP Selfie \r\n•	Features: IP64, Under Display Fingerprint, 45W Fast Charging\r\n', 'Key Features\r\n•	Model: Redmi Note 14 Pro\r\n•	Display: 6.67\" FHD+ AMOLED 120Hz Display \r\n•	Processor: Helio G100 Ultra (6nm) \r\n•	Camera: Triple 200+8+2MP on Rear 32MP Selfie \r\n•	Features: IP64, Under Display Fingerprint, 45W Fast Charging\r\n', 'Redmi Note 14', 'Redmi Note 14', 29399.00, NULL, 0.00, 'manual', 0, 8, 5, '68d3a4a3e52c9_1758700707.jpg', 1, 1, 0.12, '10x10x10', '2025-09-24 07:58:27', '2025-10-08 04:42:19', 29, 'Redmi Note 14', 'Key Features\r\n•	Model: Redmi Note 14 Pro\r\n•	Display: 6.67\" FHD+ AMOLED 120Hz Display \r\n•	Processor: Helio G100 Ultra (6nm) \r\n•	Camera: Triple 200+8+2MP on Rear 32MP Selfie \r\n•	Features: IP64, Under Display Fingerprint, 45W Fast Charging\r\n', 'Redmi Note 14', NULL, NULL, NULL, 1, 0, 0, 0, 4.00, 1, '[\"68d3a4a3e559e_1758700707_0.jpg\"]', 'Redmi Note 14', 'active'),
(91, 15, 32, 'Vivo Y04', 'vivo-y04', 'Key Features\r\n•	Model: Y04\r\n•	Display: 6.74\" HD+ 90Hz IPS LCD Display \r\n•	Processor: Unisoc T7225 (12 nm) \r\n•	Camera: Dual 13+0.08MP on Rear, 5MP on Front \r\n•	Features: IP64, 5500mAh Battery, 15W Fast Charging\r\n', 'Key Features\r\n•	Model: Y04\r\n•	Display: 6.74\" HD+ 90Hz IPS LCD Display \r\n•	Processor: Unisoc T7225 (12 nm) \r\n•	Camera: Dual 13+0.08MP on Rear, 5MP on Front \r\n•	Features: IP64, 5500mAh Battery, 15W Fast Charging\r\n', 'Vivo Y04', 'Vivo Y04', 11999.00, NULL, 0.00, 'manual', 0, 19, 5, '68d3a553af098_1758700883.jpg', 1, 1, 0.10, '', '2025-09-24 08:01:23', '2025-09-28 18:01:49', 28, 'Vivo', 'Key Features\r\n•	Model: Y04\r\n•	Display: 6.74\" HD+ 90Hz IPS LCD Display \r\n•	Processor: Unisoc T7225 (12 nm) \r\n•	Camera: Dual 13+0.08MP on Rear, 5MP on Front \r\n•	Features: IP64, 5500mAh Battery, 15W Fast Charging\r\n', 'vivo phoone', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68d3a553af409_1758700883_0.jpg\"]', 'Vivo', 'active'),
(92, 15, 32, 'Vivo Y19s Pro', 'vivo-y19s-pro', 'Key Features\r\n•	Model: Y19s Pro\r\n•	Display: 6.68\" HD+ IPS LCD 90Hz Display \r\n•	Processor: Unisoc T612 (12nm) \r\n•	Camera: 50MP on Rear, 5MP Front \r\n•	Features: IP64, Side-mounted fingerprint\r\n', 'Key Features\r\n•	Model: Y19s Pro\r\n•	Display: 6.68\" HD+ IPS LCD 90Hz Display \r\n•	Processor: Unisoc T612 (12nm) \r\n•	Camera: 50MP on Rear, 5MP Front \r\n•	Features: IP64, Side-mounted fingerprint\r\n', 'Vivo Y19s Pro', 'Vivo Y19s Pro', 16999.00, NULL, 0.00, 'manual', 0, 28, 5, '68d3a5de51875_1758701022.jpg', 1, 1, 0.10, '', '2025-09-24 08:03:42', '2025-09-28 19:25:56', 28, 'Vivo Y19s Pro', 'Key Features\r\n•	Model: Y19s Pro\r\n•	Display: 6.68\" HD+ IPS LCD 90Hz Display \r\n•	Processor: Unisoc T612 (12nm) \r\n•	Camera: 50MP on Rear, 5MP Front \r\n•	Features: IP64, Side-mounted fingerprint\r\n', 'Vivo phone', NULL, NULL, NULL, 1, 0, 0, 0, 0.00, 0, '[\"68d3a5de51ba1_1758701022_0.jpg\"]', 'VIVO PHONE', 'active'),
(93, 14, 26, 'KAIMAN Z22 Walton', 'kaiman-z22-walton', 'Operating System\r\nscreen\r\nWindows 10/11 Supported (Free DOS)\r\nProcessor\r\n\r\nscreen\r\nIntel® Core™ i7-13700\r\nClock Speed\r\n\r\nBase Frequency 2.10 GHz, Max Turbo Frequency 5.20 GHz\r\nCache Memory\r\n\r\n30 MB Intel® Smart Cache\r\nCPU Cooler\r\n\r\nStock Cooler\r\nChipset\r\n\r\nIntel® H610\r\nGraphic\r\n\r\nscreen\r\nIntel® UHD Graphics 770\r\nMemory\r\n\r\n8GB DDR5 RAM, Expandable up to 64GB\r\nStorage\r\n\r\n512GB M.2 NVMe SSD\r\nAudio\r\n\r\nRealtek® High-Definition Audio\r\nNetwork interface\r\n\r\nGbE LAN Chip\r\nFront IO ports\r\n\r\n2 x USB 2.0\r\n1 x USB 3.1\r\n1 x Microphone In\r\n1 x Headphone Out\r\nBack Panel IO ports\r\n\r\n1 x HDMI port\r\n2 x USB 2.0/1.1 ports\r\n1 x RJ-45 port\r\n2 x USB 3.2 Gen 1 ports\r\n3 x Audio jacks\r\nChassis\r\n\r\nTower\r\nPower\r\n\r\n500W 80+ Bronze\r\nAccessories\r\n\r\nStandard Wired Keyboard\r\nStandard Wired Mouse\r\nSystem Power Cord\r\nMonitor\r\n\r\nWalton 21.45\" IPS Monitor (WD215I09)', 'KAIMAN (WDPC104056 DPI) With a Core i7 processor, 8GB of RAM & 512GB M.2 NVMe SSD which provides the flow that you can work smoothly.', 'KAIMAN Z22', 'KAIMAN Z22', 79999.00, NULL, 0.00, 'manual', 0, 9, 5, '68d9a3e546754_1759093733.png', 0, 1, 5.00, '10x10x10', '2025-09-28 21:08:53', '2025-10-07 17:34:03', 6, 'KAIMAN Z22 Walton', 'KAIMAN (WDPC104056 DPI) With a Core i7 processor, 8GB of RAM & 512GB M.2 NVMe SSD which provides the flow that you can work smoothly.', 'walton desktop', NULL, NULL, NULL, 1, 0, 0, 0, 5.00, 1, '[\"68d9a3e546a4b_1759093733_0.png\"]', '', 'active'),
(94, 15, 40, 'XANON X1 Ultra', 'xanon-x1-ultra', '\r\nXANON X1 Ultra\r\n\r\n   - Display: 6.6” HD+ Punch Hole Display | 120Hz Refresh Rate | 700nits Peak Brightness\r\n   - Processor: MediaTek Helio G99 Gaming SoC | 6nm High-Efficiency Process\r\n   - Rapid Memory: 12GB | 128GB Internal Storage (UFS 2.2)\r\n   - Rear Camera: 50MP Dual Rear Cameras | 8MP Selfie\r\n   - Dual Stereo Speakers\r\n   - Battery: 5000mAh (3 Years Battery Performance Assurance)\r\n   - Charging: 33W Super Fast Charging\r\n   - Android™ 15', '\r\nXANON X1 Ultra\r\n\r\n   - Display: 6.6” HD+ Punch Hole Display | 120Hz Refresh Rate | 700nits Peak Brightness\r\n   - Processor: MediaTek Helio G99 Gaming SoC | 6nm High-Efficiency Process\r\n   - Rapid Memory: 12GB | 128GB Internal Storage (UFS 2.2)\r\n   - Rear Camera: 50MP Dual Rear Cameras | 8MP Selfie\r\n   - Dual Stereo Speakers\r\n   - Battery: 5000mAh (3 Years Battery Performance Assurance)\r\n   - Charging: 33W Super Fast Charging\r\n   - Android™ 15', 'XANON X1 Ultra', 'XANON X1 Ultra', 17999.00, NULL, 0.00, 'manual', 0, 19, 2, '68e54e34953c5_1759858228.webp', 0, 1, 0.20, 'Height: 163.69 mm Width: 75.69 mm Depth: 8.5 mm', '2025-10-07 17:30:28', '2025-10-07 17:34:03', 6, 'walton-phone', '', '', 17099.00, NULL, NULL, 0, 0, 0, 0, 0.00, 0, '[\"68e54e3495916_1759858228_0.webp\"]', '', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `product_discounts`
--

CREATE TABLE `product_discounts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount','buy_x_get_y') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_quantity` int(11) DEFAULT 1,
  `max_quantity` int(11) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `applies_to` enum('all_products','specific_products','categories','brands') DEFAULT 'all_products',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_discount_relations`
--

CREATE TABLE `product_discount_relations` (
  `id` int(11) NOT NULL,
  `discount_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_inventory_logs`
--

CREATE TABLE `product_inventory_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `action_type` enum('add','remove','adjust','sale','return','damaged','expired') NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_changed` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'Order ID, Purchase ID, etc.',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_pricing_history`
--

CREATE TABLE `product_pricing_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `old_selling_price` decimal(10,2) DEFAULT NULL,
  `new_selling_price` decimal(10,2) NOT NULL,
  `old_cost_price` decimal(10,2) DEFAULT NULL,
  `new_cost_price` decimal(10,2) DEFAULT NULL,
  `reason` enum('cost_change','manual_update','promotion','markup_change') NOT NULL,
  `margin_percentage` decimal(5,2) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `effective_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT 'Rating 1-5',
  `title` varchar(200) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `helpful_votes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'User ID if review is from logged-in user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `customer_name`, `customer_email`, `rating`, `title`, `review_text`, `is_approved`, `is_featured`, `helpful_votes`, `created_at`, `updated_at`, `user_id`) VALUES
(4, 51, 'Mike Johnson', 'mike@example.com', 3, 'Average product', 'It works but nothing special. Could be better for the price.', 0, 0, 0, '2025-10-06 17:04:06', '2025-10-06 17:04:06', NULL),
(7, 85, 'Mr', 'mr@gmail.com', 3, 'ok', 'ok', 0, 0, 0, '2025-10-06 17:09:06', '2025-10-06 17:19:22', NULL),
(8, 82, 'Admin', 'mr@gmail.com', 3, 'Good', 'Very good product', 1, 0, 0, '2025-10-06 17:12:24', '2025-10-06 17:17:44', NULL),
(9, 86, 'Apple', 'abc@yahoo.com', 5, 'The Best smart phone', 'This is the best smart phone of apple. i-phone', 1, 0, 0, '2025-10-06 17:30:35', '2025-10-06 17:30:45', NULL),
(10, 68, 'Shakib', 'shakib@gmail.com', 4, 'Best product', 'Very good product', 0, 0, 0, '2025-10-06 17:57:39', '2025-10-06 17:57:39', NULL),
(11, 93, 'Shakib', 'shakib@gmail.com', 5, 'Best Item', 'I give five star as this product is very usefully and best service provided', 1, 0, 0, '2025-10-07 17:12:13', '2025-10-07 17:13:46', NULL),
(12, 83, 'Admin', 'admin@yahoo.com', 4, 'Good', 'Best product', 1, 0, 0, '2025-10-08 03:54:32', '2025-10-08 03:55:03', NULL),
(13, 90, 'Shahin Hossen', 'shahin@gmail.com', 4, 'WOW', 'Excellent Product.', 1, 0, 0, '2025-10-08 04:23:46', '2025-10-08 04:42:19', NULL),
(14, 53, 'shahin Hossen', 'shahin@gmail.com', 3, '3 star', 'user friendly and low budget price', 1, 0, 0, '2025-10-08 04:48:16', '2025-10-08 04:48:46', 23);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) NOT NULL COMMENT 'e.g., Size, Color, Material',
  `variant_value` varchar(100) NOT NULL COMMENT 'e.g., XL, Red, Cotton',
  `sku_suffix` varchar(50) DEFAULT NULL COMMENT 'Additional SKU identifier',
  `price_adjustment` decimal(10,2) DEFAULT 0.00 COMMENT 'Price difference from base product',
  `stock_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `supplier_contact` varchar(100) DEFAULT NULL,
  `status` enum('pending','received','partial','cancelled') DEFAULT 'pending',
  `total_amount` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ordered_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `quantity_received` int(11) DEFAULT 0,
  `cost_price` decimal(10,2) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_alerts`
--

CREATE TABLE `report_alerts` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `condition_type` enum('threshold','change','anomaly','schedule') NOT NULL,
  `condition_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Alert conditions and thresholds' CHECK (json_valid(`condition_config`)),
  `notification_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Email, SMS, webhook settings' CHECK (json_valid(`notification_config`)),
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `is_active` tinyint(1) DEFAULT 1,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `trigger_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_cache`
--

CREATE TABLE `report_cache` (
  `id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `report_key` varchar(100) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_categories`
--

CREATE TABLE `report_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#007bff',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_categories`
--

INSERT INTO `report_categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Sales & Revenue', 'sales-revenue', 'Sales performance, revenue analysis, and financial metrics', 'fas fa-chart-line', '#28a745', 1, 1, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(2, 'Customer Analytics', 'customer-analytics', 'Customer behavior, demographics, and retention analysis', 'fas fa-users', '#007bff', 2, 1, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(3, 'Product Performance', 'product-performance', 'Product sales, inventory, and performance metrics', 'fas fa-box', '#fd7e14', 3, 1, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(4, 'Marketing & Campaigns', 'marketing-campaigns', 'Marketing ROI, campaign performance, and conversion metrics', 'fas fa-bullhorn', '#e83e8c', 4, 1, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(5, 'Operations & Logistics', 'operations-logistics', 'Order fulfillment, shipping, and operational efficiency', 'fas fa-truck', '#6f42c1', 5, 1, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(6, 'Financial Reports', 'financial-reports', 'Financial statements, tax reports, and accounting metrics', 'fas fa-calculator', '#20c997', 6, 1, '2025-09-28 22:27:47', '2025-09-28 22:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `report_dashboards`
--

CREATE TABLE `report_dashboards` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dashboard layout and widget positions' CHECK (json_valid(`layout_config`)),
  `widgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Widget configurations' CHECK (json_valid(`widgets`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Global dashboard filters' CHECK (json_valid(`filters`)),
  `refresh_interval` int(11) DEFAULT 300 COMMENT 'Auto refresh in seconds',
  `access_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`access_roles`)),
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_dashboards`
--

INSERT INTO `report_dashboards` (`id`, `name`, `slug`, `description`, `layout_config`, `widgets`, `filters`, `refresh_interval`, `access_roles`, `is_default`, `is_active`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'E-commerce Dashboard', 'ecommerce-dashboard', 'Comprehensive overview of sales, orders, inventory, and performance metrics', '{\"cols\":12,\"rowHeight\":60,\"margin\":[10,10],\"containerPadding\":[10,10]}', '[{\"id\":\"widget_1\",\"template_id\":1,\"title\":\"Sales Trend\",\"position\":{\"x\":0,\"y\":0},\"size\":{\"width\":8,\"height\":4},\"parameters\":{\"days\":30},\"filters\":[]},{\"id\":\"widget_2\",\"template_id\":8,\"title\":\"Category Performance\",\"position\":{\"x\":8,\"y\":0},\"size\":{\"width\":4,\"height\":4},\"parameters\":{\"days\":30},\"filters\":[]},{\"id\":\"widget_3\",\"template_id\":12,\"title\":\"Order Status\",\"position\":{\"x\":0,\"y\":4},\"size\":{\"width\":6,\"height\":3},\"parameters\":{\"days\":30},\"filters\":[]},{\"id\":\"widget_4\",\"template_id\":6,\"title\":\"Low Stock Alert\",\"position\":{\"x\":6,\"y\":4},\"size\":{\"width\":6,\"height\":3},\"parameters\":{\"threshold\":10},\"filters\":[]}]', NULL, 300, NULL, 1, 1, 0, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `report_executions`
--

CREATE TABLE `report_executions` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `result_data` longtext DEFAULT NULL,
  `result_count` int(11) DEFAULT 0,
  `execution_time` decimal(8,3) DEFAULT NULL COMMENT 'Execution time in seconds',
  `cache_key` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','completed','failed','cached') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_executions`
--

INSERT INTO `report_executions` (`id`, `template_id`, `user_id`, `parameters`, `filters`, `result_data`, `result_count`, `execution_time`, `cache_key`, `expires_at`, `status`, `error_message`, `created_at`) VALUES
(1, 1, 7, '{\"days\":30}', '[]', NULL, 11, 0.001, 'report_1_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-09-29 02:37:08'),
(2, 8, 7, '{\"days\":30}', '[]', NULL, 2, 0.001, 'report_8_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-09-29 02:37:08'),
(3, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-09-29 02:37:08'),
(4, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-09-29 02:37:08'),
(5, 1, NULL, '{\"days\":\"30\"}', '[]', NULL, 11, 0.002, 'report_1_28f1189767c0c6014fab5550daab1520', NULL, 'completed', NULL, '2025-09-29 02:37:16'),
(6, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-09-29 02:41:04'),
(7, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-09-29 02:41:04'),
(8, 8, NULL, '{\"days\":\"30\"}', '[]', NULL, 2, 0.004, 'report_8_28f1189767c0c6014fab5550daab1520', NULL, 'completed', NULL, '2025-09-29 02:41:27'),
(9, 3, NULL, '{\"days\":\"30\"}', '[]', NULL, 5, 0.000, 'report_3_28f1189767c0c6014fab5550daab1520', NULL, 'completed', NULL, '2025-09-29 02:48:49'),
(10, 3, NULL, '{\"days\":\"30\"}', '[]', NULL, 5, 0.002, 'report_3_28f1189767c0c6014fab5550daab1520', NULL, 'completed', NULL, '2025-09-29 08:49:57'),
(11, 1, 7, '{\"days\":30}', '[]', NULL, 12, 0.002, 'report_1_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-03 02:38:04'),
(12, 8, 7, '{\"days\":30}', '[]', NULL, 2, 0.007, 'report_8_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-03 02:38:04'),
(13, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-03 02:38:04'),
(14, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-03 02:38:04'),
(15, 8, NULL, '{\"days\":\"30\"}', '[]', NULL, 2, 0.001, 'report_8_28f1189767c0c6014fab5550daab1520', NULL, 'completed', NULL, '2025-10-03 02:38:11'),
(16, 1, 7, '{\"days\":30}', '[]', NULL, 11, 0.002, 'report_1_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-04 07:03:10'),
(17, 8, 7, '{\"days\":30}', '[]', NULL, 2, 0.006, 'report_8_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-04 07:03:10'),
(18, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-04 07:03:10'),
(19, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-04 07:03:10'),
(20, 1, 7, '{\"days\":30}', '[]', NULL, 11, 0.004, 'report_1_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-04 07:16:53'),
(21, 8, 7, '{\"days\":30}', '[]', NULL, 2, 0.001, 'report_8_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-04 07:16:53'),
(22, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-04 07:16:53'),
(23, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-04 07:16:53'),
(24, 3, NULL, '{\"days\":\"30\"}', '[]', NULL, 4, 0.001, 'report_3_28f1189767c0c6014fab5550daab1520', NULL, 'completed', NULL, '2025-10-05 01:36:30'),
(25, 1, 7, '{\"days\":30}', '[]', NULL, 13, 0.001, 'report_1_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-06 16:04:30'),
(26, 8, 7, '{\"days\":30}', '[]', NULL, 2, 0.001, 'report_8_0d56e96c607c7332eb205dd263b472b4', NULL, 'completed', NULL, '2025-10-06 16:04:30'),
(27, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-06 16:04:30'),
(28, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-06 16:04:30'),
(29, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-06 16:05:03'),
(30, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-06 16:05:03'),
(31, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-06 16:05:16'),
(32, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-06 16:05:16'),
(33, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-06 16:05:17'),
(34, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-06 16:05:17'),
(35, 12, 7, '{\"days\":30}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC\' at line 1', '2025-10-06 16:05:17'),
(36, 6, 7, '{\"threshold\":10}', '[]', NULL, 0, NULL, NULL, NULL, 'failed', 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC\' at line 1', '2025-10-06 16:05:17');

-- --------------------------------------------------------

--
-- Table structure for table `report_exports`
--

CREATE TABLE `report_exports` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `format` enum('pdf','excel','csv','json') NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `record_count` int(11) DEFAULT 0,
  `generation_time` decimal(8,3) DEFAULT NULL,
  `status` enum('generating','completed','failed','expired') DEFAULT 'generating',
  `error_message` text DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_schedules`
--

CREATE TABLE `report_schedules` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `schedule_type` enum('daily','weekly','monthly','quarterly','yearly','custom') NOT NULL,
  `schedule_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Cron expression and schedule details' CHECK (json_valid(`schedule_config`)),
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Email addresses and notification settings' CHECK (json_valid(`recipients`)),
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `export_format` enum('pdf','excel','csv','json') DEFAULT 'pdf',
  `is_active` tinyint(1) DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `run_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `query_template` longtext NOT NULL,
  `chart_type` enum('line','bar','pie','doughnut','area','column','table','metric','gauge') DEFAULT 'table',
  `chart_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`chart_config`)),
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`columns`)),
  `aggregations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aggregations`)),
  `refresh_interval` int(11) DEFAULT 0 COMMENT 'Auto refresh in minutes, 0 = manual',
  `cache_duration` int(11) DEFAULT 300 COMMENT 'Cache duration in seconds',
  `access_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`access_roles`)),
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_templates`
--

INSERT INTO `report_templates` (`id`, `category_id`, `name`, `slug`, `description`, `query_template`, `chart_type`, `chart_config`, `parameters`, `filters`, `columns`, `aggregations`, `refresh_interval`, `cache_duration`, `access_roles`, `is_active`, `is_featured`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Daily Sales Summary', 'daily-sales-summary', 'Daily sales performance with trends and comparisons', 'SELECT DATE(created_at) as sale_date, COUNT(*) as total_orders, SUM(total_amount) as total_sales, AVG(total_amount) as avg_order_value, order_type FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != \"cancelled\" GROUP BY DATE(created_at), order_type ORDER BY sale_date DESC', 'line', '{\"responsive\": true, \"scales\": {\"y\": {\"beginAtZero\": true}}}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{\"status\": {\"type\": \"select\", \"options\": [\"pending\", \"processing\", \"shipped\", \"delivered\"], \"multiple\": true}}', '[{\"key\": \"sale_date\", \"label\": \"Date\", \"type\": \"date\"}, {\"key\": \"total_orders\", \"label\": \"Orders\", \"type\": \"number\"}, {\"key\": \"total_sales\", \"label\": \"Sales\", \"type\": \"currency\"}, {\"key\": \"avg_order_value\", \"label\": \"AOV\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 1, 0, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(2, 1, 'Monthly Revenue Analysis', 'monthly-revenue-analysis', 'Monthly revenue breakdown with year-over-year comparison', 'SELECT YEAR(created_at) as sale_year, MONTH(created_at) as sale_month, MONTHNAME(created_at) as month_name, COUNT(*) as total_orders, SUM(total_amount) as total_sales, SUM(tax_amount) as total_tax FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND status != \"cancelled\" GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY sale_year DESC, sale_month DESC', 'bar', '{\"responsive\": true, \"plugins\": {\"legend\": {\"display\": true}}}', '{}', '{\"year\": {\"type\": \"select\", \"options\": [\"2024\", \"2025\"], \"multiple\": false}}', '[{\"key\": \"month_name\", \"label\": \"Month\", \"type\": \"text\"}, {\"key\": \"total_orders\", \"label\": \"Orders\", \"type\": \"number\"}, {\"key\": \"total_sales\", \"label\": \"Revenue\", \"type\": \"currency\"}, {\"key\": \"total_tax\", \"label\": \"Tax\", \"type\": \"currency\"}]', NULL, 60, 300, NULL, 1, 1, 0, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(3, 2, 'Customer Acquisition Report', 'customer-acquisition-report', 'New vs returning customer analysis', 'SELECT DATE(created_at) as registration_date, COUNT(*) as new_customers FROM users WHERE role = \"customer\" AND created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY DATE(created_at) ORDER BY registration_date DESC', 'area', '{\"responsive\": true, \"fill\": true}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"registration_date\", \"label\": \"Date\", \"type\": \"date\"}, {\"key\": \"new_customers\", \"label\": \"New Customers\", \"type\": \"number\"}]', NULL, 60, 300, NULL, 1, 1, 0, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(4, 3, 'Top Selling Products', 'top-selling-products', 'Best performing products by sales volume and revenue', 'SELECT p.name, p.sku, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as total_revenue, AVG(oi.price) as avg_price FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND o.status != \"cancelled\" GROUP BY p.id ORDER BY total_revenue DESC LIMIT {{limit}}', 'bar', '{\"responsive\": true, \"indexAxis\": \"y\"}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}, \"limit\": {\"type\": \"number\", \"default\": 20, \"label\": \"Limit\"}}', '{\"category\": {\"type\": \"select\", \"source\": \"categories\", \"multiple\": true}}', '[{\"key\": \"name\", \"label\": \"Product\", \"type\": \"text\"}, {\"key\": \"sku\", \"label\": \"SKU\", \"type\": \"text\"}, {\"key\": \"total_sold\", \"label\": \"Qty Sold\", \"type\": \"number\"}, {\"key\": \"total_revenue\", \"label\": \"Revenue\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 1, 0, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(5, 1, 'Sales by Payment Method', 'sales-by-payment-method', 'Revenue breakdown by payment methods (bKash, Cash, Card)', 'SELECT payment_method, COUNT(*) as order_count, SUM(total_amount) as total_revenue, AVG(total_amount) as avg_order_value FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != \'cancelled\' GROUP BY payment_method ORDER BY total_revenue DESC', 'pie', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"payment_method\", \"label\": \"Payment Method\", \"type\": \"text\"}, {\"key\": \"order_count\", \"label\": \"Orders\", \"type\": \"number\"}, {\"key\": \"total_revenue\", \"label\": \"Revenue\", \"type\": \"currency\"}, {\"key\": \"avg_order_value\", \"label\": \"AOV\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 1, 1, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(6, 1, 'Refunds & Returns Analysis', 'refunds-returns-analysis', 'Analysis of refunded and cancelled orders', 'SELECT DATE(created_at) as date, COUNT(*) as refund_count, SUM(total_amount) as refund_amount, payment_method FROM orders WHERE status IN (\'refunded\', \'cancelled\') AND created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC', 'line', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"date\", \"label\": \"Date\", \"type\": \"date\"}, {\"key\": \"refund_count\", \"label\": \"Refunds\", \"type\": \"number\"}, {\"key\": \"refund_amount\", \"label\": \"Amount\", \"type\": \"currency\"}, {\"key\": \"payment_method\", \"label\": \"Payment Method\", \"type\": \"text\"}]', NULL, 30, 300, NULL, 1, 0, 2, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(7, 1, 'Average Order Value Trends', 'aov-trends', 'Average order value trends over time', 'SELECT DATE(created_at) as date, AVG(total_amount) as avg_order_value, COUNT(*) as order_count FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != \'cancelled\' GROUP BY DATE(created_at) ORDER BY date DESC', 'line', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"date\", \"label\": \"Date\", \"type\": \"date\"}, {\"key\": \"avg_order_value\", \"label\": \"AOV\", \"type\": \"currency\"}, {\"key\": \"order_count\", \"label\": \"Orders\", \"type\": \"number\"}]', NULL, 30, 300, NULL, 1, 0, 3, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(8, 1, 'Sales by Region', 'sales-by-region', 'Sales performance by shipping regions/cities', 'SELECT shipping_city as region, COUNT(*) as order_count, SUM(total_amount) as total_sales FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != \'cancelled\' AND shipping_city IS NOT NULL GROUP BY shipping_city ORDER BY total_sales DESC LIMIT 20', 'bar', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"region\", \"label\": \"Region\", \"type\": \"text\"}, {\"key\": \"order_count\", \"label\": \"Orders\", \"type\": \"number\"}, {\"key\": \"total_sales\", \"label\": \"Sales\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 0, 4, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(9, 2, 'Customer Lifetime Value', 'customer-lifetime-value', 'Top customers by total purchase value', 'SELECT CONCAT(u.first_name, \' \', u.last_name) as customer_name, u.email, COUNT(o.id) as total_orders, SUM(o.total_amount) as lifetime_value, AVG(o.total_amount) as avg_order_value, MAX(o.created_at) as last_order_date FROM users u JOIN orders o ON u.id = o.user_id WHERE o.status != \'cancelled\' GROUP BY u.id ORDER BY lifetime_value DESC LIMIT {{limit}}', 'table', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"limit\": {\"type\": \"number\", \"default\": 50, \"label\": \"Top Customers\"}}', '{}', '[{\"key\": \"customer_name\", \"label\": \"Customer\", \"type\": \"text\"}, {\"key\": \"email\", \"label\": \"Email\", \"type\": \"text\"}, {\"key\": \"total_orders\", \"label\": \"Orders\", \"type\": \"number\"}, {\"key\": \"lifetime_value\", \"label\": \"LTV\", \"type\": \"currency\"}, {\"key\": \"avg_order_value\", \"label\": \"AOV\", \"type\": \"currency\"}, {\"key\": \"last_order_date\", \"label\": \"Last Order\", \"type\": \"date\"}]', NULL, 30, 300, NULL, 1, 1, 5, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(10, 2, 'Customer Demographics', 'customer-demographics', 'Customer distribution by location and registration date', 'SELECT city, COUNT(*) as customer_count, DATE_FORMAT(created_at, \'%Y-%m\') as registration_month FROM users WHERE role = \'customer\' AND created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) GROUP BY city, DATE_FORMAT(created_at, \'%Y-%m\') ORDER BY customer_count DESC', 'bar', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"months\": {\"type\": \"number\", \"default\": 12, \"label\": \"Months\"}}', '{}', '[{\"key\": \"city\", \"label\": \"City\", \"type\": \"text\"}, {\"key\": \"customer_count\", \"label\": \"Customers\", \"type\": \"number\"}, {\"key\": \"registration_month\", \"label\": \"Month\", \"type\": \"text\"}]', NULL, 30, 300, NULL, 1, 0, 6, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(11, 2, 'Customer Retention Analysis', 'customer-retention', 'New vs returning customer analysis', 'SELECT DATE_FORMAT(o.created_at, \'%Y-%m\') as month, COUNT(DISTINCT CASE WHEN customer_orders.order_count = 1 THEN o.user_id END) as new_customers, COUNT(DISTINCT CASE WHEN customer_orders.order_count > 1 THEN o.user_id END) as returning_customers FROM orders o JOIN (SELECT user_id, COUNT(*) as order_count FROM orders WHERE status != \'cancelled\' GROUP BY user_id) customer_orders ON o.user_id = customer_orders.user_id WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) AND o.status != \'cancelled\' GROUP BY DATE_FORMAT(o.created_at, \'%Y-%m\') ORDER BY month DESC', 'line', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"months\": {\"type\": \"number\", \"default\": 12, \"label\": \"Months\"}}', '{}', '[{\"key\": \"month\", \"label\": \"Month\", \"type\": \"text\"}, {\"key\": \"new_customers\", \"label\": \"New Customers\", \"type\": \"number\"}, {\"key\": \"returning_customers\", \"label\": \"Returning Customers\", \"type\": \"number\"}]', NULL, 30, 300, NULL, 1, 0, 7, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(12, 3, 'Low Stock Alert', 'low-stock-alert', 'Products with low inventory levels', 'SELECT p.name, p.sku, p.stock_quantity, c.name as category, b.name as brand, p.selling_price FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.stock_quantity <= {{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC', 'table', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"threshold\": {\"type\": \"number\", \"default\": 10, \"label\": \"Stock Threshold\"}}', '{}', '[{\"key\": \"name\", \"label\": \"Product\", \"type\": \"text\"}, {\"key\": \"sku\", \"label\": \"SKU\", \"type\": \"text\"}, {\"key\": \"stock_quantity\", \"label\": \"Stock\", \"type\": \"number\"}, {\"key\": \"category\", \"label\": \"Category\", \"type\": \"text\"}, {\"key\": \"brand\", \"label\": \"Brand\", \"type\": \"text\"}, {\"key\": \"selling_price\", \"label\": \"Price\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 1, 8, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(13, 3, 'Product Performance by Category', 'product-performance-category', 'Sales performance breakdown by product categories', 'SELECT c.name as category, COUNT(DISTINCT p.id) as product_count, COALESCE(SUM(oi.quantity), 0) as total_sold, COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue FROM categories c LEFT JOIN products p ON c.id = p.category_id LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id WHERE (o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) OR o.created_at IS NULL) AND (o.status != \'cancelled\' OR o.status IS NULL) GROUP BY c.id, c.name ORDER BY total_revenue DESC', 'doughnut', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"category\", \"label\": \"Category\", \"type\": \"text\"}, {\"key\": \"product_count\", \"label\": \"Products\", \"type\": \"number\"}, {\"key\": \"total_sold\", \"label\": \"Qty Sold\", \"type\": \"number\"}, {\"key\": \"total_revenue\", \"label\": \"Revenue\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 0, 9, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(14, 3, 'Dead Stock Analysis', 'dead-stock-analysis', 'Products with no sales in specified period', 'SELECT p.name, p.sku, p.stock_quantity, p.cost_price, p.selling_price, (p.stock_quantity * p.cost_price) as inventory_value, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND o.status != \'cancelled\' WHERE p.is_active = 1 AND p.stock_quantity > 0 AND oi.id IS NULL ORDER BY inventory_value DESC', 'table', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 90, \"label\": \"Days\"}}', '{}', '[{\"key\": \"name\", \"label\": \"Product\", \"type\": \"text\"}, {\"key\": \"sku\", \"label\": \"SKU\", \"type\": \"text\"}, {\"key\": \"stock_quantity\", \"label\": \"Stock\", \"type\": \"number\"}, {\"key\": \"inventory_value\", \"label\": \"Inventory Value\", \"type\": \"currency\"}, {\"key\": \"category\", \"label\": \"Category\", \"type\": \"text\"}]', NULL, 30, 300, NULL, 1, 0, 10, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(15, 4, 'Coupon Usage Report', 'coupon-usage-report', 'Coupon and discount code usage analysis', 'SELECT c.code, c.name, c.type, c.value, c.used_count, c.usage_limit, COALESCE(SUM(cu.discount_amount), 0) as total_discount_given FROM coupons c LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id LEFT JOIN orders o ON cu.order_id = o.id WHERE (o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) OR o.created_at IS NULL) GROUP BY c.id ORDER BY total_discount_given DESC', 'table', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"code\", \"label\": \"Code\", \"type\": \"text\"}, {\"key\": \"name\", \"label\": \"Name\", \"type\": \"text\"}, {\"key\": \"type\", \"label\": \"Type\", \"type\": \"text\"}, {\"key\": \"used_count\", \"label\": \"Used\", \"type\": \"number\"}, {\"key\": \"usage_limit\", \"label\": \"Limit\", \"type\": \"number\"}, {\"key\": \"total_discount_given\", \"label\": \"Total Discount\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 0, 11, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(16, 4, 'Conversion Funnel Analysis', 'conversion-funnel', 'Customer journey from registration to purchase', 'SELECT \'Registered Users\' as stage, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) UNION ALL SELECT \'Users with Orders\' as stage, COUNT(DISTINCT user_id) as count FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) UNION ALL SELECT \'Completed Orders\' as stage, COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status = \'delivered\'', 'bar', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"stage\", \"label\": \"Stage\", \"type\": \"text\"}, {\"key\": \"count\", \"label\": \"Count\", \"type\": \"number\"}]', NULL, 30, 300, NULL, 1, 0, 12, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(17, 5, 'Order Fulfillment Status', 'order-fulfillment-status', 'Current status of all orders', 'SELECT status, COUNT(*) as order_count, SUM(total_amount) as total_value, AVG(DATEDIFF(CURDATE(), created_at)) as avg_days_since_order FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY status ORDER BY order_count DESC', 'pie', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"status\", \"label\": \"Status\", \"type\": \"text\"}, {\"key\": \"order_count\", \"label\": \"Orders\", \"type\": \"number\"}, {\"key\": \"total_value\", \"label\": \"Value\", \"type\": \"currency\"}, {\"key\": \"avg_days_since_order\", \"label\": \"Avg Days\", \"type\": \"number\"}]', NULL, 30, 300, NULL, 1, 1, 13, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(18, 5, 'Shipping Performance', 'shipping-performance', 'Delivery performance by shipping method and region', 'SELECT shipping_city, COUNT(*) as total_orders, COUNT(CASE WHEN status = \'delivered\' THEN 1 END) as delivered_orders, AVG(CASE WHEN status = \'delivered\' THEN DATEDIFF(updated_at, created_at) END) as avg_delivery_days FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND shipping_city IS NOT NULL GROUP BY shipping_city ORDER BY total_orders DESC LIMIT 20', 'table', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"shipping_city\", \"label\": \"City\", \"type\": \"text\"}, {\"key\": \"total_orders\", \"label\": \"Total Orders\", \"type\": \"number\"}, {\"key\": \"delivered_orders\", \"label\": \"Delivered\", \"type\": \"number\"}, {\"key\": \"avg_delivery_days\", \"label\": \"Avg Delivery Days\", \"type\": \"number\"}]', NULL, 30, 300, NULL, 1, 0, 14, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(19, 6, 'Tax Collection Report', 'tax-collection-report', 'VAT and tax collection summary', 'SELECT DATE_FORMAT(created_at, \'%Y-%m\') as month, COUNT(*) as order_count, SUM(subtotal) as gross_sales, SUM(tax_amount) as total_tax, SUM(total_amount) as net_sales FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) AND status != \'cancelled\' GROUP BY DATE_FORMAT(created_at, \'%Y-%m\') ORDER BY month DESC', 'bar', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"months\": {\"type\": \"number\", \"default\": 12, \"label\": \"Months\"}}', '{}', '[{\"key\": \"month\", \"label\": \"Month\", \"type\": \"text\"}, {\"key\": \"order_count\", \"label\": \"Orders\", \"type\": \"number\"}, {\"key\": \"gross_sales\", \"label\": \"Gross Sales\", \"type\": \"currency\"}, {\"key\": \"total_tax\", \"label\": \"Tax Collected\", \"type\": \"currency\"}, {\"key\": \"net_sales\", \"label\": \"Net Sales\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 1, 15, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(20, 6, 'Payment Method Analysis', 'payment-method-analysis', 'Revenue and transaction fees by payment method', 'SELECT payment_method, COUNT(*) as transaction_count, SUM(total_amount) as total_revenue, AVG(total_amount) as avg_transaction_value, payment_status FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY payment_method, payment_status ORDER BY total_revenue DESC', 'table', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"days\": {\"type\": \"number\", \"default\": 30, \"label\": \"Days\"}}', '{}', '[{\"key\": \"payment_method\", \"label\": \"Payment Method\", \"type\": \"text\"}, {\"key\": \"transaction_count\", \"label\": \"Transactions\", \"type\": \"number\"}, {\"key\": \"total_revenue\", \"label\": \"Revenue\", \"type\": \"currency\"}, {\"key\": \"avg_transaction_value\", \"label\": \"Avg Value\", \"type\": \"currency\"}, {\"key\": \"payment_status\", \"label\": \"Status\", \"type\": \"text\"}]', NULL, 30, 300, NULL, 1, 0, 16, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47'),
(21, 6, 'Profit & Loss Summary', 'profit-loss-summary', 'Basic P&L statement based on orders and costs', 'SELECT DATE_FORMAT(o.created_at, \'%Y-%m\') as month, SUM(o.total_amount) as total_revenue, SUM(o.tax_amount) as total_tax, SUM(oi.quantity * p.cost_price) as cost_of_goods, (SUM(o.total_amount) - SUM(oi.quantity * p.cost_price)) as gross_profit FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) AND o.status != \'cancelled\' GROUP BY DATE_FORMAT(o.created_at, \'%Y-%m\') ORDER BY month DESC', 'line', '{\"responsive\":true,\"maintainAspectRatio\":false}', '{\"months\": {\"type\": \"number\", \"default\": 12, \"label\": \"Months\"}}', '{}', '[{\"key\": \"month\", \"label\": \"Month\", \"type\": \"text\"}, {\"key\": \"total_revenue\", \"label\": \"Revenue\", \"type\": \"currency\"}, {\"key\": \"cost_of_goods\", \"label\": \"COGS\", \"type\": \"currency\"}, {\"key\": \"gross_profit\", \"label\": \"Gross Profit\", \"type\": \"currency\"}, {\"key\": \"total_tax\", \"label\": \"Tax\", \"type\": \"currency\"}]', NULL, 30, 300, NULL, 1, 0, 17, NULL, '2025-09-28 22:27:47', '2025-09-28 22:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `report_user_preferences`
--

CREATE TABLE `report_user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `dashboard_id` int(11) DEFAULT NULL,
  `preference_type` enum('favorite','bookmark','recent','custom_filter','layout') NOT NULL,
  `preference_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preference_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'store_name', 'My Store', 'Store name', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(2, 'currency', 'BDT', 'Default currency', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(3, 'tax_rate', '0.00', 'Default tax rate percentage', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(4, 'low_stock_threshold', '5', 'Default low stock threshold', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(5, 'bkash_merchant_number', '', 'bKash merchant number', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(6, 'nogod_merchant_id', '', 'Nogod merchant ID', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(7, 'inventory_method', 'FIFO', 'Inventory costing method: FIFO, LIFO, or WEIGHTED_AVERAGE', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(8, 'auto_price_update', '0', 'Automatically update selling prices when cost changes (0=No, 1=Yes)', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(9, 'default_markup_percentage', '20.00', 'Default markup percentage for cost-plus pricing', '2025-05-25 06:24:17', '2025-05-25 06:24:17'),
(10, 'price_update_threshold', '5.00', 'Minimum cost change percentage to trigger price update', '2025-05-25 06:24:17', '2025-05-25 06:24:17');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'DigiEcho', '2025-09-28 20:23:00', '2025-10-04 17:51:31'),
(2, 'site_description', 'Discover amazing products with great deals, quality assurance, and fast delivery. Shop with confidence!', '2025-09-28 20:23:00', '2025-09-28 20:59:45'),
(3, 'contact_email', 'info@digiecho.com', '2025-09-28 20:23:01', '2025-09-28 20:23:01'),
(4, 'contact_phone', '+880 1234567890', '2025-09-28 20:23:01', '2025-09-28 20:23:01'),
(5, 'address', 'Dhaka, Bangladesh', '2025-09-28 20:23:01', '2025-09-28 20:23:01'),
(6, 'logo', 'assets/images/logo_1759092103.jpg', '2025-09-28 20:23:01', '2025-09-28 20:41:43'),
(7, 'notice_enabled', '1', '2025-09-28 20:23:01', '2025-09-28 20:39:56'),
(8, 'notice_text', '', '2025-09-28 20:23:01', '2025-09-29 07:15:48'),
(9, 'notice_type', 'danger', '2025-09-28 20:23:01', '2025-09-29 05:09:35'),
(27, 'theme_mode', 'auto', '2025-09-29 05:17:27', '2025-10-05 17:36:48'),
(28, 'day_start_time', '06:00', '2025-09-29 05:17:27', '2025-10-05 17:36:48'),
(29, 'night_start_time', '18:00', '2025-09-29 05:17:27', '2025-10-05 17:36:48'),
(30, 'default_language', 'en', '2025-09-29 05:17:27', '2025-10-05 17:36:48'),
(31, 'currency_format', 'BDT', '2025-09-29 05:17:27', '2025-10-05 17:36:48'),
(32, 'date_format', 'Y-m-d', '2025-09-29 05:17:27', '2025-10-05 17:36:48'),
(33, 'timezone', 'Asia/Dhaka', '2025-09-29 05:17:27', '2025-10-05 17:36:48');

-- --------------------------------------------------------

--
-- Table structure for table `stock_batches`
--

CREATE TABLE `stock_batches` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `purchase_order_item_id` int(11) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `quantity_available` int(11) NOT NULL,
  `quantity_sold` int(11) DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `received_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `movement_type` enum('IN','OUT','ADJUSTMENT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(12,2) DEFAULT NULL,
  `reference_type` enum('PURCHASE','SALE','ADJUSTMENT','RETURN','TRANSFER') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meta_title` varchar(200) DEFAULT NULL COMMENT 'SEO meta title',
  `meta_description` text DEFAULT NULL COMMENT 'SEO meta description',
  `meta_keywords` text DEFAULT NULL COMMENT 'SEO meta keywords',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Featured sub-category flag'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `slug`, `description`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `meta_keywords`, `is_featured`) VALUES
(21, 5, 'Lenovo', 'lenovo', 'Smarter Technology For All', '1758221610_68cc552a481b3.jpg', 1, 0, '2025-08-29 15:43:37', '2025-09-19 06:18:07', 'Lenovo', 'Lenovo', 'Lenovo', 1),
(23, 14, 'HP', 'hp', 'HP is a global technology company that designs, manufactures, and sells personal computers, printers, monitors, and related accessories. Founded in 1939 by Bill Hewlett and Dave Packard, the company, originally known as Hewlett-Packard, has grown into a major player in the tech industry. HP offers a wide range of products for various users, from', '1758139958_68cb163642e25.png', 1, 0, '2025-08-29 18:13:47', '2025-09-24 05:22:28', 'HP', '', '', 0),
(24, 15, 'i-phone', 'i-phone', 'smart phone', '1758140023_68cb1677bb80d.png', 1, 0, '2025-09-02 17:05:56', '2025-09-24 05:21:25', 'i-phone', '', '', 0),
(25, 14, 'Dell', 'dell', 'Dell Desktop Computer', '1758221575_68cc550756946.png', 1, 0, '2025-09-18 18:51:58', '2025-09-29 03:14:04', 'Dell', 'Dell', 'Dell, Desktop', 1),
(26, 14, 'Walton', 'walton', 'Discover the latest computer price in Bangladesh. Find the best deals and budget-friendly options for your next PC purchase from the top manufacturer in Bangladesh.', '1758252897_68cccf61538cc.png', 1, 0, '2025-09-18 19:22:36', '2025-09-19 03:34:57', 'Walton', 'Walton', 'Walton', 1),
(29, 5, 'Walton', 'walton-1', 'walton Laptop', '1758449282_68cfce8253db9.png', 1, 0, '2025-09-19 04:17:28', '2025-09-21 10:08:02', 'Walton', 'Walton', 'Walton', 1),
(30, 26, 'Fridge', 'fridge', 'Fridge Price in Bangladesh starts from BDT 27,900 to BDT 288,000 depending on brand, capacity, and features. Buy high-quality refrigerator and deep fridge at best price from Star Tech online shop. Browse below and order yours now!', '1758445834_68cfc10a9402f.png', 1, 1, '2025-09-21 09:08:18', '2025-09-21 09:10:34', 'Fridge', 'Fridge', 'Fridge', 1),
(31, 15, 'Xiaomi', 'xiaomi', '', '1758697623_68d398974bc3e.png', 1, 0, '2025-09-24 07:07:03', '2025-09-24 07:07:03', 'Xiaomi', 'smart phone, smart phone products, smart phone items', 'smart phone, smart phone products, smart phone items', 0),
(32, 15, 'Vivo', 'vivo', 'Explore a wide range of vivo products including smartphones, earphones. Shop now at vivo for the latest phone technology and innovative features.', '1758697767_68d39927596ba.png', 1, 0, '2025-09-24 07:09:27', '2025-09-24 07:09:27', 'vivo', 'Explore a wide range of vivo products including smartphones, earphones. Shop now at vivo for the latest phone technology and innovative features.', 'vivo', 0),
(33, 15, 'Nokia', 'nokia', 'Nokia Corporation is a Finnish multinational telecommunications, information technology, and consumer electronics corporation, originally established as a pulp mill in 1865.', '1758697866_68d3998a27b6b.png', 1, 0, '2025-09-24 07:11:06', '2025-09-24 07:11:06', 'Nokia', 'Nokia Corporation is a Finnish multinational telecommunications, information technology, and consumer electronics corporation, originally established as a pulp mill in 1865.', 'smart phone, smart phone products, smart phone items', 0),
(34, 15, 'Symphony', 'symphony', 'Symphony Mobile launched in 2008 as a rebranded Chinese company. At the end of 2012, the company released its first Android-powered mobile phone.[citation needed] In 2014, Symphony launched the Roar A50, one of the first phones in the Android One lineup, which ran near-stock versions of Android.[2]\r\nphone mot', '1758697973_68d399f533a29.png', 1, 0, '2025-09-24 07:12:53', '2025-09-24 07:12:53', 'Symphony', 'Symphony Mobile launched in 2008 as a rebranded Chinese company. At the end of 2012, the company released its first Android-powered mobile phone.[citation needed] In 2014, Symphony launched the Roar A50, one of the first phones in the Android One lineup, which ran near-stock versions of Android.[2]', 'smart phone, smart phone products, smart phone items', 0),
(35, 15, 'Oppo', 'oppo', 'Oppo is a private Chinese consumer electronics manufacturer and technology company headquartered in Shenzhen, Guangdong. Founded in 2004, its major product lines include smartphones, smart devices, audio devices, power banks, and other electronic products.', '1758698050_68d39a424157f.png', 1, 0, '2025-09-24 07:14:10', '2025-09-24 07:14:10', 'Oppo', 'Oppo is a private Chinese consumer electronics manufacturer and technology company headquartered in Shenzhen, Guangdong. Founded in 2004, its major product lines include smartphones, smart devices, audio devices, power banks, and other electronic products.', 'smart phone, smart phone products, smart phone items', 0),
(36, 15, 'Realme', 'realme', 'realme is an emerging mobile phone brand which is committed to offering mobile phones with powerful performance, stylish design and sincere services.', '1758698361_68d39b79899a9.png', 1, 0, '2025-09-24 07:19:21', '2025-09-24 07:19:21', 'Realme', 'realme is an emerging mobile phone brand which is committed to offering mobile phones with powerful performance, stylish design and sincere services.', 'smart phone, smart phone products, smart phone items', 0),
(37, 15, 'HUAWEI', 'huawei', 'Explore the latest technologies in smartphone with HUAWEI, and check out the HUAWEI Mate series, HUAWEI Pura series and HUAWEI nova series.', '1758698531_68d39c23b619a.png', 1, 0, '2025-09-24 07:22:11', '2025-09-24 07:22:11', 'HUAWEI Phones', 'Explore the latest technologies in smartphone with HUAWEI, and check out the HUAWEI Mate series, HUAWEI Pura series and HUAWEI nova series.', 'smart phone, smart phone products, smart phone items', 0),
(38, 15, 'Itel', 'itel', 'Hot Products · CITY 100 · Power 70 · S25 Ultra · A90 · S25. Tk 13,990 + VAT. News. itel launches CITY 100 smartphone with ai features and bold design for gen z ...', '1758699264_68d39f0029e56.png', 1, 0, '2025-09-24 07:34:24', '2025-09-24 07:34:24', 'Itel', 'Hot Products · CITY 100 · Power 70 · S25 Ultra · A90 · S25. Tk 13,990 + VAT. News. itel launches CITY 100 smartphone with ai features and bold design for gen z ...', 'smart phone, smart phone products, smart phone items', 0),
(39, 15, 'Samsung', 'samsung', '', '1758699921_68d3a191cbe17.jpeg', 1, 0, '2025-09-24 07:45:21', '2025-09-24 07:45:21', 'Samsung', '', 'smart phone, smart phone products, smart phone items', 0),
(40, 15, 'Walton Phone', 'walton-phone', 'Walton', '1758699978_68d3a1cae52b0.jpg', 1, 0, '2025-09-24 07:46:18', '2025-09-24 07:46:18', 'Walton Phone', 'Walton Mobile', 'Walton-phone', 0),
(41, 15, 'Tecno', 'tecno', 'Techno Mobile phone', '1758700078_68d3a22e84a6d.png', 0, 0, '2025-09-24 07:47:58', '2025-09-26 17:06:25', 'Techno', 'Smart phone of Techno', 'smart phone, smart phone products, smart phone items', 0),
(42, 23, 'Mouse', 'mouse', 'All type of mouse', '1759115514_68d9f8fac75ec.webp', 1, 0, '2025-09-29 03:11:54', '2025-09-29 03:11:54', 'M', '', 'accessories, accessories products, accessories items', 0),
(43, 23, 'keyboard', 'keyboard', 'All Kind of keyboard', '1759115566_68d9f92e3fc21.webp', 1, 0, '2025-09-29 03:12:46', '2025-09-29 03:12:46', 'k', '', 'accessories, accessories products, accessories items', 0),
(44, 19, 'LED TV', 'led-tv', 'LED TV', '1759115958_68d9fab66069e.jpg', 1, 0, '2025-09-29 03:19:18', '2025-09-29 03:19:18', 'L', '', 'monitor, monitor products, monitor items', 0);

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`, `position`, `description`, `image`, `email`, `phone`, `linkedin`, `twitter`, `facebook`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Md Shakibul Islam Liton', 'Developer', 'Department of WEB. Leading our company with vision and dedication, Ahmed brings over 10 years of experience in e-commerce and business development.', 'uploads/team/team_68d948dad256f.jpg', 'mr@gmail.com', '01946851369', 'https://www.linkedin.com/', 'https://x.com/', 'https://www.facebook.com/', 0, 1, '2025-09-28 13:27:23', '2025-09-28 20:40:26'),
(2, 'Abu Bokkor Siddk', 'Web Designer', 'Abu Bokkor Siddk over sees our technical infrastructure and ensures our platform delivers the best user experience with cutting-edge technology.', 'uploads/team/team_68d8e4324ca63.png', 'ab@gmail.com', '01900765432', 'https://www.linkedin.com/', 'https://x.com/', 'https://www.facebook.com/', 2, 1, '2025-09-28 13:27:23', '2025-09-28 13:30:58'),
(3, 'Abu Shalehin', 'Technical Support', 'Technical manages our day-to-day operations, ensuring smooth order processing, inventory management, and customer satisfaction.', 'uploads/team/team_68d8e47ee877e.png', 'shalehin@gmail.com', '01911765432', 'https://www.linkedin.com/', 'https://x.com/', 'https://www.facebook.com/', 3, 1, '2025-09-28 13:27:23', '2025-09-28 13:32:14'),
(4, 'Nizam Uddin', 'Graphics Desinger', 'Nizam Uddin leads our marketing initiatives and customer outreach programs, helping us connect with customers across Bangladesh.', 'uploads/team/team_68d8e4cdc5207.png', 'nizam@gmail.com', '01998765400', 'https://www.linkedin.com/', 'https://x.com/', 'https://www.facebook.com/', 4, 1, '2025-09-28 13:27:23', '2025-09-28 13:33:33'),
(5, 'Sadia Khan', 'HR', 'HR\" most commonly refers to Human Resources, which is the department or function in a business that manages the workforce, focusing on employees as a key asset to achieve company goals. HR departments handle tasks from recruiting and hiring to training, payroll, and ensuring legal compliance and a positive workplace culture', 'uploads/team/team_68d94872833c5.png', 'sadia@digico.com', '01822558800', 'https://www.linkedin.com/', 'https://x.com/', 'https://www.facebook.com/', 4, 0, '2025-09-28 20:38:42', '2025-09-28 20:46:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier','customer') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `role`, `is_active`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(7, 'Admin', '', 'admin@gmail.com', '01568314966', '$2y$10$kyKkl7SXSsXE1IEzdbapGOS90AgPdzzIGzx/veB/lXOlZTz0gpUmC', 'admin', 1, NULL, NULL, '2025-06-04 04:59:25', '2025-09-29 06:28:37'),
(12, 'Nizam', 'Uddin', 'nizam@gmail.com', '01827272722', '$2y$10$shXucHjpZOcUAZWhnQlX3eT1LLZzCiz7hVMd/BJiCQJg8j9Eul7vW', 'customer', 1, NULL, NULL, '2025-08-21 04:54:16', '2025-09-28 15:27:15'),
(13, 'Mr', 'Shakib', 'mr@gmail.com', '01912332100', '$2y$10$P7uTFr5C8TtUz2IbgJdj2.Gt3PAOj0uze.5A01DCakghF3UmuOUvG', 'customer', 1, NULL, NULL, '2025-08-29 16:12:15', '2025-09-28 14:30:29'),
(14, 'Abu `', 'Bokkor', 'absiddik@gmail.com', '', '$2y$10$f7qjOiI92JDDVNELD5Xj0.WKD5pECT7EXanVP5HGJZWXqbiThLRfC', 'customer', 1, NULL, NULL, '2025-09-05 06:00:34', '2025-09-28 15:11:14'),
(15, 'Mr', 'Shalehin', 'mrshalehin@gmail.com', '01998700000', '$2y$10$bLTR4w0TqRwrH53ZPegvAup2v8ulBRGw/MmTieKSWo9BVG6CT7JTC', 'customer', 1, NULL, NULL, '2025-09-05 06:28:07', '2025-09-28 14:29:45'),
(16, 'a', 'b', 'ab@gmail.com', '', '$2y$10$ReqeBTWTViznikjokV.czOtq5QN8MRNC5n6cSbny8tNYnRU3BK102', 'customer', 1, NULL, NULL, '2025-09-18 15:04:54', '2025-09-19 11:12:58'),
(17, 'Mrs', 'Khadiza', 'khadiza@gmail.com', '', '$2y$10$eOZGC1TWwhS.NBAVz4IU4O6ECnedX3jFEGVS94JJ/x0DxjlqUAz..', 'customer', 1, NULL, NULL, '2025-09-26 18:56:04', '2025-09-28 15:51:35'),
(18, 'Asad', 'khan', 'asad@outlook.com', '', '$2y$10$NtrICEdZeGOHX8Lb6etEEeLykiJf9lJEXKuFRsRd39tgxPx8r61E6', 'customer', 1, NULL, NULL, '2025-09-28 10:31:04', '2025-09-29 02:45:53'),
(19, 'sajjad', 'mia', 'sajjad@gmail.com', '', '$2y$10$.ehFda99H2sXxxDCclEQreW1NhQEX0rjdUeTPFSYUO1Kcvxv8DBcu', 'customer', 1, NULL, NULL, '2025-09-28 11:10:07', '2025-09-28 14:53:44'),
(21, 'rayhan', 'Khan', 'rayhan@gmail.com', NULL, '$2y$10$fGB.spn/xF3.Ov84mTNxK.sYKbcpO3xvIbNZ288xx48do7MYdpl5G', 'customer', 1, NULL, NULL, '2025-09-28 19:14:45', '2025-09-28 19:14:45'),
(22, 's', 's', 'ss@gmail.com', '', '$2y$10$uuHm5zkEeltHcVLm1DlTSuqvssd8NMjvAXAyYQdAAZbqek1alALSm', 'customer', 1, NULL, NULL, '2025-09-28 21:21:38', '2025-10-06 18:13:03'),
(23, 'shahin', 'Hossen', 'shahin@gmail.com', NULL, '$2y$10$slkAuS1s/OEtZs/GTm9a1One6aRFtoYfcwk063HtOSAZ7FHF.3hFi', 'customer', 1, NULL, NULL, '2025-10-08 04:05:02', '2025-10-08 04:05:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `billing_company` varchar(255) DEFAULT NULL,
  `billing_address_line_1` varchar(255) DEFAULT NULL,
  `billing_address_line_2` varchar(255) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_postal_code` varchar(20) DEFAULT NULL,
  `billing_country` varchar(100) DEFAULT 'Bangladesh',
  `billing_phone` varchar(20) DEFAULT NULL,
  `shipping_company` varchar(255) DEFAULT NULL,
  `shipping_address_line_1` varchar(255) DEFAULT NULL,
  `shipping_address_line_2` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_postal_code` varchar(20) DEFAULT NULL,
  `shipping_country` varchar(100) DEFAULT 'Bangladesh',
  `shipping_phone` varchar(20) DEFAULT NULL,
  `same_as_billing` tinyint(1) DEFAULT 1,
  `newsletter_subscription` tinyint(1) DEFAULT 0,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `profile_image`, `date_of_birth`, `gender`, `billing_company`, `billing_address_line_1`, `billing_address_line_2`, `billing_city`, `billing_state`, `billing_postal_code`, `billing_country`, `billing_phone`, `shipping_company`, `shipping_address_line_1`, `shipping_address_line_2`, `shipping_city`, `shipping_state`, `shipping_postal_code`, `shipping_country`, `shipping_phone`, `same_as_billing`, `newsletter_subscription`, `sms_notifications`, `created_at`, `updated_at`) VALUES
(1, 7, 'Admin', 'Bhai', '01568314966', 'profile_7_1759127339.jpg', '1996-01-11', 'male', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'no', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 1, 0, 1, '2025-07-17 13:12:40', '2025-10-05 08:41:23'),
(2, 12, 'Nizam', 'Uddin', '01827272722', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, 1, 0, 1, '2025-08-21 04:54:46', '2025-09-28 15:27:15'),
(3, 13, 'Mr', 'Shakib', '01912332100', 'profile_13_1759076227.png', '2025-08-29', 'male', 'DighoEcho User', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01312345678', 'DighoEcho User', 'Pallabi', 'Rupnagar', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01312345678', 1, 0, 1, '2025-08-29 16:12:32', '2025-10-05 14:34:46'),
(4, 14, 'Abu `', 'Bokkor', '', NULL, '2025-09-05', 'male', NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, 1, 0, 1, '2025-09-05 06:03:08', '2025-09-28 15:11:14'),
(5, 15, 'Mr', 'Shalehin', '01998700000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, 1, 0, 1, '2025-09-05 06:28:48', '2025-09-28 14:29:45'),
(6, 16, 'Mr', 'Shakib', '01539278444', NULL, '2000-07-19', 'male', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 'No', 'Mirpur', 'Dhaka', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01911039525', 1, 1, 1, '2025-09-18 15:05:55', '2025-09-19 11:53:05'),
(7, 17, 'Mrs', 'Khadiza', '', 'profile_17_1759087657.png', '2002-06-26', 'female', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 'DigiEcho Bangladesh', 'Sector 11', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01998765432', 1, 1, 1, '2025-09-26 18:56:15', '2025-09-28 19:27:46'),
(8, 19, 'sajjad', 'mia', '', 'profile_19_1759071259.jpg', '2000-05-17', 'male', NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, 1, 0, 1, '2025-09-28 11:11:49', '2025-09-28 14:54:21'),
(10, 18, 'Asad', 'khan', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, 1, 0, 1, '2025-09-28 15:03:12', '2025-09-29 02:45:53'),
(11, 21, 'Rayhan', 'Khan', '01612345678', 'profile_21_1759086993.jpeg', '2001-10-30', 'male', 'Section 10', 'Senpara', 'parbota', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01612345678', 'Section 10', 'Senpara', 'parbota', 'Dhaka', 'Mirpur', '1216', 'Bangladesh', '01612345678', 1, 0, 1, '2025-09-28 19:16:00', '2025-09-28 19:18:15'),
(12, 22, 's', 's', '', 'profile_22_1759094682.png', '2000-08-10', 'male', 'Section 12', 'Pallabi', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01421346581', 'Section 12', 'Pallabi', 'Mirpur', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01421346581', 1, 0, 1, '2025-09-28 21:22:28', '2025-10-06 18:13:03'),
(13, 23, 'Shahin', 'Hossen', '01412345678', 'profile_23_1759896575.jpg', '1992-05-28', 'male', 'Dighi Customer', 'House-12, Road-05, Block-B, Housing', 'Mirpur-11.5', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01412345678', 'Dighi Customer', 'House-12, Road-05, Block-B, Housing', 'Mirpur-11.5', 'Dhaka', 'Dhaka', '1216', 'Bangladesh', '01412345678', 1, 0, 1, '2025-10-08 04:05:17', '2025-10-08 04:37:28');

-- --------------------------------------------------------

--
-- Structure for view `low_stock_products`
--
DROP TABLE IF EXISTS `low_stock_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `low_stock_products`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`sku` AS `sku`, `p`.`stock_quantity` AS `stock_quantity`, `p`.`min_stock_level` AS `min_stock_level`, `c`.`name` AS `category_name`, `sc`.`name` AS `subcategory_name` FROM ((`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) left join `subcategories` `sc` on(`p`.`subcategory_id` = `sc`.`id`)) WHERE `p`.`stock_quantity` <= `p`.`min_stock_level` AND `p`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `monthly_sales_summary`
--
DROP TABLE IF EXISTS `monthly_sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `monthly_sales_summary`  AS SELECT year(`orders`.`created_at`) AS `sale_year`, month(`orders`.`created_at`) AS `sale_month`, count(0) AS `total_orders`, sum(`orders`.`total_amount`) AS `total_sales`, avg(`orders`.`total_amount`) AS `average_order_value`, `orders`.`order_type` AS `order_type` FROM `orders` WHERE `orders`.`payment_status` = 'paid' GROUP BY year(`orders`.`created_at`), month(`orders`.`created_at`), `orders`.`order_type` ;

-- --------------------------------------------------------

--
-- Structure for view `old_daily_sales_summary`
--
DROP TABLE IF EXISTS `old_daily_sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `old_daily_sales_summary`  AS SELECT cast(`orders`.`created_at` as date) AS `sale_date`, count(0) AS `total_orders`, sum(`orders`.`total_amount`) AS `total_sales`, avg(`orders`.`total_amount`) AS `average_order_value`, `orders`.`order_type` AS `order_type` FROM `orders` WHERE `orders`.`payment_status` = 'paid' GROUP BY cast(`orders`.`created_at` as date), `orders`.`order_type` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `assigned_admin` (`assigned_admin`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_type` (`sender_type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `chat_typing_indicators`
--
ALTER TABLE `chat_typing_indicators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conversation_user` (`conversation_id`,`user_type`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `priority` (`priority`),
  ADD KEY `email` (`email`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `subject` (`subject`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_validity` (`valid_from`,`valid_until`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_coupon_order` (`coupon_id`,`order_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_user_orders` (`user_id`),
  ADD KEY `idx_order_date` (`created_at`),
  ADD KEY `idx_order_type` (`order_type`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_orders_date_type` (`created_at`,`order_type`),
  ADD KEY `idx_orders_payment` (`payment_method`,`payment_status`),
  ADD KEY `idx_orders_created_status` (`created_at`,`status`),
  ADD KEY `idx_orders_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_payment_method` (`payment_method`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_hot_items` (`is_hot_item`),
  ADD KEY `idx_stock` (`stock_quantity`),
  ADD KEY `idx_pricing` (`pricing_method`,`auto_update_price`),
  ADD KEY `idx_products_category` (`category_id`,`is_active`),
  ADD KEY `idx_products_subcategory` (`subcategory_id`,`is_active`),
  ADD KEY `idx_stock_low` (`stock_quantity`,`min_stock_level`),
  ADD KEY `products_ibfk_3` (`brand`),
  ADD KEY `idx_products_status` (`status`,`is_active`),
  ADD KEY `idx_products_stock` (`stock_quantity`,`min_stock_level`),
  ADD KEY `idx_products_featured` (`is_featured`,`status`),
  ADD KEY `idx_products_category_brand` (`category_id`,`brand`,`status`),
  ADD KEY `idx_products_price` (`selling_price`,`discount_price`),
  ADD KEY `idx_products_created` (`created_at`),
  ADD KEY `idx_products_views_sales` (`views`,`sales_count`);

--
-- Indexes for table `product_discounts`
--
ALTER TABLE `product_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_dates` (`is_active`,`start_date`,`end_date`),
  ADD KEY `idx_applies_to` (`applies_to`);

--
-- Indexes for table `product_discount_relations`
--
ALTER TABLE `product_discount_relations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `idx_discount_product` (`discount_id`,`product_id`),
  ADD KEY `idx_discount_category` (`discount_id`,`category_id`),
  ADD KEY `idx_discount_brand` (`discount_id`,`brand_id`);

--
-- Indexes for table `product_inventory_logs`
--
ALTER TABLE `product_inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_logs` (`product_id`,`created_at`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_reference` (`reference_id`);

--
-- Indexes for table `product_pricing_history`
--
ALTER TABLE `product_pricing_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_pricing` (`product_id`,`effective_date`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_reviews` (`product_id`,`is_approved`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_variants` (`product_id`,`is_active`),
  ADD KEY `idx_variant_name` (`variant_name`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `idx_po_number` (`po_number`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `report_alerts`
--
ALTER TABLE `report_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template` (`template_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `report_cache`
--
ALTER TABLE `report_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_report` (`report_type`,`report_key`);

--
-- Indexes for table `report_categories`
--
ALTER TABLE `report_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active_sort` (`is_active`,`sort_order`);

--
-- Indexes for table `report_dashboards`
--
ALTER TABLE `report_dashboards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active_default` (`is_active`,`is_default`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `report_executions`
--
ALTER TABLE `report_executions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_user` (`template_id`,`user_id`),
  ADD KEY `idx_cache_key` (`cache_key`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_user` (`template_id`,`user_id`),
  ADD KEY `idx_schedule` (`schedule_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `report_schedules`
--
ALTER TABLE `report_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template` (`template_id`),
  ADD KEY `idx_next_run` (`next_run_at`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active_featured` (`is_active`,`is_featured`);

--
-- Indexes for table `report_user_preferences`
--
ALTER TABLE `report_user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_type` (`user_id`,`preference_type`),
  ADD KEY `idx_template` (`template_id`),
  ADD KEY `idx_dashboard` (`dashboard_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_item_id` (`purchase_order_item_id`),
  ADD KEY `idx_product_batch` (`product_id`,`is_active`),
  ADD KEY `idx_fifo_order` (`product_id`,`received_date`,`id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_movement` (`product_id`,`created_at`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_users_role_created` (`role`,`created_at`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `chat_typing_indicators`
--
ALTER TABLE `chat_typing_indicators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=262;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `product_discounts`
--
ALTER TABLE `product_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_discount_relations`
--
ALTER TABLE `product_discount_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_inventory_logs`
--
ALTER TABLE `product_inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_pricing_history`
--
ALTER TABLE `product_pricing_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_alerts`
--
ALTER TABLE `report_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_cache`
--
ALTER TABLE `report_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_categories`
--
ALTER TABLE `report_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `report_dashboards`
--
ALTER TABLE `report_dashboards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report_executions`
--
ALTER TABLE `report_executions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `report_exports`
--
ALTER TABLE `report_exports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_schedules`
--
ALTER TABLE `report_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_templates`
--
ALTER TABLE `report_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `report_user_preferences`
--
ALTER TABLE `report_user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `stock_batches`
--
ALTER TABLE `stock_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_typing_indicators`
--
ALTER TABLE `chat_typing_indicators`
  ADD CONSTRAINT `chat_typing_indicators_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  ADD CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `coupon_usage_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`brand`) REFERENCES `brands` (`id`);

--
-- Constraints for table `product_discount_relations`
--
ALTER TABLE `product_discount_relations`
  ADD CONSTRAINT `product_discount_relations_ibfk_1` FOREIGN KEY (`discount_id`) REFERENCES `product_discounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_discount_relations_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_discount_relations_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_discount_relations_ibfk_4` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_inventory_logs`
--
ALTER TABLE `product_inventory_logs`
  ADD CONSTRAINT `product_inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_pricing_history`
--
ALTER TABLE `product_pricing_history`
  ADD CONSTRAINT `product_pricing_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `report_alerts`
--
ALTER TABLE `report_alerts`
  ADD CONSTRAINT `report_alerts_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_executions`
--
ALTER TABLE `report_executions`
  ADD CONSTRAINT `report_executions_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD CONSTRAINT `report_exports_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_exports_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `report_schedules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `report_schedules`
--
ALTER TABLE `report_schedules`
  ADD CONSTRAINT `report_schedules_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD CONSTRAINT `report_templates_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `report_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_user_preferences`
--
ALTER TABLE `report_user_preferences`
  ADD CONSTRAINT `report_user_preferences_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_user_preferences_ibfk_2` FOREIGN KEY (`dashboard_id`) REFERENCES `report_dashboards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD CONSTRAINT `stock_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_batches_ibfk_2` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
