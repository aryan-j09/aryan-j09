-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: May 27, 2025 at 06:05 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_37987606_sms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `approvers`
--

CREATE TABLE `approvers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('employee','admin') NOT NULL,
  `passcode` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approvers`
--

INSERT INTO `approvers` (`id`, `name`, `role`, `passcode`) VALUES
(1, 'aryanj09', 'employee', '1234'),
(2, 'aryan', 'admin', 'aryan'),
(3, 'aj', 'employee', '9999'),
(4, 'Hiren Panchal', 'admin', 'hugopharm');

-- --------------------------------------------------------

--
-- Table structure for table `back_order_list`
--

CREATE TABLE `back_order_list` (
  `id` int(30) NOT NULL,
  `receiving_id` int(30) NOT NULL,
  `po_id` int(30) NOT NULL,
  `bo_code` varchar(50) NOT NULL,
  `supplier_id` int(30) NOT NULL,
  `amount` float NOT NULL,
  `discount_perc` float NOT NULL DEFAULT 0,
  `discount` float NOT NULL DEFAULT 0,
  `tax_perc` float NOT NULL DEFAULT 0,
  `tax` float NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = pending, 1 = partially received, 2 =received',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `back_order_list`
--

INSERT INTO `back_order_list` (`id`, `receiving_id`, `po_id`, `bo_code`, `supplier_id`, `amount`, `discount_perc`, `discount`, `tax_perc`, `tax`, `remarks`, `status`, `date_created`, `date_updated`) VALUES
(5, 16, 15, 'BO-0001', 286, 250, 0, 0, 0, 0, NULL, 2, '2024-12-27 12:54:29', '2024-12-27 12:56:45'),
(6, 22, 20, 'BO-0002', 163, 15000, 0, 0, 0, 0, NULL, 2, '2024-12-31 11:09:49', '2024-12-31 11:10:06');

-- --------------------------------------------------------

--
-- Table structure for table `bo_items`
--

CREATE TABLE `bo_items` (
  `bo_id` int(30) NOT NULL,
  `item_id` int(30) NOT NULL,
  `quantity` int(30) NOT NULL,
  `price` float NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `total` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `billing_address` text NOT NULL,
  `shipping_address` text NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_no` varchar(50) NOT NULL,
  `cperson_acc` varchar(100) DEFAULT NULL,
  `cperson_no_acc` varchar(20) DEFAULT NULL,
  `cperson_pur` varchar(100) DEFAULT NULL,
  `cperson_no_pur` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `gst_number` varchar(50) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `company_name`, `billing_address`, `shipping_address`, `contact_person`, `contact_no`, `cperson_acc`, `cperson_no_acc`, `cperson_pur`, `cperson_no_pur`, `email`, `gst_number`, `date_created`, `date_updated`) VALUES
(21, 'Hugopharm', '8, Jogani Industrial Estate, 541 Senapati Bapat Marg,Dadar (W), Mumbai 400028', '8, Jogani Industrial Estate, 541 Senapati Bapat Marg,Dadar (W), Mumbai 400028', 'Hiren Panchal', '9820537200 ', NULL, NULL, NULL, NULL, 'hirenpl76@gmail.com', '27AACCH1711ZM', '2025-01-06 15:46:53', '2025-01-08 10:42:39'),
(23, 'Hindustan Unilever Limited', '#424, Hebbal Industrial Area, Mysore-29-570016', '#424, Hebbal Industrial Area, Mysore-29-570016', 'Mr. Prashant Bhatt', '9481819217', NULL, NULL, NULL, NULL, 'prashant.b@unilever.com', '29AAACH1004N1ZQ', '2025-01-21 05:08:56', '2025-02-10 01:40:05'),
(24, 'Biogen Extracts Pvt Ltd', 'Plot No. 57, Sompura Industrial Area, Dobaspet, Bangalore Rural District, 562111', 'Plot No. 57, Sompura Industrial Area, Dobaspet, Bangalore Rural District, 562111', 'Mr. Jai Shankar', '9845169701', '', '', '', '', 'jsr@bio-gen.in', '29AABCB4205E1Z5', '2025-01-29 01:07:34', '2025-05-19 01:20:33'),
(25, 'APDM Pharmaceuticals Pvt Ltd', '4th Floor-403, Patron, Opp. Kensvilla Golf Academy, Rajpath Club road, Bodakdev, Ahmedabad, Gujarat-380054.', 'Unit No. 90,91 , Parishram Industrial hub, chacharvadi, vasana, changodar, ahmedabad-382213', 'Mr. Jignesh Limbachiya', '8758172299', NULL, NULL, NULL, NULL, 'procurement@apdmpharma.com', '24AAXCA1671K1Z9', '2025-01-31 06:04:39', '2025-03-10 06:23:52'),
(26, 'Atul Limited', 'Atul 396020, Valsad, Gujarat', 'Atul 396020, Valsad, Gujarat', 'Ms. Vanita Patel', '(02632)230000', NULL, NULL, NULL, NULL, 'vanita_patel@atul.co.in', '24AABCA2390M1ZP', '2025-02-05 03:50:32', '2025-02-05 03:50:32'),
(27, 'Akay Natural Ingredients Pvt. Ltd.', '486/A-N & 486/Q-R (EOU) Ambunadu , Malaidamthuruth, kizhakambalam (via Aluva), Enarkulam, Kerla-683561', '486/A-N & 486/Q-R (EOU) Ambunadu , Malaidamthuruth, kizhakambalam (via Aluva), Enarkulam, Kerla-683561', 'Mr. Aravind P N ', '9539044916', NULL, NULL, NULL, NULL, 'aravind.pn@akay-group.com', '32AAOCS5895A1Z6', '2025-03-08 06:13:35', '2025-03-08 06:13:35'),
(28, 'Octavius Pharma Pvt. Ltd.', '407/A, 4th floor, Primate House, Nr. Judges Bunglows Cross Road, Opp Mother Milk Palace, Bodakdev, Ahmedabad-380015', '407/A, 4th floor, Primate House, Nr. Judges Bunglows Cross Road, Opp Mother Milk Palace, Bodakdev, Ahmedabad-380015', 'Mr. Hardik Makhwana', '+91 82002 73060', NULL, NULL, NULL, NULL, 'purchase@octaviuspharma.com', '24AAACO8145P1Z1', '2025-03-10 05:30:10', '2025-03-10 05:30:10'),
(29, 'Microlabs Limited', 'Plant Code: ML07, 121-124, 4th Phase KIADB, Bommasandra Industrial Estate, Anekal Taluk, Bangalore-560099', 'Plant Code: ML07, 121-124, 4th Phase KIADB, Bommasandra Industrial Estate, Anekal Taluk, Bangalore-560099', 'Mr. Surendra Singh', '080-22370451-57', NULL, NULL, NULL, NULL, 'mograpurchase@microlabs.in', '29AABCM2131N1ZE', '2025-03-26 07:50:14', '2025-03-26 07:50:14'),
(30, 'Divakar Techno Specialities & Chemicals Pvt. Ltd.', 'Sharma Industrial Estate, Walbhat Road, Goregaon (E), Industrial Society Co-Op Society Ltd. Mumbai-400063', 'Sharma Industrial Estate, Walbhat Road, Goregaon (E), Industrial Society Co-Op Society Ltd. Mumbai-400063', 'Ms. Darshana Dhuri', '45045520', NULL, NULL, NULL, NULL, 'darshana.dhuri@divakarchemicals.com', '27AAACD5744P1Z7', '2025-03-31 06:47:18', '2025-03-31 06:47:18'),
(31, 'Exemed Pharmaceuticals', 'Plot No. 133/1 & 133/2, Selvas Road, GIDC, Vapi-396195', 'Plot No. 133/1 & 133/2, Selvas Road, GIDC, Vapi-396195', 'Mr. Anup Mishra', '+91 2606617700', NULL, NULL, NULL, NULL, 'anup.mishra@exemedpharma.com', '24AACFE6957A1ZQ', '2025-03-31 07:18:09', '2025-03-31 07:18:09'),
(32, 'Micro Labs Limited', 'Plant Code: ML21, CTS-73, Saki Estate, Off Chandiwali Road, Kurla West, Mumbai-400072', 'Plant Code: ML21, CTS-73, Saki Estate, Off Chandiwali Road, Kurla West, Mumbai-400072', 'Mr. Surendra Singh', '080-22370451-57', NULL, NULL, NULL, NULL, 'mograpurchase@microlabs.in', '27AABCM2131N1ZI', '2025-04-07 02:15:40', '2025-04-07 02:15:40'),
(33, 'Unilever Industries Pvt. Ltd.', 'Unilever Industries (P) ltd. , Research Centre, 64, Main Road, Whitefield, Banglore, 29-560066', 'Unilever Industries (P) ltd. , Research Centre, 64, Main Road, Whitefield, Banglore, 29-560066', 'Mr. R. Sundaresan ', '+91 80 39830967', NULL, NULL, NULL, NULL, 'Sundaresan.Ramachandra@unilever.com', '29AAACU0701P1ZP', '2025-04-08 02:56:25', '2025-04-08 02:57:36'),
(34, 'Microlabs Limited- Veersandra', 'Plant Code: ML11, Plot no. 7B, 16 & 24, Veersandra Industrial Area, Anekal Taluk, Banglore-560100', 'Plant Code: ML11, Plot no. 7B, 16 & 24, Veersandra Industrial Area, Anekal Taluk, Banglore-560100', 'Mr. Surendra Singh', '080-22370451-57', NULL, NULL, NULL, NULL, 'mograpurchase@microlabs.in', '29AABCM2131N1ZE', '2025-04-09 06:21:45', '2025-04-09 06:21:45'),
(35, 'ITC Limited', 'ITC Lifesciences & Technology Centre,\r\nNo.3, 1st Main, 1st Phase, Penny Industrial Area, Bangalore-560058', 'ITC Lifesciences & Technology Centre,\r\nNo.3, 1st Main, 1st Phase, Penny Industrial Area, Bangalore-560058', 'Mr. Rohith Agarthimooli', '9743065571', NULL, NULL, NULL, NULL, 'rohith.a@itc.in', '29AAACI5950L1Z6', '2025-04-16 05:12:45', '2025-04-16 05:12:45'),
(36, 'Institute of Chemical Technology', 'Institute of Chemical Technology.\r\nNathalal Parikh Marg, Matunga, Mumbai-400019', 'Institute of Chemical Technology.\r\nNathalal Parikh Marg, Matunga, Mumbai-400019', 'Ms. Amin', '9820966358', '', '', '', '', 'pd.amin@ictmumbai.edu.in', '27AAATI4951J1ZG', '2025-04-18 04:53:32', '2025-04-23 00:59:57'),
(37, 'Zen Chemicals Private Limited', 'F-12, Pinncle Business Park, Mahakali Caves, Andheri East, Mumbai-400093', 'D-5, MIDC Phase II, Opp. Pimpleshwar Mandir, Dombivli East-421204.', 'Ms. Anjaly Gupta', '8879023643', NULL, NULL, NULL, NULL, 'anjaly.g@unilabchem.com', '27AAACZ2727J2Z3', '2025-04-22 03:25:15', '2025-04-22 03:25:15'),
(38, 'Accent Microcell Limited', 'Accent Microcell Limited, Plot No. Z-59,60,63,64, Dahej SEZ Limited, P-1, Tal: Vagra, Dist: Bharuch, Dahej-392130', 'Accent Microcell Limited, Plot No. Z-59,60,63,64, Dahej SEZ Limited, P-1, Tal: Vagra, Dist: Bharuch, Dahej-392130', '', '', '', '', 'Zeel Chauhan', '7575805709', 'ap2@accentmicrocell.com', '24AAKCA4497Q2ZV', '2025-04-23 02:57:48', '2025-04-23 02:57:48'),
(39, 'Zydus Lifesciences Limited', 'Survey No. 417, 419 & 420, Sarkhej-Bawla, Highway 8A, Village Moraiya, Changodar, Sanand, Ahmedabad-382210', 'Survey No. 417, 419 & 420, Sarkhej-Bawla, Highway 8A, Village Moraiya, Changodar, Sanand, Ahmedabad-382210', '', '', '', '', 'Jigar Vegada', '7948041040', 'Jigar.Vegada@zyduslife.com', '24AAACC6253G1ZZ', '2025-04-29 01:10:38', '2025-04-29 01:29:25'),
(40, 'Alcedo Pharmachem Pvt Ltd', 'Plot No.6 SY No. 342, ALEAP Industrial Estate, Near Pragathi Nagar, Gajularamram, Hyderabad, Medchal Malkajgiri, Telangana-500090', 'Plot No.6 SY No. 342, ALEAP Industrial Estate, Near Pragathi Nagar, Gajularamram, Hyderabad, Medchal Malkajgiri, Telangana-500090', 'K Subba Reddy', '7730094222', '', '', '', '', 'projects@alcedo.co.in', '36AAOCA6458R1ZO', '2025-05-07 08:00:09', '2025-05-07 08:00:09'),
(42, 'Hindustan Unilever Limited- Whitefield', 'Hindustan Unilever Limited, Research Centre Bangalore,\r\n64, Main Road, Whitefield, Bangalore,29-560066\r\n', 'Hindustan Unilever Limited, Research Centre Bangalore,\r\n64, Main Road, Whitefield, Bangalore,29-560066\r\n', 'Mr. Prakash Nagabhushan ', '', '', '', '', '', 'Prakash.Nagabhushan@unilever.com', '29AAACH1004N1ZQ', '2025-05-12 05:08:22', '2025-05-12 05:08:47'),
(43, 'Zuventus Healthcare Limited', 'SBM Building, Ground floor (Part B) & 1st Floor, Plot No. P2 ITBT Park, Phase2 MIDC , Hinjawadi, Mulsi, Pune-411057', 'SBM Building, Ground floor (Part B) & 1st Floor, Plot No. P2 ITBT Park, Phase2 MIDC , Hinjawadi, Mulsi, Pune-411057', 'Mr. Gajanan Chavan', '2066770000', '', '', '', '', 'Gajanan.Chavan@emcure.com', '27AAACZ1513C1ZT', '2025-05-12 06:06:40', '2025-05-12 06:06:40'),
(44, 'IPCA Laboratories Ltd. ', '48, Kandivli Industrial Estate, Kandivli West, Mumbai-400067', '48, kandivli Industrial Estate, Kandivli West, Mumbai-400067', 'Mr. Pravin Bhosle', '9892310956', '', '', '', '', 'pravin.bhosale@ipca.com', '27AAACI1220M1ZT', '2025-05-22 01:54:37', '2025-05-22 01:54:37');

-- --------------------------------------------------------

--
-- Table structure for table `item_attributes`
--

CREATE TABLE `item_attributes` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `attribute` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_attributes`
--

INSERT INTO `item_attributes` (`id`, `item_id`, `attribute`, `value`) VALUES
(118, 26, 'MAKE', 'OMRON'),
(119, 26, 'MODEL NO', 'E2B-M18LS08-WP-B1 2M'),
(205, 45, 'MAKE', 'SIEMENS'),
(206, 45, 'MODEL NO', '3UA5000-1C'),
(207, 45, 'CURRENT', '1.6-2.5 AMP'),
(208, 46, 'MAKE', 'SIEMENS'),
(209, 46, 'MODEL NO', '3UX14 18'),
(210, 47, 'MAKE', 'SCHMERSAL'),
(211, 47, 'MODEL NO', 'TS 232-11-I-2888'),
(215, 49, 'MAKE', 'SIEMENS'),
(216, 49, 'MODEL NO', '3TF30 10-0ARO'),
(217, 49, 'AUXILIARY CONTACT', '1NO'),
(218, 49, 'CURRENT', '9 AMP'),
(219, 50, 'MAKE', 'SIEMENS'),
(220, 50, 'MODEL NO', '3TF30 10-0APO'),
(221, 50, 'AUXILIARY CONTACT', '1 NO'),
(222, 50, 'CURRENT', '9 AMP'),
(223, 51, 'MAKE', 'SIEMENS'),
(224, 51, 'MODEL NO', '3UA5000-1E'),
(225, 51, 'CURRENT', '2.5-4 AMP '),
(229, 53, 'MAKE', 'SIEMENS'),
(230, 53, 'MODEL NO', '3TF30 10-0AP0 '),
(231, 53, 'VOLTAGE', '240V AC'),
(232, 53, 'DISCOUNT', '58%'),
(233, 39, 'MAKE', 'SIEMENS'),
(234, 39, 'MODEL NO', '3TX4 001-2A'),
(235, 39, 'AUXILIARY CONTACT', '1 NC'),
(236, 39, 'DISCOUNT', '58%'),
(237, 32, 'MAKE', 'SIEMENS'),
(238, 32, 'MODEL NO', '3UA5200-0A'),
(239, 32, 'CURRENT', '10-16AMP'),
(240, 32, 'DISCOUNT', '58%'),
(246, 35, 'MAKE', 'SIEMENS'),
(247, 35, 'MODEL NO', '3VJ9018-0HD11'),
(248, 35, 'DISCOUNT', '57%'),
(249, 17, 'MAKE', 'SIEMENS'),
(250, 17, 'MODEL NO', '3VJ9417-0HD11'),
(251, 17, 'DISCOUNT', '57%'),
(304, 41, 'MAKE', 'SIEMENS'),
(305, 41, 'MODEL NO', '3TF30 10-0AP0 '),
(306, 41, 'CURRENT', '9 AMP'),
(307, 41, 'VOLTAGE', '240V AC'),
(308, 41, 'DISCOUNT', '58%'),
(309, 41, 'AUXILIARY CONTACT', '1NO'),
(310, 37, 'MAKE', 'SIEMENS'),
(311, 37, 'MODEL NO', '3TF30 10-0AP0 '),
(312, 37, 'CURRENT', '9 AMP'),
(313, 37, 'DISCOUNT', '58%'),
(314, 37, 'AUXILIARY CONTACT', '1NO'),
(315, 38, 'MAKE', 'SIEMENS'),
(316, 38, 'MODEL NO', '3TF30 10-0AR0'),
(317, 38, 'CURRENT', '9 AMP'),
(318, 38, 'VOLTAGE', '415V AC'),
(319, 38, 'DISCOUNT', '58%'),
(320, 31, 'MAKE', 'SIEMENS'),
(321, 31, 'MODEL NO', '3TF3200-0AP0'),
(322, 31, 'CURRENT', '16 AMP'),
(323, 31, 'VOLTAGE', '240V AC'),
(324, 31, 'DISCOUNT', '58%'),
(325, 44, 'MAKE', 'SIEMENS'),
(326, 44, 'MODEL NO', '3TF30 10-0AP0'),
(327, 44, 'AUXILIARY CONTACT', '1 NO'),
(328, 44, 'CURRENT', '9 AMP'),
(329, 44, 'VOLTAGE', '240V'),
(330, 20, 'MAKE', 'SIEMENS'),
(331, 20, 'MODEL NO', '3TF53 02-0APO'),
(332, 20, 'CURRENT', '205 AMP'),
(333, 20, 'NO', '2'),
(334, 20, 'NC', '2'),
(335, 20, 'VOLTAGE', '240 V'),
(336, 20, 'SUPPLY ', 'AC'),
(337, 20, 'DISCOUNT', '63%'),
(338, 21, 'MAKE', 'SIEMENS'),
(339, 21, 'MODEL NO', '3TF34 00-0AP0'),
(340, 21, 'CURRENT', '32 AMP'),
(341, 21, 'VOLTAGE', '240 V '),
(342, 21, 'SUPPLY ', 'AC'),
(343, 21, 'DISCOUNT ', '62%'),
(344, 40, 'MAKE', 'SIEMENS'),
(345, 40, 'MODEL NO', '3UA5000-1G'),
(346, 40, 'CURRENT', '4-6.3 AMP'),
(347, 40, 'DISCOUNT', '58%'),
(348, 29, 'MAKE', 'SIEMENS'),
(349, 29, 'MODEL NO', '3UA5000-1E'),
(350, 29, 'DISCOUNT', '58%'),
(355, 18, 'MAKE', 'SIEMENS'),
(356, 18, 'MODEL NO', '3VJ9314-0ED00'),
(357, 18, 'POLE', '4 FOR 3VJ13'),
(358, 18, 'DISCOUNT', '57%'),
(359, 22, 'MAKE', 'SIEMENS'),
(360, 22, 'MODEL NO', '5SV4444-0RC '),
(361, 22, 'CURRENT', '40A'),
(362, 22, 'POLE', '4'),
(363, 22, 'CURRENT', '100MA'),
(364, 22, 'DISCOUNT ', '58%'),
(381, 25, 'MAKE', 'SIEMENS'),
(382, 25, 'MODEL NO', '3RP15 76-1NP30 8K'),
(383, 25, 'SEC', '3 TO 60'),
(389, 24, 'MAKE', 'SIEMENS'),
(390, 24, 'MODEL NO', '3UX14 18'),
(391, 23, 'MAKE', 'SIEMENS'),
(392, 23, 'MODEL NO', '3UA5000-1K'),
(393, 23, 'CURRENT ', '8-12.5 AMP'),
(394, 28, 'MAKE', 'SIEMENS'),
(395, 28, 'MODEL NO', '3TF30 10-0AP0'),
(396, 28, 'CURRENT', '9 AMP'),
(397, 28, 'VOLTAGE', '240V AC'),
(398, 28, 'DISCOUNT', '58%'),
(399, 48, 'MAKE', 'MULTISPAN'),
(400, 48, 'MODEL NO', 'MI-631-24-VA'),
(401, 48, 'VOLTAGE ', '24V DC/AC'),
(408, 55, 'MAKE', 'SIEMENS'),
(409, 55, 'MODEL NO', '3UX14 18'),
(414, 57, 'MAKE', 'SIEMENS'),
(415, 57, 'MODEL NO', '3RP15 76-1NP30 8K'),
(416, 57, 'TIME', '3-60 SEC'),
(417, 57, 'DISCOUNT', '58%'),
(423, 42, 'MAKE', 'RISHABH INSTMENT '),
(424, 42, 'MODEL NO', 'XMO7'),
(425, 42, 'CURRENT', '150/5A'),
(426, 27, 'MAKE', 'SIEMENS'),
(427, 27, 'MODEL NO', '5SL43067RC'),
(428, 27, 'CURRENT', '6A'),
(429, 27, 'POLE', '3'),
(430, 27, 'DISCOUNT', '62%'),
(431, 27, 'MAXIMUM FAULT CURRENT ', '10000A'),
(443, 56, 'MAKE', 'SIEMENS'),
(444, 56, 'MODEL NO', '5SL61027RC '),
(445, 56, 'CURRENT', '2 AMP '),
(446, 56, 'POLE', '1'),
(447, 56, 'MAXIMUM FAULT CURRENT ', '75000A'),
(452, 54, 'MAKE', 'SIEMENS'),
(453, 54, 'MODEL NO', '5SL63327RC '),
(454, 54, 'DISCOUNT', '62%'),
(455, 54, 'CURRENT', '32 AMP '),
(456, 54, 'POLE', '3'),
(457, 54, 'MAXIMUM FAULT CURRENT ', '75000'),
(458, 36, 'MAKE', 'SIEMENS'),
(459, 36, 'MODEL NO', '5SL43327RC'),
(460, 36, 'POLE', '3'),
(461, 36, 'CURRENT', '32A '),
(462, 36, 'MAXIMUM FAULT CURRENT ', '10000'),
(463, 33, 'MAKE', 'SIEMENS'),
(464, 33, 'MODEL NO', '5SL42067RC'),
(465, 33, 'CURRENT', '6AMP '),
(466, 33, 'DISCOUNT', '62%'),
(467, 33, 'MAXIMUM FAULT CURRENT ', '10000'),
(482, 16, 'MAKE', 'SIEMENS'),
(483, 16, 'MODEL NO.', '3VJ1340-3EA42-0AA0'),
(484, 16, 'CURRENT', '400 AMP'),
(485, 16, 'POLE', '4'),
(486, 16, 'MAXIMUM FAULT CURRENT ', '25000A'),
(487, 16, 'FIXED CURRENT', '400A'),
(488, 16, 'DISCOUNT', '60%'),
(489, 19, 'MAKE', 'SIEMENS'),
(490, 19, 'MODEL NO', '3VJ1008-1DB32-0AA0'),
(491, 19, 'IEC FS0', 'ADJUSTABLE '),
(492, 19, 'CURRENT ', '80 AMP'),
(493, 19, 'MAXIMUM FAULT CURRENT ', '18000A'),
(494, 19, 'POLE', '3'),
(495, 19, 'DISCOUNT', '60%'),
(496, 43, 'MAKE', 'SIEMENS'),
(497, 43, 'MODEL NO', '3VJ225-3EA42-0AA0'),
(498, 43, 'CURRENT', '250 AMP'),
(499, 43, 'POLE', '4'),
(500, 43, 'IEC NO', 'IEC FS2 '),
(501, 43, 'MAXIMUM FAULT CURRENT ', '25000 A'),
(502, 34, 'MAKE', 'SIEMENS'),
(503, 34, 'MODEL NO', '3VJ1006-0DA32-0AA0'),
(504, 34, 'CURRENT', '63AMP '),
(505, 34, 'DISCOUNT', '57%'),
(506, 34, 'MAXIMUM FAULT CURRENT ', '10000AMP'),
(507, 30, 'MAKE', 'SIEMENS'),
(508, 30, 'MODEL NO', '5SL43257RC'),
(509, 30, 'CURRENT', '25AMP'),
(510, 30, 'POLE', '3'),
(511, 30, 'DISCOUNT', '62%'),
(512, 30, 'MAXIMUM FAULT CURRENT ', '10000 A'),
(514, 52, 'MAKE', 'SIEMENS'),
(515, 52, 'MODEL NO', '3UA5000-1F '),
(516, 52, 'CURRENT', '3.2-5 AMP'),
(517, 59, 'Make', 'ABB'),
(518, 60, '', ''),
(519, 61, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `item_list`
--

CREATE TABLE `item_list` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `supplier_id` int(30) NOT NULL,
  `cost` float NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_list`
--

INSERT INTO `item_list` (`id`, `name`, `description`, `supplier_id`, `cost`, `status`, `date_created`, `date_updated`) VALUES
(16, 'MCCB - 400 AMP', 'MOLDED CASE CIRCUIT BREAKER', 189, 13866, 1, '2025-01-27 01:27:10', '2025-01-27 01:46:03'),
(17, 'EXTERNAL ACCESSORIES FOR 3VJ13', 'EXTERNAL ACCESSORIES FOR 3VJ13', 189, 1701.51, 1, '2025-01-27 01:33:12', '2025-01-27 01:33:12'),
(18, 'SPREADER LINKS', 'SPREADER LINKS', 189, 668.65, 1, '2025-01-27 01:37:59', '2025-01-27 01:37:59'),
(19, 'MCCB - 80 AMP ', 'MOLDED CASE CIRCUIT BREAKER', 189, 2908.8, 1, '2025-01-27 01:45:27', '2025-01-27 01:45:27'),
(20, 'POWER CONTACTOR - 205 AMP', 'POWER CONTACTOR', 189, 20629, 1, '2025-01-27 01:50:26', '2025-01-27 01:50:26'),
(21, 'POWER CONTACTOR - 240 AMP', 'POWER CONTACTOR - 240 AMP', 189, 2200, 1, '2025-01-27 01:53:54', '2025-01-27 01:53:54'),
(22, 'RCCB ', 'RESIDUAL CURRENT CIRCUIT BREAKER', 189, 2547, 1, '2025-01-27 01:59:42', '2025-01-27 01:59:42'),
(23, 'THERMAL.DELAYED OVERLOAD RELAY', 'THERMAL.DELAYED OVERLOAD RELAY', 189, 1065, 1, '2025-01-27 05:39:53', '2025-01-27 05:39:53'),
(24, 'MOUNTING KIT FOR 3UA50 ', 'MOUNTING KIT FOR 3UA50 ', 189, 260, 1, '2025-01-27 05:41:16', '2025-01-27 05:41:16'),
(25, 'ELECTRONIC TIMER STAR DELTA ', 'ELECTRONIC TIMER STAR DELTA ', 189, 1213, 1, '2025-01-27 05:43:14', '2025-01-27 05:43:14'),
(26, 'PROXIMITY SENSOR', 'PROXIMITY SENSOR', 189, 2700, 1, '2025-01-27 05:44:36', '2025-01-27 05:44:36'),
(27, 'MCB', 'MINIATURE CIRCUIT BREAKER', 189, 802.94, 1, '2025-01-27 05:48:36', '2025-01-27 05:48:36'),
(28, 'POWER CONTACTOR 240V', 'POER CONTACTOR', 189, 720.3, 1, '2025-01-27 05:51:53', '2025-01-27 07:14:08'),
(29, 'THERMAL.DELAYED OVERLOAD RELAY 2.5-4AMP ', 'THERMAL.DELAYED OVERLOAD RELAY', 189, 1138.2, 1, '2025-01-27 05:54:05', '2025-01-27 05:54:05'),
(30, 'MCB 25AMP TO 10KA', 'MINIATURE CIRCUIT BREAKER', 189, 802.94, 1, '2025-01-27 05:56:32', '2025-01-27 05:56:32'),
(31, 'POWER CONTACTOR 16 AMP', 'POWER CONTACTOR ', 189, 858.9, 1, '2025-01-27 05:58:56', '2025-01-27 05:58:56'),
(32, 'BIMETAL OVERLOAD RELAY', 'BIMETAL OVERLOAD RELAY', 189, 1285.2, 1, '2025-01-27 06:00:45', '2025-01-27 06:00:45'),
(33, 'MCB 6AMP TO 10KA ', 'MINIATURE CIRCUIT BREAKER', 189, 478.3, 1, '2025-01-27 06:02:59', '2025-01-27 06:02:59'),
(34, 'MCCB 63AMP TO 10KA ', 'MOLDED CASE CIRCUIT BREAKERS', 189, 1704, 1, '2025-01-27 06:05:53', '2025-01-27 06:05:53'),
(35, 'EXTENDED DOOR ROTARY HANDLE ', 'EXTENDED DOOR ROTARY HANDLE ', 189, 1122.3, 1, '2025-01-27 06:07:08', '2025-01-27 06:07:08'),
(36, 'MCB 32A TO 10KA ', 'MINIATURE CIRCUIT BREAKER', 189, 803, 1, '2025-01-27 06:10:00', '2025-01-27 06:10:00'),
(37, 'POWER CONTACTOR 9AMP', 'POWER CONTACTOR ', 189, 720, 1, '2025-01-27 06:11:51', '2025-01-27 06:11:51'),
(38, 'POWER CONTACTOR 9 AMP', 'POWER CONTACTOR ', 189, 720, 1, '2025-01-27 06:15:36', '2025-01-27 06:15:36'),
(39, 'AUXILIARY CONTACT BLOCKS', 'AUXILIARY CONTACT BLOCKS', 189, 222.6, 1, '2025-01-27 06:17:06', '2025-01-27 06:17:06'),
(40, 'THERMAL.DELAYED OVERLOAD RELAY 4-6.3 AMP', 'THERMAL.DELAYED OVERLOAD RELAY ', 189, 1138, 1, '2025-01-27 06:19:06', '2025-01-27 06:19:06'),
(41, 'POWER CONTACTOR- 9AMP ', 'POWER CONTACTOR', 189, 720, 1, '2025-01-27 06:24:01', '2025-01-27 06:24:01'),
(42, 'CURRENT TRANSFORMER', 'CURRENT TRANSFORMER', 189, 325, 1, '2025-01-27 06:26:50', '2025-01-29 05:07:23'),
(43, 'MCCB 250 AMP TO 25KA', 'MOLDED CASE CIRCUIT BREAKERS', 189, 8100, 1, '2025-01-27 06:29:06', '2025-01-27 06:29:06'),
(44, 'POWER CONTACTOR  ', 'POWER CONTACTOR  ', 189, 675, 1, '2025-01-27 06:31:27', '2025-01-27 06:31:27'),
(45, 'THERMAL.DELAYED OVERLOAD RELAY 1.6-2.5 AMP', 'THERMAL.DELAYED OVERLOAD RELAY', 189, 1055, 1, '2025-01-27 06:33:12', '2025-01-27 06:33:12'),
(46, 'MOUNTING KIT FOR  3UA50', 'MOUNTING KIT FOR 3UA50', 189, 260, 1, '2025-01-27 06:34:16', '2025-01-27 06:34:16'),
(47, 'MICRO SWITCH', 'MICRO SWITCH', 189, 1200, 1, '2025-01-27 06:35:53', '2025-01-27 06:35:53'),
(48, 'ANALOG SIGNAL CONVERTERS', 'ANALOG SIGNAL CONVERTERS', 189, 4050, 1, '2025-01-27 06:38:12', '2025-01-27 06:38:12'),
(49, 'POWER CONTACTOR -9AMP', 'POWER CONTACTOR ', 189, 675, 1, '2025-01-27 06:40:11', '2025-01-27 06:40:11'),
(50, 'POWER CONTACTOR - 9AMP', 'POWER CONTACTOR', 189, 675, 1, '2025-01-27 06:41:41', '2025-01-27 06:41:41'),
(51, 'THERMAL.DELAYED OVERLOAD RELAY 2.5-4 AMP ', 'THERMAL.DELAYED OVERLOAD RELAY', 189, 1055, 1, '2025-01-27 06:42:55', '2025-01-27 06:42:55'),
(52, 'BIMETAL OVERLOAD RELAY 3.2-5 AMP', 'BIMETAL OVERLOAD RELAY ', 189, 1055, 1, '2025-01-27 06:44:07', '2025-01-27 06:44:07'),
(53, 'POWER CONTACTOR -9 AMP', 'POWER CONTACTOR', 189, 720, 1, '2025-01-27 06:52:04', '2025-01-27 06:52:04'),
(54, 'MCB 32 AMP TO 7.5KA', 'MINIATURE CIRCUIT BREAKER', 189, 625, 1, '2025-01-28 03:55:03', '2025-01-28 03:55:03'),
(55, '3UX14 18 MOUNTING KIT FOR 3UA50 ', 'MOUNTING KIT FOR 3UA50 ', 189, 260, 1, '2025-01-28 04:13:05', '2025-01-28 04:13:05'),
(56, 'MCB 2 AMP ', 'MINIATURE CIRCUIT BREAKER ', 189, 209, 1, '2025-01-28 04:15:32', '2025-01-28 04:15:32'),
(57, 'ELECTRONIC TIMER STAR DELTA  3RP15 76-1NP30 8K', 'ELECTRONIC TIMER STAR DELTA ', 189, 1213.5, 1, '2025-01-28 04:19:43', '2025-01-28 04:19:43'),
(59, 'Variable Frequency Drive', 'ACS560-01-12A6-4 + 5.5kW 7.5HP ND', 158, 24800, 1, '2025-02-05 05:24:50', '2025-02-05 05:44:27'),
(60, 'Multispan Timer', 'Model No. : UTR 433 A2N-RG-71', 189, 0, 1, '2025-05-06 01:55:17', '2025-05-06 01:55:17'),
(61, '3 HP Homogenizer', '1. Contact parts: SS316\r\n2. Non contact parts: SS304 \r\n3. 3 HP Hindustan make non flp motor  \r\n4. 2800 rpm with pneumatic Cylinder UP & Down  \r\n5. Non flp control panel \r\n6. Delta make Vfd', 236, 0, 1, '2025-05-15 01:06:01', '2025-05-15 01:06:01');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` enum('new','contacted','qualified','negotiation','converted','lost') DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `source`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(2, 'Godrej Agrovet', 'Dr. Nesara', 'nesara.km@godrejagrovet.com', '8904525386', '', '', 'new', '', '2025-05-05 04:49:43', '2025-05-05 04:49:43'),
(3, 'Torrent Pharmaceuticals', 'Ms. Pratiksha Nayak', 'PratikshaNayak@torrentpharma.com', '9913903685', 'Gujarat', '', 'new', '', '2025-05-16 09:05:03', '2025-05-16 09:05:03'),
(4, 'Doehler India Pvt Ltd', 'Mr. Rohan Tiwari', 'Rohan.Tiwari@doehler.com', '+91 7030960370', 'Pune', '', 'new', '', '2025-05-17 04:50:23', '2025-05-17 04:50:23'),
(5, 'Central Institute of Fisheries Education ', 'Dr. Sudhanshu Raman', 'sudhanshu147@gmail.com', '9930475948', 'Department of Aquaculture(Fish Nutrition & Feed Technology)\r\nCollege of Fisheries, Rani Lakshmi Bai Central Agricultural University.\r\nNH-75, Near Pahuj Dam, Gwalior Road,\r\nJhansi (Uttar Pradesh) - 284003', '', 'new', '', '2025-05-17 05:07:28', '2025-05-17 05:07:28'),
(6, 'BioExtract', 'Mr. Jilson', 'rnd@bioextract.co.in', '9961423522', 'Banglore', '', 'new', '', '2025-05-19 06:13:42', '2025-05-19 06:13:42'),
(7, 'Aculife Healthcare pvt ltd', 'Dr. Tarak Mehta', 'tarakmehta@aculife.co.in', '9986553811', 'Ahmedabad, Gujarat', '', 'new', '', '2025-05-19 06:20:27', '2025-05-19 06:20:27'),
(8, 'Nivagen Pharma India pvt ltd', 'Mr. Siddharth Mate', 'smate@nivagen.com', '9930248118', 'Ahmedabad', '', 'new', '', '2025-05-19 06:36:07', '2025-05-19 06:36:07'),
(9, 'Godrej Industries Ltd', 'Ms. Jagruti Patil', 'jagruti.patil@godrejinds.com', '7021173514', 'Ambernath', '', 'new', '', '2025-05-19 06:44:32', '2025-05-19 06:44:32'),
(10, 'Glenmark Pharmaceuticals', 'Mr. Narayan Mahajan', 'narayan.mahajan@glenmarkpharma.com', '9970068428', 'Nashik', '', 'new', '', '2025-05-19 07:13:13', '2025-05-19 07:13:13'),
(12, 'IPCA Laboratories Ltd. ', 'Mr. Pravin Bhosle', 'pravin.bhosale@ipca.com', '9892310956', 'Mumbai', '', 'new', '', '2025-05-21 09:20:52', '2025-05-21 09:20:52'),
(13, 'Nitika Pharmaceuticals Specialties Pvt. Ltd.', 'Mr. Onkar Sandeep Annam', 'onkar.annam@nitikapharma.com', '7798880147', 'Nagpur', '', 'new', '', '2025-05-21 10:16:25', '2025-05-21 10:16:25'),
(14, 'CORONA Remedies Private Limited', 'Mr. Yagnik Goratela', 'yagnikg@coronaremedies.com', '7940233900', 'Gujarat\r\n', '', 'new', '', '2025-05-21 10:19:32', '2025-05-21 10:19:32'),
(15, 'IIEPL', 'Mr. W.Suresh Paul', 'sureshpaul@iiepl.in', '9952081161', 'Chennai', '', 'new', '', '2025-05-21 11:03:44', '2025-05-21 11:03:44'),
(16, 'Virupaksha Organics Limited', 'Dr. Sameer Sarvesh Katiyar', 'sameer@virupaksha.com', '98725501088', 'Hyderabad', '', 'new', '', '2025-05-21 11:05:14', '2025-05-21 11:05:14'),
(17, 'Medico Remedies Limited', 'Mr. Nilesh Patadia', 'rnd.mrl1995@gmail.com', '', '', '', 'new', '', '2025-05-21 11:08:18', '2025-05-21 11:08:18'),
(18, 'National Healthcare Pvt. Ltd.', 'Mr. Amit Bajaj', 'research@nationalhealthcare.com.np', '+977-9802946412', 'Nepal', '', 'new', '', '2025-05-21 11:21:21', '2025-05-21 11:21:21'),
(19, 'Exemed Pharmaceuticals', 'Mr. Sunil Mirajkar', 'Sunil.mirajkar@examedpharma.com', '', 'Vapi', '', 'new', '', '2025-05-21 11:24:53', '2025-05-21 11:24:53'),
(20, 'FUDGEKEY SOLUTIONS LLP', 'Ms. Aboli Jogi', '', '', 'Bhiwandi, Thane', '', 'new', '', '2025-05-22 05:12:40', '2025-05-22 05:12:40'),
(21, 'Noor Enzymes', 'Mr. Shabbir Solanki.', 'noorenzymes@gmail.com', '', 'Bandipur', '', 'new', '', '2025-05-22 06:27:01', '2025-05-22 06:27:01'),
(22, 'Magnee Pharma Conssultants', 'Dr. Shaliesh Kulkarni', 'magneepharmaconsultants@gmail.com', '', '', '', 'new', '', '2025-05-22 06:31:06', '2025-05-22 06:31:06'),
(23, 'NAIP', 'Dr. Shrikant Swami', 'swami_shrikant1975@yahoo.co.in', '9421610082', 'Roha', '', 'new', '', '2025-05-22 06:42:50', '2025-05-22 06:42:50'),
(24, 'Chemley Agritech Pvt Ltd', 'Mr. Vikas Saboo', 'vikas.saboo@chemleyagritech.com', '8005544028', 'Delhi', '', 'new', '', '2025-05-22 06:52:17', '2025-05-22 06:52:17'),
(25, 'Goldlake Lifesciences pvt ltd', 'Mr. Satish Paropate', 'satish@goldlakelifesciences.com', '', 'Pune', '', 'new', '', '2025-05-23 06:59:32', '2025-05-23 06:59:32'),
(26, 'Brawn Lab Research Centre', 'Mr. Naveen Maurya', 'research@brawnlabs.in', '7860666707', 'Gurugram', '', 'new', '', '2025-05-23 09:03:12', '2025-05-23 09:03:12'),
(27, 'D. Y. Patil College of Pharmacy', 'Mr. Vivek Mallurwar', 'vivek.mallurwar@dypatil.edu', '', '', '', 'new', '', '2025-05-27 09:42:44', '2025-05-27 09:42:44');

-- --------------------------------------------------------

--
-- Table structure for table `lead_activities`
--

CREATE TABLE `lead_activities` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `activity_type` enum('note','email','call','meeting','status_change') NOT NULL,
  `description` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `next_followup` datetime DEFAULT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_activities`
--

INSERT INTO `lead_activities` (`id`, `lead_id`, `activity_type`, `description`, `created_by`, `created_at`, `next_followup`, `time_from`, `time_to`) VALUES
(14, 2, 'email', 'Sent Standard Quotation ', 16, '2025-05-03 21:08:00', NULL, NULL, NULL),
(15, 3, 'email', 'Sent Quotation as per Clients Requirements and URS provided by client', 16, '2025-05-05 14:23:00', NULL, NULL, NULL),
(17, 3, 'meeting', 'Meeting at Torrent Pharma Office', 16, '2025-05-19 18:58:23', NULL, '10:30:00', '12:30:00'),
(18, 4, 'email', 'Sent Quotation for Laboratory Nitrogen Closed Loop Spray Dryer', 16, '2025-05-15 16:45:00', NULL, NULL, NULL),
(19, 5, 'email', 'Sent Quotation for Lab Scale Extruder, Spheronizer, FBD and Spray Dryer', 1, '2025-01-06 21:00:00', NULL, NULL, NULL),
(20, 6, 'email', 'Sent Quotation for 300LTR RMG ', 1, '2025-01-06 20:59:00', NULL, NULL, NULL),
(21, 6, 'email', 'Sent Quotation for Unifluid 1.1', 1, '2025-01-06 21:45:00', NULL, NULL, NULL),
(22, 7, 'email', '1. Sent Quotation for Lab Scale Pelletization & Granulation line\r\n        ( Nano, 1.1, Extruder, Spheronizer )\r\n2. Sent Quotation for Pilot Pelletization System', 1, '2025-01-23 16:48:00', NULL, NULL, NULL),
(23, 8, 'email', 'Sent Quotation for Conemill', 1, '2025-01-29 22:35:00', NULL, NULL, NULL),
(24, 9, 'email', 'Sent Quotation for Basket extruder, Single screw extruder and Nano', 1, '2025-01-28 21:28:00', NULL, NULL, NULL),
(25, 9, 'email', 'Sent Discounted Quote for Basket Extruder & Nano', 1, '2025-03-20 21:30:00', NULL, NULL, NULL),
(26, 10, 'email', 'Sent Quotation for Unifluid Nano', 1, '2025-02-04 21:02:00', NULL, NULL, NULL),
(28, 10, 'email', 'Sent Revised Quotation for Unifluid Nano with Amber colored Glass Bowl', 1, '2025-05-20 21:09:38', NULL, NULL, NULL),
(29, 12, 'email', 'Sent Quotation for Unifluid Nano', 1, '2025-05-22 19:46:18', NULL, NULL, NULL),
(30, 12, 'email', 'Sent Discounted Quotation to Mr. Subhrangshu Sengupta', 1, '2025-05-21 19:24:29', NULL, NULL, NULL),
(31, 13, 'email', 'Sent Quotation for Single Screw Extruder & Spheronizer', 1, '2025-05-21 19:47:58', NULL, NULL, NULL),
(32, 14, 'email', 'Sent quotation for Unifluid Nano', 1, '2025-05-21 19:51:30', NULL, NULL, NULL),
(33, 16, 'email', 'Sent Quotation for g2k lab machines ', 1, '2025-02-27 03:07:00', NULL, NULL, NULL),
(34, 17, 'email', 'Sent Quotation for RMG, FBD, Multimill, Octagonal Blender', 1, '2025-02-28 03:10:00', NULL, NULL, NULL),
(35, 18, 'email', 'Sent Quotation for Unifluid Nano in USD', 1, '2025-05-21 20:53:04', NULL, NULL, NULL),
(36, 18, 'email', 'Sent Discounted Quotation for Unifluid Nano in INR', 1, '2025-03-05 21:05:00', NULL, NULL, NULL),
(37, 19, 'email', 'Sent Quotation for Unifluid Nano', 1, '2025-05-21 21:45:52', NULL, NULL, NULL),
(38, 19, 'email', 'Sent Discounted Quotation for Unifluid Nano', 1, '2025-03-16 03:18:00', NULL, NULL, NULL),
(39, 19, 'email', 'Sent Revised Quotation for Unifluid Nano', 1, '2025-03-18 20:49:00', NULL, NULL, NULL),
(40, 20, 'email', 'Sent Quotation for 48 Tray VTD', 1, '2025-05-22 14:43:52', NULL, NULL, NULL),
(41, 20, 'email', 'Sent Quotation for 3 Tray VTD', 1, '2025-05-22 15:51:02', NULL, NULL, NULL),
(42, 22, 'email', 'Sent Quotation for Unifluid Nano', 1, '2025-03-17 18:49:00', NULL, NULL, NULL),
(43, 23, 'email', 'Sent Quotation for Single Screw Extruder', 1, '2025-03-19 16:31:00', NULL, NULL, NULL),
(44, 24, 'email', 'Sent Quotation and Brochure for Unifluid Nano', 1, '2025-03-22 18:54:00', NULL, NULL, NULL),
(45, 24, 'email', 'Sent Quotation for Basket Extruder', 1, '2025-03-24 15:46:00', NULL, NULL, NULL),
(51, 25, 'email', 'Sent Quotation for Unifluid Nano', 1, '2025-03-27 14:43:00', NULL, NULL, NULL),
(52, 26, 'email', 'Sent Quotation for Unifluid Nano', 1, '2025-03-27 19:38:00', NULL, NULL, NULL),
(53, 27, 'email', 'Sent Quotation for RMG and FBD', 1, '2025-03-27 19:56:00', NULL, NULL, NULL),
(54, 26, 'email', 'Sent Quotation fro unifluid Nano with SS bowl', 1, '2025-03-28 17:55:00', NULL, NULL, NULL),
(55, 26, 'email', 'Sent Discounted Offer for Unifluid Nano with SS bowl', 1, '2025-03-29 18:23:00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lead_documents`
--

CREATE TABLE `lead_documents` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `document_type` varchar(50) DEFAULT NULL,
  `document_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_documents`
--

INSERT INTO `lead_documents` (`id`, `activity_id`, `file_name`, `file_path`, `uploaded_at`, `document_type`, `document_description`) VALUES
(4, 14, 'GODREJ AGROVET_EXTR, SPH, NANO.pdf', 'uploads/lead_documents/doc_1747379329_0.pdf', '2025-05-16 07:08:49', 'quotation', ' Extruder, Spheronizer and Nano'),
(5, 15, 'TORRENT PHARMA_AUTOCOATER_Rev1.pdf', 'uploads/lead_documents/doc_1747387379_0.pdf', '2025-05-16 09:22:59', 'quotation', 'Autocoater'),
(6, 18, 'DOEHLER_NITROGEN SPRAY DRYER.pdf', 'uploads/lead_documents/doc_1747457507_0.pdf', '2025-05-17 04:51:47', 'quotation', 'Nitrogen Closed Loop Spray Dryer'),
(7, 19, 'CIFE_JHASI_EXTR, SPH, SPRAYDRYER, FBD.pdf', 'uploads/lead_documents/doc_1747458552_0.pdf', '2025-05-17 05:09:13', 'quotation', ' Extruder, Spheronizer, Nano & Spray Dryer'),
(8, 20, 'BIOEXTRACTS_300LTR RMG.pdf', 'uploads/lead_documents/doc_1747635475_0.pdf', '2025-05-19 06:17:55', 'quotation', '300LTR RMG'),
(9, 21, 'BIOEXTRACTS_1.1.pdf', 'uploads/lead_documents/doc_1747635537_0.pdf', '2025-05-19 06:18:57', 'quotation', 'Unifluid 1.1'),
(10, 22, 'ACULIFE_ PELLETIZATION,COATING.pdf', 'uploads/lead_documents/doc_1747635862_0.pdf', '2025-05-19 06:24:22', 'quotation', 'Nano, 1.1, Extruder, Spheronizer'),
(11, 22, 'ACULIFE_ PILOT PELLETIZATION SYSTEM.pdf', 'uploads/lead_documents/doc_1747635862_1.pdf', '2025-05-19 06:24:22', 'quotation', 'UNIPELL100'),
(12, 23, 'NIVAGEN_COMILL.pdf', 'uploads/lead_documents/doc_1747636932_0.pdf', '2025-05-19 06:42:12', 'quotation', 'Conemill'),
(13, 24, 'GODREJ_EXTRUDER NANO.pdf', 'uploads/lead_documents/doc_1747637219_0.pdf', '2025-05-19 06:46:59', 'quotation', 'Basket Extruder, Single Screw Extruder & Nano'),
(14, 25, 'GODREJ_EXTRUDER NANO_rev1.pdf', 'uploads/lead_documents/doc_1747637404_0.pdf', '2025-05-19 06:50:04', 'quotation', 'Basket Extruder & Nano'),
(15, 26, 'GLENMARK_UNIFLUID NANO.pdf', 'uploads/lead_documents/doc_1747638859_0.pdf', '2025-05-19 07:14:19', 'quotation', 'Unifluid Nano'),
(16, 28, 'GLENMARK_UNIFLUID NANO_rev1.pdf', 'uploads/lead_documents/doc_1747741178_0.pdf', '2025-05-20 11:39:38', 'quotation', 'Unifluid Nano'),
(17, 29, 'IPCA_NANO.pdf', 'uploads/lead_documents/doc_1747820494_0.pdf', '2025-05-21 09:41:34', 'quotation', 'Unifluid Nano'),
(18, 30, 'IPCA_NANO_rev1.pdf', 'uploads/lead_documents/doc_1747821269_0.pdf', '2025-05-21 09:54:29', 'quotation', 'Unifluid Nano'),
(19, 31, 'NITIKA PHARMA_EXTR, SPH.pdf', 'uploads/lead_documents/doc_1747822678_0.pdf', '2025-05-21 10:17:58', 'quotation', ' Extruder, Spheronizer'),
(20, 32, 'CORONA REMEDIES_NANO.pdf', 'uploads/lead_documents/doc_1747822890_0.pdf', '2025-05-21 10:21:30', 'quotation', 'Unifluid Nano'),
(21, 33, 'VIRUPAKSHA_LAB MACHINES.pdf', 'uploads/lead_documents/doc_1747825621_0.pdf', '2025-05-21 11:07:02', 'quotation', 'g2k Line'),
(22, 34, 'MEDICO REMEDIES.pdf', 'uploads/lead_documents/doc_1747825849_0.pdf', '2025-05-21 11:10:50', 'quotation', 'RMG, FBD, Multimill, Octagonal Blender'),
(23, 36, 'NATIONAL HEALTHCARE NEPAL_INR NANO_rev1.pdf', 'uploads/lead_documents/doc_1747826642_0.pdf', '2025-05-21 11:24:02', 'quotation', 'Unifluid Nano'),
(24, 37, 'EXEMED PHARMACEUTICALS_NANO.pdf', 'uploads/lead_documents/doc_1747829600_0.pdf', '2025-05-21 12:13:20', 'quotation', 'Unifluid Nano'),
(25, 38, 'EXEMED PHARMACEUTICALS_NANO_rev1.pdf', 'uploads/lead_documents/doc_1747829912_0.pdf', '2025-05-21 12:18:32', 'quotation', 'Unifluid Nano'),
(26, 39, 'EXEMED PHARMACEUTICALS_NANO_rev2.pdf', 'uploads/lead_documents/doc_1747829971_0.pdf', '2025-05-21 12:19:31', 'quotation', 'Unifluid Nano'),
(27, 40, 'FUDGEKEY_VTD_48 TRAY_BOURNVITA.pdf', 'uploads/lead_documents/doc_1747890832_0.pdf', '2025-05-22 05:13:52', 'quotation', '48 Tray VTD'),
(28, 41, 'FUDTEKEY_3 TRAY VTD.pdf', 'uploads/lead_documents/doc_1747894862_0.pdf', '2025-05-22 06:21:02', 'quotation', '3 Tray VTD'),
(29, 42, 'MAGNEE PHARMA CONSULTANTS_NANO.pdf', 'uploads/lead_documents/doc_1747895524_0.pdf', '2025-05-22 06:32:04', 'quotation', 'Unifluid Nano'),
(30, 43, 'NAIP_DR. SHRIKANT_EXTRUDER.pdf', 'uploads/lead_documents/doc_1747896235_0.pdf', '2025-05-22 06:43:55', 'quotation', 'Extruder'),
(31, 44, 'CHEMLEY AGRITECH PVT LTD_NANO.pdf', 'uploads/lead_documents/doc_1747896884_0.pdf', '2025-05-22 06:54:44', 'quotation', 'Unifluid Nano'),
(32, 45, 'CHEMLEY AGRITECH PVT LTD_BASKET EXTR.pdf', 'uploads/lead_documents/doc_1747896980_0.pdf', '2025-05-22 06:56:20', 'quotation', 'Basket Extruder'),
(33, 51, 'GOLDLAKE LIFESCIENCES PVT LTD_NANO.pdf', 'uploads/lead_documents/doc_1747983694_0.pdf', '2025-05-23 07:01:34', 'quotation', 'Unifluid Nano'),
(34, 52, 'BRAWN LAB RESEARCH CENTRE_NANO.pdf', 'uploads/lead_documents/doc_1747992328_0.pdf', '2025-05-23 09:25:28', 'quotation', 'Unifluid Nano'),
(35, 53, 'DY PATIL COLLEGE_RMG, FBD.pdf', 'uploads/lead_documents/doc_1748339925_0.pdf', '2025-05-27 09:58:45', 'quotation', 'RMG & Nano'),
(36, 54, 'BRAWN LAB RESEARCH CENTRE_NANO_rev1.pdf', 'uploads/lead_documents/doc_1748340061_0.pdf', '2025-05-27 10:01:01', 'quotation', 'Unifluid Nano'),
(37, 55, 'BRAWN LAB RESEARCH CENTRE_NANO_rev2.pdf', 'uploads/lead_documents/doc_1748340142_0.pdf', '2025-05-27 10:02:22', 'quotation', 'Unifluid Nano_rev2');

-- --------------------------------------------------------

--
-- Table structure for table `machine_list`
--

CREATE TABLE `machine_list` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `item_type` varchar(50) DEFAULT 'machine',
  `description` text NOT NULL,
  `cost` float NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machine_list`
--

INSERT INTO `machine_list` (`id`, `name`, `item_type`, `description`, `cost`, `status`, `date_created`, `date_updated`) VALUES
(12, 'UNIGRANLUATOR LAB', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 6L', 0, 1, '2025-05-07 02:14:00', '2025-05-07 02:15:37'),
(13, 'UNIGRANULATOR 15', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 11L', 0, 1, '2025-05-07 02:17:09', '2025-05-07 02:17:09'),
(14, 'UNIGRANULATOR 25', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 16L', 0, 1, '2025-05-07 02:17:39', '2025-05-07 02:17:39'),
(15, 'UNIGRANULATOR 100', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 80L', 0, 1, '2025-05-07 02:18:16', '2025-05-07 02:18:16'),
(16, 'UNIGRANULATOR 150', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 120L', 0, 1, '2025-05-07 02:19:26', '2025-05-07 02:19:26'),
(17, 'UNIGRANULATOR 250', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 220', 0, 1, '2025-05-07 02:30:46', '2025-05-07 02:30:46'),
(18, 'UNIGRANULATOR 400', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 360L', 0, 1, '2025-05-07 02:31:18', '2025-05-07 02:31:18'),
(19, 'UNIGRANULATOR 600', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 520L', 0, 1, '2025-05-07 02:33:22', '2025-05-07 02:33:22'),
(20, 'UNIGRANULATOR 1000', 'machine', 'Rapid Mixer Granulator\r\nWorking Capacity: 800L', 0, 1, '2025-05-07 02:33:49', '2025-05-07 02:33:49'),
(21, 'UNIFLUID MINI', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 50gm-200gm', 0, 1, '2025-05-07 02:40:29', '2025-05-07 02:40:29'),
(22, 'UNIFLUID 1.1', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 300gm-1Kg', 0, 1, '2025-05-07 02:41:04', '2025-05-07 02:41:04'),
(23, 'UNIFLUID 3.1', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 1Kg-3Kg', 0, 1, '2025-05-07 02:41:38', '2025-05-07 02:41:38'),
(25, 'UNIFLUID 5', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 1Kg-5Kg', 0, 1, '2025-05-07 02:44:42', '2025-05-07 02:44:42'),
(26, 'UNIFLUID 10', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 3Kg-10Kg', 0, 1, '2025-05-07 02:45:06', '2025-05-07 02:45:06'),
(27, 'UNIFLUID 30', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 30Kg\r\n\r\n\r\n', 0, 1, '2025-05-07 02:45:36', '2025-05-07 02:45:36'),
(28, 'UNIFLUID 60', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 60Kg', 0, 1, '2025-05-07 02:46:08', '2025-05-07 02:46:08'),
(29, 'UNIFLUID 120', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 100Kg', 0, 1, '2025-05-07 02:46:28', '2025-05-07 02:46:28'),
(30, 'UNIFLUID 200', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 200Kg', 0, 1, '2025-05-07 02:46:46', '2025-05-07 02:46:46'),
(31, 'UNIFLUID 300', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 300Kg', 0, 1, '2025-05-07 02:47:04', '2025-05-07 02:47:04'),
(32, 'UNIFLUID 500', 'machine', 'Fluid Bed Top Spray Granulator\r\nWorking Capacity: 500Kg', 0, 1, '2025-05-07 02:47:24', '2025-05-07 02:47:24'),
(33, 'UNIEXTRUDE-USSE LAB', 'machine', 'Single Screw Extruder\r\nBatch Capacity (kg/hr): 50gm-500gm', 0, 1, '2025-05-07 02:49:36', '2025-05-07 02:49:36'),
(34, 'UNIEXTRUDER-USSE60', 'machine', 'Single Screw Extruder\r\nBatch Capacity (kg/hr): 2-5', 0, 1, '2025-05-07 02:50:47', '2025-05-07 02:50:47'),
(35, 'UNIEXTRUDER-UTSE60', 'machine', 'Single Screw Extruder\r\nBatch Capacity (kg/hr): 25-60', 0, 1, '2025-05-07 02:51:36', '2025-05-07 02:51:36'),
(36, 'UNIEXTRUDER-UTSE100', 'machine', 'Single Screw Extruder\r\nBatch Capacity (kg/hr): 50-100', 0, 1, '2025-05-07 02:51:56', '2025-05-07 02:51:56'),
(37, 'UNIEXTRUDER-UTSE130', 'machine', 'Single Screw Extruder\r\nBatch Capacity (kg/hr): 100-200', 0, 1, '2025-05-07 02:52:27', '2025-05-07 02:52:27'),
(38, 'UNIEXTRUDE-UBAX LAB', 'machine', 'Basket Extruder \r\nBatch Capacity (kg/hr): 100gm-1Kg', 0, 1, '2025-05-07 02:58:35', '2025-05-07 02:58:35'),
(39, 'UNIEXTRUDER-UBAX 150', 'machine', 'Basket Extruder \r\nBatch Capacity (kg/hr): 1-20', 0, 1, '2025-05-07 02:59:07', '2025-05-07 02:59:07'),
(40, 'UNIEXTRUDER-UBAX300', 'machine', 'Basket Extruder \r\nBatch Capacity (kg/hr): 50-100', 0, 1, '2025-05-07 02:59:49', '2025-05-07 02:59:49'),
(41, 'UNIEXTRUDE-UBAX450', 'machine', 'Basket Extruder \r\nBatch Capacity (kg/hr): 100-200', 0, 1, '2025-05-07 03:00:18', '2025-05-07 03:00:18'),
(42, 'UNIEXTRUDER-UDRE LAB', 'machine', 'Die Roller Extruder\r\nBatch Capacity (Kg/Hr): 300gm-1kg', 0, 1, '2025-05-07 03:03:49', '2025-05-07 03:03:49'),
(43, 'UNIEXTRUDE-UDRE25', 'machine', 'Die Roller Extruder\r\nBatch Capacity (Kg/Hr): 2-25', 0, 1, '2025-05-07 03:06:50', '2025-05-07 03:06:50'),
(44, 'UNIEXTRUDER-UDRE100', 'machine', 'Die Roller Extruder\r\nBatch Capacity (Kg/Hr): 50-90', 0, 1, '2025-05-07 03:07:15', '2025-05-07 03:07:15'),
(45, 'UNISPHERE-USPH100', 'machine', 'Spheronizer\r\nBatch capacity kg/ batch: 150gm', 0, 1, '2025-05-07 03:09:19', '2025-05-07 03:09:19'),
(46, 'UNISPHERE-USPH250', 'machine', 'Spheronizer\r\nBatch capacity kg/ batch: 250gm', 0, 1, '2025-05-07 03:09:43', '2025-05-07 03:09:43'),
(47, 'UNISPHERE-USPH380', 'machine', 'Spheronizer\r\nBatch capacity kg/ batch: 3', 0, 1, '2025-05-07 03:13:54', '2025-05-07 03:13:54'),
(48, 'UNISPHERE-USPH500', 'machine', 'Spheronizer\r\nBatch capacity kg/ batch: 3-5', 0, 1, '2025-05-07 03:15:40', '2025-05-07 03:15:40'),
(49, 'UNISPHERE-USPH700', 'machine', 'Spheronizer\r\nBatch capacity kg/ batch: 7-10', 0, 1, '2025-05-07 03:19:00', '2025-05-07 03:19:00'),
(50, 'UNISPHERE-USPH900', 'machine', 'Spheronizer\r\nBatch capacity kg/ batch: 12-15', 0, 1, '2025-05-07 03:19:31', '2025-05-07 03:19:31'),
(51, 'UNIFLUID NANO', 'machine', 'Rapid Fluid Bed Dryer\r\nCapacity in kgs: 50gm-1kg', 0, 1, '2025-05-07 04:51:25', '2025-05-07 08:04:21'),
(52, 'UNIFLUID-3', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 1-3', 0, 1, '2025-05-07 04:53:20', '2025-05-07 05:12:03'),
(53, 'UNIFLUID-5', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 1-5', 0, 1, '2025-05-07 04:54:43', '2025-05-07 04:54:43'),
(54, 'UNIFLUID-10', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 3-10', 0, 1, '2025-05-07 04:56:20', '2025-05-07 04:56:20'),
(55, 'UNIFLUID-30', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 30', 0, 1, '2025-05-07 04:56:47', '2025-05-07 04:56:47'),
(56, 'UNIFLUID-60', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 60', 0, 1, '2025-05-07 05:15:15', '2025-05-07 05:15:15'),
(57, 'UNIIFLUID-120', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 100', 0, 1, '2025-05-07 05:18:04', '2025-05-07 05:18:04'),
(58, 'UNIFLUID-200', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 200\r\n', 0, 1, '2025-05-07 05:18:27', '2025-05-07 05:18:27'),
(59, 'UNIFLUID-120', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 100', 0, 1, '2025-05-07 05:52:52', '2025-05-07 05:52:52'),
(60, 'UNIFLUID-300', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 300', 0, 1, '2025-05-07 05:53:16', '2025-05-07 05:53:16'),
(61, 'UNIFLUID-500', 'machine', 'Fluid Bed Dryer\r\nCapacity in kgs: 500', 0, 1, '2025-05-07 05:53:35', '2025-05-07 05:53:35'),
(62, 'UNIVAC-3', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 5\r\n', 0, 1, '2025-05-07 05:58:07', '2025-05-07 05:59:02'),
(63, 'UNIVAC 8', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 14\r\n', 0, 1, '2025-05-07 05:58:51', '2025-05-07 05:58:51'),
(64, 'UNIVAC 12', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 42', 0, 1, '2025-05-07 05:59:28', '2025-05-07 05:59:28'),
(65, 'UNIVAC 18', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 63', 0, 1, '2025-05-07 06:00:09', '2025-05-07 06:00:09'),
(66, 'UNIVAC 24', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 84', 0, 1, '2025-05-07 06:00:30', '2025-05-07 06:00:30'),
(67, 'UNIVAC 36', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 126', 0, 1, '2025-05-07 06:00:52', '2025-05-07 06:00:52'),
(68, 'UNIVAC 48', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 168', 0, 1, '2025-05-07 06:01:08', '2025-05-07 06:01:08'),
(69, 'UNIVAC 96', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 336', 0, 1, '2025-05-07 06:03:27', '2025-05-07 06:03:27'),
(70, 'UNIVAC 192', 'machine', 'Vaccum Tray Dryer\r\nCapacity in Kgs: 672', 0, 1, '2025-05-07 06:03:44', '2025-05-07 06:03:44'),
(71, 'UNISPRAY', 'machine', 'Spray Dryer\r\nCapacity: 1liter per hour', 0, 1, '2025-05-07 07:32:11', '2025-05-07 07:32:11'),
(72, 'UNITRAY 4', 'machine', 'Tray Dryer\r\nCapacity in kg: 5', 0, 1, '2025-05-07 07:33:19', '2025-05-07 07:33:19'),
(73, 'UNITRAY 8', 'machine', 'Tray Dryer\r\nCapacity in Kg: 14', 0, 1, '2025-05-07 07:33:49', '2025-05-07 07:33:49'),
(74, 'UNITRAY 12', 'machine', 'Tray Dryer\r\nCapacity in Kg: 42', 0, 1, '2025-05-07 07:34:48', '2025-05-07 07:34:48'),
(75, 'UNITRAY 16', 'machine', 'Tray Dryer\r\nCapacity in Kg: 63', 0, 1, '2025-05-07 07:35:03', '2025-05-07 07:35:03'),
(76, 'UNITRAY 24', 'machine', 'Tray Dryer\r\nCapacity in Kg: 84', 0, 1, '2025-05-07 07:35:22', '2025-05-07 07:35:22'),
(77, 'UNITRAY 48', 'machine', 'Tray Dryer\r\nCapacity in Kg: 168', 0, 1, '2025-05-07 07:35:39', '2025-05-07 07:35:39'),
(78, 'UNITRAY 98', 'machine', 'Tray Dryer\r\nCapacity in Kg: 336', 0, 1, '2025-05-07 07:36:13', '2025-05-07 07:36:13'),
(79, 'UNITRAY 192', 'machine', 'Tray Dryer\r\nCapacity in Kg: 672', 0, 1, '2025-05-07 07:36:32', '2025-05-07 07:36:32'),
(80, 'UNICOATER 600', 'machine', 'Autocoater\r\nCapacity in kg: 10-15', 0, 1, '2025-05-07 07:40:45', '2025-05-07 07:40:45'),
(81, 'UNICOATER 900', 'machine', 'Autocoater\r\nCapacity in kg: 50-75', 0, 1, '2025-05-07 07:41:12', '2025-05-07 07:41:12'),
(82, 'UNICOATER 1200', 'machine', 'Autocoater\r\nCapacity in kg: 120-150', 0, 1, '2025-05-07 07:41:31', '2025-05-07 07:41:31'),
(83, 'UNICOATER 1500', 'machine', 'Autocoater\r\nCapacity in kg: 300-350', 0, 1, '2025-05-07 07:41:55', '2025-05-07 07:41:55'),
(84, 'UNIBLENDER-OB LAB', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 0.7-21 ', 0, 1, '2025-05-08 06:22:27', '2025-05-08 06:47:45'),
(85, 'UNIBLENDER-OB100', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 70', 0, 1, '2025-05-08 06:29:25', '2025-05-08 06:29:25'),
(86, 'UNIBLENDER-OB300', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 210', 0, 1, '2025-05-08 06:43:00', '2025-05-08 06:48:03'),
(87, 'UNIBLENDER-OB750', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 525', 0, 1, '2025-05-08 06:43:59', '2025-05-08 06:48:23'),
(88, 'UNIBLENDER-OB1500', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 1050', 0, 1, '2025-05-08 06:44:30', '2025-05-08 06:47:56'),
(89, 'UNIBLENDER-OB3000', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 2100\r\n', 0, 1, '2025-05-08 06:46:01', '2025-05-08 06:48:10'),
(90, 'UNIBLENDER-OB5000', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 3500', 0, 1, '2025-05-08 06:46:26', '2025-05-08 06:48:17'),
(91, 'UNIBLENDER-OB8000', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 5600', 0, 1, '2025-05-08 06:46:59', '2025-05-08 06:48:32'),
(92, 'UNIBLENDER-OB16000', 'machine', 'Octagonal Blender\r\nWorking Capacity (lit): 11200', 0, 1, '2025-05-08 06:47:31', '2025-05-08 06:47:31'),
(93, 'UNIBLEND-VB LAB', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 0.7-21 ', 0, 1, '2025-05-08 06:51:06', '2025-05-08 06:51:06'),
(94, 'UNIBLEND-VB100', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 70 ', 0, 1, '2025-05-08 06:51:41', '2025-05-08 06:51:41'),
(95, 'UNIBLEND-VB300', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 210', 0, 1, '2025-05-08 06:52:35', '2025-05-08 06:52:35'),
(96, 'UNIBLEND-VB500', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 350\r\n', 0, 1, '2025-05-08 06:52:59', '2025-05-08 06:52:59'),
(97, 'UNIBLEND-VB1000', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 700\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n', 0, 1, '2025-05-08 06:53:43', '2025-05-08 06:53:43'),
(98, 'UNIBLEND-VB2000', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 1400', 0, 1, '2025-05-08 06:58:07', '2025-05-08 06:58:07'),
(99, 'UNIBLEND-VB5000', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 3500', 0, 1, '2025-05-08 06:59:46', '2025-05-08 06:59:46'),
(100, 'UNIBLEND-VB10000', 'machine', '\'V\' Blender\r\nWorking Capacity (lit): 7000', 0, 1, '2025-05-08 07:00:09', '2025-05-08 07:00:09'),
(101, 'UNIBLEND-DC75', 'machine', 'Double Cone Blender\r\nWorking Capacity (lit): 53', 0, 1, '2025-05-08 07:07:02', '2025-05-08 07:07:02'),
(102, 'UNIBLEND-DC150', 'machine', 'Double Cone Blender\r\nWorking Capacity (lit): 105\r\n', 0, 1, '2025-05-13 07:25:03', '2025-05-13 07:28:20'),
(103, 'UNIBLEND-DC300', 'machine', 'Double Cone Blender\r\nWorking Capacity (lit): 210', 0, 1, '2025-05-13 07:28:06', '2025-05-13 07:28:06'),
(104, 'UNIBLEND-DC500', 'machine', 'Double Cone Blender\r\nWorking Capacity (lit): 350', 0, 1, '2025-05-13 07:30:05', '2025-05-13 07:30:05'),
(105, 'UNIBLEND-DC1000', 'machine', 'Double Cone Blender\r\nWorking Capacity (lit): 700\r\n', 0, 1, '2025-05-13 07:30:27', '2025-05-13 07:30:27'),
(106, 'UNIBLEND-DC2000', 'machine', 'Double Cone Blender\r\nWorking Capacity (lit): 1400', 0, 1, '2025-05-13 07:30:51', '2025-05-13 07:30:51'),
(107, 'UNIBLEND-DC4000', 'machine', 'Double Cone Blender\r\nWorking Capacity (lit): 2800', 0, 1, '2025-05-13 07:32:25', '2025-05-13 07:32:25');

-- --------------------------------------------------------

--
-- Table structure for table `po_items`
--

CREATE TABLE `po_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `discount` decimal(5,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `po_items`
--

INSERT INTO `po_items` (`id`, `po_id`, `item_id`, `amount`, `quantity`, `discount`, `total_amount`) VALUES
(63, 18, 60, '0.00', 15, '0.00', '0.00'),
(64, 19, 61, '190000.00', 1, '0.00', '190000.00');

-- --------------------------------------------------------

--
-- Table structure for table `po_timeline`
--

CREATE TABLE `po_timeline` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `step_name` varchar(100) NOT NULL,
  `step_date` datetime NOT NULL,
  `remarks` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `po_timeline`
--

INSERT INTO `po_timeline` (`id`, `po_id`, `step_name`, `step_date`, `remarks`, `file_path`, `created_by`, `created_at`) VALUES
(2, 33, 'proforma_sent', '2025-05-21 14:48:00', 'Payment Against Inspection', NULL, 1, '2025-05-22 09:22:40'),
(3, 14, 'other', '2025-05-21 03:54:00', 'MR VENKAT VISITED SITE AND COMPLETED INSTALLATION', NULL, 1, '2025-05-22 09:24:35'),
(5, 38, 'proforma_sent', '2025-05-22 15:43:00', '', NULL, 1, '2025-05-22 10:14:03');

-- --------------------------------------------------------

--
-- Table structure for table `po_timeline_files`
--

CREATE TABLE `po_timeline_files` (
  `id` int(11) NOT NULL,
  `timeline_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `po_timeline_files`
--

INSERT INTO `po_timeline_files` (`id`, `timeline_id`, `file_path`, `description`) VALUES
(1, 2, 'uploads/timeline_files/1747905720_519.pdf', ''),
(2, 3, 'uploads/timeline_files/1747905840_829.pdf', ''),
(3, 5, 'uploads/timeline_files/1747908840_199.pdf', 'Proforma Invoice');

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoice_items`
--

CREATE TABLE `proforma_invoice_items` (
  `id` int(11) NOT NULL,
  `proforma_invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proforma_invoice_items`
--

INSERT INTO `proforma_invoice_items` (`id`, `proforma_invoice_id`, `description`, `hsn_code`, `amount`) VALUES
(323, 68, 'LED Lamp for Unifluid 1.1', NULL, '850.00'),
(324, 68, 'Quick release Clamp for Unifluid 1.1 Bag (2Nos)', NULL, '1500.00'),
(325, 68, 'Freight ', NULL, '550.00'),
(326, 74, 'LED Lamp for Unifluid 1.1', NULL, '850.00'),
(327, 74, 'Freight', NULL, '550.00'),
(328, 71, 'Air discharge bag made from polyester material for Unifluid Nano (2Nos.)', NULL, '5500.00'),
(329, 71, 'Air discharge bag made from epitropic polyester material for Unifluid Nano (2Nos.)', NULL, '11000.00'),
(330, 71, 'Freight', NULL, '500.00'),
(334, 73, 'Polyster Bag for Fluid Bed Dryer (1 Nos.)', NULL, '3750.00'),
(347, 75, '48 Tray Vacuum Tray Dryer complete with Chamber Door', NULL, '5051000.00'),
(363, 76, 'USSE-60 Extruder Screw in SS316 (2Nos.)', NULL, '350000.00'),
(364, 76, 'USSE-60 Extruder Screw Shaft in SS316 (2Nos.)', NULL, '60000.00'),
(365, 76, 'USSE-60 0.8mm Extruder Mesh', NULL, '100000.00'),
(376, 77, 'Air Discharge Bag for 30kg FBD', NULL, '4500.00'),
(377, 77, 'Air Discharge Bag for 60kg FBD', NULL, '7500.00'),
(444, 67, 'UNIFLUID-60 Fluid Bed Dryer (Upgradeable to Processor in Future) including the Following:\r\ni.	Extra Product Container\r\nii.	Steam Pipe Header\r\niii.	Exhaust Air Scrubber\r\niv.	Flameproof Motor for Exhaust Blower\r\nv.	WIP Nozzle System', NULL, '6732000.00'),
(445, 67, 'Ducts & Bends', NULL, '496350.00'),
(446, 67, 'Installation and Commissioning', NULL, '125000.00'),
(447, 67, 'Validation Documents Comprising of DQ, IQ, OQ', NULL, '55000.00'),
(448, 67, 'Power and Signal Cables', NULL, '250000.00'),
(453, 79, 'Bin Blender', NULL, '710100.00'),
(454, 79, 'S.S Bowl (4 Nos.)', NULL, '55700.00'),
(455, 79, 'HMI', NULL, '139250.00'),
(456, 79, 'SS316 Baffle for octagonal Blender (8Nos.)', NULL, '111400.00'),
(457, 79, '21 CFR Software', NULL, '83550.00'),
(459, 64, 'O Ring set for spring Nozzle @2Nos.', NULL, '21000.00'),
(460, 64, 'Pt-100 Sensor with Temperature transmitter', NULL, '10500.00'),
(461, 64, 'SS 304 Duct Between Scrubber and Blower @1.5mtrs', NULL, '33750.00'),
(462, 64, 'PLC Digital Input Card-16 Channel', NULL, '25000.00'),
(470, 82, 'Hot Water Tank for VTD 2Nos.', NULL, '726000.00'),
(471, 82, 'Vacuum Pump for VTD 2Nos.', NULL, '924000.00'),
(472, 82, 'High Efficiency Motor for VTD 2Nos.', NULL, '99000.00'),
(473, 82, 'Vacuum Pipeline for VTD 2Nos.', NULL, '231000.00'),
(474, 82, 'Automation for VTD 2Nos.', NULL, '2640000.00'),
(477, 72, 'SHAKING FILTER BAG SATIN (UNIFLUID-10)', NULL, '27558.00'),
(478, 72, 'SHAKING FILTER BAG NYLON (UNIFLUID-10)', NULL, '31503.00'),
(516, 85, '2mm Round Hole Multimill Mesh', '84799040', '4000.00'),
(517, 85, '3mm Round Hole Multimill Mesh', '84799040', '3750.00'),
(518, 85, '4mm Round Hole Multimill Mesh', '84799040', '3500.00'),
(519, 85, '2/5 Peristaltic Pump Silicone Tube (3M)', '84799040', '3000.00'),
(520, 86, 'Variable Frequency Drive and speed Controller for Hot Melt Extruder', '84799040', '30000.00'),
(530, 87, 'Service Charges for Repair of Hot Melt Extruder', '998717', '25000.00'),
(531, 80, 'Unifluid Nano- Rapid Fluid Bed Dryer', '84798970', '595000.00'),
(532, 80, 'Safety Features for Unifluid Nano', '84798970', '46750.00'),
(533, 80, 'Installation And Commissioning', '998732', '25500.00'),
(534, 80, 'Extra Air Discharge Bag made from Cotton (3Nos.)', '84799040', '3187.00'),
(539, 84, 'UNIEXTRUDE- USSE60 Single Screw Extruder', '84798970', '658750.00'),
(540, 84, '1.5mm Cone Mesh for Extruder', '84799040', '72250.00'),
(541, 84, 'Product Feed Tray Above Screw Hopper', '84799040', '29750.00'),
(542, 84, 'Installation and Commissioning', '998732', '25500.00'),
(545, 88, 'Trial on Spray Dryer', '998346', '25000.00'),
(552, 89, '35# SS316 Silicone Moulded Mesh for Sifter', '84799040', '9500.00'),
(553, 89, '45# SS316 Silicone Moulded Mesh for Sifter', '84799040', '9500.00'),
(560, 93, 'Air Discharge Bag made from Antistatic Polyester Material for UNIFLUID1300', '84799040', '120000.00'),
(561, 93, 'Food Grade silicone tube for peristaltic Pump suitable for UNILFUID1300 (25Meter)', '84799040', '20187.00'),
(562, 93, 'Food Grade silicone tube for connection of 25liter tank to peristaltic Pump (5Meter)', '84799040', '7125.00'),
(563, 93, 'Product Transfer Tube- Flexible and antistatic spiral tube for Vacuum transfer (10meter)', '84799040', '145000.00'),
(564, 93, 'Nozzle Gasket and O-ring set (10 Nos.)', '84799040', '100000.00'),
(565, 93, 'Teflon Sleeve for Spray Chamber (2Nos.)', '84799040', '11000.00'),
(566, 93, 'PTS Bag (1Nos.)', '84799040', '47500.00'),
(567, 93, 'Quick Release Gasket 6mm (2Nos.)', '84799040', '6650.00'),
(577, 95, 'Safety Accessories For Unifluid Nano', '84799040', '55000.00'),
(578, 95, 'New Wiring for Entire Control Panel', '84799040', '20000.00'),
(581, 96, 'Air Discharge Bag made from Cotton for UNIFLUID NANO (4Nos. @ Rs.2000/- Each)', '84799040', '8000.00'),
(584, 78, 'Unifluid Nano-Fluid Bed Dryer', '84798970', '615000.00'),
(593, 90, '3HP Homogenizer with Flameproof Motor', '84798970', '382500.00'),
(599, 97, 'Antistatic Polyester Air Discharge Bag for UNIFLUID60 (2Nos.)', '84799040', '37000.00'),
(601, 91, 'UNIFLUID NANO-Rapid Fluid Bed Dryer\r\nCapacity: 200gm-1Kg', '84798970', '600000.00'),
(602, 99, 'UNIFLUID NANO \r\n(Rapid Fluid Bed Dryer Capacity in kgs: 50gm-1kg)', '84798970', '660488.00');

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoice_list`
--

CREATE TABLE `proforma_invoice_list` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `po_code` varchar(50) NOT NULL,
  `packing_forwarding` float NOT NULL DEFAULT 0 COMMENT 'Payment %',
  `freight` double DEFAULT 0,
  `packing_forwarding_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` float NOT NULL DEFAULT 0 COMMENT 'Payment %',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cgst` float NOT NULL DEFAULT 0 COMMENT 'Payment %',
  `cgst_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sgst` float NOT NULL DEFAULT 0 COMMENT 'Payment %',
  `sgst_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = pending, 1 = approved, 2 = rejected',
  `po_date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_approved` tinyint(1) DEFAULT 0,
  `admin_approved` tinyint(1) DEFAULT 0,
  `employee_approved_by` varchar(255) DEFAULT NULL,
  `admin_approved_by` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sub_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `advance_payment` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Payment %',
  `advance_payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `inspection_payment` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Payment %',
  `inspection_payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `installation_payment` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Payment %',
  `installation_payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `company` varchar(50) NOT NULL,
  `credit_payment_days` int(11) DEFAULT 0,
  `credit_payment_amount` decimal(10,2) DEFAULT 0.00,
  `freight_note` varchar(255) DEFAULT NULL,
  `authorized_signatory` varchar(255) DEFAULT NULL,
  `inspection_payment_type` varchar(20) DEFAULT 'inspection',
  `abg_required` tinyint(1) DEFAULT 0,
  `pbg_required` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proforma_invoice_list`
--

INSERT INTO `proforma_invoice_list` (`id`, `client_id`, `po_code`, `packing_forwarding`, `freight`, `packing_forwarding_amount`, `tax`, `tax_amount`, `cgst`, `cgst_amount`, `sgst`, `sgst_amount`, `status`, `po_date_created`, `date_updated`, `employee_approved`, `admin_approved`, `employee_approved_by`, `admin_approved_by`, `total_amount`, `sub_total`, `advance_payment`, `advance_payment_amount`, `inspection_payment`, `inspection_payment_amount`, `installation_payment`, `installation_payment_amount`, `remaining_amount`, `company`, `credit_payment_days`, `credit_payment_amount`, `freight_note`, `authorized_signatory`, `inspection_payment_type`, `abg_required`, `pbg_required`) VALUES
(64, 23, 'PO16294959', 2.2, 0.16, '1985.50', 18, '16602.42', 0, '0.00', 0, '0.00', 0, '2025-01-18 00:00:00', '2025-04-09 00:43:09', 0, 0, '', '', '108838.08', '90250.00', '100.00', '108838.08', '0.00', '18588.08', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(67, 24, 'BG/DP/24-25/48', 2.5, 0, '191458.75', 18, '1412965.57', 0, '0.00', 0, '0.00', 0, '2024-05-27 00:00:00', '2025-04-07 05:10:47', 0, 0, NULL, NULL, '9262774.32', '7658350.00', '40.00', '3063340.00', '50.00', '5433599.32', '10.00', '765835.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(68, 25, 'APDM/24-25/446', 0, 0, '0.00', 18, '522.00', 0, '0.00', 0, '0.00', 0, '2025-01-30 00:00:00', '2025-03-17 01:52:10', 0, 0, NULL, NULL, '3422.00', '2900.00', '100.00', '3422.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(71, 26, 'COR/ENL/1224002678', 0, 0, '0.00', 18, '3060.00', 0, '0.00', 0, '0.00', 0, '2025-02-04 00:00:00', '2025-03-17 01:52:25', 0, 0, NULL, NULL, '20060.00', '17000.00', '100.00', '20060.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(72, 27, '4500084744', 0, 0, '0.00', 18, '10630.98', 0, '0.00', 0, '0.00', 0, '2025-03-08 00:00:00', '2025-04-10 05:27:36', 0, 0, NULL, NULL, '69691.98', '59061.00', '100.00', '69691.98', '0.00', '10630.98', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(73, 28, 'PO/00458/24-25', 0, 0, '0.00', 18, '675.00', 0, '0.00', 0, '0.00', 0, '2025-03-04 00:00:00', '2025-03-17 01:52:40', 0, 0, NULL, NULL, '4425.00', '3750.00', '100.00', '4425.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(74, 25, 'APDM/24-25/493', 0, 0, '0.00', 18, '252.00', 0, '0.00', 0, '0.00', 0, '2025-03-07 00:00:00', '2025-03-17 01:52:17', 0, 0, NULL, NULL, '1652.00', '1400.00', '100.00', '1652.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(75, 29, '3020091065', 0, 0, '0.00', 18, '909180.00', 0, '0.00', 0, '0.00', 0, '2025-03-14 00:00:00', '2025-03-27 07:32:48', 0, 0, NULL, NULL, '5960180.00', '5051000.00', '30.00', '1515300.00', '60.00', '3939780.00', '10.00', '505100.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(76, 27, '4500085298', 0, 0, '0.00', 18, '91800.00', 0, '0.00', 0, '0.00', 0, '2025-03-19 00:00:00', '2025-04-02 06:04:55', 0, 0, NULL, NULL, '601800.00', '510000.00', '100.00', '601800.00', '0.00', '91800.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(77, 30, '5298', 0, 300, '0.00', 0, '0.00', 9, '1107.00', 9, '1107.00', 0, '2025-03-19 00:00:00', '2025-04-03 06:19:38', 0, 0, NULL, NULL, '14514.00', '12000.00', '100.00', '14514.00', '0.00', '2514.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(78, 31, 'EMV/2425/POS/CGM/00228', 1.5, 0, '9225.00', 18, '112360.50', 0, '0.00', 0, '0.00', 0, '2025-03-24 00:00:00', '2025-05-16 07:42:12', 0, 0, NULL, NULL, '736585.50', '615000.00', '40.00', '246000.00', '60.00', '490585.50', '0.00', '0.00', '0.00', 'Hugopharm', 45, '0.00', 'Inclusive', 'JUIE SINGH', 'inspection', 0, 0),
(79, 32, '3020091160', 0, 0, '0.00', 0, '0.00', 9, '99000.00', 9, '99000.00', 0, '2025-03-19 00:00:00', '2025-04-07 02:28:18', 0, 0, NULL, NULL, '1298000.00', '1100000.00', '30.00', '330000.00', '60.00', '858000.00', '10.00', '110000.00', '0.00', 'Hugopharm', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(80, 33, 'PO16525438', 0, 0, '35000.00', 18, '126978.66', 0, '0.00', 0, '0.00', 0, '2025-04-07 00:00:00', '2025-04-22 00:04:07', 0, 0, NULL, NULL, '832415.66', '670437.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 40, '832415.66', '', 'JUIE SINGH', 'inspection', 0, 0),
(82, 34, '3020090640', 0, 0, '0.00', 18, '831600.00', 0, '0.00', 0, '0.00', 0, '2025-02-28 00:00:00', '2025-04-09 06:22:33', 0, 0, NULL, NULL, '5451600.00', '4620000.00', '30.00', '1386000.00', '60.00', '3603600.00', '10.00', '462000.00', '0.00', 'S.B. Panchal', 0, '0.00', NULL, NULL, 'inspection', 0, 0),
(84, 33, 'PO16534526', 0, 0, '0.00', 18, '141525.00', 0, '0.00', 0, '0.00', 0, '2025-04-09 00:00:00', '2025-04-22 00:23:20', 0, 0, NULL, NULL, '927775.00', '786250.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'S.B. Panchal', 40, '927775.00', '', 'Juie Singh', 'inspection', 0, 0),
(85, 35, '5100016709', 0, 1500, '0.00', 18, '2835.00', 0, '0.00', 0, '0.00', 0, '2025-03-08 00:00:00', '2025-04-18 02:49:08', 0, 0, NULL, NULL, '18585.00', '14250.00', '0.00', '0.00', '100.00', '18585.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', '', '', 'delivery', 0, 0),
(86, 36, 'Quotation No. SBP/0404/2025-2', 0, 0, '0.00', 0, '0.00', 9, '2700.00', 9, '2700.00', 0, '2025-04-04 00:00:00', '2025-04-18 05:06:45', 0, 0, NULL, NULL, '35400.00', '30000.00', '0.00', '0.00', '100.00', '35400.00', '0.00', '0.00', '0.00', 'S.B. Panchal', 0, '0.00', '', 'JUIE SINGH', 'delivery', 0, 0),
(87, 36, 'Quotation No. SBP/0404/2025-1', 0, 0, '0.00', 0, '0.00', 9, '2250.00', 9, '2250.00', 0, '2025-04-04 00:00:00', '2025-04-21 05:22:08', 0, 0, NULL, NULL, '29500.00', '25000.00', '0.00', '0.00', '100.00', '29500.00', '0.00', '0.00', '0.00', 'S.B. Panchal', 0, '0.00', '', 'JUIE SINGH', 'delivery', 0, 0),
(88, 37, '042/25-26', 0, 0, '0.00', 0, '0.00', 9, '2250.00', 9, '2250.00', 0, '2025-04-21 00:00:00', '2025-04-22 04:39:53', 0, 0, NULL, NULL, '29500.00', '25000.00', '100.00', '29500.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', '', 'JUIE SINGH', 'inspection', 0, 0),
(89, 38, 'PO20252600077', 0, 0, '3000.00', 0, '0.00', 0, '0.00', 0, '0.00', 0, '2025-04-22 00:00:00', '2025-04-24 05:34:49', 0, 0, NULL, NULL, '22000.00', '19000.00', '100.00', '22000.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', 'At Actual in Clients Scope', 'JUIE SINGH', 'inspection', 0, 0),
(90, 39, '5100000200', 0, 0, '0.00', 18, '68850.00', 0, '0.00', 0, '0.00', 0, '2025-04-24 00:00:00', '2025-05-19 08:02:31', 0, 0, NULL, NULL, '451350.00', '382500.00', '20.00', '76500.00', '70.00', '336600.00', '10.00', '38250.00', '0.00', 'Hugopharm', 0, '0.00', 'Included', 'JUIE SINGH', 'inspection', 0, 0),
(91, 40, 'ALB/PO/25-26/005', 0, 0, '0.00', 18, '108000.00', 0, '0.00', 0, '0.00', 0, '2025-05-02 00:00:00', '2025-05-21 01:34:17', 0, 0, NULL, NULL, '708000.00', '600000.00', '40.00', '240000.00', '50.00', '408000.00', '10.00', '60000.00', '0.00', 'Hugopharm', 0, '0.00', 'At Actual in Clients Scope', 'JUIE SINGH', 'inspection', 0, 0),
(93, 23, 'PO16517874', 0, 12500, '9149.00', 18, '86239.98', 0, '0.00', 0, '0.00', 0, '2025-04-03 00:00:00', '2025-05-08 03:19:59', 0, 0, NULL, NULL, '565350.98', '457462.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 40, '565350.98', '', '', 'inspection', 0, 0),
(95, 42, 'PO16619232', 0, 0, '1000.00', 18, '13680.00', 0, '0.00', 0, '0.00', 0, '2025-05-12 00:00:00', '2025-05-12 05:09:01', 0, 0, NULL, NULL, '89680.00', '75000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'S.B. Panchal', 30, '89680.00', 'At Actual in Clients Scope', 'JUIE SINGH', 'inspection', 0, 0),
(96, 43, '6500128386', 0, 0, '0.00', 0, '0.00', 9, '720.00', 9, '720.00', 0, '2025-05-09 00:00:00', '2025-05-12 06:10:38', 0, 0, NULL, NULL, '9440.00', '8000.00', '100.00', '9440.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'S.B. Panchal', 0, '0.00', 'Included ; P&F: Included', 'JUIE SINGH', 'inspection', 0, 0),
(97, 24, 'BGDP/25-26/61', 0, 0, '0.00', 18, '6660.00', 0, '0.00', 0, '0.00', 0, '2025-05-15 00:00:00', '2025-05-20 00:58:40', 0, 0, NULL, NULL, '43660.00', '37000.00', '100.00', '43660.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', 'At Actual in Clients Scope ;', 'JUIE SINGH', 'inspection', 0, 0),
(99, 44, 'CR2500016-Mumbai', 0, 0, '0.00', 0, '0.00', 9, '59443.92', 9, '59443.92', 0, '2025-05-16 00:00:00', '2025-05-22 05:13:26', 0, 0, NULL, NULL, '779375.84', '660488.00', '20.00', '132097.60', '80.00', '647278.24', '0.00', '0.00', '0.00', 'Hugopharm', 0, '0.00', 'Included', 'JUIE SINGH', 'inspection', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `po_code` varchar(50) NOT NULL,
  `requirement` text DEFAULT NULL,
  `specification` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `advance_received` decimal(10,2) DEFAULT 0.00,
  `inspection_received` decimal(10,2) DEFAULT 0.00,
  `installation_received` decimal(10,2) DEFAULT 0.00,
  `credit_received` decimal(10,2) DEFAULT 0.00,
  `expected_delivery` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `bill_file` varchar(255) DEFAULT NULL,
  `challan_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `po_file` varchar(255) DEFAULT NULL,
  `lr_file` varchar(255) DEFAULT NULL,
  `eway_file` varchar(255) DEFAULT NULL,
  `quotation_file` varchar(255) DEFAULT NULL,
  `requirements_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `requirements_hash` varchar(64) DEFAULT NULL,
  `last_content_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `client_id`, `po_code`, `requirement`, `specification`, `remarks`, `advance_received`, `inspection_received`, `installation_received`, `credit_received`, `expected_delivery`, `actual_delivery_date`, `bill_file`, `challan_file`, `status`, `po_file`, `lr_file`, `eway_file`, `quotation_file`, `requirements_verified`, `verified_by`, `verified_at`, `requirements_hash`, `last_content_update`) VALUES
(11, 27, '4500084744', 'SHAKING FILTER BAG SATIN (UNIFLUID-10)\r\nSHAKING FILTER BAG NYLON (UNIFLUID-10)', '', '', '69691.98', '0.00', '0.00', '0.00', '2025-04-12', '2025-04-10', 'bill_4500084744_1744712303.pdf', 'challan_4500084744_1744712303.pdf', 'completed', NULL, NULL, NULL, NULL, 1, 1, '2025-04-25 15:54:24', '7213b75925f8af6aa94c99bf600fd7d1a1eceac722f0d372cb2bad7aa133f1ca', NULL),
(12, 29, '3020091065', '48 Tray Vacuum Tray Dryer complete with Chamber Door', '', '', '0.00', '0.00', '0.00', '0.00', '2025-06-06', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(13, 30, '5298', 'Air Discharge Bag for 30kg FBD\r\nAir Discharge Bag for 60kg FBD', '', '', '14514.00', '0.00', '0.00', '0.00', '2025-04-16', '2025-04-16', 'bill_5298_1744957142.pdf', 'challan_5298_1744957142.doc', 'completed', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(14, 31, 'EMV/2425/POS/CGM/00228', 'Unifluid Nano-Fluid Bed Dryer', '<p>1.Unifluid Nano which includes SS316 Bowl, Product Temperature Sensor, a bag 2. Extra Epitropic bag 3. Validation documents 4. Installation and commissioning</p>', 'Freight Included', '246000.00', '378225.00', '0.00', '0.00', '2025-06-16', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(15, 27, '4500085298', 'USSE-60 Extruder Screw in SS316 (2Nos.)\r\nUSSE-60 Extruder Screw Shaft in SS316 (2Nos.)\r\nUSSE-60 0.8mm Extruder Mesh', '', 'PACKING CHARGES OF RS3000/-  MENTIONED IN THE QUOTE ARE INCLUDED IN THE BASIC PRICE ON REQUEST OF CLIENT AS DISCUSSED WITH HIREN SIR\r\n', '601800.00', '0.00', '0.00', '0.00', '2025-04-16', '2025-04-14', 'bill_4500085298_1744782915.pdf', 'challan_4500085298_1744782915.pdf', 'completed', 'po_4500085298_1745060969.pdf', 'lr_4500085298_1744976033.pdf', NULL, 'quotation_4500085298_1745060931.pdf', 0, NULL, NULL, NULL, NULL),
(16, 32, '3020091160', 'Bin Blender\r\nS.S Bowl (4 Nos.)\r\nHMI\r\nSS316 Baffle for octagonal Blender (8Nos.)\r\n21 CFR Software', '1. Bin Blender with 2,5,10,20 Liter bins.\r\n2. 7\" HMI\r\n3. SS316 Baffles for Existing Blender Bowl (8Nos)\r\n4. 21 CFR Software', '', '0.00', '0.00', '0.00', '0.00', '2025-06-11', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, 'quotation_3020091160_1745062770.pdf', 0, NULL, NULL, NULL, NULL),
(17, 33, 'PO16525438', 'Unifluid Nano- Rapid Fluid Bed Dryer\r\n', '1. Unifluid Nano with 6ltr glass bowl and a bag\r\n2. Safety features:\r\na. Tower Lamp to be fixed - When machine is switched ON red light should turn on and when process starts green light should turn ON\r\nb. Temperature control - If Temperature goes beyond set limit, a beep sound should be emitted, the heater should cut off and blower should run.\r\nc. 30mA ELCB shall be provided in the incomer power line. Control Voltage shall be 110V.\r\n\r\n3. Installation and Commissioning\r\n4. 3Nos. Extra Air Discharge bag made from cotton cloth.\r\n5. packing forwarding and freight', '', '0.00', '0.00', '0.00', '0.00', '2025-06-30', NULL, NULL, NULL, 'pending', 'po_PO16525438_1744968332.pdf', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(22, 34, '3020090640', 'Hot Water Tank for VTD 2Nos.\r\nVacuum Pump for VTD 2Nos.\r\nHigh Efficiency Motor for VTD 2Nos.\r\nVacuum Pipeline for VTD 2Nos.\r\nAutomation for VTD 2Nos.', '<p>avcd</p>', '', '1386000.00', '0.00', '0.00', '0.00', '2025-07-18', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(24, 33, 'PO16534526', 'UNIEXTRUDE- USSE60 Single Screw Extruder\r\n1.5mm Cone Mesh for Extruder\r\nProduct Feed Tray Above Screw Hopper\r\nInstallation and Commissioning', '1) Single Screw Extruder\r\n2)S.S. 316 feed tray shall be provided with proximity switch which shall be placed over the screw hopper for feeding with safety interlock. \r\n(For Provision of Product Feed Tray above the Screw hopper with Safety Interlock to ensure that machine shall not start in case Feed Tray is not\r\nin place. The Feed tray distance from the moving part of the screw shall be 4â€.)\r\n3) 30mA ELCB shall be provided in incomer\r\n4) Control Voltage shall be 110V.\r\n5)0.8mm and 1.5mm Cone Mesh', '', '0.00', '0.00', '0.00', '0.00', '2025-07-30', NULL, NULL, NULL, 'pending', 'po_PO16534526_1744968307.pdf', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(25, 35, '5100016709', '2mm Round Hole Multimill Mesh\r\n3mm Round Hole Multimill Mesh\r\n4mm Round Hole Multimill Mesh\r\n2/5 Peristaltic Pump Silicone Tube (3M)', '', '', '0.00', '18569.00', '0.00', '0.00', '2025-04-19', '2025-04-14', 'bill_5100016709_1745647168.pdf', 'challan_5100016709_1745647168.doc', 'completed', '', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(26, 36, 'Quotation No. SBP/0404/2025-2', 'Variable Frequency Drive and speed Controller for Hot Melt Extruder', '', '', '0.00', '0.00', '0.00', '0.00', '2025-04-18', '2025-04-15', 'bill_Quotation_No__SBP_0404_2025-2_1744967522.pdf', 'challan_Quotation_No__SBP_0404_2025-2_1744967522.doc', 'completed', '', NULL, NULL, 'quotation_Quotation_No__SBP_0404_2025-2_1745645234.pdf', 0, NULL, NULL, NULL, NULL),
(27, 36, 'Quotation No. SBP/0404/2025-1', 'Service Charges for Repair of Hot Melt Extruder', '', '', '0.00', '0.00', '0.00', '0.00', '2025-04-18', '2025-04-15', 'bill_Quotation_No__SBP_0404_2025-1_1744967556.pdf', 'challan_Quotation_No__SBP_0404_2025-1_1744967556.doc', 'completed', '', NULL, NULL, 'quotation_Quotation_No__SBP_0404_2025-1_1745645202.pdf', 0, NULL, NULL, NULL, NULL),
(30, 38, 'PO20252600077', '35# SS316 Silicone Moulded Mesh for Sifter\r\n45# SS316 Silicone Moulded Mesh for Sifter', '', '', '0.00', '0.00', '0.00', '0.00', '2025-05-20', NULL, NULL, NULL, 'pending', 'po_PO20252600077_1745904741.pdf', '', '', 'quotation_PO20252600077_1745904741.pdf', 1, 14, '2025-04-28 11:30:23', 'c1896812cba137773c74a295101adf56b7721f0f1dd83ac197396ee2b82c738d', NULL),
(31, 39, '5100000200', '3HP Homogenizer with Flameproof Motor', '', 'P&F, Freight Inclusive', '0.00', '0.00', '0.00', '0.00', '2025-06-26', NULL, NULL, NULL, 'pending', 'po_5100000200_1745904521.PDF', '', '', 'quotation_5100000200_1745904521.pdf', 0, NULL, NULL, NULL, NULL),
(32, 23, 'PO16517874', 'Air Discharge Bag made from Antistatic Polyester Material for UNIFLUID1300\r\nFood Grade silicone tube for peristaltic Pump suitable for UNILFUID1300 (25Meter)\r\nFood Grade silicone tube for connection of 25liter tank to peristaltic Pump (5Meter)\r\nProduct Transfer Tube- Flexible and antistatic spiral tube for Vacuum transfer (10meter)\r\nNozzle Gasket and O-ring set (10 Nos.)\r\nTeflon Sleeve for Spray Chamber (2Nos.)\r\nPTS Bag (1Nos.)\r\nQuick Release Gasket 6mm (2Nos.)', '', '', '0.00', '0.00', '0.00', '0.00', '2025-06-12', NULL, NULL, NULL, 'pending', 'po_PO16517874_1746695443.pdf', '', '', '', 0, NULL, NULL, NULL, NULL),
(33, 40, 'ALB/PO/25-26/005', 'UNIFLUID NANO-Rapid Fluid Bed Dryer\r\nCapacity: 200gm-1Kg', '<p>UNIFLUID NANO- Rapid Fluid Bed Dryer</p>\r\n<p>1 Set of Unifluid Nano comprising of :</p>\r\n<p>1.S.S. 316 Bowl<br>2. PC Satin Bag<br>3. Surge Suppressor for Protection from any Electrical Power Surge.<br>4. Validation Documents DQ/IQ/OQ<br>5. Packing and Forwarding&nbsp;</p>', '', '240000.00', '0.00', '0.00', '0.00', '2025-06-13', NULL, NULL, NULL, 'pending', 'po_ALB_PO_25-26_005_1746695498.pdf', '', '', 'quotation_ALB_PO_25-26_005_1746697277.pdf', 1, 1, '2025-05-19 16:01:03', '9a15cad183832f65c39395e0874fd20de745957230743c2cb0b5e4a82944bdb2', NULL),
(34, 42, 'PO16619232', 'Safety Accessories For Unifluid Nano\r\nNew Wiring for Entire Control Panel', '<ol>\r\n<li>Safety Accessories for Unifluid Nano</li>\r\n</ol>\r\n<p>Scope of Work:</p>\r\n<ul>\r\n<li>Tower Lamp to be Fixed: When machine is switched ON Red light should turn ON, When Process Starts Green light should turn ON</li>\r\n<li>Temperstaure Control: If Temperature goes beyond the set limit, a beep sound should be emitted, the heater should cut off and the blower should run.</li>\r\n</ul>\r\n<p>2. New Wiring for Entire control Panel</p>', '', '0.00', '0.00', '0.00', '0.00', '2025-06-23', NULL, NULL, NULL, 'pending', 'po_PO16619232_1747041340.pdf', '', '', 'quotation_PO16619232_1747041340.pdf', 0, NULL, NULL, NULL, NULL),
(35, 43, '6500128386', 'Air Discharge Bag made from Cotton for UNIFLUID NANO (4Nos. @ Rs.2000/- Each)', '', '', '9440.00', '0.00', '0.00', '0.00', '2025-06-20', NULL, NULL, NULL, 'pending', 'po_6500128386_1747044736.pdf', '', '', 'quotation_6500128386_1747044736.pdf', 0, NULL, NULL, NULL, NULL),
(36, 24, 'BGDP/25-26/61', 'Antistatic Polyester Air Discharge Bag for UNIFLUID60 (2Nos.)', '', '', '43660.00', '0.00', '0.00', '0.00', '2025-06-12', NULL, NULL, NULL, 'pending', 'po_BGDP_25-26_61_1747372442.pdf', '', '', 'quotation_BGDP_25-26_61_1747372442.pdf', 0, NULL, NULL, NULL, NULL),
(38, 44, 'CR2500016-Mumbai', 'UNIFLUID NANO \r\n(Rapid Fluid Bed Dryer Capacity in kgs: 50gm-1kg)', '<ol>\r\n<li>Complete Set of Unifluid Nano with SS316 Bowl and a bag</li>\r\n<li>&nbsp;2Nos. Extra Air Discharge Bag made from cotton cloth&nbsp;</li>\r\n<li>1no. Extra Air Discharge Bag made from Polyster Material</li>\r\n<li>Validation Documents&nbsp;</li>\r\n<li>Installation &amp; Commissioning</li>\r\n<li>Freight: Included</li>\r\n</ol>', '', '0.00', '0.00', '0.00', '0.00', '2025-08-08', NULL, NULL, NULL, 'pending', 'po_CR2500016-Mumbai_1747908468.pdf', '', '', 'quotation_CR2500016-Mumbai_1747908500.pdf', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_list`
--

CREATE TABLE `purchase_order_list` (
  `id` int(11) NOT NULL,
  `po_code` varchar(50) NOT NULL,
  `internal_ref_no` varchar(50) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tax` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `cgst` decimal(5,2) DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst` decimal(5,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `sub_total` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT 0.00,
  `final_discounted_price` decimal(10,2) DEFAULT NULL,
  `company` varchar(50) NOT NULL,
  `material_delivery` varchar(20) DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `delivery_period` varchar(50) DEFAULT NULL,
  `authorized_signatory` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_list`
--

INSERT INTO `purchase_order_list` (`id`, `po_code`, `internal_ref_no`, `supplier_id`, `remarks`, `created_at`, `updated_at`, `tax`, `tax_amount`, `cgst`, `cgst_amount`, `sgst`, `sgst_amount`, `sub_total`, `grand_total`, `final_discounted_price`, `company`, `material_delivery`, `payment_terms`, `delivery_period`, `authorized_signatory`) VALUES
(18, 'HUGO/0605/2025-1', 'Hugo', 189, 'Price As per Last Supply', '2025-05-06 05:56:12', '2025-05-07 11:24:41', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Hugopharm', NULL, NULL, NULL, NULL),
(19, 'HUGO/1505/2025-1', 'HUGO/PZLL', 236, '', '2025-05-15 05:08:45', '2025-05-15 05:08:45', '0.00', '0.00', '9.00', '17100.00', '9.00', '17100.00', '190000.00', '224200.00', '0.00', 'Hugopharm', 'DOM', 'Against Delivery', '2-4 Weeks', 'JUIE SINGH');

-- --------------------------------------------------------

--
-- Table structure for table `receiving_list`
--

CREATE TABLE `receiving_list` (
  `id` int(30) NOT NULL,
  `form_id` int(30) NOT NULL,
  `from_order` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=PO ,2 = BO',
  `amount` float NOT NULL DEFAULT 0,
  `discount_perc` float NOT NULL DEFAULT 0,
  `discount` float NOT NULL DEFAULT 0,
  `tax_perc` float NOT NULL DEFAULT 0,
  `tax` float NOT NULL DEFAULT 0,
  `stock_ids` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_list`
--

CREATE TABLE `return_list` (
  `id` int(30) NOT NULL,
  `return_code` varchar(50) NOT NULL,
  `supplier_id` int(30) NOT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `stock_ids` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_list`
--

CREATE TABLE `sales_list` (
  `id` int(30) NOT NULL,
  `sales_code` varchar(50) NOT NULL,
  `client` text DEFAULT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `stock_ids` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_list`
--

CREATE TABLE `stock_list` (
  `id` int(30) NOT NULL,
  `item_id` int(30) NOT NULL,
  `quantity` int(30) NOT NULL,
  `unit` varchar(250) DEFAULT NULL,
  `price` float NOT NULL DEFAULT 0,
  `total` float NOT NULL DEFAULT current_timestamp(),
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=IN , 2=OUT',
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_list`
--

INSERT INTO `stock_list` (`id`, `item_id`, `quantity`, `unit`, `price`, `total`, `type`, `date_created`) VALUES
(42, 12, 0, 'PCS', 50, 2500, 1, '2024-12-30 16:08:06'),
(44, 14, 0, 'PC', 1000, 100000, 1, '2024-12-31 11:08:15'),
(48, 14, 0, 'PCs', 1000, 100000, 1, '2024-12-31 12:32:07'),
(50, 14, 0, 'Pcs', 1000, 100000, 1, '2025-01-01 11:45:39'),
(51, 14, 0, 'pcs', 1000, 50000, 1, '2025-01-01 14:47:42'),
(65, 12, 50, 'pcs', 50, 2500, 1, '2025-01-02 14:28:43'),
(70, 14, 50, 'pc', 1000, 50000, 1, '2025-01-08 09:37:00'),
(72, 14, 10, 'pc', 1000, 10000, 1, '2025-01-10 09:39:22'),
(73, 13, 10, 'pcs', 1800, 18000, 1, '2025-01-10 14:43:01'),
(74, 16, 10, 'pc', 13866, 138660, 1, '2025-01-28 23:44:28');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_list`
--

CREATE TABLE `supplier_list` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `address` text NOT NULL,
  `cperson` text NOT NULL,
  `contact` text NOT NULL,
  `cperson_acc` varchar(100) DEFAULT NULL,
  `contact_acc` varchar(20) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `category` varchar(255) DEFAULT NULL,
  `subcategory` varchar(255) DEFAULT NULL,
  `gst_number` varchar(15) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_list`
--

INSERT INTO `supplier_list` (`id`, `name`, `address`, `cperson`, `contact`, `cperson_acc`, `contact_acc`, `status`, `category`, `subcategory`, `gst_number`, `rating`, `email`) VALUES
(4, 'Sanjay Steel Centre', '101, Omkar Housing Society, Opp. Alankar Cinema, 1st Floor, Mumbai 400 004.', 'Sanjay Jain', '022 2386 3378', NULL, NULL, 1, NULL, 'AL Pipe', '', NULL, ''),
(5, 'Choudhary Aluminium', '72, Nanubhai Desai Road, Near Alankar Cinema Mumbai 400 004', 'Mahesh P. Choudhary', '9869311105/66518726/66518606', NULL, NULL, 1, NULL, 'AL Sheet', '', NULL, 'choudharyaluminium@gmail.com'),
(6, 'J.M. & Co.', '147/149, Kika Street, Gulalwadi Mumbai 400 004', 'Tejas Patel', '022 6636 3195', NULL, NULL, 1, NULL, 'AL Sheet', '', NULL, 'marketing@sjexport.com'),
(7, 'Neelkanth Metal Industries', '37, Amar Bhavan, Shop No.1A, Dr.M.G. Mahimtura Marg, Opp. Durgadevi Garden, 3rd Khumbarwada, Mumbai 400 004', 'Narpat Singh', '022 6752 1216 / 17', NULL, NULL, 1, NULL, 'AL Sheet', '', NULL, 'neelkanthmetal2015@gmail.com'),
(8, 'Rajasthan Metal House', '192, Gulalwadi, (Kika Street), Mumbai 400 002', 'Chandu', '022 2242 0822', NULL, NULL, 1, NULL, 'AL Sheet', '', NULL, 'rmh@bhansali.com'),
(9, 'Sagar Aluminium', 'SAMVED\' Shop No. 3, Near Antonio Desilva School, S.K. Bole Road, Dadar (W), Mumbai 400 028.', 'Rahul', '022 2436 1556', NULL, NULL, 1, NULL, 'AL Sheet', '', NULL, ''),
(10, 'Bhansali Aluminium', 'Panch-Sheela, 267, Thakurdwar Road, Near Charni Road Station, Mumbai 400 002. India', 'Vasant', '022 22055615/16/17/18', NULL, NULL, 1, NULL, 'AL Sheet', '', NULL, 'info@bhansali.com'),
(11, 'Nakoda Metals', '34, Krishna Bldg., Shop No. 9, 6th Khetwadi Lane, S.V.P. Road, Mumbai-400004.', 'Dilip M. Sheth', '022 6752 1180/1276', NULL, NULL, 1, NULL, 'Angle', '27BALPS1911G1ZN', NULL, 'nakodametals@gmail.com'),
(12, 'Steelmax (India)', '103, Netwarwala Bldg., Shop No. 91, Sant Sena Maharaj Marg, Mumbai 400 004.', 'Ghewarchand Angara', '+91-92201 27880', NULL, NULL, 1, NULL, 'Elbow', '', NULL, ''),
(13, 'Diamond Steel (India)', '40/25. Shri Niwas Building, 2nd Carpenter Streel, Mumbai 400 004', 'Prajapati G.K.', '022-6610 9201', NULL, NULL, 1, NULL, 'Elbow', '', NULL, 'info@diamondsteellndia.com'),
(14, 'Sunrise Engineers', '', 'Jaykishan Ji', '022 2382 4707', NULL, NULL, 1, NULL, 'Elbow', '', NULL, ''),
(15, 'Gururaj Metal', '135/141, Gururajendra House, 1st Floor, Room No. 23, Mumbai 400 004', 'Amit Tiwari', '022 6743 6797/98', NULL, NULL, 1, NULL, 'Elbow', '', NULL, 'gururajmetal@rediffmail.com'),
(16, 'Arihant Cables', '27, Shreenath Bhuvan, 6/12, Picket Cross Road, Lohar Chawl, Mumbai 400 002', '', '022 2208 4443/22069420', NULL, NULL, 1, NULL, 'Electrical', '27AABFA3073E1ZW', NULL, 'sales@arihantcables.com'),
(17, 'Essar Electro Control', '104, Pandya Mansion, 625, J.S.S. Road, Dhobitalao, Next to Kalbadevi Post Office, Mumbai - 400 002.', 'Rahul Shah', '022 3956 7703', NULL, NULL, 1, NULL, 'Electrical', '27AALPS6551K1Z1', NULL, 'essarelectro@yahoo.com'),
(18, 'HEM Traders Electrical And General Merchants', '19/21, Picket Cross Road, 2nd Floor, Lohar Chawl, Mumbai 400 004,India', 'Himanshu Sanghavi', '+91-22-6637 4595', NULL, NULL, 1, NULL, 'Electrical', '27AAFFH6413D1ZP', NULL, 'hemtraders@gmail.com'),
(19, 'Shivansh Enterprises Is working with HEM Traders', '', 'Vikas Chavan', '9769537775', NULL, NULL, 1, NULL, 'Electricals', '', NULL, ''),
(20, 'Navjyoti Metal', 'Bldg. No. 39, Shop No. 1, Dr.M.G. Mahimtura Marg, Opp. Durgadevi Garden, 3rd Kumbharwada, Mumbai - 400004.', 'Mahavir Sanghavi', '022 67496176', NULL, NULL, 1, NULL, 'ERW Pipe', '', NULL, 'navjyotimetal@yahoo.in'),
(21, 'Ujwal Metal & Tubes', '439, Pathe Bapurao Marg, Somaji Building, Mumbai 400 064', 'Parasmal Shah', '022 66595521', NULL, NULL, 1, NULL, 'ERW Pipe', '', NULL, ''),
(22, 'P.K. Tube', '203, T.P. Street, 6th Kumbharwada Mumbai 400 004', 'P.K. Tube', '9324022197', NULL, NULL, 1, NULL, 'ERW Pipe', '', NULL, ''),
(23, 'Famous Tubes', 'Shop No. 4, Bhandari Street (1st Kumbharwada) Mumbai 400 004.', 'Dhanpat M. Bhandari', '022-6743 6462', NULL, NULL, 1, NULL, 'Flat', '', NULL, ''),
(24, 'Rajendra Steel (India)', '50/52, Bhandari Street, Shop No. 3, 1 st Kumbharwada Near Gol Mandir Mumbai 400 004', 'Ashok V. Jain', '022 23828432', NULL, NULL, 1, NULL, 'Flat', '', NULL, ''),
(25, 'Mohan steel Centre', '54, Swami Samrath, Shop No.3, 2nd Kumbharwada Lane Sant sena Maharaj Marg Mumbai 400 004', 'Sukesh', '022 2389 0826', NULL, NULL, 1, NULL, 'Flat', '27ADHPJ3906E1ZS', NULL, ''),
(27, 'Vikas Steel (India)', 'Shop No. 1, 7884, Sant Sena Maharaj Marg, 2nd Kumbharwada Mumbai 400 004', 'V.R. Patel', '022 6651 8702', NULL, NULL, 1, NULL, 'Flat', '', NULL, ''),
(28, 'Mukund Steel', '76, Durgadevi Street Kumbharwada Corner Mumbai 400 004', 'Sumer Sanghavi', '022 2386 3599', NULL, NULL, 1, NULL, 'Flat', '27ANYPS108N1Z0', NULL, 'mukundsteels@gmail.com'),
(29, 'R.G. Metal', 'Shop No.3, Gr. Floor, 28 / 42, Shri Ganesh Darshan Bldg., Sant Sena Maharaj Mumbai 400 004', 'Ramchandra G. Sant', '022-6610 9242', NULL, NULL, 1, NULL, 'Flat', '', NULL, ''),
(30, 'Lalit Steel India', 'Shop No. 1, Ground Floor, Rassiwala Bldg., No. 13, 2nd Kumbharwada', 'Vikram', '9833474735', NULL, NULL, 1, NULL, 'Flat', '', NULL, ''),
(31, 'Manidhari Steels', '42/46, Guru Rajendra House, Shop No. 6, 5th Kumbharwada Mumbai 400 004', 'R.C. Gandhi', '022 6639 4096', NULL, NULL, 1, NULL, 'Flats', '', NULL, 'manidharisteels@rediffmail.com'),
(32, 'Steel Age (India)', 'Gr. Flr, Shop No.21 , Badrikashram Building 100, Nanubhai Desai Road Mumbai 400 004', 'Ramesh Jain', '022 6636 3130', NULL, NULL, 1, NULL, 'Flats', '27AAPFS7204R1ZC', NULL, 'steelageindia01@yahoo.in'),
(33, 'Namandeep Metal', 'Khandke Building, Shop No. 99, 3rd Kumbharwada, Mumbai', '', '7400231761/9930946502', NULL, NULL, 1, NULL, 'Flats', '27ADCPJ4662N1Z7', NULL, 'namandeepmetal@gmail.com'),
(34, 'Sakshi Steel', '25/33, Shop No. 8, Cresent Mansion, @nd Bhandari Cross Lane, 1st Kumbharwada, Mumbai-400004', '', '66109343/67437646', NULL, NULL, 1, NULL, 'SS Circle', '27AIIPJ9095J1ZL', NULL, ''),
(35, 'Hi-Tech Lifting Equipment', '213, Nagdevi Street, Jamal Building, Mumbai - 400 003. INDIA', 'Abbas Ali Hemani', '022-6517 5491', NULL, NULL, 1, NULL, 'Hardware', '', NULL, 'sales@hitechlifting.com'),
(36, 'Divya Jyot Pipe Stores', 'Shop No. 17, Bibijan Street, Ground Floor, (Nagdevi Street), Mumbai Maharashtra 400 004', 'Hiren Sheth', '022 23430715', NULL, NULL, 1, NULL, 'Pipe', '', NULL, 'hirensheth13@gmail.com'),
(38, 'Multilinks', 'Gala No. 141, C Wing, Mittal Industrial Estate, Building No. 3, Andheri Kurla Road, Marol Naka, Andheri (East), Mumbai-400059', 'Shrikant', '49740974/75', NULL, NULL, 1, NULL, 'Pneumatics Janatics Dealer', '27AAIFM4349J1ZX', NULL, 'multilinks2008@gmail.com'),
(39, 'Jyoti Sales Corporation', '91/93, Nagdevi Street, Mumbai-400003', 'Bharat Bhai', '23401337/23439747', NULL, NULL, 1, NULL, 'Pneumatic SMC And Local', '27AAAFJ1696F1ZD', NULL, 'info@phoenixhose.com'),
(40, 'B.Chimanlal & Company', 'Ram Mandir Road, Near, Nagdevi St, Mumbai, Maharashtra 400003', 'Bharat Bhai', '', NULL, NULL, 1, NULL, 'Pneumatic Janatics', '27AAEPD5321M1ZT', NULL, 'bchimanlal@gmail.com'),
(41, 'Bhavani Steel House', '96, Bhandari Street, Near Goldeval Mandir Mumbai 400 004.', 'Bhavani Steel House', '022 6639 4697', NULL, NULL, 1, NULL, 'Square Pipe', '', NULL, ''),
(42, 'Bharat Steel (India)', '62, Durgadevi Street (Kumbharwada), Mumbai 400 004', 'Bharat jain', '022 6638 1380', NULL, NULL, 1, NULL, 'Square Pipe', '', NULL, ''),
(43, 'Aman Metal & Alloys', 'Shop No. 3, Bldg No. 4, Ground Floor, Bhimnathji Mandir, 2nd, Khetwadi, Lane, (oop Alankar Cinema) Mumbai 400 004', 'Tayab Khan', '022-6743 7389', NULL, NULL, 1, NULL, 'Square Pipe', '', NULL, ''),
(44, 'Shrikant Steel Centre', '64, Prabhu Shriram Mandir Marg, 4th Kumbharwada, Mumbai-4.', 'Bharat Mehta', '022 6636 3079', NULL, NULL, 1, NULL, 'Square Pipe', '27AAMPM4942K1Z7', NULL, 'shrikantsteel@outlook.com'),
(45, 'Bhagyashree Steel', '7-B, 1st Parsiwada Lane, Nanuabhai Desai Road, Mumbai 400 004', 'Milap M. Sanghavi', '022 67437106', NULL, NULL, 1, NULL, 'Square Pipe', '', NULL, ''),
(46, 'Naresh Steel Corporation', '41-A, Dodia House, 5th Kumbharwada, Maruti Mandir Marg, Mumbai - 400 004', 'Naresh Shah', '9869907919', NULL, NULL, 1, NULL, 'Square Pipe', '', NULL, 'nareshsteelc@gmail.com'),
(47, 'Sumex Steel', 'Shop No. 2, 84 Lucky Mansion Durgadevi Street, Kumbharwada Mumbai 400 004', 'Shantilal Shah', '022 - 6636 2137', NULL, NULL, 1, NULL, 'Square Pipe', '', NULL, 'sumexsteelsvj@gmail.com/'),
(48, 'Bombay Tube Agency', 'Shop No. 29, Piru Lane, Pathanwadi, Mumbai-40009.', '', '9892917346', NULL, NULL, 1, NULL, 'MS Square Pipe', '27EFDPK3578D1ZD', NULL, 'abdulhafeez937@yahoo.com'),
(49, 'Kamal Metal Syndicate', '503 / 505, Mohamadi Bldg., 1st Floor, Office No. 9, Maulana Azad Road, Mumbai 400 004', 'Kaluram D. Bishnoi', '(226) 752-122', NULL, NULL, 1, NULL, 'SS Monel', '', NULL, 'kamalmetal29@gmail.com'),
(50, 'Munani Metal', '42/46, Guru Rajendra House, 5th Kumbharwada Lane, Shop No. 1. Maruti Marg. Mumbai 400 004', 'Paresh Shah', '(222) 2388-4803', NULL, NULL, 1, NULL, 'SS Monel', '', NULL, ''),
(51, 'Rooplaxmi Steels', '26, Durgadevi Street, Shop No. 1, Mumbai 400 004', 'S. M. Doshi', '9833038595', NULL, NULL, 1, NULL, 'SS Monel', '', NULL, 'rooplaxmisteels@gmail.com'),
(52, 'Perfect Tubes (India)', '47,Navnidhi Bhavan, Ground Floor, Shop No. 2, 5th Kumbharwada lane, Mumbai-4004', 'Bhavarlal', '67438320/6491/6481', NULL, NULL, 1, NULL, 'SS Pipe', '27ACVPJ6373J1ZU', NULL, ''),
(53, 'Nirma Tube (India)', '154/156, Sant Sena Maharaj Marg, 2nd Khumbharwada, Near Gol Deol Temple, Mumbai-400004', 'Alpesh Angara Tubes', '', NULL, NULL, 1, NULL, 'SS Pipe', '27ADDPJ3902G1ZW', NULL, 'nirmatubeindia@gmail.com'),
(54, 'Pavan Steel India', '29/33, Shop No. 4, Dongre Bldg., 2nd Duncan X Road Lane Ramagalli, 2nd Kumbharwada Mumbai 400 004', 'Bhavesh K. Bhansali', '022-6743 7762', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, 'pavansteelindia2014@gmail.com'),
(55, 'Mangalam Steel Corporation', 'Shop No. 81, 5th Kumbharwada Mumbai 400 004', 'Ghewarchand Bhansali', '022 6659 5737', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, 'mangalam_steel@hotmail.com'),
(56, 'Rajveer Steel', 'Shop No. 12, Ground Floor, 41, Sindhi Lane, Nanubhai Desai Road', 'Raju Bishnoi', '9930155030', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, 'rajputanamumbai@gmail.com'),
(57, 'Parmeshwar Metal', '36/44, Mukund Building, Shop No. 2/A, Ducnan \'X\' Lane. (Rama Gally), 2nd Kumbharwada, Mumbai 400 004', 'P.K. Choudhary', '022 6610 9762', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(58, 'Gajanan Steel', '163, Sant Sena Maharaj Marg 2nd Kumbharwada Mumbai 400 004', 'Bhanwarlal P. jain', '022 6743 6440', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(59, 'Darshan Tubes', '133/135, Shop No. 2, Ground Floor, M.G. Mahimtura Marg 3rd Kumbharwada, Nr. Goldeval Temple Mumbai 400 004', 'Nemichan M. Jain', '-9619308948', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, 'darshantubes@yahoo.com'),
(60, 'Kamlesh Pipe Fittings', '162, S.V.P. Road, Shop No. 4 Near Round Temple, Mumbai-4.', 'Pannalal P. jain', '9867575353', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(61, 'Kuber Tube India', '162, S.V.P. Road, Shop No. 4 Near Round Temple, Mumbai-4.', 'Pannalal P. jain', '022 6743 7333', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(62, 'Gayatri Tube India', '72, 2nd Pathan Street (Kumbharwada 5th Lane), Mumbai 400 004', 'K.K. Mehta', '022 6639 4520', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(63, 'Arbuda Steel (India)', '63/65, Prabhu Shree Ram Mandir Marg 4th Kumbharwada Mumbai 400 004', 'Vijay Modi', '+91-22-6639 4447', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, 'arbudasteelindia@rediffmail.com'),
(64, 'Mahendra Steel', '66, Sant Sena Maharaj Marg, 2nd Kumbharwada, Mumbai 400 004', 'M.S. Prajapati', '022 6651 8876', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(65, 'Mahavir Steels', '78, Dr.M.G. Mahimtura Marg (3RD Kumbharwada), Mumbai 400 004', 'Sumer', '022 2386 2881', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(66, 'Marudhar Tubes (India)', '165, Sant Sena Maharaj Marg, 2nd Kumbharwada, Mumbai-400 004', '', '67496062/66362818', NULL, NULL, 1, NULL, 'SS Pipe', '27AJOPB4162D1ZG', NULL, ''),
(67, 'Satyam Metal (India)', '107/111, Matkawala Bldg., 1st Floor, R. No 14, Dr.M.G. Mahimtura Mars 3rd Kumbharwada, Mumbai 400 004', 'Govind Choudhary', '022 2388 2516', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(68, 'Harsh Steel (India)', 'Shop No. 7/A, Dongre Building, 29/33, Sant Sena Maharaj Marg 2nd Duncan Cross Lane (Ramagali), Mumbai 400 004', 'Pravin G. Bhansali', '022 6610 9317', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(69, 'Kalpataru Stainless Steels', '236/240, Laheri Mansion R.No. 8, 1st Floor Mumbai 400 004.', 'Pravin H. Bhansali', '+91 22 6743 7130', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'kalpataru1980@hotmail.com'),
(70, 'Vikram Metal (India)', '82, 3rd Kumbharwada Lane Dr.M.G. Mahim tura Marg Mumbai 400 004', 'Hitesh Bokadia', '-66362779', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'hitesh2848@yahoo.com'),
(71, 'Sarvoday Steel', '89/91. Durgadevi Sadan, 4th Kumbharwada, Shop No. G7, Mumbai - 400 004', 'J.V. Choudhary', '9869532652', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'sarvodaystainless@hotmail.com'),
(72, 'Roman Metals', '126/8, Dr.M.G. Mahimtura Marg, 3rd Kumbharwada Mumbai 400 004', 'B.A. Choudhary', '9223208600', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(73, 'Narayan Steel India', '19, Doctor Bldg., Trimbak Parshuram Street, (6th Kumbharwada)', 'N.M. Choudhary', '022-6610 9301', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'narayansteelindia@gmail.com'),
(74, 'Metrik Metal', '51/10, Dhobi Building, Dr.M.g. Mahimtura Marg 3rd Kumbharwada Mumbai 400 004', 'N.P. Choudhary', '022-6615 1766', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(75, 'Kohinoor Metal Corporation', '37, Amar Bhavan, Dr.M.G. Mahimtura Marg, Opp. Durgadevi Garden (3rd Kumbharwada) Mumbai 400 004', 'P.R. Chouhan', '022 67521216/17', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(76, 'Vipul Steel', '22 / 24, 4th Kumbharwada, Prabhu Sri Ram Mandir Marg. Mumbai 400 004', 'Bhavesh Jain', '-66363174', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'vipulsteel@gmail.com'),
(77, 'Astsiddhi Metal', 'Shop No. 106 A. 2nd Kumbharwada. Mumbai 400 004', 'Naresh Jain', '022-67437458', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'info@astsiddhimetal.com'),
(78, 'Verai Impex', '119/125 Patel Mansion 4th Kumbharwada Mumbai 400 004', 'Naresh V. jain', '022 6651 8996', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'nareshjain9789@yahoo.com'),
(79, 'Pankaj Steel', '14, Pandurang Bhavan, 1ST Floor, Room No. 11-A, 2ND Suttar Gali, Mumbai - 400 004', 'Pankaj Jain', '9930383971', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'pankajsteel@live.com'),
(80, 'J.P. Sales Corporation', '28/42, Sant Sena Maharaj Marg, 107-B, Ganesh Darshan Bldg., 1 st Floor 2nd kumbharwada Mumbai 400 004', 'S.C. Jain', '022 66394830', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'jpsalescorpo@gmail.com'),
(81, 'S.B. Metal', '49, Durgadevi Street, Kumbharwada Mumbai 400 004', 'Suresh B. Jain', '022-23857618', NULL, NULL, 1, NULL, 'SS Sheet', '27AAAPV9771H1Z4', NULL, ''),
(82, 'Ratan Steel Industries (Sundeshwar Steel Industries)', '76, 3rd Kumbharawada lane Mumbai 400 004', 'Vijay Jain', '022-66151 431', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(83, 'Suraj Metal Corporation', '21, 2nd Carpenter Street Mumbai 400 004', 'Kishore Mehta', '(91-22) 2386 6919', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'sales@surajmetal.com'),
(84, 'Ambika Impex', 'Shop No. 179/181 sant sena Maharaj Marg, Near Gol Deol Temple, 2nd Kumbharwada, Mumbai 400 004', 'Bharat B. Mutta', '022 67436731', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(85, 'Prabhat Metals', '130, T.P. Street, 6th Kumbharwada Mumbai 400 004', 'T.R. Patel', '022-66109414', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(86, 'V.L. Choudhary', 'Guru Rajendra House, Shop No. 10, 135/141, T.P. Street, 6th Kumbarwada, Mumbai', 'Shree Rajaram Steel (India)', '23892045', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(87, 'Gautam G. Jain', 'Shop No. 54, Kamal Niwas, Maruti Mandir Mard, 5th Kumbharwada, Mumbai - 400004', 'Rishabh Steel', '66393686/66394086', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(88, 'Meena Metal Corporation', 'Shop No. 31-B, 74/80, Gr. Flr., Patra Chawl, Maruti Mandir Marg, 5th Kumbharwada, Mumbai 400 004', 'Ramesh Jain', '66109468/66109478', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'mmcmetal.jain286@gmail.com'),
(89, 'Santosh Metal (India)', '110/112, Laxmi Bldg., Shop No. 8, Sant Sana Maharaj Marg, 2nd Kumbharwada, Mumbai 400 004', 'T.P. Choudhary', '9869876199', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(90, 'Kajal Steel', '89/91, Durgadevi Street, Shop No. G8, Prabhu Shree Ram Mandir Marg, 4th Kumbharwada, Mumbai - 400 004', 'K. C. Mardia', '23820587', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(91, 'Navrekha Steel', '1, 168/172, Ram Sadan, Sant Sena Maharaj Marg, 2nd kumbharwada Lane, Mumbai - 400 004', 'Lalit Jain', '9930485636', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'navrekha@hotmail.com'),
(92, 'S.R. Steel Centre', 'Shop No. 111/2, Doctor Building, T.P. Street 6TH Kumbharwada Mumbai 400 004', 'T.R. Patel', '022-6749 6307', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(93, 'Pragati Steel India', '57, 2nd Duncan Road. Cross Lane, 2nd Kumbharwada Mumbai 400 004', 'Pukhraj B. Prajapati', '022 - 6610 9527', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(94, 'Ameet Industrial Corporation', '118, Rambha Bhavan, 3rd Kumbharwada Mumbai 400 004', 'G.G. Ranka', '022-6639 3692', NULL, NULL, 1, NULL, 'SS Sheet', '27AADPR7765E1ZD', NULL, 'ameetindicorp@gmail.com'),
(95, 'Sparsh Impex', '91/95 Mukund Bhavan, Shop No.1, 3rd Kumbarwada Lane Mumbai 400 004', 'Kamlesh H. Ranka', '022 - 6615 1401', NULL, NULL, 1, NULL, 'SS Sheet', '27AAIPR4686D1ZC', NULL, 'sparshimpex@ymail.com'),
(97, 'K.P. Stainless Steel', '84/86, Khetwadi Back Road, Opp. 7th Lane, Shop No.2 Mumbai 400 004', 'Jagdish Sanghavi', '022 56097131', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(98, 'Bhagyashali Metal', '42 / 44, Shop No7 Matru Aashish Bldg. 5th Kumbharwada( Maruti Mandir Marg) Mumbai 400 004', 'Champak Sanghavi', '022 6749 6183', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(99, 'Sigma Steel', '39/45 Sonawala Building Shop-5, 3rd Panjarapole, Near Gulalwadi Circle, Mumbai 400 004', 'Kamlesh Shah', '-3215227.19', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'sales@sigmasteel.co.in'),
(100, 'Sea-Rock Steels', '104, Natwar House, 1st Floor, Off. No. 3, Ardeshar Dadi Str., Mumbai 400 004', 'B.K. Solanki', '022 6743 6601 / 6743 6438', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'searock89@yahoo.com'),
(101, 'Shree Siddhivinayak Steel', '36/44, Rama Gally, Duncan Road, 2nd X Lane, Mumbai 400 004', 'Jorsing B. Solanki', '022-6636 2574', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'ronaksheth@yahoo.com'),
(102, 'Neelkamal Steel Centre', '94/98 Prabhu Shri Ram Mandir Marg, 4th Khumbharwada Lane Mumbai 400 004', 'mayur Solanki', '+91 22 2387 5534', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'info@neelkamalsteel.com'),
(103, 'Supreme Steel', 'Shop No. 7-A, 29/33, Dongare Building, 2nd Duncan X Lane, (Ramagally), Mumbai 400 004', 'Rajesh Trivedi', '022 2380 2696', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, 'supremestee12012@hotmail.com'),
(104, 'Shrawan Steel', 'Shop No. 1, Ground Floor, 45/49, Gulam Mohamed, 2nd Duncan Lane, M.S. Bhawan Patel Road, Girgaon Mumbai 400 004', 'Girdhrilal', '022-6636 2847', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(105, 'Mahavir Steels', '78, Dr.M.G. Mahimtura Marg., (3RD Kumbharwada), Mumbai 400 004', 'Ramesh', '022 2386 2881', NULL, NULL, 1, NULL, 'SS Sheet', '', NULL, ''),
(106, 'Prime Steel (India)', '45/54, Durgadevi Street, Kumbharwada, Mumbai 400 004', 'Mahesh S. Jain', '9773320233', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(107, 'Padmavati Metals', '15, Bhandari Street, 1st Kumbharwada, Mumbai 400 004', 'Mangilal H. jain', '9221444064', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, 'padmavatimetals4667@gmail.com'),
(108, 'M. Lalit Metal', 'Gr. Floor, Shop No.1, Bld.-8, 1st Kumbharwada, Mumbai 400 004', 'Suresh J. Jain', '022 23883602', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(109, 'Bhavana Industries', 'Shop No. 11, Haji Kasam Bldg. 65/71 Sant Sena Maharaj Marg, Mumbai 400 004', 'D.M. Mehta', '022-6636 2397 / 6743 7585', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(110, 'Lucky Steel Company', 'Shop No. 13, Bhandari Street, 1st Kumbharwada Mumbai 400 004', 'Kamlesh Mehta', '98691 60423 / +91 22 67496157', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, 'luckysteelcentre@gmail.com'),
(111, 'A.V. Metal', '72, Durgadevi Street, 3rd Kumbarwada Lane, Mumbai 400 004', 'Rakesh R. Mehta', '9833200349', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(112, 'Chamunda Steels', 'Shop No. 2, Jivraj Bhavan, Durgadevi, Street, Opp. Durgadevi Mandir, Mumbai 400 004', 'A. M. Modi', '98330 27751', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(113, 'Shivsagar Steel Corporation', '68/74, Sant Sena Maharaj Marg, Kesarwalla Mansion, (2nd Kumbharwada), Mumbai 400 004', 'L.B. Parmar', '9892040516', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, 'shivsagarsalescorp2010@rediffmail.com'),
(114, 'Shree Ganesh Steel Industries', '107/111, Matka Bldg., Shop No. 2, 3rd Kumbharwada, Mumbai - 400 004', 'Jitendra Jain', '66518632', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(115, 'Mamta Metal (India)', '133,135, 3rd Kumbharwada, Dr. M.G. Mahimtura Marg, Mumbai - 400 004', 'M.M. Bhansali', '23882029/66394632', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(116, 'Chanderdeep Metals', '101, 1st Pathan Street, Pansere House, 4th Kumbharwada, Mumbai - 400 004', 'Dinesh Jain', '56362918', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(117, 'Harsh Deep Metal', '51, Bhandari Street, Hira Kunj Bldg., Mumbai 400 004', 'Ravi T. Prajapati', '9819942942 / 022-6659 5120 / 6743 7877', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(118, 'Karan Steel', '25/27, Durgadevi Street, Kumbharwada, Mumbai 400 004', 'B.T. Purohit', '022 6743 8201', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(119, 'Ramdev Steel', '26, Durgadevi Street, Shop No. 5, Ground Floor, Kumbharwada, Mumbai 400 004', 'S.U. Purohit', '022 66394624', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(120, 'Sunder Steel', '68-A, Bhandari Street, Todker Bldg., Shop No.1 , Mumbai 400 004', 'Bastimal J. Sheth', '9967238215', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(121, 'I.S. Steel Traders', '13, Kharwagali, Meherbazar, Falkland Road, Mumbai 400 004', 'Ishaque', '9821360595', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, ''),
(122, 'Suryadeep Metal', '59, Shatwara Mansion, Ground Floor, Durgadevi Udyan, 3rd Kumbharwada Mumbai 400 004', 'Suresh', '022-6743 8349', NULL, NULL, 1, NULL, 'SS-Circle', '', NULL, 'suryadeep_metal@yahoo.com'),
(123, 'Ashok Steel', '6, Bhandari Street, 1ST Kumeharwada, Mumbai 400 004', 'Dinesh K. Jain', '9869754960', NULL, NULL, 1, NULL, 'SS-Circles', '', NULL, ''),
(124, 'Vishal Metal Syndicate', '89/91, Shop No. 19 & 20, Durgadevi Street, Kumbharwada, Mumbai-400004', '', '23883512/23882096', NULL, NULL, 1, NULL, 'SS Rod', '', NULL, 'vishalmetalsy@yahoo.co.in'),
(125, 'Padmavati Steel', '2nd Khetwadi Back Road', 'Mahavir', '67437180', NULL, NULL, 1, NULL, 'SS Rod 316', '', NULL, ''),
(126, 'Gunratna Metal', 'Shop No. 2, Bldg. 25/1, Khambata Lane Khetwadi Back Lane Opp. Mahavir Darshan', '', '66394001/66363214', NULL, NULL, 1, NULL, 'SS Rod 316', '27AACPJ9090R1ZX', NULL, 'gunratnametals@gmail.com'),
(127, 'Ratnajyot Overseas / Prithvi', 'Shop No. 102, Ground Floor, 2nd Khumbharwada Lane, Mumbai-400004', 'Naresh Bhai', '02266581536/1537', NULL, NULL, 1, NULL, 'SS Rod', '27AYOPJ0884D1Z9', NULL, 'ratnajyotoverseas@gmail.com'),
(128, 'Azamin Enterprise Pvt Ltd', '274/290, Nagdevi Street Ground Floor, MUMBAi-400 003.', 'Azamin Enterprise', '022 2311 7560', NULL, NULL, 1, NULL, 'Welding Accessories', '', NULL, 'azaminpl@vsnl.net'),
(129, 'Pluto Industries', 'Pluto Industries gr. Flr., Singh Industrial Estate No. 2, Ram mandir road, goregaon west mumbai- 400 104', 'Narshi Bhai Patel', '', NULL, NULL, 1, NULL, '', '', NULL, ''),
(130, 'Omnitech Transformer', '39, Universal Industrial estate, I B Patel Road, Goregaon East, Mumbai- 63', 'Sood', '9892520063', NULL, NULL, 1, NULL, 'Choke', '27AAEPS4985J1Z0', NULL, 'omnitechh@rediffmail.com'),
(131, 'Pacific Flameproof Industries', '40, Singh industrial estate No. 2, Ram Mandir Road, Goregaon West, Mumbai - 400 104', 'Hardik', '9820790519', NULL, NULL, 1, NULL, 'Instruments', '', NULL, ''),
(132, 'Sachin Hiremath', '392, 10th cross, 10th phase, Peenya Industrial Estate, Bangalore- 560058', 'Techno Talent', '080 41170131/ 41172312/ 28367363/41492827', NULL, NULL, 1, NULL, 'Motor Ital Vibras', '29AACCT9144M1ZP', NULL, 'contact@technotalent.in'),
(133, 'Vikas Doke', 'Popular Industrial Estate, 11-1st Floor Ghokhale Compound, Raghuvir Nagar Near Char Rasta dombivli East-421 201', 'TRI-FLP Engineers Pvt. Ltd.', '+91 0251 2452001 / +91 0251 2433393', NULL, NULL, 1, NULL, 'FLP Light', '27AABCT9376Q1ZC', NULL, 'triflp@gmail.com'),
(134, 'Mitesh Gandhi Owner Pravin Sales', '214, Diamond Industrial Estate, Ketaki Pada, Dahisar East, Near Dhaisar Toll Naka, Mumbai 68.', 'Flowtech', '022-2848 3010/2848 2876', NULL, NULL, 1, NULL, 'Peristaltic Pump', '27AAIHA8443B1ZJ', NULL, ''),
(135, 'Paras Packaging', '4 Manik Co.-Op- Housing Society, Siddivinayak Annexe, S.J. Marg, Lower Parel (W), Mumbai-400013', '', '24984242/7666662433', NULL, NULL, 1, NULL, 'Packaging Material', '27AGBPP6869K1ZP', NULL, 'paraspackaging005@gmail.com'),
(136, 'Bluetech Infosys', 'Shop No. 10, Ground Floor, Aditya Arcade Bldg. Topiwala Lane, Lamington Road, Mumbai- 400007', 'Madan', '9821563659 Narpat', NULL, NULL, 1, NULL, 'Computer', '27ATIPP9333Q1ZQ', NULL, 'bluetechcomputers7625@gmail.com'),
(137, 'Think Engineering Co.', 'Sona Udyog, Building No. 1,1st Flr., Gala No. 107,Parsi Panchayat Road, Andheri East,Mumbai ? 400 069', 'Pooja Parab', '', NULL, NULL, 1, NULL, '', '', NULL, ''),
(138, 'Nippon', '237/8, New Sonal Link Ind. Estate, Bldg. 2, Link Road, Malad(W), Mumbai-400 064', 'Anil Joshi / Tech- Bhargo', '4062 0000', NULL, NULL, 1, NULL, 'Meanwell SMPS', '27AABPJ2875P1Z7', NULL, 'sales@nipponindia.com'),
(139, 'Chair House', '', 'Amit Gupta', '9930015967', NULL, NULL, 1, NULL, 'Chair Repairing', '', NULL, ''),
(140, '##', '', 'Santosh Kasbe', '9527520687', NULL, NULL, 1, '', 'Chair Repairing', '', 1, ''),
(141, 'Aarya Laxmi', 'Room No. 361, Anna Bhau Sathe Nagar Co. Op. Hsg. Soc. G.D. Amberkar Marg, Hindu Smashan Bhumi Rd., Naigaon, Dadar, Mumbai-400014', 'Ravi More', '', NULL, NULL, 1, NULL, 'Chair Repairing', '', NULL, 'rmore558@gmail.com'),
(143, 'Deepak Industries', 'Shahid Bhagat Singh Rahivasi Sang, Thakur Chawl, Behind Seepz Colony, Speez++ Road, Near to MIDC Staff Quarters, Andheri East Mumbai 400 093', 'Deepak', '9870400248', NULL, NULL, 1, NULL, 'Sensors', '27AACPN1057G1ZX', NULL, 'deepakelect9@yahoo.in'),
(145, 'Om Industries', 'Surve Compound, Radhakrishna Mandir Road, Konkani Pada, Dahisar East, Mumbai - 400 068', 'Ravi Bhosale', '9987223124/9320637622 Shashank 9892637622', NULL, NULL, 1, NULL, 'Transformers/ Chokes', '27AHZPB3998E1ZQ', NULL, 'info@omindus.com'),
(146, 'Smisen Controls Pvt. Ltd.', 'Office No. 608, Filix Tower, Lal Bahadur Shastri  Road, Sonapur, Bhandup West, Mumbai- 400078.', 'Pooja Jakulwar', '2225942024', NULL, NULL, 1, '', 'Transformers/ Chokes CE Marked', '', NULL, 'marketing@smisencontrols.com'),
(147, 'Beijer Electronics Technologies Pvt. Ltd', 'B-406, 4th Flr., Teerth Technospace IT Park,Survey No. 103/2/14, Baner, Off. NH-4, Pune, Maharashtra- 411045', 'Kushal Rawal', '', NULL, NULL, 1, NULL, 'Beijer HMI', '27AAGCB3555Q1Z4', NULL, 'mumbaisales@beijerelectronics.co.in'),
(148, 'Jupiter Electronics', '', 'Prashant Patil', '', NULL, NULL, 1, NULL, '', '', NULL, ''),
(150, 'Chavare Engineering Pvt. Ltd.', 'Plot No. D-32, Phase 2, MIDC, Dombivli (E), Dist. Thane', '', '', NULL, NULL, 1, NULL, 'Mitsubishi/VFD/PLC/HMI', '27AAACC7062R1Z5', NULL, 'chavareengg@vsnl.com'),
(151, 'Jay Enterprises', 'Unit No. 9, New Modella Industrial Estate, Padwal Nagar, Thane - 400 604', 'Sainik Panchal', '', NULL, NULL, 1, NULL, 'Perforated Pan', '27AHDPP5242P1ZU', NULL, ''),
(152, 'NK Filter Fabrics', '232, Hiranandani Industrial Estate,Opp. Railway Station, Kanjur Marg West,Mumbai - 400078', 'Maria- Sales Nandkumar- Production', '022-25786927', NULL, NULL, 1, NULL, 'Filter Bag', '27AAAFN9652R1ZG', NULL, 'account@nkfilter.com'),
(153, 'San Air Tools', 'No. 192/3, Pokhran Road, No. 2, United Industries Compound, Thane, Maharashtra ? 400 601, India', 'Manisha/Mr.Samson', '', NULL, NULL, 1, NULL, 'Air Motor', '27CMBPS3344C1Z8', NULL, 'sanairtools@gmail.com'),
(154, 'Pinnacle Industrial Controls Pvt. Ltd.', 'J208, Ansa Industrial Estate, Saki Vihar Road, Andheri East, Mumbai 400072', 'Pratish', '022 42281900', NULL, NULL, 1, NULL, 'Delta VFD', '27AABCP7858B1ZC', NULL, 'rushikesh.d@pinnacle-controls.com'),
(155, 'Apple Automation And Sensor', '12 Parth, Ganesh Peth Lane, Near Plaza Cinema, Near Shivaji Park Post Office, Dadar West Mumbai 400028', 'Sneha Parab', '9699397939/9699397949/9699092002', NULL, NULL, 1, NULL, 'Delta VFD', '27ACRPG0717F1ZQ', NULL, 'applesensor@gmail.com'),
(156, 'BPL Enterprises', '170/172, Kalbadevi Road, 3rd Floor, Mumbai ? 400 002', 'Mr. Pradeep', '', NULL, NULL, 1, NULL, '', '27AACPL2146G1ZZ', NULL, 'bplenterprises@yahoo.com'),
(157, 'Aczet Pvt. Ltd.', 'Unit No. E2, Plot No. 15, WICEL, Opp. Seepz Gate No.1, Andheri (East), Mumbai 400 093', 'Sanjeev Kumar', 'More 9975084725/Manoj 9730972258', NULL, NULL, 1, NULL, 'Weighting Scale', '27AABCC3800G1Z5', NULL, 'sanjeev.k@aczet.com'),
(159, 'Vashi Electricals Pvt. Ltd.', '165-A, Bijlee Ghar, Cardial Gracious Road, Chakala, Andheri  East, Mumbai - 400099', '', '0252 2661600/7506719953 Pranjal', NULL, NULL, 1, NULL, 'Hindustan Motors', '27AAACV1496Q3ZK', NULL, 'sales@vashielectricals.com'),
(160, 'Mehta Trading Corporation', '', 'Kishor Mehta', '9324422442', NULL, NULL, 1, NULL, 'Hindustan Motors', '', NULL, ''),
(161, 'Sentinel', 'Lawyers Chamber, 24 R.C. Mama Path, Lohar Chawl, Mumbai - 400 002', 'Reshma', '022 2205 3536/022 2206 3528', NULL, NULL, 1, NULL, 'Schneider/Selec', '27AAAFS4582K1ZU', NULL, 'sentinel.mumbai@gmail.com'),
(162, 'Sheetal Sales Corporation', '81-A, khadilkar Road, Gandhi Niwas, No. 2, Mumbai - 400 004', '', '022-23825576', NULL, NULL, 1, NULL, 'Siemens', '27AABPP1150B1ZC', NULL, 'dakshesh2309@gmail.com'),
(164, 'Prime Electric Company', '64,Mangaldas Road, Dwarkadas Building, Lohar Chawl,Mumbai - 400 002', 'Kayshu Bhai', '022-22003648', NULL, NULL, 1, NULL, 'Anchor 35% Disc', '27AMIPP4873H1ZJ', NULL, 'primeelectriccompany@gmail.com'),
(165, 'Triveni Control Gears', '18, Masakati Mahal,119, Patakwadi, Lohar Chawl, Mumbai - 400004', 'Akshat Doshi', '22085618', NULL, NULL, 1, '', 'Siemens', '27AAAFT2264R1ZO', NULL, 'sales@trivenicontrol.com'),
(166, 'Indian Trade Agencies', 'P.O.Box 2504, Bhabha Building, 151, Princess Street, Mumbai-400002', 'Chintan Desai', '22083338/22030153', NULL, NULL, 1, NULL, 'Siemens MCB', '27AAAFI7463F1ZD', NULL, 'intrade@bom8.vsnl.net.in'),
(167, 'Tejashvi Impex', '1st Cross Lane, 1st Kumbharwada, Mumbai - 400 004', 'Jogaram Prajabati', '6743 7421', NULL, NULL, 1, NULL, '', '', NULL, ''),
(168, 'Manglam Pipe & Fitting', '73, Ganga House, Shop No. 3, 4th Kumbharwada, Mumbai - 400 004', 'Kantilal Purohit', '9820848307', NULL, NULL, 1, NULL, 'Elbow', '', NULL, ''),
(169, 'K.P.Steel', '23/27, rahim Baksh Bldg., Sant Sena Marg, 2nd Kumbharwada, Mumbai - 400 004', 'Lalaram Mali', '9987202821', NULL, NULL, 1, NULL, 'Circle', '', NULL, 'kpsteel51@gmail.com'),
(170, 'Shrikant Steel Centre', '164, T.P. Street, 6th Kumbharwada Lane, Mumbai - 400 004', 'Popat Mehta', '', NULL, NULL, 1, NULL, 'SS Welded Pipe', '', NULL, 'info@shrikantsteel.com'),
(171, 'Three Gee Engineers Pvt. Ltd.', 'Plot No. A-476, Road No. 26, MIDC, Wagle Estate, Thane West - 400 604', 'Pankaj Marwah', '', NULL, NULL, 1, NULL, '', '27AAACT2528F1ZK', NULL, 'threegee@wiremeshfilters.com'),
(172, 'Allied Electronics Corporation', '12-D, Vikas Centre, 106, S.V. Road, Santacruz (W), Mumbai- 400 054', 'Hema/Sharmita', '02261953636/61953608', NULL, NULL, 1, NULL, '', '27AAAFA2043F1Z3', NULL, 'sales@aeconnectors.com'),
(173, 'Arham Stainless', '42/46, Guru Rajendra House, Shop No. 6, 5th kumbharwada, Mumbai - 400 004', '', '022-66394096/67496204', NULL, NULL, 1, NULL, 'SS Flats', '2APUPG8675G1Z5', NULL, 'arhamstainless@rediffmail.com'),
(174, 'HEM Enterprises', '19/21, Picket Cross Road, 2nd Floor, Lohar Chawl, Mumbai 400 004,India', 'Himanshu Sanghavi', '+91-22-6637 4595', NULL, NULL, 1, NULL, '', '27AADPS7211G1ZR', NULL, 'hemtraders@gmail.com'),
(175, 'Steel-Smith', 'Plot No. 12, Sector-2, Vasai Taluka Indl. Co-op Estate Ltd, Gauraipada, Vasai East, Palghar - 401 208', '', '0250-2451105/06/07/08/09', NULL, NULL, 1, NULL, 'Toggle Clamps', '27AACFS3656E1Z8', NULL, 'clamps@steelsmith.com'),
(176, 'Bhagya Impex', '41-A, Dodia House, 5th Kumbharwada, Maruti Mandir Marg, Mumbai - 400 004', '', '022-66394678/66394719', NULL, NULL, 1, NULL, 'SS Welded Pipe', '27AANPS8762J1ZS', NULL, 'Funiversal'),
(177, 'Steel-O-Fab Engineers', 'Building No. A-12, Gala No. 7/8, Harihar Complex, Mankoli Naka, Dapode Village Road, Near Gajanan Petrol Pump, Bhiwandi- Mumbai-421302.', 'Sameer Gawde/ Mahi', '4290 3100', NULL, NULL, 1, NULL, 'Motors/GearBox', '27AAAFS3298L1ZP', NULL, 'support.mkt@gmail.com'),
(178, 'Ansh Steel', '3rd Kumbharwada', 'Vishnu/Bhagwan', '', NULL, NULL, 1, NULL, 'SS Rod Sq. Hex.', '', NULL, ''),
(179, 'Abhishek Enterprises', 'Near Joy Coach, Off. W.E. Highway, Near Goregaon East, Mumbai -400 063', 'Singhania', '', '', '', 1, '', 'Filter Bags', '27AQUPS2763C1ZE', NULL, 'ae@singhaniaindustries.com'),
(180, 'Festo', '5th Flr. Raheja Point 1, Santacruz', 'Ashok Gawde', '', NULL, NULL, 1, NULL, 'Pneumatics', '29AAACF2940F1ZU', NULL, 'ashok.gawde@festo.com'),
(181, 'K.G.N. Engineering Works', '¬, Shyam Niwas, Mohili Village, Behind Noori Jama Masjid, Sakinaka, Mumbai ? 400 072', '', '9833967053', NULL, NULL, 1, NULL, 'SS Panel', '27BZKPS9255D1ZU', NULL, 'kgnengg@gmail.com'),
(182, 'Star Scientific', 'Raut Industrial Estate, 424-A, Senapati Bapat Marg, Mahim West, Mumbai-400016', '', '24457965', NULL, NULL, 1, NULL, 'Gauge', '27AACPG3310R1ZP', NULL, 'starscientific55@gmail.com'),
(183, 'Bharat Telecom EPBAX', '1301, Janshakti Tower, Next to Bhoomi Plaza. Dr. Waman Shankar Matkar Cross Lane, Dadar West, Mumbai-400028', 'Vishwajeet', '24366194/24310762', NULL, NULL, 1, NULL, 'Intercom Phone AMC', '27AIWPP6231E1ZW', NULL, '8655316266 Engineer'),
(185, 'Network Inc.', 'Antop Hill Warehousing Complex, B007, Ground Floor, Near Barkat Ali Naka , Vadala (E). Mumbai 400037.', '', '', NULL, NULL, 1, NULL, 'Meanwell SMPS', '', NULL, ''),
(186, 'Chimanlal Manilal & Co.', '42/44, Jamil Mohalla, Bapu Khote St., Mumbai 400 003', 'Hasmukhbhai Trivedi', '23462568', NULL, NULL, 1, NULL, 'Glue Glass to Metal', '', NULL, ''),
(187, 'P. Chandra Trading Co.', '67/A, MangalDas Road, Lohar Chawl', '', '39567757', NULL, NULL, 1, NULL, 'UV Tubelight', '', NULL, ''),
(188, 'Dodia Electricals', '9/11, Picket \'X\' Road, Ground Floor, G.T. Building, Lohar Chawl, Mumbai 400 002', '', '22064807/2207/0921', NULL, NULL, 1, NULL, 'Electricals', '27AACFD2956N1Z3', NULL, 'sales@dodiaelectricals.com'),
(189, 'Ronak Switchgear & Automation', 'Shop No. 6/7/8, Gr. Flr., 468 Bharat Bhuvan, Junction of Kalbadevi & Princess Street, Opp. Bank of India, Mumbai-400002.', 'Ketan Bhai', '22064800/01/02/03', NULL, NULL, 1, '', 'Electricals', '27AAZFR3024N1ZH', NULL, 'ketandodia@ronakautomation.com'),
(190, 'Process Precision Instruments', '101, Diamond Industrial Estate, Behind Shakti Complex Navghar, Vasai Road East 401210', 'Sujata/Lata', '0250 2391722/33/37/42', NULL, NULL, 1, NULL, 'PPI', '27AAAFP0413C1Z0', NULL, 'sales@ppiindia.net'),
(193, 'Elite Electricals & Equipment', '33, Ismail Building, 3rd Floor, Office No. 8, Pathakwadi, Lohar Chawl, Mumbai ? 400 002.', 'Talk to sameep before calling them 9594997873', '22037152/22113665', NULL, NULL, 1, NULL, 'Selec Mazhar Bhai', '27AAAFE9092Q1ZP', NULL, 'sales@eliteelectricalsindia.com'),
(194, 'Selec Sales Engineer', '', 'Sameep Gorivale', '', NULL, NULL, 1, NULL, 'Selec', '', NULL, ''),
(195, 'Balaria Cork', '', 'Cork Insertion Sheet', '23112789', NULL, NULL, 1, NULL, 'Rubber', '', NULL, ''),
(196, 'Galaxy Trading Corporation', 'Office No. 208, K. K. Arcade, No. 98, Narayan Dhuru Street Masjid Bunder West, Masjid,ÿMumbai-400003, Maharashtra, India', 'Satyen', '', NULL, NULL, 1, NULL, 'Insertion Sheet', '', NULL, ''),
(197, 'A.A. Malla & Company', '104, Nagdevi St, Chippi Chawl, Kalbadevi, Mumbai, Maharashtra 400003', '', '022 2342 0961', NULL, NULL, 1, '', 'Insertion Sheet/ Red Star', '27AABFA1358E1ZW', 2, 'aa.malla@yahoo.com'),
(198, 'Ravasco Swastik Industries LLP', '159, Nagdevi Street, Mumbai-400003.', 'Nilesh Bhai', '23400694/0790', NULL, NULL, 1, NULL, 'Insertion Sheet/ Red Star', '27AACHN4733K1Z0', NULL, 'ravascoswastik1@gmail.com'),
(199, 'Xpress Computers Ltd.', '2A Majestic Mansion, 380 S.V.P. Road, Prathana Samaj, Girgaon, Mumbai - 400 004', 'Chandrashekhar Dhawde', '66122090', NULL, NULL, 1, NULL, 'Server AMC', '27AAACX0178R1ZP', NULL, 'shekhar@xpresslive.in'),
(200, 'Ambekar Transport', 'Wadki Nala, Near Sakal Press, Pune- Saswad Road, Tal - Haveli, Dist. Pune-412 308', '', '9765610141', NULL, NULL, 1, NULL, '', '', NULL, 'ambekarmp@gmail.com'),
(201, 'Shree Creation', '3/28, Kamgar Nagar No. 1, New Prabhadevi Road, Prabhadevi, Mumbai - 400 025.', '', '24382556', NULL, NULL, 1, NULL, 'Panel Label', '27ANRPK0960D1ZY', NULL, 's.shreecreation@yahoo.co.in'),
(202, 'Labdi Enterprise', 'Room No. 7, 2nd Floor, Jai Sai Prasad CHS, Pandurang Wadi, Manpada Road, Dombivli (East) - 421201', 'Akshay', '9167043489', NULL, NULL, 1, NULL, 'Siemens Dombavli', '27BSBPS9699R1z8', NULL, 'devangright@gmail.com'),
(203, 'Raichand And Sons', '7, Damji Shamji Industrial Complex, Off. Mahakali Caves Road, Andheri East, Mumbai 400 093.', '', '26874440', NULL, NULL, 1, NULL, 'Siemens', '27AAACR2130B1Z4', NULL, 'rsepl@hotmail.com'),
(204, 'Zenith Industrial Rubber Products Pvt. Ltd.', 'Shop No. 177, Nagdevi Street, Mumbai - 400 003', 'Abhijeet', '23445560', NULL, NULL, 1, NULL, 'Insertion Sheet/Zenith', '', NULL, 'abhijeet.u@zenithrubber.com'),
(205, 'New Western Light', '33-A, Sardar Graha Bldg., Ground Floor, Lohar Chawl, Mumbai- 400 002', '', '22113659', NULL, NULL, 1, NULL, 'LED Light', '27ADHPK0712H1ZV', NULL, ''),
(206, 'Mahavir Courier', '22/A, Nikam Wadi, Manik Apartment, Nr. Bhavani Plaza, Bhavani Shankar Road, Dadar (W), Mumbai 400 028', '', '24224501', NULL, NULL, 1, NULL, '', '', NULL, 'shreemahavircourier.dadar@gmail.com'),
(207, 'K L Traders', 'Ganesh Bhavan, 12, , Off Lamington Road, Shamrao Vittal Ln, Shapur Baug, Grant Rd, Mumbai, Maharashtra 400007', 'Vicky Motwani', '23820070', NULL, NULL, 1, NULL, 'Shielded Cable', '27AAEPM1907E1Z1', NULL, 'kltraders@hotmail.com'),
(208, 'Emerald Enviro Systems LLP', '205, Crown Jewel, Yogi Hills, Mulund (W), Mumbai- 400 080.', 'Nand Thadani', '', NULL, NULL, 1, NULL, 'Scrubber', '27AAGFE4464L1Z3', NULL, 'emeraldenviro@gmail.com'),
(209, 'P.R. Shah & Co.', 'Prabhat C.H.S. Ltd., 1st Floor, R. No. 7/A, 204, Shamaldas Gandhi Marg, (Princess Street), Mumbai ? 400 002', 'Mitesh Bhai', '22033840/49137060', NULL, NULL, 1, NULL, 'Heat Sink G-68', '27AAPFP7785A1ZS', NULL, 'prshahco@rediffmail.com'),
(210, 'Mansukhlal & Co.', '163, Narayan Dhuru Street, Mumbai', '', '23426227/23424969', NULL, NULL, 1, NULL, 'Spray Gun Service', '', NULL, ''),
(211, 'Hugopharm Technologies Pvt. Ltd. ', 'Plot No. TS-20, MIDC Phase 2, Sangam Manpada Road, Dombivali East - 421204', '', '24222815 Mtnl', NULL, NULL, 1, NULL, '', '27AACCH1711N1ZM', NULL, ''),
(212, 'SB Panchal & Co.', '8, Jogani Industrial Estate, 541, Senapati Bapat Marg, Dadar (W), Mumbai-400028', '', '', NULL, NULL, 1, NULL, '', '27AAAFS5950K1ZW', NULL, ''),
(213, 'Hi-Tech Enviro Engineers', 'Shop No. 8, Silver Coin C.H.S., Below Saraswat Bank, Nr. P.P. Chambers, S. Bhagat Singh Road, Dombivli East-421201', '', '9820417305', NULL, NULL, 1, NULL, 'Scrubber', '', NULL, 'hitech_enviro_engg@yahoo.co.in'),
(214, 'Siddhi Corporation', '12-A, Swastik Building, 3rd. Floor, Damale Wada, Sarvankar Road, Dombivli East Near Star Colony', '', '9820162146', NULL, NULL, 1, NULL, 'Stretch Flim', '27ABFFS3228N1ZS', NULL, 'siddhi.corporation4@gmail.com'),
(216, 'Tools-N-Abrasives', '95,Nagdevi X Lane, 1st Flr., Mazid Bunder, Mumbai 400 003', '', '23434810/23449615/23114567', NULL, NULL, 1, NULL, 'Hiflex', '27AADPS5588A1ZJ', NULL, 'keni@mtnl.net.in'),
(217, 'National Tools Centre', '2, Sai Darshan Co-Op Hsg. Society, R.K. Talreja College Road, Ulhasnagar 421003', '', '', NULL, NULL, 1, NULL, 'Argon Our', '27AACFN2431J1ZF', NULL, 'National Gas & Allied Equipments'),
(218, 'Spa Electronica Pvt. Ltd.', '22, Gopal House Indl. Premises, I.B. Patel Road, Goregaon East, Mumbai-400063', '', '', NULL, NULL, 1, NULL, 'Air Heaters', '27AALCS7513G1ZS', NULL, 'spa.electronica@gmail.com'),
(219, 'Guddi Corporation', 'B-16, Taj Building, Gowaliya Tank, Mumbai-400036', '', '23863196/23865961', NULL, NULL, 1, NULL, 'Air Heaters', '27APBPS0928R1Z9', NULL, ''),
(220, 'Raj Tools', '217-A, Huseini House, 1st Flr., Room No. 1, Chhatriwala Compound, Dr. Mascranhes Road, Mazgaon, Mumbai-400010', '', '9821685901', NULL, NULL, 1, NULL, 'Cutting Wheel', '27ACQPK0930R1ZZ', NULL, 'huzaifa_kapasi@yahoo.co.in'),
(221, 'Kevision Systems', 'B 202, Safal Pegasus, Anand Nagar Road, Satellite, Ahmedabad-380015', 'Pallavi Patel Sales', '', NULL, NULL, 1, NULL, 'Solid Flow Detector', '24AAPFK5208D1ZJ', NULL, ''),
(222, 'Vijay Enterprises', '12/8, Lakdi Bunder Road, Dharukhana, Mumbai-10', '', '0251-2456213/2445646', NULL, NULL, 1, NULL, 'CRCA Sheet', '27ADCPM5859N1ZV', NULL, 'vijaymehta_ent@hotmail.com'),
(223, 'Aditya Control Systems', 'A/9/17, Sakinaka Industrial Market, Link Road, Opp. Maharashtra Weight Bridge, Sakinaka, Mumbai-400 072', '', '28508667/9594396402.', NULL, NULL, 1, NULL, 'Panel Wiring', '27AZMPS6160L1ZM', NULL, 'accounts@adityacontrols.co.in'),
(226, 'Bhagwan Associates', 'Gala No. 117, Udyaog Kshetra, Mulund Goregaon Link Road, Mulund West', 'Sundarraj', '', NULL, NULL, 1, NULL, 'Steam Control Valve AVCON', '27AADPM9603N1ZA', NULL, 'sundarraj@bhagwanassociates.co.in'),
(227, 'Duplex Engineering Works', '224, Acharya Commercial Centre, Dr. C.G. Road, Opp. Borla Society, Chembur, Mumbai-74', 'C.H. Hiranandani', '9820698464', NULL, NULL, 1, NULL, 'Brake for motors', '27AAAFD4883E1ZI', NULL, 'duplex84@hotmail.com'),
(228, 'Devak Infomark Pvt Ltd.', '432/433, Bldg 2, New Sonal Link Industrial Estate, Link Road, Kanchpada, Malad(W), Mumbai- 400 064', '', '', NULL, NULL, 1, NULL, 'Search Engine Optz.', '27AAECD9271B1ZQ', NULL, 'accounts@devakgroup.com'),
(229, 'Jetspray Innovations Pvt. Ltd.', 'F-331, 1st Floor, Dreams Mail, L.B.S. Marg, Bhandup (W), Mumbai- 400078', '', '41205328/21660056', NULL, NULL, 1, NULL, 'Spray Ball', '27AAECJ0459M1Z9', NULL, 'jetspray.innovations@gmail.com'),
(230, 'Ishwar Trading', '107, Great Western Building, 23, Fort 400023, Bake House Ln, Kala Ghoda, Fort, Mumbai, Maharashtra 400001', 'Ganesh', '22042187/66360880', NULL, NULL, 1, NULL, 'Danfoss Pressure Switch', '27AABFI8710J1ZB', NULL, 'sales@ishwartrading.com'),
(231, 'Softcon Systems Pvt. Ltd.', 'Softcon House, Sr. No. 80/02,\nOpp. Mumbai - Bangalore Highway,\nNext to JSPM, Tathawade, Pune - 411033', 'Abhishek/Rupali', '+91 20 6710 8000', NULL, NULL, 1, NULL, 'Danfoss Pressure Switch', '27AAJCS0459H1Z6', NULL, 'sales@softcon.co.in'),
(232, 'Shree Chehar Pharma Machinery', 'Office-A-1/402, Sukur Residency, Nr. Muchhala College, G.B. Road, Thane (W)- 400607', '', '', NULL, NULL, 1, NULL, '', '27AFHPJ9603E1ZI', NULL, 'shreecheharpharma@gmail.com'),
(233, 'National Trading Corporation', '25, Nakoda Street, 1st Flr, Mumbai- 400 003.', 'Shah', '', NULL, NULL, 1, NULL, 'A4 Size Paper', '27AAFPS363N1Z4', NULL, 'ntc1962@ymail.com'),
(234, 'Alphatech Engineers', 'Gala No. H-2, Bldg. No. 2B, Sagar Industrial Estate, Dhumal Nagar, Waliv, Vasai East, Dist. Palghar - 401208', 'Nilesh Bhai', '', NULL, NULL, 1, NULL, '', '27AKZPC8812D1ZX', NULL, 'eng.alphatech@gmail.com'),
(236, 'Shree Ganesh Engineering', 'Gala No. C-7 & 8, Gokul Ind. Estate, Sag Baug, Opp. Andheri Kurla Road, Marol Co-op. Ind. Estate, Andheri (East), Mumbai - 400059', '', '', NULL, NULL, 1, NULL, 'Multimill Screen', '27AFJPP9903R1ZG', NULL, 'sge47@rediffmail.com'),
(237, 'Trishul Electrical Works', '85, New Unique Industrial Estate, Dr. RP Road, Opp. Jawahar Talkies, Mulund (West), Mumbai - 400 080', 'Rajesh Rathod', '25640033/25607774', NULL, NULL, 1, NULL, 'Ferrule Printing Machine', '27AAAFT1502E1ZS', NULL, 'admin@trishulgroup.com'),
(239, 'Shreeji Metal', 'W-64, Phase-2, Dombivli MIDC, Behind DNS Bank, Dombivli (East)-421204', '', '', NULL, NULL, 1, NULL, '', '27ADDFS9102R1ZH', NULL, 'shreejimcs@gmail.com'),
(240, 'Stallion Engineering Co.', '158-160, Narayan Dhruv Street, Ground Floor, Mumbai- 400 003', '', '23441255/40111255', NULL, NULL, 1, NULL, '', '27ADBFS9752R1Z3', NULL, 'stallion2k16@yahoo.com'),
(242, 'Innovative Enterprise', 'A-50, 4th Floor, Royal Ind Estate, Naigaon Cross Road, Wadala (W), Mumbai - 400031.', 'Abhishek', '24112321 Nikita', NULL, NULL, 1, NULL, 'Epson Printer', '27AAHPM1183F1ZS', NULL, 'abhishek@innovativeindia.com'),
(243, 'Excellent Infomatic', '20, Basement, A.C. Market, Tardeo Road, Mumbai- 400 034', 'Archana/Govardhan', '40228451 archana', NULL, NULL, 1, NULL, 'Epson Printer', '', NULL, 'archana@eipl.in'),
(245, 'JVM Industries', '80A, Virwani Industrial Estate, Western Express Highway, Goregaon (East), Mumbai 400063', 'Rupali/Kaustubh', '9870000586/8208997523 Kaustubh', NULL, NULL, 1, NULL, 'Sensor Calibration', '27AACFJ3300L3ZJ', NULL, 'sales@jvmlabs.com'),
(246, 'Autocal Solutions Pvt. Ltd.', 'Unit No.4, Ruby Industrial Estate, PCS ltd,, Dewan Rd, Navghar Road, Samarth Krupa Nagar, Vasai East, Vasai, Maharashtra 401210', 'Samina', '8450952497 Hitesh', NULL, NULL, 1, NULL, 'Sensor Calibration', '27AAGCA9480P1ZV', NULL, 'sales@autocal.net'),
(247, 'Parahex Process Controls', '210, Shree Krishna Ind. Estate, Ketki Pada, Nr. Dahisar Toll Naka, Dahisar (East), Mumbai- 400068', 'Mukesh', '022 28976290', NULL, NULL, 1, NULL, 'Earth Safety Relay', '27AFWPM5837L1ZH', NULL, 'parahex@gmail.com'),
(248, 'V.N. Hydro-Pneumatics', 'W-204, MIDC Phase 2, Near Sonarpada, Temple Naka, Dombivli(East)', '', '9987905668', NULL, NULL, 1, NULL, 'Tele. Pneumatic Cylinder', '27AOZPB8874Q1ZL', NULL, 'abhii.ny@gmail.com'),
(249, 'Advance Power Sources Limited', 'Survey No. 172/1, Plakee 2, Old GIDC, Gundlav-396035, Valsad Gujrat.', '', '02632-236799/98', NULL, NULL, 1, NULL, 'Welding Machine', '24AAJCA8292L1Z6', NULL, 'info@apsl.in'),
(250, 'Raj Tools', '9-A, Sarang Street, Khoka Bazar, Mumbai-400003', 'Hussain', '9821685901', NULL, NULL, 1, NULL, 'Extra Power Cutting Wheel', '', NULL, 'huzaifa_kapasi@yahoo.co.in'),
(251, 'Industrial Marketing Associates', '15, Bhakta Kutir Bhavan, Plot No. 72, Shivaee Nagar, Pokhran Road No.1, Thane (W) - 400 606', 'Suyog Gujar', '25853044/25881763/25883153', NULL, NULL, 1, NULL, 'Grundfos Pump', '', NULL, 'imabharat2018@gmail.com'),
(252, 'Polygon', 'Plot No 180 Sector ? 5, Charkop, Kandivali(W),Mumbai - 400 067.', 'Jayashree', 'ÿ22 ? 28672195', NULL, NULL, 1, NULL, 'PID Actuator Siemens', '', NULL, 'jayashree.phalke@invotec.in'),
(253, 'Zabs', '91, 2nd Floor, Ashoka Shopping Centre, L.T. Marg, Near Crawford Market, Mumbai - 400 001', 'Zarin', '22-22651841', NULL, NULL, 1, NULL, 'Pneumatic Valves', '', NULL, 'info@zabsindia.com'),
(254, 'Slicer', '234/A1, Hedavkar Wadi No. 2, Bhavani Shankar X Road, Dadar (W), Mumbai - 4000 028', 'Pradeep Shirvankar', '9870019995/9324085585', NULL, NULL, 1, NULL, 'Mounted Point/ Grinding top wheel', '27AZCPS4138E1ZD', NULL, 'slicergw@yahoo.co.in'),
(255, 'Universal Engineering Co.', '155, Narayan Dhuru Street, Mumbai - 400 003', 'Naresh Bhai', '9321127742', NULL, NULL, 1, NULL, 'Nut & Bolts', '27AAAFU9029P1ZI', NULL, 'uec.mumbai@gmail.com'),
(256, 'Rajesh Industries', '135, Narayan Dhuru Street, Mumbai-400003', '', '23112100', NULL, NULL, 1, NULL, 'Nut & Bolts (APL)', '27AAPFR6926F1ZR', NULL, 'rajeshindustries2@gmail.com'),
(257, 'J. Khushaldas & Co.', 'J K Chambers, 77-83, Nagdevi Street, Mumbai-400003', 'Nirupama', '23424219', NULL, NULL, 1, NULL, 'UHMW Polyethlene', '27AAHFJ2002Q1Z9', NULL, 'sales@jkushaldas.in'),
(259, 'Drier Chemical', '100, Dada Saheb Phalke Road, Dadar East, Mumbai, Maharashtra 400014', 'Rashmin Bhai', '2414 7051', NULL, NULL, 1, NULL, 'Silicon Pouch', '', NULL, ''),
(260, 'Shree Poly', 'Gala No. 24&25, Jamnadas Indl. Estate, Dr. R.P. Road, Near Jawahar Talkies, Mulund (West), Mumbai - 400 080.', '', '25678400/25678500/25678600', NULL, NULL, 1, NULL, 'Tarpaulins', '27ACHFS9139F1ZR', NULL, 'shreetarpaulins@gmail.com'),
(261, 'I. M. International', '31, Sarang Street, Mumbai - 400003', '', '23426175', NULL, NULL, 1, NULL, 'Spanner Screw Driver', '27AAAFI0900K1ZQ', NULL, 'im.trade@hotmail.com'),
(262, 'Shanti Mineral Water', '1, Mishra Sadan, B.S. Road, Opp. Police Stn, Dadar (W), Mumbai- 400028', 'Ajit (Harjeet Bedi)', '9869246722', NULL, NULL, 1, '', 'Water', '', NULL, ''),
(263, 'A.R. Engineers', 'Factory : 384, Bharucha Comp.,Agripada Indl. Premises, Sangeguruji Marg, Mumbai Central, Mumbai-400011', 'A. Rehman', '23001218', NULL, NULL, 1, NULL, 'Hose Nipple', '', NULL, 'ar.engineers71@gmail.com'),
(264, 'APEX Hyadrulic', '97, Morland Road, Mamsa End, State 4, E, Gala No. 4, Naya Nagar, Mumbai-400008', 'Sarwar Khan', '9820795048/9221576549/8693846042 OWNERS NOS.', NULL, NULL, 1, NULL, 'SS Hose', '', NULL, ''),
(265, 'Fazlehusein Asgarali & Co.', '196, Mutton Street, Null Bazar Market, Mumbai-400003', '', '23474765', NULL, NULL, 1, NULL, 'Tap/Drill', '27AAAFF1377R1ZY', NULL, 'fazlehuseinasgarali@yahoo.co.in');
INSERT INTO `supplier_list` (`id`, `name`, `address`, `cperson`, `contact`, `cperson_acc`, `contact_acc`, `status`, `category`, `subcategory`, `gst_number`, `rating`, `email`) VALUES
(266, 'Karan Industrial Corporation', 'G-14/B, Devkaran Mansion Compound, Pathakwadi, S.G. Marg, Princess Street, Mumbai-400 002', 'Nileshbhai', '49137213/9769789105', NULL, NULL, 1, NULL, 'Connectwell Dealer', '', NULL, 'rujutaent@gmail.com'),
(267, 'Abhishek Scientific Co.', '208, Shiva Industrial Estate, Lake Road, Near Tata Power House, Bhandup (W), Mumbai-400078', 'Tiwari', '', NULL, NULL, 1, '', 'Nano Bowl', '27ADUPT9796P1ZQ', 1, ''),
(268, 'Welcome', '49/51 G.K. Building, Vithaldas Lane, Near Shankar Temple, Lohar Chawl, Mumbai-400002', 'Rahul More', '', NULL, NULL, 1, NULL, 'Phillips 5Watts Panel Tube', '27AAAFW0823H1ZD', NULL, 'welcome_4951@hotmail.com'),
(269, 'Componex', 'D-112, Upvan Tower, Upper Govind Nagar, Malad (East), Mumbai-4000097', 'Sanjay Verma', '', NULL, NULL, 1, NULL, 'Bourn Dial', '27ACCPV1998C1ZB', NULL, 'sales.componex@gmail.com'),
(270, 'Jain Electricals & Engineers', '53, Cavel Cross Lane No. 3, 1st Floor, Ram Wadi (Bhaji Galli), Kalbadevi Road, Mumbai-400002', '', '9322855219', NULL, NULL, 1, NULL, 'Wago', '27AABPJ8217B1Z4', NULL, 'jainelec2@gmail.com'),
(271, 'Dataformatics Infotech', '8, Siddhrudh Building, 75 Bhawani Shankar Road, Dadar West, Mumbai 400028', '', '', NULL, NULL, 1, NULL, 'Sequrite Antivirus', '27AAMFD1965B1ZJ', NULL, 'sales@dataformatics.com'),
(272, 'First Base Solutions Pvt. Ltd.', 'Office No. 605 &618, 6th Flr. Navjivan Commercial Premises, Building No. 3, Mumbai Central, Mumbai-400008', '', '62633999 (100 Lines)', NULL, NULL, 1, NULL, 'Tally', '27AACCF8189L1ZY', NULL, 'tallybase@gmail.com'),
(274, 'Leelavati Automation Pvt. Ltd.', '21C, Saraswati Niwas, Kurla Kamgar Nagar, Kurla East, Mumbai ? 400024.', 'Sonali', '25220885/25220886', NULL, NULL, 1, NULL, 'SSR Carlo Gavazzi', '27AABCL9949B1ZC', NULL, 'support@leelavati.com'),
(275, 'Gayatri Tube India', '72, 2nd Pathan Street, (Kumbharwada 5th Lane), Mumbai-400004', '', '66394520/67437734', NULL, NULL, 1, NULL, '6x14 Tube 550Rs/Kg', '27AADFG9860F1Z7', NULL, 'gayatritubesindia@gmail.com'),
(278, 'Perfect Safety Equipment', 'Shop No. 16, Ganjawala Shopping Centre, S.V.P. Road, Borivali (W), Mumbai-400092', 'Kunal Anand', '28951895/28933012', NULL, NULL, 1, NULL, 'Shoes/Uniform', '', NULL, 'perfectsafety@gmail.com'),
(279, 'Panorama Industries', 'Shop No. 2&3, Padma Prabha Soc. Near Canara Bank, Bangur Nagar, Goregaon West, Mumbai-400104', '', '8657268885 Office', NULL, NULL, 1, NULL, 'Tea', '27AAEHA1021P1ZI', NULL, 'info@teacoffeevendingmachine.com'),
(280, 'Girnar Tea Dadar', 'Ginger Tea 415/kg & Lemon Tea 300/kg', 'Ketan Bhai', '', NULL, NULL, 1, NULL, 'Girnar Dadar', '', NULL, ''),
(281, 'Excel Envirotech', '4/25, Piramal Industrial Estate, S V Road, Goregaon West, Mumbai, Maharashtra 400062', 'Kamlesh', '', NULL, NULL, 1, NULL, '5micron pre filter', '', NULL, ''),
(282, 'Montana International', '71/73, Nagdevi Street, 2nd Floor, Mumbai-400003', 'Jitendra', '23455653', NULL, NULL, 1, NULL, 'Timing Pulley', '', NULL, 'montanainternational.keyur@gmail.com'),
(283, 'Western Rubber', 'Plot number 8, behind shreeji industrial estate,, chinchpada, Waliv Road, Vasai East, Waliv, Maharashtra 401208', 'Ketan Bhai', '', NULL, NULL, 1, NULL, 'Molded Mesh', '', NULL, ''),
(284, 'Lanco Pipes & Fitting', '4th Khumbarwada', 'Manish Gandhi', '9167676183', NULL, NULL, 1, NULL, 'SS Pipe', '', NULL, ''),
(285, 'Pharma Spares Pvt. Ltd.', 'Plot No. 7, Survey no. 241, Hissa No. 1, Village Gokhivare,Chinchpada, Vasai (E), Dist. Thane - 401 208', 'Shweta Sales', '7420086207', NULL, NULL, 1, NULL, 'Conemill Mesh', '27AAGCP5635D1ZG', NULL, 'sales@pharmaspares.com'),
(286, 'Aadinath Peripherals & Consumables', 'Shop No. 15, Ground Floor, 128/130 Bora Bazaar Street, Nr. Jain Medical Store, Fort', 'Alpesh Bhai', '66105438', NULL, NULL, 1, NULL, 'Brother Printer Access.', '', NULL, ''),
(288, 'R.K.S.S. Industries', 'Dawadi Naka, Gaikar Compound, Kalyan Shill Road, Dombivli (East) 421204', '', '', NULL, NULL, 1, NULL, 'MS Panel', '27AHEPP0850E1ZK', NULL, 'panchal.kishore59@gmail.com'),
(289, 'Baumuller', 'Kavi, S. No. 94/7, Plot - 38, Paud Road, Bhusari Colony, Kothrud, Pune- 411038', 'Prashant Kolte', '2040160312', NULL, NULL, 1, NULL, 'Servo Motors', '', NULL, 'prashant.kolte@baumuller.in'),
(290, 'Madan Trans Power', 'B-709, Neelkanth Business Park, Near Railway Station, Vidyavihar (W), Mumbai-400086', '', '25104350', NULL, NULL, 1, NULL, 'Bowex Coupling', '27AAVFM3838M1ZE', NULL, 'sameer@madantranspower.com'),
(291, 'Haren Shah/ Anish Shah', '90, Nagdevi Cross Lane, Mumbai -400003', '', '23448585/2585', NULL, NULL, 1, NULL, 'Bowex Coupling', '', NULL, 'translovejoy@gmail.com'),
(292, 'Ambetronics Engineers Pvt. Ltd.', '17-A,B,F,N & O, Tarun Industrial Estate, 3rd Floor, New Nagardas Road,Andheri (E), Mumbai- 400089', 'Anil Samgir', '61673000/66995525/28371143', NULL, NULL, 1, NULL, 'Datalogger', '27AAHCA0780B1Z2', NULL, 'sales1@ambetronics.com'),
(293, 'Close Contact', '43/347, 1st Floor, Unnat Nagar 4, M.G. Road, Goregaon West, Mumbai-400 090', 'Mr. Vaidya', '', NULL, NULL, 1, NULL, 'Proxy Sensor & Relay', '27ACBPK7942G1ZJ', NULL, ''),
(294, 'Spraytech Systems India Pvt. Ltd.', 'Plot No. R-513, MIDC TTC Industrial Area, Rabale, Navi Mumbai-400701', 'Ankush Jain', '25828929/2735/2736', NULL, NULL, 1, NULL, 'Spray Gun', '27AAKCS8770L', NULL, 'Vipul Dispatch 8452058452'),
(295, 'Leopard Investments Pvt. Ltd.', '', '', 'Pathak 9824122566/9136152171 Mukhiyaji', NULL, NULL, 1, NULL, 'We have sent ss panel door', '', NULL, ''),
(296, 'Mercantile Electric Corporation', '154, Kantilal Sharma Marg, Lohar Chawl, Mumbai-400002', 'Dilesh Bhai', '22089295/22062688/22060951/40029295', NULL, NULL, 1, NULL, 'Dowell Lugs same as schneider; Suraj Connectors', '27AAAFM0595N1ZX', NULL, 'mercantile1962@gmail.com'),
(297, 'Sweta Electric Corp.', '', '', '22087171', NULL, NULL, 1, NULL, 'Dowell Lugs same as schneider', '', NULL, ''),
(298, 'M.J. Traders', '', '', '22060992', NULL, NULL, 1, NULL, 'Dowell Lugs same as schneider', '', NULL, ''),
(299, 'Sonal Hardware Stores', 'Smitta Hardware Stores 83/87, Narayan Dhuru Street, Gr. Floor, Mumbai-400003', 'Nilesh', '23443628/23112380', NULL, NULL, 1, NULL, 'MS/SS Circlips', '', NULL, 'smittahw@yahoo.com'),
(300, 'Starlite Engineering Co.', '220, Nagdevi Street, Mumbai-400003', '', '23410397/66374717/23', NULL, NULL, 1, NULL, 'SS Cleaner/ Silicon Sealent tube', '27AAPFS0208M1ZW', NULL, 'info@starlite.net.in'),
(301, 'Cali Mic Industrial Products', '44, Nagdevi Cross Lane, Mumbai', 'Harshit Bhai', '23454596/23454597/30117727', NULL, NULL, 1, NULL, 'Totem Tap and Dienut will give flexible discount', '27AAJFC1985F1ZB', NULL, 'calimicind@gmail.com'),
(303, 'Digital Metrology', '79 & 80, A-Sector, 5th Cross, Muneswara Block, Amruthnagar, Opp. Kodigehalli Gate, Banglore-560092', 'Ganeshan', '7760830307/9845001429', NULL, NULL, 1, NULL, 'Kipp Locking Bolt', '29ADWPG8041Q1ZJ', NULL, 'digitalblr@gmail.com'),
(304, 'Power Trade Link', 'Office No. 30, 2nd Floor, Ashish Bldg., 40, Babu Genu Road, Mumbai-400002', 'Paumil Shah', '22113929/49137929', NULL, NULL, 1, NULL, 'Jainson Air Vent Echaust Panel Fan/Electro/Neotech', '27BJSPS4844L1Z8', NULL, 'powertradelink@gmail.com'),
(305, 'Mahendra Electric Corporation', '51, Shreeji Bhavan, 1st Floor, Mangaldas Road, Lohar Chawl, Mumbai-400002', 'Prabhav', '22007384/22066744', NULL, NULL, 1, NULL, 'Jainson Air Vent Echaust Panel Fan/Electro/Neotech', '27AADPS1570Q1Z4', NULL, 'mahendraelectriccorporation@gmail.com'),
(306, 'Bharat Engineering Stores', '31, Nagdevi Cross Lane, Ground Flr., Mumbai-400004', '', '9930187490', NULL, NULL, 1, NULL, 'Drill Tap etc.', '27AAAFB8262B1ZT', NULL, 'besbom3@yahoo.in'),
(307, 'Gaurav Pneumatics', 'A/201, Tokyo Houseÿ, J.P.Road , Andheri West, Mumbai -400 053', 'Dilip Deogoankar', '022 2674 4018', NULL, NULL, 1, NULL, 'Avcon Valves', '27AABPD4352K1ZV', NULL, 'gaurav_pneumatics1@hotmail.com'),
(308, 'Belimo Actuators India Pvt. Ltd. ', 'Plot No. 24/ABCD, Government Industrial Estate, Charkop, Kandavali West, Mumbai-400067', 'Sanjay Talreja', '40254800', NULL, NULL, 1, NULL, 'Damper Actuator/ Phase and bypass system', '', NULL, 'sanjay.talreja@belimo.ch'),
(309, 'Samson Controls Private Lim', '407,Marathon Max, Mulund-Goregaon Link Road, Near Nirmal Lifestyle,Mulund (West) - 400080', 'Paras Vaghela', '25917087', NULL, NULL, 1, NULL, 'Steam Control Valve ', '', NULL, ''),
(310, 'Spectra Connectronics LLP', '11/B, Muthul Industrial Estate, Kaman Satavali Road, Near Nicholas Garage, Waliv Village, Vasai East, Mumbai- 401 208.', 'Shweta', '022-26210023', NULL, NULL, 1, NULL, 'Wire Stripper/ Plier', '27ADGFS8222F1Z0', NULL, 'sales@spectra.in'),
(311, 'Nimble Electric', '', 'Sandeep/Uday', 'Banglore', NULL, NULL, 1, NULL, 'Torque Motors', '', NULL, 'sales@ucamind.com'),
(312, 'Mark Electriks', 'Plot No. 15, Gultekdi Industrial Estate, Pune 411037', 'Akshara Maladkar', '', NULL, NULL, 1, NULL, 'Torque Motors', '', NULL, 'markelectriks@vsnl.com'),
(313, 'GSN Electricals', 'Gala No. 2 Pratiksha Niwas, Datta Nagar, Virar Road, Nalasopara-401209', 'Ganesh Gurav', '', NULL, NULL, 1, NULL, 'Electric Volt & Ammeter', '27ANSPG8735B1ZS', NULL, 'gnelectricals2019@gmail.com'),
(314, 'R.M. Traders', '9A, Bibijan Street, Near Dr. Rangwala, Opp. Attari Enterprise, Mumbai-400003.', 'Raju Bhai', '9820861578', NULL, NULL, 1, NULL, 'Nylon Teflon Cut Rods', '', NULL, ''),
(315, 'Valison & Co.', '68/B, Sarang Street, Mumbai-400003.', '', '23426251/23447854/23438774', NULL, NULL, 1, NULL, 'Taparia', '27AAAFV5145M1ZU', NULL, 'sales@valisons.com'),
(316, 'Micro Traders', '237,  Madhani Estate, 542, Senapati Bapat Marg, Dadar (W), Mumbai- 400028', 'Hitesh Bhai', '24303807', NULL, NULL, 1, NULL, 'CNC Jobs & Turner', '27AAAFM4271B1ZP', NULL, ''),
(317, 'Abrazo Industries', 'Abdulla Mansion 32, Mirza Street, Off. A. R. Street, Mumbai-400003', '', '23447148/23414792', NULL, NULL, 1, NULL, 'Wire Mesh 50/250', '27AACPM2598R1ZV', NULL, 'abrazo_ind@hotmail.com'),
(318, 'Laxmi Sales Corporation', 'Laheri Buildingm, Shop NO. 13, 274, S.V.P. Road, 4th Khetwadi Corner, Mumbai-400004', '', '23851035', NULL, NULL, 1, NULL, 'Buffing Soap', '27AAFPS0023C1Z9', NULL, ''),
(320, 'Tejas Enterprise', '72/74, Nagdevi Street, Unique House, 2nd Floor, Off. 2, Masjid Bunder, Mumbai-400003', 'Tejas Shah', '66154264/23117394', NULL, NULL, 1, NULL, 'Gedore', '27AAEPS3891F1ZE', NULL, ''),
(321, 'Gouary Industries', '3/9, Sudhama Ashram, Gamdevi Road, Bhandup (W), Mumbai-400078', '', '25952002', NULL, NULL, 1, NULL, 'Hydraulic Cylinder Manufacturer/Press Machine', '27ADWPV8121E1Z0', NULL, 'gouary.ind@gmail.com'),
(322, 'PT Instruments Pvt. Ltd.', '204-D, Twin Arcade, Military Road, Marol, Andheri (E), Mumbai-59', '', '42250505', NULL, NULL, 1, NULL, '1.1 Product Temp. Connector LEMO Make', '27AAACP6603P1Z3', NULL, 'response@ptinstruments-lemo.com'),
(323, 'IBK Engineers Pvt. Ltd.', '1/2 A , Eshwara Temple Road, Doddakallasandra, Off Kanakapura Road, Bangalore - 560 062', 'Ms. Rani', '80 26321112/ +9180 41510061', NULL, NULL, 1, NULL, 'McMaster-Carr products', '', NULL, 'rani.m@ibizkart.com'),
(324, 'Dynamic Technologies', '2-2-1100/1-A, Jaya Residency, New Nallakunta, Hyderabad-500044', 'Raguraj ji', '', NULL, NULL, 1, NULL, 'Southco products', '', NULL, 'sales@dynamic-technologies.in'),
(325, 'Savariya Auto Spare Parts', 'Near Suyog Hotel, Milap Nagar, Highway Naka, Kalyan Shil Road, Dombivli East', '', '', NULL, NULL, 1, NULL, 'Hydraulic Oil HP Enclo 68 26Lit. @ 2150/-', '', NULL, ''),
(326, 'KTC Industries / Sreeram Industries', 'Plot No. 2A, Near Shalimar Industrial Estate, Matunga Labour Camp, Matunga, Mumbai-400019', '', '', NULL, NULL, 1, NULL, 'Flexible Shaft Grinder /Flexible Rubber Shaft', '27ANSPS3961K1Z3', NULL, 'ktcindustries@gmail.com'),
(327, 'Neutron Power Tools', '467, Siddheshwari Estate, Near Sadvichar Eye Hospital, G.I.D.C. Naroda, Ahmedabad', '', '', NULL, NULL, 1, NULL, 'Flexible Shaft Grinder', '24AABFN3981G1ZC', NULL, 'info@neutronpowertools.com'),
(328, 'Moosa Haji Patrawala Pvt. Ltd.', 'Moosa Haji Industrial Estate, 20DR.E. Moses Road, Mahalaxmi, Mumbai-400011.', 'Nabil/Salim', '08976347595/09029071321', NULL, NULL, 1, NULL, 'Dehumidifier', '27AAACM3053G1ZR', NULL, 'salimyakub@gmail.com'),
(329, 'Paras Machine Tools', '158,160, Narayan Dhuru Street, 1st Floor, Mumbai-400003', '', '9820151797', NULL, NULL, 1, NULL, 'Chuck/Lathe machine parts', '27AAVFP7347J1ZD', NULL, 'shahatul1008@gmail.com'),
(330, 'Santram Engineers Pvt. Ltd.', '808, Iscon Elegance, Nr. Jain Temple, Circle-P, Prahaladnagar, Cross Road, S.G. Highway, Ahmedabad', '', '', NULL, NULL, 1, NULL, 'Gearbox/Motor', '24AADCS0491D1ZQ', NULL, 'accounts@santramengineers.com'),
(331, 'Swiss Precision Redefined', 'Works: Gala No. 01, 1B, Ground Floor, Bldg. No. 03, Rajprabha Mohan Indl. Estate, Waliv, Vasai (E), Dist-Palghar-401 208.   Regd. Office: A-102, Shree Vardhaman CHS Ltd., Sodawala Lane, Borivali (W), Mumbai-400092.', '', '8068343896', NULL, NULL, 1, NULL, 'Wheels', '27ABAPG3971B1Z3', NULL, 'info@swissgroupworld.com'),
(332, 'Omicron Sensing Pvt. Ltd.', '721-723, Goldcreast Business Park, Opp. Shreyas Cinemas, LBS Marg, Ghatkopar (W), Mumbai-400086', 'Nikita/Priyanka', '25007007/49766866', NULL, NULL, 1, NULL, 'DP Gauge', '27AABCO5632L1Z8', NULL, 'sales@omicron.in'),
(333, 'Sunbeam Industries', '30/1, Meghal Industrial Estate, Devi Dayal Road, Mulund (W), Mumbai-400080', 'Vikrant Rao', '9320078019', NULL, NULL, 1, NULL, 'Rotex Dealer', '27AFUPR5418L1ZL', NULL, 'sunbeam231@gmail.com'),
(334, 'Deep Machinery Spares', 'B-30, C/1, Mahavir Jain CHS, Shankar Lane, Kandivali West Mumbai', '', '9820654599', NULL, NULL, 1, NULL, 'SS Anti Vibration Pad', '27ABEPS1579H1ZB', NULL, 'deepmachineryspares@yahoo.com'),
(335, 'Sreeram Industries', 'Plot No. 2A, Near Shalimar Industrial Estate, Matunga Labour Camp, Matunga, Mumbai-400019', '', '', NULL, NULL, 1, NULL, 'Flexible Rubber Shaft', '27ABXFS0960R1Z1', NULL, 'sales@sreeram.in'),
(336, 'Siddhivinayak Enterprise', '61/73, Narayan Dhuru Street, Gr. Flr., Mumbai-400003', 'Darshan Parekh', '23112333/23112666', NULL, NULL, 1, NULL, 'Nut Bolt Dom Nut', '', NULL, ''),
(338, 'Chintamani Engineering Services', 'A4/204, Shankheshwar Nagar, Manpada Road, Opp. Shani Mandir, Dombiali East -421201', '', '', NULL, NULL, 1, NULL, 'Laser Cutting', '27AFAPM8922L1Z3', NULL, 'marathemc@gmail.com'),
(339, 'Micon Automation Systems Pvt. Ltd.', 'A-814, Siddhi Vinayak Towers, Behind DCP Office, Off. S.G. Road, Makarba, Ahmedabad-380051', 'Parth ', '079-32900400/29701448', NULL, NULL, 1, NULL, 'Data Repeater', '24AAECM7879C1ZB', NULL, 'info@miconindia.com'),
(341, 'Sahajanand Sales Corporation', 'Shop No. 110-D, Nagdevi Street, Mumbai-400003', 'Sanjeev', '23413837/23447087', NULL, NULL, 1, NULL, 'Neoprene Lock Gasket 3/4\" Rs. 170 per meter\"', '', NULL, 'sahajanandsales@gmail.com'),
(342, 'Deluxe Electrical Corporation', 'Kanji Gokuldas Bldg. 2nd Floor, 158, Lohar Chawl, Mumbai- 400002', 'Gyanchand Jain', '22057088/39567601', NULL, NULL, 1, NULL, 'Meco Dealer/ Clamp Meter/ Tacho Meter', '27AAHPM5670M1Z5', NULL, 'gcjain1958@gmail.com'),
(343, 'Meco Instruments Pvt. Ltd.', 'Plot No. EL-60., Electronic Zone, MIDC Industrial Area, Mahape, Navi Mumbai, Maharashtra 400710', 'Sadanand', '27636162', NULL, NULL, 1, NULL, 'Meco Service/Repair', '', NULL, ''),
(344, 'Davison Instruments Pvt. Ltd.', 'Gala No. 113, Building No. 4, Nirav Industrial Estate, Gaondevi, Sativali, Vasai East, Dist. Palghar-401208', 'Nandu Sales Person', '', NULL, NULL, 1, NULL, 'Float Switch', '27AAFCD5398C1ZJ', NULL, ''),
(345, 'Durga Engineering Works', '3340(2), Bharat Coal Compound, Bail Bazar, Kale Marg, Old Kurla, Kurla-West', '', '', NULL, NULL, 1, NULL, 'Speronizer Body & Motor Plate Machining', '27AANFD6183E1Z8', NULL, 'durga_engg_works@yahoo.co.in'),
(346, 'Trinity Electric Syndicate', '154, Shamaldas Gandhi Marg, Mumbai-400 002.', '', '40181818', NULL, NULL, 1, NULL, 'Press Switch Alfa Make', '27AAAFT0659G1Z8', NULL, 'sales@trinityswitchgear.com'),
(347, 'Mehta Sanghvi & Co.', 'Unit No. 21, Gokul Industrial Estate, Plot No. 150, Marol, M.V. Road, Andheri (East), Mumbai-400059', '', '28592847', NULL, NULL, 1, NULL, 'Weld brite K2 Paste', '27AAEFM5114F1ZL', NULL, ''),
(348, 'Divyatejaswi Engineering', 'Plot No. 74, Sharnam Estate, Kathwada G.I.D.C., Singarwa Road, Ahmadabad-382430', '', '', NULL, NULL, 1, NULL, 'FBD 30 & 60 Body', '24AOTPP7836A1Z0', NULL, 'hasmukhpanchal410@gmail.com'),
(349, 'Anshul Life Sciences', '4th Floor, 410, Jagdamba House, Peru Baug, Goregaon East, Mumbai-400063', '', '02245045566-70', NULL, NULL, 1, NULL, 'Lactose for trials', '', NULL, ''),
(350, 'Chemfield Cellulose Pvt. Ltd.', 'B-40/6&7, MIDC Area, Kaimeshwar, Dist. Nagpur Pin : 441501', '', '07118-271489,272172', NULL, NULL, 1, NULL, 'MCCP PH 101 MC', '27AABCF8364J1ZA', NULL, ''),
(352, 'PSamirCo Trading Pvt. Ltd.', '59, Nagdevi street, 2nd floor,\r\nMumbai, Maharashtra, 400003', 'Jay', '9820804655', NULL, NULL, 1, 'Kluber High Temp, Grease, Bearings', 'HIWIN, NSK, IKO, RHP', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_info`
--

CREATE TABLE `system_info` (
  `id` int(30) NOT NULL,
  `meta_field` text NOT NULL,
  `meta_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_info`
--

INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`) VALUES
(1, 'name', 'HUGOPHARM'),
(6, 'short_name', 'HUGO'),
(11, 'logo', 'uploads/logo-1737439360.png'),
(13, 'user_avatar', 'uploads/user_avatar.jpg'),
(14, 'cover', 'uploads/cover-1735271794.png'),
(15, 'content', 'Array');

-- --------------------------------------------------------

--
-- Table structure for table `usage_history`
--

CREATE TABLE `usage_history` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `used_by` varchar(250) NOT NULL,
  `quantity` float NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(50) NOT NULL,
  `firstname` varchar(250) NOT NULL,
  `middlename` text DEFAULT NULL,
  `lastname` varchar(250) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `avatar` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `username`, `password`, `avatar`, `last_login`, `type`, `date_added`, `date_updated`) VALUES
(1, 'Aryan', NULL, 'Jain', 'aryan', 'e2071528cf1aa685779d9898ccd9b308', 'uploads/avatar-1.png?v=1736313175', NULL, 1, '2021-01-20 14:02:37', '2025-05-20 01:23:21'),
(16, 'JUIE', NULL, 'SINGH', 'Juiesingh', 'b9eb41716c4011acbf913aaa488884f0', NULL, NULL, 1, '2025-05-16 02:40:58', NULL),
(17, 'HIREN', NULL, 'PANCHAL', 'Hiren', 'abcc4d4c301d2ca9e28cbd1c1134a0bc', NULL, NULL, 1, '2025-05-16 02:41:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_meta`
--

CREATE TABLE `user_meta` (
  `user_id` int(30) NOT NULL,
  `meta_field` text NOT NULL,
  `meta_value` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approvers`
--
ALTER TABLE `approvers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `back_order_list`
--
ALTER TABLE `back_order_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `receiving_id` (`receiving_id`);

--
-- Indexes for table `bo_items`
--
ALTER TABLE `bo_items`
  ADD KEY `item_id` (`item_id`),
  ADD KEY `bo_id` (`bo_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `item_attributes`
--
ALTER TABLE `item_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `item_list`
--
ALTER TABLE `item_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Indexes for table `lead_documents`
--
ALTER TABLE `lead_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Indexes for table `machine_list`
--
ALTER TABLE `machine_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `po_items`
--
ALTER TABLE `po_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`);

--
-- Indexes for table `po_timeline`
--
ALTER TABLE `po_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `po_timeline_files`
--
ALTER TABLE `po_timeline_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timeline_id` (`timeline_id`);

--
-- Indexes for table `proforma_invoice_items`
--
ALTER TABLE `proforma_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proforma_invoice_id` (`proforma_invoice_id`);

--
-- Indexes for table `proforma_invoice_list`
--
ALTER TABLE `proforma_invoice_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_code` (`po_code`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_company` (`company`),
  ADD KEY `idx_date` (`po_date_created`),
  ADD KEY `idx_client` (`client_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `po_code` (`po_code`);

--
-- Indexes for table `purchase_order_list`
--
ALTER TABLE `purchase_order_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receiving_list`
--
ALTER TABLE `receiving_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `return_list`
--
ALTER TABLE `return_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `sales_list`
--
ALTER TABLE `sales_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_list`
--
ALTER TABLE `stock_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `supplier_list`
--
ALTER TABLE `supplier_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_info`
--
ALTER TABLE `system_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usage_history`
--
ALTER TABLE `usage_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_meta`
--
ALTER TABLE `user_meta`
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approvers`
--
ALTER TABLE `approvers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `back_order_list`
--
ALTER TABLE `back_order_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `item_attributes`
--
ALTER TABLE `item_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=520;

--
-- AUTO_INCREMENT for table `item_list`
--
ALTER TABLE `item_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `lead_documents`
--
ALTER TABLE `lead_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `machine_list`
--
ALTER TABLE `machine_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `po_items`
--
ALTER TABLE `po_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `po_timeline`
--
ALTER TABLE `po_timeline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `po_timeline_files`
--
ALTER TABLE `po_timeline_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `proforma_invoice_items`
--
ALTER TABLE `proforma_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=603;

--
-- AUTO_INCREMENT for table `proforma_invoice_list`
--
ALTER TABLE `proforma_invoice_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `purchase_order_list`
--
ALTER TABLE `purchase_order_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `receiving_list`
--
ALTER TABLE `receiving_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `return_list`
--
ALTER TABLE `return_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_list`
--
ALTER TABLE `sales_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stock_list`
--
ALTER TABLE `stock_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `supplier_list`
--
ALTER TABLE `supplier_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=353;

--
-- AUTO_INCREMENT for table `system_info`
--
ALTER TABLE `system_info`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `usage_history`
--
ALTER TABLE `usage_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bo_items`
--
ALTER TABLE `bo_items`
  ADD CONSTRAINT `bo_items_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item_list` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bo_items_ibfk_2` FOREIGN KEY (`bo_id`) REFERENCES `back_order_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `item_attributes`
--
ALTER TABLE `item_attributes`
  ADD CONSTRAINT `item_attributes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD CONSTRAINT `lead_activities_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_documents`
--
ALTER TABLE `lead_documents`
  ADD CONSTRAINT `lead_documents_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `lead_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `po_items`
--
ALTER TABLE `po_items`
  ADD CONSTRAINT `po_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_order_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `po_timeline`
--
ALTER TABLE `po_timeline`
  ADD CONSTRAINT `po_timeline_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `po_timeline_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `proforma_invoice_items`
--
ALTER TABLE `proforma_invoice_items`
  ADD CONSTRAINT `proforma_invoice_items_ibfk_1` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoice_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proforma_invoice_list`
--
ALTER TABLE `proforma_invoice_list`
  ADD CONSTRAINT `proforma_invoice_list_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`po_code`) REFERENCES `proforma_invoice_list` (`po_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
