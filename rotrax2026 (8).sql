-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 21, 2026 at 09:59 AM
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
-- Database: `rotrax2026`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'rotraxproject@gmail.com', 'rotrax@123');

-- --------------------------------------------------------

--
-- Table structure for table `otp_table`
--

CREATE TABLE `otp_table` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_table`
--

INSERT INTO `otp_table` (`id`, `email`, `otp`, `expires_at`) VALUES
(3, 'shwetapatil7946@gmail.com', '186625', '2026-03-14 07:24:51'),
(7, 'manasiombale6440@gmail.com', '903524', '2026-03-17 07:22:13'),
(8, 'tanvishirke7@gmail.com', '204919', '2026-03-18 07:00:36'),
(13, 'pawarshreyaganpat25@gmail.com', '393386', '2026-04-03 10:08:23'),
(14, 'sejalshinde888@gmail.com', '762608', '2026-04-22 17:23:54'),
(18, 'pranalisabale94@gmail.com', '892990', '2026-05-18 12:08:05'),
(19, 'ankitarajendrakulkarni15@gmail.com', '608052', '2026-05-18 12:28:05'),
(20, 'sujalshinde318@gmail.com', '278183', '2026-05-18 18:05:07'),
(21, 'sujalshin08@gmail.com', '674315', '2026-05-18 18:11:35'),
(22, 'shindegaurav556@gmail.com', '515244', '2026-05-21 09:26:13'),
(23, 'ganesh.shinde@mahity.com', '751791', '2026-08-20 12:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `order_id` varchar(100) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `breakfast_done` tinyint(1) DEFAULT 0,
  `lunch_done` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_proposals`
--

CREATE TABLE `project_proposals` (
  `id` int(11) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `requirements` text DEFAULT NULL,
  `project_title` varchar(200) NOT NULL,
  `project_theme` varchar(150) NOT NULL,
  `branch_department` varchar(150) NOT NULL,
  `department` varchar(150) DEFAULT NULL,
  `project_discipline` text DEFAULT NULL,
  `project_description` text NOT NULL,
  `innovative_features` text NOT NULL,
  `tools_technologies` text NOT NULL,
  `expected_outcome` text NOT NULL,
  `attachment_file` varchar(255) DEFAULT NULL,
  `declaration` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `token_number` varchar(50) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `signed_project_pdf` varchar(255) DEFAULT NULL,
  `bonafide_file` varchar(255) DEFAULT NULL,
  `self_eval_file` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `user_registered_id` varchar(255) DEFAULT NULL,
  `abstract_pdf_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_proposals`
--

INSERT INTO `project_proposals` (`id`, `user_email`, `requirements`, `project_title`, `project_theme`, `branch_department`, `department`, `project_discipline`, `project_description`, `innovative_features`, `tools_technologies`, `expected_outcome`, `attachment_file`, `declaration`, `created_at`, `status`, `token_number`, `approved_at`, `signed_project_pdf`, `bonafide_file`, `self_eval_file`, `is_locked`, `user_registered_id`, `abstract_pdf_path`) VALUES
(1, 'pawarshreyaganpat25@gmail.com', 'sejres', 'dfhsdhfdsjfhfhjffsj', 'Agentic AI and the Future of Automation', '', NULL, 'MCA', 'djjfdhjkf', 'jfghg', 'fghdgdh', 'rihtrkt', 'pawarshreyaganpat25@gmail.com_principal_letter.pdf', 1, '2026-04-03 05:04:47', 'Pending', NULL, NULL, NULL, 'pawarshreyaganpat25@gmail.com_bonafide.pdf', 'uploads/evaluations/EVAL_1775193041_0df826fe.pdf', 0, NULL, NULL),
(2, 'sejalshinde888@gmail.com', 'dfgf', 'dfgdg', 'E-Commerce Web Application', '', NULL, 'MCA', 'dfg', 'fdgf', 'dgdf', 'fddgdg', '', 1, '2026-04-24 09:51:01', 'Pending', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(3, 'pranalisabale94@gmail.com', 'wifi', 'Food waste Management system', 'E-Commerce Web Application', '', NULL, 'MCA', 'abc', 'abc', 'php', 'abc', '', 1, '2026-05-18 10:05:30', 'Pending', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(4, 'ankitarajendrakulkarni15@gmail.com', 'no', 'Kishor Events and Services', 'E-Commerce Web Application', '', NULL, 'MCA', 'aba', 'abc', 'php', 'abc', '', 1, '2026-05-18 10:27:45', 'Pending', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(5, 'sujalshin08@gmail.com', 'Laptop, Wi-Fi', 'Kishor Events and Services', 'Software Development & Application Engineering', '', NULL, 'Computer Engineering & Allied', 'This website is developed to manage the event related operations.', 'Event Booking, Feedback, Inquiry etc.', 'PHP, HTNL, CSS', 'Handle Event Related operations successfully', '', 1, '2026-05-18 16:07:10', 'Pending', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(6, 'sujalshinde318@gmail.com', 'Laptop, Wi-Fi', 'Food waste Management system', 'Software Development & Application Engineering', '', NULL, 'BCA', 'Developed to manage the waste of food and help needy people', 'donate food, order food, manage waste of food and help needy people', 'PHP, HTML, CSS', 'Help needy people', '', 1, '2026-05-18 16:09:01', 'Pending', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(7, 'shindegaurav556@gmail.com', 'yfy', 'gfbhhb', 'Smart Healthcare Systems', '', NULL, 'Computer Engineering & Allied', 'hbyyvy', 'gftrf', 'ygyf6t', 'jhvy', '', 1, '2026-05-21 07:29:21', 'Pending', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `stream` varchar(50) NOT NULL,
  `program` varchar(50) NOT NULL,
  `discipline` varchar(100) DEFAULT NULL,
  `theme` varchar(50) NOT NULL,
  `region` varchar(50) NOT NULL,
  `completed_steps` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `user_email`, `stream`, `program`, `discipline`, `theme`, `region`, `completed_steps`, `created_at`, `updated_at`) VALUES
(1, 'pawarshreyaganpat25@gmail.com', '', 'PG', 'MCA', 'IoT & Embedded Systems', 'Jalna', 1, '2026-04-03 08:03:19', '2026-04-03 08:03:19'),
(2, 'sejalshinde888@gmail.com', '', 'PG', 'MCA', 'E-Commerce Web Application', 'Satara', 1, '2026-04-22 15:15:47', '2026-04-22 15:15:47'),
(3, 'pranalisabale94@gmail.com', '', 'PG', 'MCA', 'E-Commerce Web Application', 'Satara', 1, '2026-05-18 10:00:00', '2026-05-18 10:00:00'),
(4, 'ankitarajendrakulkarni15@gmail.com', '', 'PG', 'MCA', 'E-Commerce Web Application', 'Satara', 1, '2026-05-18 10:21:35', '2026-05-18 10:21:35'),
(5, 'sujalshinde318@gmail.com', '', 'UG', 'BCA', 'Software Development & Application Engineering', 'Pune', 1, '2026-05-18 15:57:34', '2026-05-18 15:57:34'),
(6, 'sujalshin08@gmail.com', '', 'UG', 'Computer Engineering & Allied', 'Software Development & Application Engineering', 'Pune', 1, '2026-05-18 16:03:48', '2026-05-18 16:03:48'),
(7, 'admin_config@innovahub.com', 'NA', 'MCA', 'Computer Application', 'Software Development', 'Satara', 1, '2026-05-19 17:28:11', '2026-05-19 17:28:11'),
(8, 'shindegaurav556@gmail.com', '', 'UG', 'Computer Engineering & Allied', 'Smart Healthcare Systems', 'Bhandara', 1, '2026-05-21 07:22:22', '2026-05-21 07:22:22'),
(9, 'ganesh.shinde@mahity.com', '', 'UG', 'Computer Engineering & Allied', 'Smart Healthcare Systems', 'Satara', 1, '2026-08-20 10:12:10', '2026-08-20 10:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `registration_payments`
--

CREATE TABLE `registration_payments` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `payment_id` varchar(100) NOT NULL,
  `receipt_uploaded` int(1) DEFAULT 1,
  `order_id` varchar(100) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'Success',
  `receipt_file` varchar(255) DEFAULT NULL,
  `final_submitted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_payments`
--

INSERT INTO `registration_payments` (`id`, `email`, `fullname`, `phone`, `payment_id`, `receipt_uploaded`, `order_id`, `category`, `amount`, `status`, `receipt_file`, `final_submitted`) VALUES
(1, 'pranalisabale94@gmail.com', 'Pranali Pradip Sabale', '7219798204', 'pay_SqmvknUlTXEzgB', 1, '', 'PG', 750, 'Success', NULL, 0),
(2, 'ankitarajendrakulkarni15@gmail.com', 'Ankita Rajendra Kulkarni', '9356860540', 'pay_SqnI4RG09zQP23', 1, '', 'PG', 750, 'Success', NULL, 0),
(3, 'shindegaurav556@gmail.com', 'Gaurav Shashikant Shinde', '9325817337', 'pay_SrvrGyeACmKrzY', 1, '', 'UG', 750, 'Success', 'receipt_pay_SrvrGyeACmKrzY_1779348747.pdf', 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_feedback`
--

CREATE TABLE `student_feedback` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_email` varchar(150) NOT NULL,
  `course` varchar(50) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `feedback` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','open','solved') DEFAULT 'pending',
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `solved_at` datetime DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_email`, `subject`, `category`, `priority`, `status`, `message`, `created_at`, `solved_at`, `screenshot_path`) VALUES
(1, 'shwetapatil7946@gmail.com', 'gdhgjdfgdfjsgd', 'General', 'medium', 'solved', 'jfhdsjfsjfdfsgjfd', '2026-04-02 10:42:17', '2026-04-02 11:03:56', 'uploads/tickets/1775106737_img4.jpg'),
(2, 'shwetapatil7946@gmail.com', 'gdhgjdfgdfjsgd', 'General', 'medium', 'solved', 'ksdjfhaeruiohi', '2026-04-02 12:23:04', '2026-04-02 12:23:18', 'uploads/tickets/1775112784_img3.jpg'),
(3, 'shwetapatil7946@gmail.com', 'gdhgjdfgdfjsgd', 'General', 'medium', '', 'ksdjfhaeruiohi', '2026-04-02 12:42:38', NULL, 'uploads/tickets/1775113958_img3.jpg'),
(4, 'shwetapatil7946@gmail.com', 'Payment Issue', 'General', 'medium', '', 'The QR is not visible', '2026-04-02 13:10:32', NULL, 'uploads/tickets/1775115632_img3.jpg'),
(5, 'shwetapatil7946@gmail.com', 'fgdgfdg', 'General', 'medium', '', 'ggdgf', '2026-04-02 13:18:19', NULL, 'uploads/tickets/1775116099_img2.jpg'),
(6, 'shwetapatil7946@gmail.com', 'dsdfs', 'General', 'medium', '', 'cgcgcgg', '2026-04-02 13:30:27', NULL, 'uploads/tickets/1775116827_img3.jpg'),
(7, 'sejalshinde888@gmail.com', 'Payment Successful but Status still \\\"Pending\\\"', 'General', 'medium', '', 'Hi Team, I completed my registration payment of ₹500 via UPI today at 2:30 PM. The amount has been debited from my bank account, but my dashboard still shows \\\"Action Required: Pay Now.\\\" Please verify my transaction.', '2026-04-02 21:55:41', NULL, 'uploads/tickets/1775147141_self evaluation.pdf'),
(8, 'pawarshreyaganpat25@gmail.com', 'adc', 'General', 'medium', '', 'adc', '2026-04-03 10:42:42', NULL, 'uploads/tickets/1775193162_Receipt_pay_SYtk7vkDKVfc2j.pdf'),
(9, 'pawarshreyaganpat25@gmail.com', 'payment issue', 'General', 'medium', 'solved', 'status is showing unpaid', '2026-04-03 10:44:21', '2026-04-03 10:45:43', 'uploads/tickets/1775193261_Receipt_pay_SYtk7vkDKVfc2j.pdf'),
(10, 'sejalshinde888@gmail.com', 'dhgfgdshf', 'General', 'medium', '', 'kjjkjk', '2026-05-19 22:16:22', NULL, 'uploads/tickets/1779209182_img3.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `team_information`
--

CREATE TABLE `team_information` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `team_name` varchar(150) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `guide_name` varchar(150) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `contact_number` varchar(10) NOT NULL,
  `alt_contact_number` varchar(10) DEFAULT NULL,
  `guide_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pass_token` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `payment_status` varchar(20) DEFAULT 'Pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_information`
--

INSERT INTO `team_information` (`id`, `user_email`, `team_name`, `college_name`, `guide_name`, `branch`, `designation`, `contact_number`, `alt_contact_number`, `guide_email`, `created_at`, `pass_token`, `is_locked`, `payment_status`, `transaction_id`, `amount_paid`) VALUES
(2, 'shwetapatil7946@gmail.com', 'ALPHA', 'YSPM Satara', 'SUJAL SHINDE', 'MCA', 'Professor', '8596584785', '9145202635', 'manasiombale6440@gmail.com', '2026-03-14 07:15:21', NULL, 0, 'Pending', NULL, 0.00),
(4, 'sujalshin08@gmail.com', 'XYZ', 'YSPM satara', 'VAISHALI TANAJI SHINDE', 'MCA', 'Professor', '9860549846', '8007675471', 'manasiombale6440@gmail.com', '2026-03-17 04:46:19', NULL, 0, 'Pending', NULL, 0.00),
(5, 'manasiombale6440@gmail.com', 'YTC', 'YSPM Satara', 'SUJAL SHINDE', 'MCA', 'Professor', '9145202635', '8007675471', 'sujalshinde318@gmail.com', '2026-03-18 07:28:50', NULL, 0, 'Pending', NULL, 0.00),
(6, 'pawarshreyaganpat25@gmail.com', 'DGF', 'YSPM Satara', 'SUJAL SHINDE', 'MCA', 'Professor', '9145202635', '8007675471', 'sujalshinde318@gmail.com', '2026-03-18 07:54:27', NULL, 0, 'Pending', NULL, 0.00),
(7, 'tanvishirke7@gmail.com', 'ABC', 'YSPM satara', 'SEJAL SHANTARAM SHINDE', 'MCA', 'Professor', '7768809303', '9145202635', 'janhavishinde636@gmail.com', '2026-03-19 14:54:45', NULL, 0, 'Pending', NULL, 0.00),
(8, 'sejalshinde888@gmail.com', 'HFHF', 'ffdhdh', 'SUJAL SHINDE', 'MCA', 'Professor', '8596584785', '8958478596', 'manasiombale6440@gmail.com', '2026-04-02 16:20:49', NULL, 0, 'Pending', NULL, 0.00),
(9, 'pranalisabale94@gmail.com', 'BETA', 'YSPM Satara', 'VANMALA KADAM', 'MCA', 'Professor', '8007675471', '9852643852', 'vkadam@gmail.com', '2026-05-18 10:04:26', NULL, 0, 'Pending', NULL, 0.00),
(10, 'ankitarajendrakulkarni15@gmail.com', 'GAMMA', 'YTC Satara', 'PROF JEEVIKA KADAM', 'MCA', 'Professor', '8007675471', '9356860540', 'jkadam@gmail.com', '2026-05-18 10:24:13', NULL, 0, 'Pending', NULL, 0.00),
(11, 'sujalshinde318@gmail.com', 'SIGMA', 'YSPM satara', 'JANHAVI SHANTARAM SHINDE', 'BCA', 'Professor', '9356860540', '8958478985', 'ankitakulkarni152004@gmai.com', '2026-05-18 15:59:11', NULL, 0, 'Pending', NULL, 0.00),
(12, 'shindegaurav556@gmail.com', 'HVTCNTTN', 'hfggcftgbhu', 'GVGBHBHH', 'dgvv', 'tgfb', '6578945654', '4589662573', 'abc@123gmail.com', '2026-05-21 07:28:24', NULL, 0, 'Pending', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `enrollment_prn` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `mobile_no` varchar(10) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `email` varchar(255) NOT NULL,
  `member_id_code` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `breakfast_served` tinyint(1) DEFAULT 0,
  `lunch_served` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `student_name`, `branch`, `enrollment_prn`, `year`, `mobile_no`, `photo_path`, `gender`, `email`, `member_id_code`, `photo`, `created_at`, `breakfast_served`, `lunch_served`) VALUES
(1, 6, 'SHREYA GANPAT PAWAR', 'MCA', '646444664644', 2, '8955748596', NULL, 'Female', 'pawarshreyaganpat25@gmail.com', 'T-JN-RTX2026-01-01', 'uploads/photos/photo_T-JN-RTX2026-01-01_1775203451.jpeg', '2026-04-03 08:04:11', 0, 0),
(2, 8, 'SEJAL SHANTARAM SHINDE', 'MCA', '896587485965875', 2, '8928458864', NULL, 'Female', 'sejalshinde888@gmail.com', 'T-ST-RTX2026-02-01', 'uploads/photos/photo_T-ST-RTX2026-02-01_1776871000.jpeg', '2026-04-22 15:16:40', 0, 0),
(3, 9, 'PRANALI PRADIP SABALE', 'MCA', '569358478565433', 1, '7219798204', NULL, 'Female', 'pranalisabale94@gmail.com', 'T-ST-RTX2026-03-01', '', '2026-05-18 10:04:26', 0, 0),
(15, 10, 'ANKITA RAJENDRA KULKARNI', 'commerce', '7219798204', 1, '7219798204', NULL, 'Female', 'ankitarajendrakulkarni15@gmail.com', 'T-ST-RTX2026-04-01', '', '2026-05-18 10:26:56', 0, 0),
(16, 11, 'JANHAVI SHANTARAM SHINDE', 'BCA', '896587485965875', 2, '8928458864', NULL, 'Female', 'sujalshinde318@gmail.com', 'T-PU-RTX2026-05-01', '', '2026-05-18 15:59:11', 0, 0),
(17, 11, 'SUJAL SHANTARAM SHINDE', 'BCA', '569358478565433', 2, '7768809303', NULL, 'Female', 'janhavishinde636@gmail.com', 'T-PU-RTX2026-05-02', '', '2026-05-18 15:59:21', 0, 0),
(18, 4, 'MANASI MAHESH OMBALE', 'BCA', '478965878965896', 3, '8965874859', NULL, 'Female', 'sujalshin08@gmail.com', 'T-PU-RTX2026-06-01', '', '2026-05-18 16:04:47', 0, 0),
(19, 4, 'SEJAL SHANTARAM SHINDE', 'BCA', '569358478565433', 3, '8928458864', NULL, 'Female', 'sejalshinde888@gmail.com', 'T-PU-RTX2026-06-02', '', '2026-05-18 16:05:02', 0, 0),
(20, 12, 'GAURAV SHASHIKANT SHINDE', 'hbhb', '5455651955472', 1, '6185828881', NULL, 'Male', 'shindegaurav556@gmail.com', 'T-BH-RTX2026-07-01', '', '2026-05-21 07:28:24', 0, 0),
(21, 12, 'YVGY', 'yt', '54851484545', 1, '4582825515', NULL, 'Male', 'abc@gmail.com', 'T-BH-RTX2026-07-02', '', '2026-05-21 07:28:34', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `alternate_phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'student',
  `unique_reg_id` varchar(30) DEFAULT NULL,
  `category` varchar(10) DEFAULT 'UG',
  `team_name` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_locked` tinyint(1) DEFAULT 0,
  `evaluation_email_sent` int(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `phone`, `alternate_phone`, `email`, `role`, `unique_reg_id`, `category`, `team_name`, `password`, `email_verified`, `created_at`, `is_locked`, `evaluation_email_sent`, `deleted_at`) VALUES
(1, 'Shreya Ganpat Pawar', '7038955832', '9876543212', 'pawarshreyaganapat25@gmail.com', 'student', 'JN-RTX2026-01', 'UG', NULL, '$2y$10$aLslKHo4R.2IMZ47qX7NmOD2.nOeuYTyXlhlfKNGkpKrmL83APqxq', 1, '2026-04-03 08:02:09', 0, 1, NULL),
(2, 'Sejal Shantaram Shinde', '8928458864', '9145202635', 'sejalshinde888@gmail.com', 'student', 'ST-RTX2026-02', 'UG', NULL, '$2y$10$hsgdOsHOM01Auz4UeAqMdOEg0ZOcJd2en/x0zrXK/qk4ueASM11Ti', 1, '2026-04-22 15:14:46', 0, 1, NULL),
(3, 'Pranali Pradip Sabale', '7219798204', '8928458864', 'pranalisabale94@gmail.com', 'student', 'ST-RTX2026-03', 'UG', NULL, '$2y$10$yoZGgLrivXFdKqRL3kuW0ekwuvmehhR96/eFf1EcGjmpz9LfSL0R6', 1, '2026-05-18 09:58:46', 0, 1, NULL),
(4, 'Ankita Rajendra Kulkarni', '9356860540', '7768809303', 'ankitarajendrakulkarni15@gmail.com', 'student', 'ST-RTX2026-04', 'UG', NULL, '$2y$10$WTxYua5lqnOtez94wo/zTe82yCK8Ut211Bj3o905OCj1qYkaoBxeu', 1, '2026-05-18 10:18:59', 0, 1, NULL),
(5, 'Janhavi Shantaram Shinde', '8928458864', '9145202635', 'sujalshinde318@gmail.com', 'student', 'PU-RTX2026-05', 'UG', NULL, '$2y$10$fcTt5495K5xA7nrlZi7KOOu3WLDULGxuquWey4BUySUUd/vwvoO0.', 1, '2026-05-18 15:56:40', 0, 1, NULL),
(6, 'Manasi Mahesh Ombale', '8928458864', '9145202635', 'sujalshin08@gmail.com', 'student', 'PU-RTX2026-06', 'UG', NULL, '$2y$10$tlvlALEhQ8pZrEhxNC0y7OHGI3BP4ReqoNePjCGa7liOaW6zpvqPG', 1, '2026-05-18 16:02:37', 0, 1, NULL),
(7, 'Gaurav Shashikant Shinde', '9325817337', '9325817337', 'shindegaurav556@gmail.com', 'student', 'BH-RTX2026-07', 'UG', NULL, '$2y$10$6LIuop.sXdo5JpWvlt8wTO5bAKsRdOxl3Ewy3oGAm93nl8eDyZM1O', 1, '2026-05-21 07:16:48', 1, 1, NULL),
(8, 'Ganesh Shinde', '7021281857', '9145202635', 'ganesh.shinde@mahity.com', 'student', 'ST-RTX2026-08', 'UG', NULL, '$2y$10$rv6OAry.xMqUhdax2X3RZeBqH8.rRWnZKIEI6xuu7L4GbxctelJP2', 1, '2026-08-20 10:10:15', 0, 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `otp_table`
--
ALTER TABLE `otp_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_proposals`
--
ALTER TABLE `project_proposals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- Indexes for table `registration_payments`
--
ALTER TABLE `registration_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_feedback`
--
ALTER TABLE `student_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `team_information`
--
ALTER TABLE `team_information`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_team` (`team_name`,`user_email`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_team_members` (`team_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `unique_reg_id` (`unique_reg_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otp_table`
--
ALTER TABLE `otp_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_proposals`
--
ALTER TABLE `project_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `registration_payments`
--
ALTER TABLE `registration_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_feedback`
--
ALTER TABLE `student_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `team_information`
--
ALTER TABLE `team_information`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `fk_team_members` FOREIGN KEY (`team_id`) REFERENCES `team_information` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
