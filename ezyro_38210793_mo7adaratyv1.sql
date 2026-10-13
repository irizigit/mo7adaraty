-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql102.ezyro.com
-- Generation Time: 09 أكتوبر 2026 الساعة 10:42
-- إصدار الخادم: 11.4.13-MariaDB
-- PHP Version: 7.2.22

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ezyro_38210793_mo7adaratyv1`
--

-- --------------------------------------------------------

--
-- بنية الجدول `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `year_label` varchar(50) NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `academic_years`
--

INSERT INTO `academic_years` (`id`, `year_label`, `is_current`, `created_at`) VALUES
(1, '2025-2026', 0, '2026-03-17 07:57:47'),
(2, '2026-2027', 1, '2026-03-17 08:55:17');

-- --------------------------------------------------------

--
-- بنية الجدول `admin_messages`
--

DROP TABLE IF EXISTS `admin_messages`;
CREATE TABLE `admin_messages` (
  `message_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `message_subject` varchar(255) NOT NULL,
  `message_content` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `status` enum('pending_reply','replied') NOT NULL DEFAULT 'pending_reply',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admin_messages`
--

INSERT INTO `admin_messages` (`message_id`, `student_user_id`, `message_subject`, `message_content`, `admin_reply`, `status`, `created_at`, `replied_at`) VALUES
(1, 1, 'سثش', 'ثب', 'شصق', 'replied', '2025-08-21 23:02:30', '2025-08-21 23:02:41'),
(2, 1, 'ثغف', 'دج', 'صث(èغص\'(', 'replied', '2025-08-28 21:14:51', '2025-09-30 19:36:07'),
(3, 35, 'سلام ????????????????', 'كيف الحال ????????????', 'اهلا اهلا بخير', 'replied', '2025-10-10 23:36:19', '2025-10-10 23:37:10'),
(4, 45, 'المحاضرات', 'دخلت إلى المنصة لم اجد أي محاضرة . هل يمكنك مساعدتي أخي الكريم. وشكرا', 'خويا بخير لباس عليك غادي يتنشرو عما قريب ان شاء الله', 'replied', '2025-10-11 07:50:00', '2025-10-11 09:15:32'),
(5, 45, 'الشكر', 'جزاكم الله خيرا', NULL, 'pending_reply', '2025-10-11 14:46:10', NULL),
(6, 1, 'Vufufhj', 'Chfhhssgbsdh', 'Ugfhfj', 'replied', '2025-10-13 16:12:14', '2025-10-13 16:13:26'),
(7, 140, 'لا شيء????', 'رحبو بي ????????', 'اهلا اهلا', 'replied', '2025-10-27 22:15:29', '2025-11-04 19:48:13'),
(8, 143, 'محاضرات s3', 'السلام عليكم ورحمه الله تعالى وبركاته \r\nهل توجد فقط محاضرة واحدة في هذه المنصة لأنه كمثال pdfالاستاذ النصيحي لا يوجد هنا وشكرا', NULL, 'pending_reply', '2025-12-21 12:32:54', NULL),
(9, 167, 'Hdjej', 'Jdjdu', NULL, 'pending_reply', '2026-03-04 21:37:13', NULL),
(10, 168, 'iuyà', 'ytify', 'mrahba', 'replied', '2026-03-11 11:34:15', '2026-03-11 11:36:20'),
(11, 1, 'ثغف', 'غعف', NULL, 'pending_reply', '2026-03-26 12:44:46', NULL),
(12, 1, 'ثغف', 'قèçه', NULL, 'pending_reply', '2026-03-26 12:55:02', NULL),
(13, 1, 'قغعهخ', 'بغعخه', NULL, 'pending_reply', '2026-03-26 14:09:45', NULL),
(14, 182, 'Uudu', 'Uis', NULL, 'pending_reply', '2026-06-22 17:30:39', NULL),
(15, 172, 'dgiyjh', 'cdygij', 'rtè_', 'replied', '2026-07-13 13:17:49', '2026-07-13 13:18:18');

-- --------------------------------------------------------

--
-- بنية الجدول `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `target_type` enum('all','specific') DEFAULT 'all',
  `academic_year_id` int(11) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `class_image` varchar(255) DEFAULT 'default_class.png',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `class_image`, `is_deleted`, `deleted_at`) VALUES
(1, 'الفصل الأول', 'cls_69bee9f68f5aa.png', 0, NULL),
(2, 'الفصل الثاني', 'cls_69bf21ee35c9a.png', 0, NULL),
(3, 'الفصل الثالث', 'cls_69bf22080d096.png', 0, NULL),
(4, 'الفصل الرابع', 'cls_69bf222a437ea.png', 0, NULL),
(5, 'الفصل الخامس', 'cls_69bf223b5cfe2.png', 0, NULL),
(6, 'الفصل السادس', 'cls_69bf2249aa4d1.png', 0, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `content_type` enum('lecture','exam','post') NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `comments`
--

INSERT INTO `comments` (`comment_id`, `content_type`, `content_id`, `user_id`, `parent_id`, `comment_text`, `created_at`, `updated_at`) VALUES
(47, 'post', 19, 49, NULL, 'شكرا جزيلا ،بارك الله في مجهوداتكم .', '2025-10-10 23:52:00', NULL),
(48, 'post', 18, 49, NULL, 'جميل جدا ،بوركتم بالتوفيق للجميع', '2025-10-10 23:53:00', NULL),
(50, 'post', 19, 35, NULL, 'الله يجزيك بالخير على المجهودات ديالك معنا ❤️????', '2025-10-10 23:57:29', NULL),
(53, 'lecture', 36, 83, NULL, '????', '2025-10-11 14:13:37', NULL),
(56, 'post', 19, 91, NULL, 'بارك الله فيك اين توجد المحاضرات لانه لا يوجد اي ملف تم تنزيله', '2025-10-11 17:41:37', NULL),
(58, 'lecture', 37, 100, NULL, 'جزاك الله خيرا', '2025-10-12 11:00:55', NULL),
(59, 'post', 19, 160, NULL, 'khoya 7awl twer sit rah zwen', '2026-01-17 21:37:57', NULL),
(100, 'post', 36, 172, NULL, 'امين يارب وحزاكم مشكورة جزيل الشكر ⁦(⁠◍⁠•⁠ᴗ⁠•⁠◍⁠)⁠❤⁩', '2026-07-06 07:37:01', '2026-07-06 07:37:37');

-- --------------------------------------------------------

--
-- بنية الجدول `community_items`
--

DROP TABLE IF EXISTS `community_items`;
CREATE TABLE `community_items` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `drive_link` varchar(500) NOT NULL,
  `author` varchar(100) DEFAULT 'طالب محاضراتي',
  `device_id` varchar(100) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `community_items`
--

INSERT INTO `community_items` (`id`, `title`, `description`, `drive_link`, `author`, `device_id`, `likes`, `comments`, `created_at`) VALUES
(1, 'الفصل الدراسي: الفصل الدراسي الأول - 2026', 'مجلد عام: الفصل الدراسي: الفصل الدراسي الأول - 2026', 'https://irizi.unaux.com/mo7adaraty/uploads/1785279785_الفصل الدراسي: الفصل الدراسي الأول - 2026.zip', 'طالب محاضراتي', '68f8869555290665', 0, 0, '2026-07-28 16:03:05'),
(2, 'الشعبة: علوم الحاسوب والبرمجيات', 'مجلد عام: الشعبة: علوم الحاسوب والبرمجيات', 'https://irizi.unaux.com/mo7adaraty/uploads/1785280823_الشعبة: علوم الحاسوب والبرمجيات.zip', 'طالب محاضراتي', '68f8869555290665', 0, 0, '2026-07-28 16:20:23');

-- --------------------------------------------------------

--
-- بنية الجدول `content_reactions`
--

DROP TABLE IF EXISTS `content_reactions`;
CREATE TABLE `content_reactions` (
  `reaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `content_type` enum('lecture','exam','post') NOT NULL,
  `reaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `content_reactions`
--

INSERT INTO `content_reactions` (`reaction_id`, `user_id`, `content_id`, `content_type`, `reaction_type`, `created_at`) VALUES
(91, 35, 18, 'post', 'like', '2025-10-10 23:35:37'),
(92, 49, 19, 'post', 'like', '2025-10-10 23:50:58'),
(93, 49, 18, 'post', 'like', '2025-10-10 23:52:14'),
(95, 55, 36, 'lecture', 'like', '2025-10-11 01:49:04'),
(96, 57, 19, 'post', 'like', '2025-10-11 06:10:35'),
(99, 83, 36, 'lecture', 'like', '2025-10-11 14:13:20'),
(100, 91, 19, 'post', 'like', '2025-10-11 17:40:58'),
(102, 92, 19, 'post', 'like', '2025-10-11 18:43:00'),
(108, 106, 38, 'lecture', 'like', '2025-10-12 21:30:19'),
(110, 107, 36, 'lecture', 'like', '2025-10-12 22:35:53'),
(111, 108, 36, 'lecture', 'like', '2025-10-13 01:06:52'),
(112, 113, 36, 'lecture', 'like', '2025-10-13 15:02:40'),
(114, 88, 19, 'post', 'like', '2025-10-16 13:26:59'),
(122, 138, 19, 'post', 'like', '2025-10-26 19:12:35'),
(124, 49, 46, 'lecture', 'like', '2025-10-31 12:18:00'),
(125, 49, 44, 'lecture', 'like', '2025-10-31 12:19:48'),
(127, 49, 36, 'lecture', 'like', '2025-10-31 12:20:30'),
(128, 49, 41, 'lecture', 'like', '2025-10-31 12:20:43'),
(129, 30, 19, 'post', 'like', '2025-11-09 14:37:25'),
(130, 30, 20, 'post', 'like', '2025-11-09 14:37:53'),
(131, 150, 20, 'post', 'like', '2025-11-13 10:16:10'),
(134, 160, 36, 'lecture', 'like', '2026-01-17 21:36:36'),
(141, 167, 44, 'lecture', 'like', '2026-03-04 21:35:08'),
(143, 168, 36, 'lecture', 'like', '2026-03-11 11:32:03'),
(144, 168, 23, 'post', 'like', '2026-03-11 11:33:26'),
(171, 171, 19, 'post', 'like', '2026-03-26 21:13:00'),
(172, 43, 49, 'lecture', 'like', '2026-04-28 12:21:21'),
(174, 172, 45, 'lecture', 'dislike', '2026-05-06 20:08:40'),
(180, 172, 43, 'lecture', 'dislike', '2026-05-06 23:19:07'),
(185, 172, 49, 'lecture', 'dislike', '2026-05-06 23:36:01'),
(220, 172, 20, 'post', 'like', '2026-07-03 04:15:30'),
(221, 172, 28, 'post', 'like', '2026-07-03 10:28:58'),
(228, 172, 58, 'lecture', 'like', '2026-07-12 02:08:08'),
(229, 172, 36, 'post', 'like', '2026-07-13 13:11:03');

-- --------------------------------------------------------

--
-- بنية الجدول `course_offerings`
--

DROP TABLE IF EXISTS `course_offerings`;
CREATE TABLE `course_offerings` (
  `offering_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT 1,
  `division_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `track_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `course_offerings`
--

INSERT INTO `course_offerings` (`offering_id`, `academic_year_id`, `division_id`, `class_id`, `group_id`, `track_id`, `status`) VALUES
(36, 1, 3, 2, 1, NULL, 'active'),
(37, 1, 3, 2, 2, NULL, 'active'),
(38, 1, 3, 2, 3, NULL, 'active'),
(39, 1, 3, 2, 4, NULL, 'active'),
(40, 1, 3, 2, 5, NULL, 'active'),
(41, 1, 3, 2, 6, NULL, 'active'),
(42, 1, 3, 4, 1, NULL, 'active'),
(43, 1, 3, 4, 2, NULL, 'active'),
(44, 1, 3, 4, 3, NULL, 'active'),
(45, 1, 3, 4, 4, NULL, 'active'),
(46, 1, 3, 6, 1, 1, 'active'),
(47, 1, 3, 6, 2, 1, 'active'),
(48, 1, 3, 6, 1, 2, 'active'),
(51, 1, 4, 2, 1, NULL, 'active'),
(52, 1, 4, 2, 2, NULL, 'active'),
(53, 1, 4, 2, 3, NULL, 'active'),
(54, 1, 4, 2, 4, NULL, 'active'),
(55, 1, 4, 2, 5, NULL, 'active'),
(56, 1, 4, 2, 6, NULL, 'active'),
(61, 1, 4, 4, 1, NULL, 'active'),
(62, 1, 4, 4, 2, NULL, 'active'),
(63, 1, 4, 4, 3, NULL, 'active'),
(64, 1, 4, 4, 4, NULL, 'active'),
(71, 1, 4, 6, 1, 3, 'active'),
(72, 1, 4, 6, 1, 4, 'active'),
(81, 1, 5, 2, 1, NULL, 'active'),
(82, 1, 5, 2, 2, NULL, 'active'),
(83, 1, 5, 2, 3, NULL, 'active'),
(91, 1, 5, 4, 1, NULL, 'active'),
(92, 1, 5, 4, 2, NULL, 'active'),
(96, 1, 5, 6, 1, 5, 'active'),
(101, 1, 6, 2, 1, NULL, 'active'),
(102, 1, 6, 2, 2, NULL, 'active'),
(103, 1, 6, 2, 3, NULL, 'active'),
(104, 1, 6, 2, 4, NULL, 'active'),
(105, 1, 6, 2, 5, NULL, 'active'),
(106, 1, 6, 2, 6, NULL, 'active'),
(111, 1, 6, 4, 1, NULL, 'active'),
(112, 1, 6, 4, 2, NULL, 'active'),
(113, 1, 6, 4, 3, NULL, 'active'),
(114, 1, 6, 4, 4, NULL, 'active'),
(121, 1, 6, 6, 1, 6, 'active'),
(122, 1, 6, 6, 1, 7, 'active'),
(131, 1, 7, 2, 1, NULL, 'active'),
(132, 1, 7, 2, 2, NULL, 'active'),
(133, 1, 7, 2, 3, NULL, 'active'),
(134, 1, 7, 2, 4, NULL, 'active'),
(135, 1, 7, 4, 1, NULL, 'active'),
(136, 1, 7, 4, 2, NULL, 'active'),
(141, 1, 7, 6, 1, 8, 'active'),
(142, 1, 7, 6, 1, 9, 'active'),
(151, 1, 8, 2, 1, NULL, 'active'),
(152, 1, 8, 2, 2, NULL, 'active'),
(155, 1, 8, 4, 1, NULL, 'active'),
(161, 1, 8, 6, 1, 10, 'active'),
(171, 1, 9, 2, 1, NULL, 'active'),
(172, 1, 9, 2, 2, NULL, 'active'),
(173, 1, 9, 2, 3, NULL, 'active'),
(174, 1, 9, 2, 4, NULL, 'active'),
(175, 1, 9, 2, 5, NULL, 'active'),
(176, 1, 9, 2, 6, NULL, 'active'),
(181, 1, 9, 4, 1, NULL, 'active'),
(182, 1, 9, 4, 2, NULL, 'active'),
(183, 1, 9, 4, 3, NULL, 'active'),
(184, 1, 9, 4, 4, NULL, 'active'),
(191, 1, 9, 6, 1, 11, 'active'),
(201, 1, 10, 2, 1, NULL, 'active'),
(202, 1, 10, 2, 2, NULL, 'active'),
(203, 1, 10, 2, 3, NULL, 'active'),
(204, 1, 10, 2, 4, NULL, 'active'),
(205, 1, 10, 2, 5, NULL, 'active'),
(206, 1, 10, 2, 6, NULL, 'active'),
(211, 1, 10, 4, 1, NULL, 'active'),
(212, 1, 10, 4, 2, NULL, 'active'),
(213, 1, 10, 4, 3, NULL, 'active'),
(214, 1, 10, 4, 4, NULL, 'active'),
(221, 1, 10, 6, 1, 12, 'active'),
(222, 1, 10, 6, 1, 13, 'active'),
(223, 1, 10, 6, 2, 12, 'active'),
(224, 1, 10, 6, 2, 13, 'active'),
(226, 2, 3, 2, 1, NULL, 'active'),
(227, 2, 3, 2, 2, NULL, 'active'),
(228, 2, 3, 2, 3, NULL, 'active'),
(229, 2, 3, 2, 4, NULL, 'active'),
(230, 2, 3, 2, 5, NULL, 'active'),
(231, 2, 3, 2, 6, NULL, 'active'),
(232, 2, 3, 4, 1, NULL, 'active'),
(233, 2, 3, 4, 2, NULL, 'active'),
(234, 2, 3, 4, 3, NULL, 'active'),
(235, 2, 3, 4, 4, NULL, 'active'),
(236, 2, 3, 6, 1, 1, 'active'),
(237, 2, 3, 6, 2, 1, 'active'),
(238, 2, 3, 6, 1, 2, 'active'),
(239, 2, 4, 2, 1, NULL, 'active'),
(240, 2, 4, 2, 2, NULL, 'active'),
(241, 2, 4, 2, 3, NULL, 'active'),
(242, 2, 4, 2, 4, NULL, 'active'),
(243, 2, 4, 2, 5, NULL, 'active'),
(244, 2, 4, 2, 6, NULL, 'active'),
(245, 2, 4, 4, 1, NULL, 'active'),
(246, 2, 4, 4, 2, NULL, 'active'),
(247, 2, 4, 4, 3, NULL, 'active'),
(248, 2, 4, 4, 4, NULL, 'active'),
(249, 2, 4, 6, 1, 3, 'active'),
(250, 2, 4, 6, 1, 4, 'active'),
(251, 2, 5, 2, 1, NULL, 'active'),
(252, 2, 5, 2, 2, NULL, 'active'),
(253, 2, 5, 2, 3, NULL, 'active'),
(254, 2, 5, 4, 1, NULL, 'active'),
(255, 2, 5, 4, 2, NULL, 'active'),
(256, 2, 5, 6, 1, 5, 'active'),
(257, 2, 6, 2, 1, NULL, 'active'),
(258, 2, 6, 2, 2, NULL, 'active'),
(259, 2, 6, 2, 3, NULL, 'active'),
(260, 2, 6, 2, 4, NULL, 'active'),
(261, 2, 6, 2, 5, NULL, 'active'),
(262, 2, 6, 2, 6, NULL, 'active'),
(263, 2, 6, 4, 1, NULL, 'active'),
(264, 2, 6, 4, 2, NULL, 'active'),
(265, 2, 6, 4, 3, NULL, 'active'),
(266, 2, 6, 4, 4, NULL, 'active'),
(267, 2, 6, 6, 1, 6, 'active'),
(268, 2, 6, 6, 1, 7, 'active'),
(269, 2, 7, 2, 1, NULL, 'active'),
(270, 2, 7, 2, 2, NULL, 'active'),
(271, 2, 7, 2, 3, NULL, 'active'),
(272, 2, 7, 2, 4, NULL, 'active'),
(273, 2, 7, 4, 1, NULL, 'active'),
(274, 2, 7, 4, 2, NULL, 'active'),
(275, 2, 7, 6, 1, 8, 'active'),
(276, 2, 7, 6, 1, 9, 'active'),
(277, 2, 8, 2, 1, NULL, 'active'),
(278, 2, 8, 2, 2, NULL, 'active'),
(279, 2, 8, 4, 1, NULL, 'active'),
(280, 2, 8, 6, 1, 10, 'active'),
(281, 2, 9, 2, 1, NULL, 'active'),
(282, 2, 9, 2, 2, NULL, 'active'),
(283, 2, 9, 2, 3, NULL, 'active'),
(284, 2, 9, 2, 4, NULL, 'active'),
(285, 2, 9, 2, 5, NULL, 'active'),
(286, 2, 9, 2, 6, NULL, 'active'),
(287, 2, 9, 4, 1, NULL, 'active'),
(288, 2, 9, 4, 2, NULL, 'active'),
(289, 2, 9, 4, 3, NULL, 'active'),
(290, 2, 9, 4, 4, NULL, 'active'),
(291, 2, 9, 6, 1, 11, 'active'),
(292, 2, 10, 2, 1, NULL, 'active'),
(293, 2, 10, 2, 2, NULL, 'active'),
(294, 2, 10, 2, 3, NULL, 'active'),
(295, 2, 10, 2, 4, NULL, 'active'),
(296, 2, 10, 2, 5, NULL, 'active'),
(297, 2, 10, 2, 6, NULL, 'active'),
(298, 2, 10, 4, 1, NULL, 'active'),
(299, 2, 10, 4, 2, NULL, 'active'),
(300, 2, 10, 4, 3, NULL, 'active'),
(301, 2, 10, 4, 4, NULL, 'active'),
(302, 2, 10, 6, 1, 12, 'active'),
(303, 2, 10, 6, 1, 13, 'active'),
(304, 2, 10, 6, 2, 12, 'active'),
(305, 2, 10, 6, 2, 13, 'active');

-- --------------------------------------------------------

--
-- بنية الجدول `design_assets`
--

DROP TABLE IF EXISTS `design_assets`;
CREATE TABLE `design_assets` (
  `id` int(11) NOT NULL,
  `page_section` varchar(100) NOT NULL,
  `asset_key` varchar(100) NOT NULL,
  `asset_type` enum('icon','image','pattern') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `design_assets`
--

INSERT INTO `design_assets` (`id`, `page_section`, `asset_key`, `asset_type`, `file_path`, `description`, `updated_at`) VALUES
(22, 'header', 'icon_dashboard', 'icon', 'includesimg/header/icon_dashboard.png', NULL, '2026-03-21 20:59:38'),
(23, 'header', 'icon_profile', 'icon', 'includesimg/header/icon_profile.png', NULL, '2026-03-21 20:59:38'),
(24, 'header', 'icon_community', 'icon', 'includesimg/header/icon_community.png', NULL, '2026-03-21 10:45:38'),
(25, 'header', 'icon_announcements', 'icon', 'includesimg/header/icon_announcements.png', NULL, '2026-03-21 10:48:46'),
(26, 'header', 'icon_archive', 'icon', 'includesimg/header/icon_archive.png', NULL, '2026-03-21 20:59:37'),
(27, 'header', 'icon_whatsapp', 'icon', 'includesimg/header/icon_whatsapp.png', NULL, '2026-03-21 10:48:47'),
(28, 'header', 'icon_contact', 'icon', 'includesimg/header/icon_contact.png', NULL, '2026-03-21 20:59:37'),
(29, 'header', 'icon_notifications', 'icon', 'includesimg/header/icon_notifications.png', NULL, '2026-03-21 20:59:38'),
(30, 'header', 'icon_logout', 'icon', 'includesimg/header/icon_logout.png', NULL, '2026-03-21 20:59:36'),
(31, 'class_lectures', 'cl_bg_header', 'image', 'includesimg/class_lectures/cl_bg_header.png', 'خلفية رأس صفحة المحاضرات', '2026-03-21 00:13:58'),
(32, 'class_lectures', 'cl_icon_school', 'image', 'admin/assets/icons/cl_icon_school.png', 'أيقونة الجامعة', '2026-04-28 20:06:58'),
(33, 'class_lectures', 'cl_icon_back', 'image', 'admin/assets/icons/cl_icon_back.png', 'أيقونة العودة', '2026-04-28 20:08:21'),
(34, 'class_lectures', 'cl_icon_upload', 'icon', 'includesimg/class_lectures/cl_icon_upload.png', 'أيقونة رفع ملف', '2026-03-21 20:53:02'),
(35, 'class_lectures', 'cl_icon_nav_lectures', 'image', 'admin/assets/icons/cl_icon_nav_lectures.png', 'أيقونة تبويب المحاضرات', '2026-04-28 20:08:21'),
(36, 'class_lectures', 'cl_icon_nav_exams', 'image', 'admin/assets/icons/cl_icon_nav_exams.png', 'أيقونة تبويب الامتحانات', '2026-04-28 20:08:21'),
(37, 'class_lectures', 'cl_icon_nav_community', 'image', 'admin/assets/icons/cl_icon_nav_community.png', 'أيقونة ساحة المشاركة', '2026-04-28 20:08:21'),
(38, 'class_lectures', 'cl_icon_sidebar', 'image', 'admin/assets/icons/cl_icon_sidebar.png', 'أيقونة الشريط الجانبي', '2026-04-28 20:08:21'),
(39, 'class_lectures', 'cl_icon_subject', 'image', 'admin/assets/icons/cl_icon_subject.png', 'أيقونة المادة', '2026-04-28 20:08:21'),
(40, 'class_lectures', 'cl_icon_grid', 'image', 'admin/assets/icons/cl_icon_grid.png', 'أيقونة العرض الشبكي', '2026-04-28 20:08:21'),
(41, 'class_lectures', 'cl_icon_list', 'image', 'admin/assets/icons/cl_icon_list.png', 'أيقونة العرض الطولي', '2026-04-28 20:08:21'),
(42, 'class_lectures', 'cl_icon_empty', 'icon', 'includesimg/class_lectures/cl_icon_empty.png', 'أيقونة المحتوى الفارغ', '2026-03-21 20:53:09'),
(43, 'class_lectures', 'cl_icon_pdf', 'icon', 'includesimg/class_lectures/cl_icon_pdf.png', 'أيقونة PDF', '2026-03-21 20:53:10'),
(44, 'class_lectures', 'cl_icon_image', 'icon', 'includesimg/class_lectures/cl_icon_image.png', 'أيقونة صورة', '2026-03-21 20:53:10'),
(45, 'class_lectures', 'cl_icon_word', 'icon', 'includesimg/class_lectures/cl_icon_word.png', 'أيقونة Word', '2026-04-28 19:07:33'),
(46, 'class_lectures', 'cl_icon_excel', 'icon', 'includesimg/class_lectures/cl_icon_excel.png', 'أيقونة Excel', '2026-04-28 19:07:33'),
(47, 'class_lectures', 'cl_icon_ppt', 'icon', 'includesimg/class_lectures/cl_icon_ppt.png', 'أيقونة PowerPoint', '2026-04-28 19:07:33'),
(48, 'class_lectures', 'cl_icon_video', 'icon', 'includesimg/class_lectures/cl_icon_video.png', 'أيقونة فيديو', '2026-04-28 19:07:33'),
(49, 'class_lectures', 'cl_icon_audio', 'icon', 'includesimg/class_lectures/cl_icon_audio.png', 'أيقونة صوت', '2026-04-28 19:07:33'),
(50, 'class_lectures', 'cl_icon_default', 'icon', 'includesimg/class_lectures/cl_icon_default.png', 'أيقونة ملف افتراضي', '2026-03-21 20:53:09'),
(51, 'class_lectures', 'cl_icon_view', 'icon', 'includesimg/class_lectures/cl_icon_view.png', 'أيقونة معاينة', '2026-03-21 20:53:05'),
(52, 'class_lectures', 'cl_icon_download', 'icon', 'includesimg/class_lectures/cl_icon_download.png', 'أيقونة تحميل', '2026-03-21 20:53:06'),
(53, 'class_lectures', 'cl_icon_report', 'icon', 'includesimg/class_lectures/cl_icon_report.png', 'أيقونة إبلاغ', '2026-03-21 20:53:06'),
(54, 'class_lectures', 'cl_icon_like', 'icon', 'includesimg/class_lectures/cl_icon_like.png', 'أيقونة إعجاب', '2026-03-21 20:53:07'),
(55, 'class_lectures', 'cl_icon_dislike', 'icon', 'includesimg/class_lectures/cl_icon_dislike.png', 'أيقونة عدم إعجاب', '2026-03-21 20:53:07'),
(56, 'class_lectures', 'cl_icon_comments', 'icon', 'includesimg/class_lectures/cl_icon_comments.png', 'أيقونة تعليقات', '2026-03-21 20:53:08'),
(57, 'class_lectures', 'cl_icon_send', 'icon', 'includesimg/class_lectures/cl_icon_send.png', 'أيقونة إرسال', '2026-03-21 20:53:08'),
(58, 'header', 'header_logo', 'image', 'includesimg/header/header_logo.png', 'شعار الموقع الأساسي (Logo)', '2026-03-21 00:13:59'),
(59, 'header', 'header_bg', 'image', 'includesimg/header/header_bg.png', 'صورة خلفية الهيدر (اختياري)', '2026-03-21 00:13:59'),
(61, 'header', 'icon_home', 'icon', 'includesimg/header/icon_home.png', 'أيقونة الرئيسية', '2026-03-21 20:59:40'),
(62, 'header', 'icon_login', 'icon', 'includesimg/header/icon_login.png', 'أيقونة تسجيل الدخول', '2026-03-21 20:59:40'),
(63, 'header', 'icon_signup', 'icon', 'includesimg/header/icon_signup.png', 'أيقونة إنشاء حساب', '2026-03-21 20:59:41'),
(64, 'header', 'icon_privacy', 'icon', 'includesimg/header/icon_privacy.png', 'أيقونة سياسة الخصوصية', '2026-03-21 20:59:40'),
(65, 'header', 'icon_terms', 'icon', 'includesimg/header/icon_terms.png', 'أيقونة شروط الخدمة', '2026-04-28 19:07:33'),
(66, 'header', 'icon_contact_us', 'icon', 'includesimg/header/icon_contact_us.png', 'أيقونة تواصل معنا', '2026-03-21 20:59:39'),
(67, 'header', 'icon_about_us', 'icon', 'includesimg/header/icon_about_us.png', 'أيقونة عن الموقع', '2026-03-21 20:59:39'),
(68, 'header', 'icon_lang_ar', 'icon', 'includesimg/header/icon_lang_ar.png', 'أيقونة اللغة العربية', '2026-03-21 20:59:41'),
(69, 'header', 'icon_lang_en', 'icon', 'includesimg/header/icon_lang_en.png', 'أيقونة اللغة الإنجليزية', '2026-03-21 20:59:42'),
(70, 'header', 'icon_lang_fr', 'icon', 'includesimg/header/icon_lang_fr.png', 'أيقونة اللغة الفرنسية', '2026-03-21 20:59:42'),
(71, 'header', 'icon_burger', 'icon', 'includesimg/header/icon_burger.png', 'أيقونة قائمة الهاتف (البرجر)', '2026-03-21 20:59:41'),
(72, 'login', 'login_bg', 'image', 'includesimg/login/login_bg.png', 'صورة خلفية صفحة الدخول (تخفي الفيديو إن وجدت)', '2026-03-21 00:14:00'),
(73, 'login', 'login_side_image', 'image', 'includesimg/login/login_side_image.webp', 'الصورة أو الشعار في القسم الترحيبي الجانبي', '2026-03-21 01:07:04'),
(74, 'login', 'login_logo', 'image', 'includesimg/login/login_logo.png', 'الشعار الصغير أعلى نموذج تسجيل الدخول', '2026-03-21 21:01:34'),
(75, 'login', 'login_icon_user', 'icon', 'includesimg/login/login_icon_user.png', 'أيقونة حقل اسم المستخدم/البريد', '2026-03-21 21:01:36'),
(76, 'login', 'login_icon_password', 'icon', 'includesimg/login/login_icon_password.png', 'أيقونة حقل كلمة المرور', '2026-03-21 21:01:36'),
(77, 'login', 'login_icon_btn', 'icon', 'includesimg/login/login_icon_btn.png', 'أيقونة زر تسجيل الدخول', '2026-03-21 21:01:35'),
(78, 'login', 'login_bg_body', 'image', 'includesimg/login/login_bg_body.png', 'خلفية صفحة تسجيل الدخول بالكامل', '2026-03-21 00:14:00'),
(79, 'login', 'login_bg_card', 'image', 'includesimg/login/login_bg_card.png', 'خلفية البطاقة (Card) التي تحتوي الفورم', '2026-03-21 00:14:00'),
(80, 'login', 'login_icon_eye', 'icon', 'includesimg/login/login_icon_eye.png', 'أيقونة العين (إظهار كلمة المرور)', '2026-03-21 21:01:35'),
(85, 'signup', 'signup_icon_email', 'icon', 'includesimg/signup/signup_icon_email.png', 'أيقونة البريد الإلكتروني', '2026-03-21 21:06:52'),
(86, 'signup', 'signup_icon_gender', 'icon', 'includesimg/signup/signup_icon_gender.png', 'أيقونة تحديد الجنس', '2026-04-28 18:40:23'),
(87, 'signup', 'signup_icon_upload', 'icon', 'includesimg/signup/signup_icon_upload.png', 'أيقونة رفع الصورة الشخصية', '2026-03-21 21:06:53'),
(88, 'signup', 'signup_icon_division', 'icon', 'includesimg/signup/signup_icon_division.png', 'أيقونة اختيار الشعبة', '2026-04-28 18:40:23'),
(89, 'signup', 'signup_icon_add', 'icon', 'includesimg/signup/signup_icon_add.png', 'أيقونة زر إضافة مقرر آخر', '2026-03-21 21:06:51'),
(90, 'signup', 'signup_icon_btn', 'icon', 'includesimg/signup/signup_icon_btn.png', 'أيقونة زر إنشاء الحساب', '2026-03-21 21:06:51'),
(91, 'signup', 'signup_feat_grad', 'icon', 'includesimg/signup/signup_feat_grad.png', 'أيقونة القبعة (في اللوحة الجانبية)', '2026-03-21 21:06:54'),
(92, 'signup', 'signup_feat_book', 'icon', 'includesimg/signup/signup_feat_book.png', 'أيقونة كتاب (في اللوحة الجانبية)', '2026-03-21 21:06:53'),
(93, 'signup', 'signup_feat_users', 'icon', 'includesimg/signup/signup_feat_users.png', 'أيقونة مجتمع (في اللوحة الجانبية)', '2026-03-21 21:06:54'),
(94, 'signup', 'signup_feat_mobile', 'icon', 'includesimg/signup/signup_feat_mobile.png', 'أيقونة هاتف (في اللوحة الجانبية)', '2026-03-21 21:06:54'),
(95, 'signup', 'icon_gender', 'icon', 'includesimg/signup/icon_gender.png', 'أيقونة تحديد الجنس', '2026-04-28 18:40:23'),
(96, 'signup', 'icon_graduation', 'icon', 'includesimg/signup/icon_graduation.png', 'أيقونة اختيار الشعبة', '2026-04-28 18:40:23'),
(97, 'signup', 'icon_signup', 'icon', 'includesimg/signup/icon_signup.png', 'أيقونة إنشاء حساب', '2026-03-21 21:06:51'),
(111, 'signup', 'signup_icon_user', 'icon', 'includesimg/signup/signup_icon_user.png', 'أيقونة حقل اسم المستخدم', '2026-03-21 21:06:53'),
(112, 'signup', 'signup_icon_password', 'icon', 'includesimg/signup/signup_icon_password.png', 'أيقونة حقل كلمة المرور', '2026-04-28 18:40:23'),
(113, 'signup', 'signup_icon_eye', 'icon', 'includesimg/signup/signup_icon_eye.png', 'أيقونة العين (لإظهار وإخفاء كلمة المرور)', '2026-03-21 21:06:53'),
(114, 'signup', 'signup_icon_login', 'icon', 'includesimg/signup/signup_icon_login.png', 'أيقونة رابط تسجيل الدخول (أسفل الفورم)', '2026-03-21 21:06:53'),
(145, 'admin_sidebar', 'admin_logo', 'image', 'admin/dashboard_design/images/admin_logo.png', 'شعار لوحة التحكم (Logo)', '2026-03-21 03:19:35'),
(146, 'admin_sidebar', 'admin_icon_dashboard', 'icon', 'admin/dashboard_design/images/admin_icon_dashboard.png', 'أيقونة الرئيسية', '2026-03-21 10:37:33'),
(147, 'admin_sidebar', 'admin_icon_users', 'icon', 'admin/dashboard_design/images/admin_icon_users.png', 'أيقونة إدارة المستخدمين', '2026-03-21 10:37:33'),
(148, 'admin_sidebar', 'admin_icon_academic_years', 'icon', 'admin/dashboard_design/images/admin_icon_academic_years.png', 'أيقونة السنوات الجامعية', '2026-03-21 10:42:21'),
(149, 'admin_sidebar', 'admin_icon_divisions', 'icon', 'admin/dashboard_design/images/admin_icon_divisions.png', 'أيقونة إدارة الشعب', '2026-03-21 10:37:34'),
(150, 'admin_sidebar', 'admin_icon_classes', 'icon', 'admin/dashboard_design/images/admin_icon_classes.png', 'أيقونة إدارة الفصول', '2026-04-28 19:07:33'),
(151, 'admin_sidebar', 'admin_icon_groups', 'icon', 'admin/dashboard_design/images/admin_icon_groups.png', 'أيقونة إدارة المجموعات', '2026-03-21 21:52:53'),
(152, 'admin_sidebar', 'admin_icon_tracks', 'icon', 'admin/dashboard_design/images/admin_icon_tracks.png', 'أيقونة إدارة المسارات', '2026-03-21 10:44:38'),
(153, 'admin_sidebar', 'admin_icon_offerings', 'icon', 'admin/dashboard_design/images/admin_icon_offerings.png', 'أيقونة إدارة المقررات', '2026-03-21 21:52:55'),
(154, 'admin_sidebar', 'admin_icon_professors', 'icon', 'admin/dashboard_design/images/admin_icon_professors.png', 'أيقونة إدارة الأساتذة', '2026-03-21 10:37:34'),
(155, 'admin_sidebar', 'admin_icon_subjects', 'icon', 'admin/dashboard_design/images/admin_icon_subjects.png', 'أيقونة إدارة المواد', '2026-03-21 10:28:19'),
(156, 'admin_sidebar', 'admin_icon_group_subjects', 'icon', 'admin/dashboard_design/images/admin_icon_group_subjects.png', 'أيقونة ربط المواد بالمقررات', '2026-04-28 19:07:33'),
(157, 'admin_sidebar', 'admin_icon_lectures', 'icon', 'admin/dashboard_design/images/admin_icon_lectures.png', 'أيقونة إدارة المحاضرات', '2026-03-21 10:37:35'),
(158, 'admin_sidebar', 'admin_icon_exams', 'icon', 'admin/dashboard_design/images/admin_icon_exams.png', 'أيقونة إدارة الامتحانات', '2026-03-21 10:37:35'),
(159, 'admin_sidebar', 'admin_icon_post_reviews', 'icon', 'admin/dashboard_design/images/admin_icon_post_reviews.png', 'أيقونة مراجعة المنشورات', '2026-03-21 10:37:36'),
(160, 'admin_sidebar', 'admin_icon_enrollment_requests', 'icon', 'admin/dashboard_design/images/admin_icon_enrollment_requests.png', 'أيقونة طلبات تغيير المقرر', '2026-03-22 15:35:08'),
(161, 'admin_sidebar', 'admin_icon_reset_requests', 'icon', 'admin/dashboard_design/images/admin_icon_reset_requests.png', 'أيقونة طلبات كلمة المرور', '2026-03-21 10:37:36'),
(162, 'admin_sidebar', 'admin_icon_reports', 'icon', 'admin/dashboard_design/images/admin_icon_reports.png', 'أيقونة الإبلاغات', '2026-03-21 10:37:37'),
(163, 'admin_sidebar', 'admin_icon_design', 'icon', 'admin/dashboard_design/images/admin_icon_design.png', 'أيقونة إعدادات التصميم', '2026-03-21 10:37:34'),
(164, 'admin_sidebar', 'admin_icon_logout', 'icon', 'admin/dashboard_design/images/admin_icon_logout.png', 'أيقونة تسجيل الخروج', '2026-03-21 10:42:44'),
(165, 'admin_sidebar', 'admin_icon_whatsapp', 'icon', 'admin/dashboard_design/images/admin_icon_whatsapp.png', 'أيقونة مجموعات الواتساب', '2026-03-21 10:37:37'),
(166, 'admin_sidebar', 'admin_icon_announcement', 'icon', 'admin/dashboard_design/images/admin_icon_announcement.png', 'أيقونة نشر إعلان عام', '2026-03-21 21:52:56'),
(167, 'admin_sidebar', 'admin_icon_reviews', 'icon', 'admin/dashboard_design/images/admin_icon_reviews.png', 'أيقونة مراجعة المحاضرات', '2026-03-21 21:18:23'),
(168, 'admin_sidebar', 'admin_icon_messages', 'icon', 'admin/dashboard_design/images/admin_icon_messages.png', 'أيقونة الرسائل الواردة', '2026-03-21 10:37:38'),
(169, 'student_profile', 'profile_header_bg', 'image', 'includesimg/student/profile_header_bg.png', 'صورة الغلاف (الخلفية العلوية)', '2026-03-21 11:35:05'),
(170, 'student_profile', 'profile_icon_archive', 'icon', 'includesimg/student/profile_icon_archive.png', 'أيقونة أرشيف المواسم', '2026-03-21 11:37:32'),
(171, 'student_profile', 'profile_icon_lectures', 'icon', 'includesimg/student/profile_icon_lectures.png', 'أيقونة محاضراتي', '2026-03-21 11:37:33'),
(172, 'student_profile', 'profile_icon_settings', 'icon', 'includesimg/student/profile_icon_settings.png', 'أيقونة إعدادات الحساب', '2026-03-21 11:37:33'),
(173, 'student_profile', 'profile_icon_check', 'icon', 'includesimg/student/profile_icon_check.png', 'أيقونة علامة الصح (للنجاح)', '2026-03-21 11:37:34'),
(174, 'student_profile', 'profile_icon_gender', 'icon', 'includesimg/student/profile_icon_gender.png', 'أيقونة استكمال البيانات (الجنس)', '2026-04-28 19:07:33'),
(175, 'student_profile', 'profile_icon_enter', 'icon', 'includesimg/student/profile_icon_enter.png', 'أيقونة الدخول للمقرر', '2026-03-21 11:37:33'),
(176, 'student_profile', 'profile_icon_graduation', 'icon', 'includesimg/student/profile_icon_graduation.png', 'أيقونة الشعبة / المقرر', '2026-03-21 11:37:33'),
(177, 'student_profile', 'profile_icon_pdf', 'icon', 'includesimg/student/profile_icon_pdf.png', 'أيقونة ملف المحاضرة (PDF)', '2026-03-21 11:37:34'),
(178, 'global_icons', 'add', 'icon', 'admin/assets/icons/add.png', 'أيقونة الإضافة (Add)', '2026-03-21 23:29:47'),
(179, 'global_icons', 'edit', 'icon', 'admin/assets/icons/edit.png', 'أيقونة التعديل (Edit)', '2026-03-21 23:29:47'),
(180, 'global_icons', 'delete', 'icon', 'admin/assets/icons/delete.png', 'أيقونة الحذف (Delete)', '2026-03-21 23:29:47'),
(181, 'global_icons', 'sitemap', 'icon', 'admin/assets/icons/sitemap.png', 'أيقونة الشعب/الهيكلة (Sitemap)', '2026-03-21 23:29:47'),
(182, 'global_icons', 'menu', 'icon', 'admin/assets/icons/menu.png', 'أيقونة القائمة الجانبية (Menu)', '2026-03-21 23:29:47'),
(183, 'global_icons', 'student', 'icon', 'admin/assets/icons/student.png', 'أيقونة الواجهة الطلابية (Student)', '2026-03-21 23:29:47'),
(199, 'global_icons', 'chart-line', 'icon', 'admin/assets/icons/chart-line.png', 'أيقونة لوحة التحكم (Chart Line)', '2026-03-22 12:01:07'),
(200, 'global_icons', 'circle-exclamation', 'icon', 'admin/assets/icons/circle-exclamation.png', 'أيقونة المهام العاجلة (Circle Exclamation)', '2026-03-22 12:01:07'),
(201, 'global_icons', 'hourglass-half', 'icon', 'admin/assets/icons/hourglass-half.png', 'أيقونة قيد الانتظار (Hourglass Half)', '2026-03-22 12:01:07'),
(202, 'global_icons', 'check-to-slot', 'icon', 'admin/assets/icons/check-to-slot.png', 'أيقونة مراجعة المشاركات (Check To Slot)', '2026-03-22 12:01:07'),
(203, 'global_icons', 'exchange-alt', 'icon', 'admin/assets/icons/exchange-alt.png', 'أيقونة تبادل/تغيير (Exchange Alt)', '2026-03-22 12:01:07'),
(204, 'global_icons', 'inbox', 'icon', 'admin/assets/icons/inbox.png', 'أيقونة الرسائل (Inbox)', '2026-03-22 12:01:07'),
(205, 'global_icons', 'flag', 'icon', 'admin/assets/icons/flag.png', 'أيقونة البلاغات (Flag)', '2026-03-22 12:01:07'),
(206, 'global_icons', 'globe', 'icon', 'admin/assets/icons/globe.png', 'أيقونة إحصاءات عامة (Globe)', '2026-03-22 12:01:07'),
(207, 'global_icons', 'users', 'icon', 'admin/assets/icons/users.png', 'أيقونة الطلاب/المستخدمين (Users)', '2026-03-22 12:01:07'),
(208, 'global_icons', 'user-tie', 'icon', 'admin/assets/icons/user-tie.png', 'أيقونة الأساتذة (User Tie)', '2026-03-22 12:01:07'),
(209, 'global_icons', 'book-open', 'icon', 'admin/assets/icons/book-open.png', 'أيقونة المحاضرات (Book Open)', '2026-03-22 12:01:07'),
(210, 'global_icons', 'file-signature', 'icon', 'admin/assets/icons/file-signature.png', 'أيقونة الامتحانات (File Signature)', '2026-03-22 12:01:07'),
(211, 'global_icons', 'file-alt', 'icon', 'admin/assets/icons/file-alt.png', 'أيقونة الملفات/المحاضرات (File Alt)', '2026-03-22 12:01:07'),
(212, 'global_icons', 'layer-group', 'icon', 'admin/assets/icons/layer-group.png', 'أيقونة المقررات (Layer Group)', '2026-03-22 12:01:07'),
(214, 'global_icons', 'num_0', 'icon', 'admin/assets/icons/num_0.png', 'رقم 0', '2026-03-22 13:30:59'),
(215, 'global_icons', 'num_1', 'icon', 'admin/assets/icons/num_1.png', 'رقم 1', '2026-03-22 13:31:00'),
(216, 'global_icons', 'num_2', 'icon', 'admin/assets/icons/num_2.png', 'رقم 2', '2026-03-22 13:31:00'),
(217, 'global_icons', 'num_3', 'icon', 'admin/assets/icons/num_3.png', 'رقم 3', '2026-03-22 13:31:01'),
(218, 'global_icons', 'num_4', 'icon', 'admin/assets/icons/num_4.png', 'رقم 4', '2026-03-22 13:31:01'),
(219, 'global_icons', 'num_5', 'icon', 'admin/assets/icons/num_5.png', 'رقم 5', '2026-03-22 13:31:01'),
(220, 'global_icons', 'num_6', 'icon', 'admin/assets/icons/num_6.png', 'رقم 6', '2026-03-22 13:31:02'),
(221, 'global_icons', 'num_7', 'icon', 'admin/assets/icons/num_7.png', 'رقم 7', '2026-03-22 13:31:02'),
(222, 'global_icons', 'num_8', 'icon', 'admin/assets/icons/num_8.png', 'رقم 8', '2026-03-22 13:31:02'),
(223, 'global_icons', 'num_9', 'icon', 'admin/assets/icons/num_9.png', 'رقم 9', '2026-03-22 13:31:02'),
(224, 'global_icons', 'line_h', 'image', 'admin/assets/icons/line_h.png', 'الخط الأفقي الفاصل (Horizontal Line)', '2026-03-22 13:47:57'),
(225, 'global_icons', 'line_v', 'image', 'admin/assets/icons/line_v.png', 'الخط العمودي الفاصل (Vertical Line)', '2026-03-22 13:47:57'),
(226, 'global_icons', 'table_header_bg', 'image', 'admin/assets/icons/table_header_bg.png', 'صورة خلفية رأس الجدول (Table Header BG)', '2026-03-22 13:47:57'),
(227, 'loader', 'loader_logo', 'image', 'includesimg/loader/loader_logo.png', 'شعار المنصة في شاشة التحميل', '2026-07-03 07:43:28'),
(228, 'loader', 'loader_step_1', 'icon', 'includesimg/loader/loader_step_1.png', 'أيقونة الخطوة 1 (التحضير)', '2026-03-25 12:45:23'),
(229, 'loader', 'loader_step_2', 'icon', 'admin/assets/icons/num_2.png', 'أيقونة الخطوة 2 (الاتصال)', '2026-03-25 12:21:13'),
(230, 'loader', 'loader_step_3', 'icon', 'admin/assets/icons/num_3.png', 'أيقونة الخطوة 3 (التحميل)', '2026-03-25 12:21:13'),
(231, 'loader', 'loader_step_4', 'icon', 'admin/assets/icons/num_4.png', 'أيقونة الخطوة 4 (الاكتمال)', '2026-03-25 12:21:13');
INSERT INTO `design_assets` (`id`, `page_section`, `asset_key`, `asset_type`, `file_path`, `description`, `updated_at`) VALUES
(232, 'home', 'home_sneak_profile_ar', 'image', 'includesimg/home/home_sneak_profile_ar.png', 'لقطة الملف الشخصي - عربي', '2026-06-30 14:00:12'),
(233, 'home', 'home_sneak_profile_fr', 'image', 'includesimg/home/home_sneak_profile_fr.png', 'لقطة الملف الشخصي - فرنسي', '2026-06-30 14:00:12'),
(234, 'home', 'home_sneak_profile_en', 'image', 'includesimg/home/home_sneak_profile_en.png', 'لقطة الملف الشخصي - إنجليزي', '2026-06-30 14:00:12'),
(235, 'home', 'home_sneak_settings_ar', 'image', 'includesimg/home/home_sneak_settings_ar.png', 'لقطة الإعدادات - عربي', '2026-06-30 14:00:12'),
(236, 'home', 'home_sneak_settings_fr', 'image', 'includesimg/home/home_sneak_settings_fr.png', 'لقطة الإعدادات - فرنسي', '2026-06-30 14:00:12'),
(237, 'home', 'home_sneak_settings_en', 'image', 'includesimg/home/home_sneak_settings_en.png', 'لقطة الإعدادات - إنجليزي', '2026-06-30 14:00:12'),
(238, 'home', 'home_sneak_community_ar', 'image', 'includesimg/home/home_sneak_community_ar.png', 'لقطة المجتمع - عربي', '2026-06-30 14:00:12'),
(239, 'home', 'home_sneak_community_fr', 'image', 'includesimg/home/home_sneak_community_fr.png', 'لقطة المجتمع - فرنسي', '2026-06-30 14:00:12'),
(240, 'home', 'home_sneak_community_en', 'image', 'includesimg/home/home_sneak_community_en.png', 'لقطة المجتمع - إنجليزي', '2026-06-30 14:00:12'),
(241, 'global', 'icon_arrow_down', 'icon', 'admin/assets/icons/arrow_down.png', 'أيقونة سهم لأسفل (بديل FontAwesome)', '2026-03-25 13:52:57'),
(242, 'home', 'home_feat_1', 'icon', 'includesimg/student/icon_bell.png', 'أيقونة ميزة الإشعارات الفورية', '2026-03-25 14:21:28'),
(243, 'home', 'home_feat_2', 'icon', 'includesimg/class_lectures/cl_icon_upload.png', 'أيقونة ميزة مشاركة الملفات', '2026-03-25 14:21:28'),
(244, 'home', 'home_feat_3', 'icon', 'includesimg/header/icon_whatsapp.png', 'أيقونة ميزة مجتمع الواتساب', '2026-03-25 14:21:28'),
(245, 'home', 'home_stat_1', 'icon', 'includesimg/class_lectures/cl_icon_school.png', 'أيقونة إحصائية الشعب والتخصصات', '2026-04-28 19:07:33'),
(246, 'home', 'home_stat_2', 'icon', 'includesimg/header/icon_profile.png', 'أيقونة إحصائية الطلاب المسجلين', '2026-03-25 14:21:28'),
(247, 'home', 'home_stat_3', 'icon', 'includesimg/class_lectures/cl_icon_pdf.png', 'أيقونة إحصائية المحاضرات', '2026-03-25 14:21:28'),
(248, 'home', 'home_stat_4', 'icon', 'includesimg/class_lectures/cl_icon_comments.png', 'أيقونة إحصائية المشاركات', '2026-03-25 14:21:28'),
(249, 'home', 'home_icon_check', 'icon', 'includesimg/student/icon_check.png', 'أيقونة علامة الصح (قسم الآراء)', '2026-03-25 14:21:28'),
(250, 'home', 'home_icon_arrow', 'icon', 'includesimg/home/home_icon_arrow.png', 'أيقونة السهم للأسفل', '2026-03-25 14:26:17'),
(251, 'header', 'icon_faq', 'icon', 'includesimg/header/icon_faq.png', 'أيقونة الأسئلة الشائعة', '2026-03-25 14:40:49'),
(252, 'header', 'icon_explore_classes', 'icon', 'includesimg/header/icon_explore_classes.png', 'أيقونة استكشاف الفصول', '2026-03-25 14:40:49'),
(253, 'header', 'icon_dark_mode', 'icon', 'includesimg/header/icon_dark_mode.png', 'أيقونة الوضع المظلم', '2026-03-25 20:54:39'),
(254, 'header', 'icon_light_mode', 'icon', 'includesimg/header/icon_light_mode.png', 'أيقونة الوضع المضيء', '2026-03-25 20:54:40'),
(256, 'header', 'header_logo_ar', 'image', 'includesimg/header/header_logo_ar.svg', 'شعار الموقع (عربي)', '2026-06-30 13:47:53'),
(257, 'header', 'header_logo_en', 'image', 'includesimg/header/header_logo_en.svg', 'شعار الموقع (إنجليزي)', '2026-06-30 13:48:28'),
(258, 'header', 'header_logo_fr', 'image', 'includesimg/header/header_logo_fr.svg', 'شعار الموقع (فرنسي)', '2026-06-30 13:48:28'),
(259, 'forgot_password', 'forgot_icon_shield', 'icon', 'includesimg/forgot_password/forgot_icon_shield.png', 'أيقونة الدرع (رأس الصفحة)', '2026-03-25 21:18:42'),
(260, 'forgot_password', 'forgot_icon_user', 'icon', 'includesimg/login/login_icon_user.png', 'أيقونة حقل اسم المستخدم', '2026-03-25 21:05:29'),
(261, 'forgot_password', 'forgot_icon_search', 'icon', 'includesimg/forgot_password/forgot_icon_search.png', 'أيقونة زر البحث والتحقق', '2026-03-25 21:18:42'),
(262, 'forgot_password', 'forgot_icon_email', 'icon', 'includesimg/signup/signup_icon_email.png', 'أيقونة حقل البريد الإلكتروني', '2026-03-25 21:05:29'),
(263, 'forgot_password', 'forgot_icon_contact', 'icon', 'includesimg/header/icon_whatsapp.png', 'أيقونة حقل وسيلة التواصل', '2026-03-25 21:05:29'),
(264, 'forgot_password', 'forgot_icon_send', 'icon', 'includesimg/class_lectures/cl_icon_send.png', 'أيقونة زر الإرسال', '2026-03-25 21:05:29'),
(265, 'forgot_password', 'forgot_icon_back', 'icon', 'includesimg/class_lectures/cl_icon_back.png', 'أيقونة العودة لتسجيل الدخول', '2026-04-28 19:07:33'),
(267, 'header', 'icon_', 'icon', 'includesimg/header/icon_.png', 'مورد: icon_', '2026-04-26 20:02:02'),
(268, 'header', 'icon_timetables', 'icon', 'includesimg/header/icon_timetables.png', 'مورد: icon_timetables', '2026-04-26 20:02:44'),
(284, 'AboutUs', 'mission_check', 'image', 'uploads/design/AboutUs/mission_check.png', 'أيقونة الصح في المهام', '2026-04-30 20:58:28'),
(285, 'ContactUs', 'icon_working_hours', 'image', 'uploads/design/ContactUs/icon_working_hours.png', 'أيقونة بطاقة ساعات العمل', '2026-04-30 21:01:18'),
(286, 'Explore', 'tab_academic', 'image', 'uploads/design/Explore/tab_academic.png', 'أيقونة تبويب الشعب والأساتذة', '2026-04-30 21:20:45'),
(288, 'AboutUs', 'feature_search', 'image', 'uploads/design/AboutUs/feature_search.png', 'أيقونة ميزة البحث', '2026-04-30 20:58:29'),
(289, 'AboutUs', 'feature_upload', 'image', 'uploads/design/AboutUs/feature_upload.png', 'أيقونة ميزة الرفع', '2026-04-30 20:58:29'),
(290, 'AboutUs', 'feature_users', 'image', 'uploads/design/AboutUs/feature_users.png', 'أيقونة ميزة المستخدمين', '2026-04-30 20:58:29'),
(291, 'AboutUs', 'feature_comments', 'image', 'uploads/design/AboutUs/feature_comments.png', 'أيقونة ميزة التعليقات', '2026-04-30 20:58:30'),
(292, 'AboutUs', 'story_quote', 'image', 'uploads/design/AboutUs/story_quote.png', 'أيقونة الاقتباس في القصة', '2026-04-30 20:58:30'),
(293, 'AboutUs', 'team1_linkedin', 'image', 'uploads/design/AboutUs/team1_linkedin.png', 'عضو الفريق 1 - لينكد إن', '2026-04-30 20:58:30'),
(294, 'AboutUs', 'team1_github', 'image', 'uploads/design/AboutUs/team1_github.png', 'عضو الفريق 1 - جيت هاب', '2026-04-30 20:58:30'),
(295, 'AboutUs', 'team2_linkedin', 'image', 'uploads/design/AboutUs/team2_linkedin.png', 'عضو الفريق 2 - لينكد إن', '2026-04-30 20:58:30'),
(296, 'AboutUs', 'team2_twitter', 'image', 'uploads/design/AboutUs/team2_twitter.png', 'عضو الفريق 2 - تويتر', '2026-04-30 20:58:30'),
(297, 'AboutUs', 'team3_linkedin', 'image', 'uploads/design/AboutUs/team3_linkedin.png', 'عضو الفريق 3 - لينكد إن', '2026-04-30 20:58:30'),
(298, 'AboutUs', 'team3_facebook', 'image', 'uploads/design/AboutUs/team3_facebook.png', 'عضو الفريق 3 - فيسبوك', '2026-04-30 20:58:30'),
(299, 'AboutUs', 'cta_signup', 'image', 'uploads/design/AboutUs/cta_signup.png', 'أيقونة زر إنشاء حساب', '2026-04-30 20:58:31'),
(300, 'AboutUs', 'cta_contact', 'image', 'uploads/design/AboutUs/cta_contact.png', 'أيقونة زر اتصل بنا', '2026-04-30 20:58:31'),
(302, 'ContactUs', 'icon_email_card', 'image', 'uploads/design/ContactUs/icon_email_card.png', 'أيقونة بطاقة البريد الإلكتروني', '2026-04-30 21:01:18'),
(303, 'ContactUs', 'icon_social_card', 'image', 'uploads/design/ContactUs/icon_social_card.png', 'أيقونة بطاقة التواصل الاجتماعي', '2026-04-30 21:01:18'),
(304, 'ContactUs', 'team1_avatar', 'image', 'uploads/design/ContactUs/team1_avatar.png', 'صورة الفريق 1 (المدير)', '2026-04-30 21:01:18'),
(305, 'ContactUs', 'team2_avatar', 'image', 'uploads/design/ContactUs/team2_avatar.png', 'صورة الفريق 2 (خدمة العملاء)', '2026-04-30 21:01:19'),
(306, 'ContactUs', 'team3_avatar', 'image', 'uploads/design/ContactUs/team3_avatar.png', 'صورة الفريق 3 (الدعم الفني)', '2026-04-30 21:01:19'),
(307, 'FAQ', 'icon_general', 'image', 'uploads/design/FAQ/icon_general.png', 'أيقونة قسم \"الأسئلة العامة\"', '2026-04-30 21:07:09'),
(308, 'FAQ', 'icon_account', 'image', 'uploads/design/FAQ/icon_account.png', 'أيقونة قسم \"الحساب والتسجيل\"', '2026-04-30 21:07:10'),
(309, 'FAQ', 'icon_community', 'image', 'uploads/design/FAQ/icon_community.png', 'أيقونة قسم \"ساحة المشاركات\"', '2026-04-30 21:07:10'),
(310, 'FAQ', 'search_icon', 'image', 'uploads/design/FAQ/search_icon.png', 'أيقونة حقل البحث', '2026-04-30 21:07:10'),
(311, 'FAQ', 'no_results_icon', 'image', 'uploads/design/FAQ/no_results_icon.png', 'أيقونة \"لا توجد نتائج\"', '2026-04-30 21:07:10'),
(312, 'FAQ', 'toggle_icon', 'image', 'uploads/design/FAQ/toggle_icon.png', 'أيقونة السهم لفتح السؤال (Toggle)', '2026-04-30 21:07:10'),
(313, 'FAQ', 'cta_support_icon', 'image', 'uploads/design/FAQ/cta_support_icon.png', 'أيقونة زر الدعم الفني', '2026-04-30 21:07:11'),
(314, 'PrivacyPolicy', 'icon_shield', 'image', 'uploads/design/PrivacyPolicy/icon_shield.png', 'أيقونة جمع المعلومات (درع)', '2026-04-30 21:11:17'),
(315, 'PrivacyPolicy', 'icon_database', 'image', 'uploads/design/PrivacyPolicy/icon_database.png', 'أيقونة نوع البيانات (قاعدة بيانات)', '2026-04-30 21:11:17'),
(316, 'PrivacyPolicy', 'icon_cookie', 'image', 'uploads/design/PrivacyPolicy/icon_cookie.png', 'أيقونة ملفات الارتباط (Cookie)', '2026-04-30 21:11:18'),
(317, 'PrivacyPolicy', 'icon_cogs', 'image', 'uploads/design/PrivacyPolicy/icon_cogs.png', 'أيقونة استخدام المعلومات (إعدادات)', '2026-04-30 21:11:18'),
(318, 'PrivacyPolicy', 'icon_share', 'image', 'uploads/design/PrivacyPolicy/icon_share.png', 'أيقونة مشاركة المعلومات', '2026-04-30 21:11:18'),
(319, 'PrivacyPolicy', 'icon_lock', 'image', 'uploads/design/PrivacyPolicy/icon_lock.png', 'أيقونة حماية البيانات (قفل)', '2026-04-30 21:11:18'),
(320, 'PrivacyPolicy', 'icon_user_shield', 'image', 'uploads/design/PrivacyPolicy/icon_user_shield.png', 'أيقونة حماية الحسابات', '2026-04-30 21:11:18'),
(321, 'PrivacyPolicy', 'icon_server', 'image', 'uploads/design/PrivacyPolicy/icon_server.png', 'أيقونة حماية الخوادم', '2026-04-30 21:11:18'),
(322, 'PrivacyPolicy', 'icon_info', 'image', 'uploads/design/PrivacyPolicy/icon_info.png', 'أيقونة ملاحظة (Info)', '2026-04-30 21:11:18'),
(323, 'PrivacyPolicy', 'icon_check', 'image', 'uploads/design/PrivacyPolicy/icon_check.png', 'أيقونة علامة الصح (في الإحصائيات)', '2026-04-30 21:11:18'),
(324, 'PrivacyPolicy', 'icon_envelope', 'image', 'uploads/design/PrivacyPolicy/icon_envelope.png', 'أيقونة البريد (زر المراسلة)', '2026-04-30 21:11:19'),
(325, 'TermsOfService', 'icon_handshake', 'image', 'uploads/design/TermsOfService/icon_handshake.png', 'أيقونة بند المقدمة وقبول الشروط', '2026-04-30 21:15:21'),
(326, 'TermsOfService', 'icon_user_circle', 'image', 'uploads/design/TermsOfService/icon_user_circle.png', 'أيقونة بند الحسابات والتسجيل', '2026-04-30 21:15:21'),
(327, 'TermsOfService', 'icon_file_upload', 'image', 'uploads/design/TermsOfService/icon_file_upload.png', 'أيقونة بند المحتوى المرفوع', '2026-04-30 21:15:21'),
(328, 'TermsOfService', 'icon_ban', 'image', 'uploads/design/TermsOfService/icon_ban.png', 'أيقونة تحذير المحتوى المحظور', '2026-04-30 21:15:21'),
(329, 'TermsOfService', 'icon_gavel', 'image', 'uploads/design/TermsOfService/icon_gavel.png', 'أيقونة القانون وحل النزاعات (مطرقة)', '2026-04-30 21:15:21'),
(330, 'TermsOfService', 'icon_headset', 'image', 'uploads/design/TermsOfService/icon_headset.png', 'أيقونة زر التواصل والدعم', '2026-04-30 21:15:21'),
(332, 'Explore', 'tab_lectures', 'image', 'uploads/design/Explore/tab_lectures.png', 'أيقونة تبويب المحاضرات', '2026-04-30 21:20:46'),
(333, 'Explore', 'tab_exams', 'image', 'uploads/design/Explore/tab_exams.png', 'أيقونة تبويب الامتحانات', '2026-04-30 21:20:46'),
(334, 'Explore', 'tab_community', 'image', 'uploads/design/Explore/tab_community.png', 'أيقونة تبويب المجتمع', '2026-04-30 21:20:46'),
(335, 'Explore', 'icon_professor', 'image', 'uploads/design/Explore/icon_professor.png', 'أيقونة الأستاذ', '2026-04-30 21:20:46'),
(336, 'Explore', 'empty_lectures', 'image', 'uploads/design/Explore/empty_lectures.png', 'أيقونة حالة \"لا توجد محاضرات\"', '2026-04-30 21:20:46'),
(337, 'Explore', 'empty_exams', 'image', 'uploads/design/Explore/empty_exams.png', 'أيقونة حالة \"لا توجد امتحانات\"', '2026-04-30 21:20:46'),
(338, 'Explore', 'empty_community', 'image', 'uploads/design/Explore/empty_community.png', 'أيقونة حالة \"لا توجد منشورات\"', '2026-04-30 21:20:47'),
(339, 'Explore', 'meta_subject', 'image', 'uploads/design/Explore/meta_subject.png', 'أيقونة المادة (في البطاقات)', '2026-04-30 21:20:47'),
(340, 'Explore', 'meta_professor', 'image', 'uploads/design/Explore/meta_professor.png', 'أيقونة الأستاذ (في بطاقة المحاضرة)', '2026-04-30 21:20:47'),
(341, 'Explore', 'meta_date', 'image', 'uploads/design/Explore/meta_date.png', 'أيقونة التاريخ (في بطاقة الامتحان)', '2026-04-30 21:20:47'),
(342, 'Explore', 'btn_download', 'image', 'uploads/design/Explore/btn_download.png', 'أيقونة زر التحميل', '2026-04-30 21:20:47'),
(343, 'Explore', 'btn_like', 'image', 'uploads/design/Explore/btn_like.png', 'أيقونة الإعجاب', '2026-04-30 21:20:47'),
(344, 'Explore', 'btn_comment', 'image', 'uploads/design/Explore/btn_comment.png', 'أيقونة التعليق', '2026-04-30 21:20:47'),
(345, 'Explore', 'modal_lock', 'image', 'uploads/design/Explore/modal_lock.png', 'أيقونة القفل (النافذة المنبثقة)', '2026-04-30 21:20:48'),
(346, 'Explore', 'modal_signup', 'image', 'uploads/design/Explore/modal_signup.png', 'أيقونة زر التسجيل', '2026-04-30 21:20:48'),
(347, 'Explore', 'modal_login', 'image', 'uploads/design/Explore/modal_login.png', 'أيقونة زر الدخول', '2026-04-30 21:20:48');

-- --------------------------------------------------------

--
-- بنية الجدول `divisions`
--

DROP TABLE IF EXISTS `divisions`;
CREATE TABLE `divisions` (
  `division_id` int(11) NOT NULL,
  `division_name` varchar(100) NOT NULL,
  `division_image` varchar(255) DEFAULT 'default_division.png',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `divisions`
--

INSERT INTO `divisions` (`division_id`, `division_name`, `division_image`, `is_deleted`, `deleted_at`) VALUES
(3, 'شعبة الدراسات الإسلامية', 'div_6a38783d734cf.png', 0, NULL),
(4, 'شعبة الدراسات العربية', 'div_6a3878549cea3.png', 0, NULL),
(5, 'مسلك التاريخ والتراث والحضارة', 'div_6a3878a895e22.png', 0, NULL),
(6, 'مسلك الجغرافيا', 'div_6a3878b40b7da.png', 0, NULL),
(7, 'شعبة الدراسات الفرنسية', 'div_6a38786d350a8.png', 0, NULL),
(8, 'شعبة الفلسفة', 'div_6a3878905eec9.png', 0, NULL),
(9, 'مسلك علم الاجتماع', 'div_6a3878c69b4ad.png', 0, NULL),
(10, 'شعبة الدراسات الإنجليزية', 'div_6a38782b5fe7f.png', 0, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `enrollment_change_requests`
--

DROP TABLE IF EXISTS `enrollment_change_requests`;
CREATE TABLE `enrollment_change_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `current_enrollment_info` text NOT NULL,
  `requested_offering_id` int(11) NOT NULL,
  `requested_subjects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `requested_offering_id` (`requested_offering_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- إرجاع أو استيراد بيانات الجدول `enrollment_change_requests`
--

INSERT INTO `enrollment_change_requests` (`request_id`, `user_id`, `current_enrollment_info`, `requested_offering_id`, `requested_subjects`, `reason`, `status`, `request_date`) VALUES
(1, 2, 'شعبة الدراسات الاسلامية > الفصل الأول / الفوج 1\n', 2, NULL, '', 'completed', '2025-08-21 22:55:35'),
(2, 1, 'شعبة الدراسات الاسلامية > الفصل الأول / الفوج 1\nشعبة الدراسات الاسلامية > الفصل الأول / الفوج 2\n', 3, NULL, 'بلغهفةغ_ق-', 'rejected', '2025-08-23 20:24:55'),
(3, 1, 'شعبة الدراسات الاسلامية > الفصل الأول / ✨ G 1 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G 2 ✨\n', 3, NULL, 'ثفèعه-ف', 'rejected', '2025-09-30 19:40:21'),
(4, 1, 'شعبة الدراسات الاسلامية > الفصل الثالث / ✨ G1 / G2 / G3  ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G3 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G 4 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G 5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G2 /G1 ✨\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الخامس / ✨ G2 /G1 ✨ / الداسات القرآنية والحديثية وقضايا العصر\nشعبة الدراسات الاسلامية > الفصل الخامس / ✨ G1 ✨ / الدراسات العقائدية والأصولية وقضايا العصر\n', 10, NULL, 'ثصضق', 'rejected', '2025-10-03 21:00:31'),
(5, 1, 'شعبة الدراسات الاسلامية > الفصل الثالث / ✨ G1 / G2 / G3  ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G3 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G 4 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G 5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G2 /G1 ✨\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الخامس / ✨ G2 /G1 ✨ / الداسات القرآنية والحديثية وقضايا العصر\nشعبة الدراسات الاسلامية > الفصل الخامس / ✨ G1 ✨ / الدراسات العقائدية والأصولية وقضايا العصر\n', 13, NULL, 'ثصضق', 'rejected', '2025-10-03 21:00:31'),
(6, 9, 'شعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\n', 12, NULL, '(غاث', 'completed', '2025-10-10 06:18:05'),
(7, 9, 'شعبة الدراسات الاسلامية > الفصل الثالث / ✨ G1 / G2 / G3  ✨\n', 8, NULL, 'ث(فش', 'completed', '2025-10-10 06:19:57'),
(8, 9, 'شعبة الدراسات الاسلامية > الفصل الثالث / ✨ G1 / G2 / G3  ✨\n', 17, NULL, 'ث(فش', 'completed', '2025-10-10 06:19:57'),
(9, 108, 'شعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\n', 17, NULL, '', 'completed', '2025-10-13 01:06:32'),
(10, 108, 'شعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\n', 12, NULL, '', 'completed', '2025-10-13 01:06:32'),
(11, 1, 'شعبة الدراسات الاسلامية > الفصل الثالث / ✨ G1 / G2 / G3  ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨G1 / G2 / G3  ✨\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الخامس / ✨ G1 ✨ / الدراسات العقائدية والأصولية وقضايا العصر\nشعبة الدراسات الاسلامية > الفصل الخامس / ✨ G1 / G2✨ / الداسات القرآنية والحديثية وقضايا العصر\n', 15, NULL, '', 'rejected', '2025-10-13 16:08:49'),
(12, 133, 'شعبة الدراسات الاسلامية > الفصل الأول / ✨G1 / G2 / G3  ✨\n', 11, NULL, '', 'completed', '2025-11-21 21:17:14'),
(13, 143, 'شعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\n', 11, NULL, '', 'completed', '2025-12-21 11:18:24'),
(14, 143, 'شعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\n', 13, NULL, '', 'completed', '2025-12-21 11:18:24'),
(15, 169, 'الدراسات الانجليزية > الفصل الأول / ✨ G1 ✨\n', 21, NULL, '', 'completed', '2026-03-17 16:06:03'),
(16, 1, 'شعبة الدراسات الاسلامية > الفصل الخامس / ✨ G1 / G2✨ / الداسات القرآنية والحديثية وقضايا العصر\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨G1 / G2 / G3  ✨\nشعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\nشعبة الدراسات الاسلامية > الفصل الثالث / ✨ G1 / G2 / G3  ✨\n', 11, NULL, 'ض\"ث(غف', 'completed', '2026-03-26 14:39:47'),
(17, 171, 'شعبة الدراسات الاسلامية > الفصل الخامس / ✨ G1 / G2✨ / الداسات القرآنية والحديثية وقضايا العصر\n', 33, NULL, 'قفعاغثفق', 'completed', '2026-03-26 18:10:29'),
(18, 1, 'شعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\n', 23, NULL, '', 'completed', '2026-05-06 18:05:57'),
(19, 1, 'شعبة الدراسات الاسلامية > الفصل الأول / ✨ G4 / G5 / G6 ✨\n', 13, NULL, '', 'completed', '2026-05-06 18:05:58'),
(20, 172, 'الطالب غير مسجل حالياً في أي مقرر.', 41, NULL, '', 'completed', '2026-06-19 18:37:55'),
(21, 172, 'شعبة الدراسات الإسلامية > الفصل الثاني / الفوج 6\n', 226, '[108, 109, 110, 111, 112, 113, 114]', '', 'completed', '2026-06-21 11:25:57'),
(22, 172, 'شعبة الدراسات الإسلامية > الفصل الثاني / الفوج 1\nشعبة الدراسات الإسلامية > الفصل الثاني / الفوج 3\n', 294, '[801, 803, 804, 805, 806, 807]', '', 'completed', '2026-06-21 11:56:03'),
(23, 172, 'شعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 3\nشعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\n', 201, '[801, 803, 804, 805, 806, 807]', '', 'completed', '2026-06-21 12:06:15'),
(24, 172, 'شعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 3\nشعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\n', 211, '[822, 823, 824, 825, 826, 827]', '', 'completed', '2026-06-21 12:06:15'),
(25, 172, 'شعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\nشعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\nشعبة الدراسات الإنجليزية > الفصل الرابع / الفوج 1\n', 227, '[108,109,110,111,112,113,114]', '', 'completed', '2026-06-28 21:08:28'),
(26, 172, 'شعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\nشعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\nشعبة الدراسات الإنجليزية > الفصل الرابع / الفوج 1\n', 232, '[122,123,124,125,126,128]', '', 'completed', '2026-06-28 21:08:28'),
(27, 172, 'شعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\nشعبة الدراسات الإنجليزية > الفصل الرابع / الفوج 1\nشعبة الدراسات الإسلامية > الفصل الثاني / الفوج 2\nشعبة الدراسات الإسلامية > الفصل الرابع / الفوج 1\n', 36, '[108,109,110,111,112,113,114]', '', 'completed', '2026-06-28 21:25:41'),
(28, 172, 'شعبة الدراسات الإنجليزية > الفصل الثاني / الفوج 1\nشعبة الدراسات الإنجليزية > الفصل الرابع / الفوج 1\nشعبة الدراسات الإسلامية > الفصل الثاني / الفوج 2\nشعبة الدراسات الإسلامية > الفصل الرابع / الفوج 1\n', 42, '[122,123,124,125,126,128]', '', 'completed', '2026-06-28 21:25:41'),
(29, 7, 'شعبة الدراسات الفرنسية > الفصل الرابع / الفوج 1\n', 45, '[122,123,124,125,126,128]', '', 'completed', '2026-07-05 15:29:52'),
(30, 167, 'الطالب غير مسجل حالياً في أي مقرر.', 37, '[108,109,110,111,112,113,114]', '', 'completed', '2026-07-07 06:31:43');

-- --------------------------------------------------------

--
-- بنية الجدول `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT 1,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `offering_subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `uploader_user_id` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `division_id` int(11) DEFAULT NULL,
  `offering_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `global_announcements`
--

DROP TABLE IF EXISTS `global_announcements`;
CREATE TABLE `global_announcements` (
  `announcement_id` int(11) NOT NULL,
  `admin_user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `global_announcements`
--

INSERT INTO `global_announcements` (`announcement_id`, `admin_user_id`, `title`, `content`, `created_at`) VALUES
(1, 1, 'صش(ث', 'قسف', '2025-08-21 22:30:46'),
(2, 1, 'صش(ث', 'قسف', '2025-08-21 22:35:47'),
(3, 1, 'ققف', 'فغ', '2025-08-21 22:36:06'),
(4, 1, 'يف', 'يفسا', '2025-08-21 22:36:18'),
(5, 1, 'يغع', 'قغ', '2025-08-24 11:52:12'),
(6, 1, 'بل', 'سيال', '2025-08-24 12:00:33'),
(7, 1, 'بل', 'سيال', '2025-08-24 12:05:51'),
(8, 1, 'بل', 'سيال', '2025-08-24 12:06:02'),
(9, 1, 'بل', 'سيال', '2025-08-24 12:17:10'),
(10, 1, 'رلا', 'يفغا', '2025-08-24 12:22:09'),
(11, 1, 'ض', 'ضسئ', '2025-10-03 17:25:57'),
(12, 1, 'rh', 'tfrbh', '2025-10-06 16:31:18'),
(13, 1, 'ثغ', 'فغي', '2025-10-10 06:12:34'),
(14, 1, 'ابع', 'غعغ', '2025-10-10 06:14:59'),
(15, 1, 'صغا', 'قيفللا', '2025-10-10 06:16:03'),
(16, 1, 'لغقف', 'قفل', '2025-10-10 06:16:42'),
(17, 1, 'رسالة ترحيبية', 'أرحب بجميع الطلبة نتمنتا تعجبكم المنصة', '2025-10-10 23:49:53'),
(18, 1, 'إعلان بخصوص المحاضرات', 'أرحب بكم ممجداا في النصة بخصوص المحاضرات سيتم نشرها في الايام القادمة ان شاء الله تحياتي لكم بالتوفيق والسداد', '2025-10-12 20:41:09');

-- --------------------------------------------------------

--
-- بنية الجدول `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_image` varchar(255) DEFAULT 'default_group.png',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `group_image`, `is_deleted`, `deleted_at`) VALUES
(1, 'الفوج 1', 'grp_6a387925dcc72.png', 0, NULL),
(2, 'الفوج 2', 'grp_6a3878f994565.png', 0, NULL),
(3, 'الفوج 3', 'grp_6a387969420a6.png', 0, NULL),
(4, 'الفوج 4', 'grp_6a3879781a49b.png', 0, NULL),
(5, 'الفوج 5', 'grp_6a387983294a5.png', 0, NULL),
(6, 'الفوج 6', 'grp_6a38799380f71.png', 0, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `independent_whatsapp_groups`
--

DROP TABLE IF EXISTS `independent_whatsapp_groups`;
CREATE TABLE `independent_whatsapp_groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_link` varchar(255) NOT NULL,
  `added_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- إرجاع أو استيراد بيانات الجدول `independent_whatsapp_groups`
--

INSERT INTO `independent_whatsapp_groups` (`group_id`, `group_name`, `group_link`, `added_by_user_id`, `created_at`) VALUES
(4, 's3 islamique', 'https://chat.whatsapp.com/IkKx5ROVe185jxWcWiGMbC?mode=ems_copy_t', 1, '2025-10-11 17:50:31'),
(3, 's1 islamique', 'https://chat.whatsapp.com/FPjoTRochrt7EIjkqiIzkx?mode=ems_copy_t', 1, '2025-10-11 17:48:57'),
(5, 'g1', 'https://chat.whatsapp.com/FPjoTRochrt7EIjkqiIzkx?mode=ems_copy_t', 1, '2026-03-24 13:11:16');

-- --------------------------------------------------------

--
-- بنية الجدول `lectures`
--

DROP TABLE IF EXISTS `lectures`;
CREATE TABLE `lectures` (
  `lecture_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT 1,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `offering_id` int(11) DEFAULT NULL,
  `offering_subject_id` int(11) DEFAULT NULL,
  `uploader_user_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'pending',
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `lectures`
--

INSERT INTO `lectures` (`lecture_id`, `academic_year_id`, `title`, `description`, `file_path`, `file_type`, `cover_image`, `offering_id`, `offering_subject_id`, `uploader_user_id`, `status`, `upload_date`, `is_deleted`, `deleted_at`, `created_by`) VALUES
(55, 1, 'شببيسل', 'سثشثيب', 'uploads/lectures/lec_6a35900575a9c.jpg', 'jpg', NULL, 41, 515, 172, 'approved', '2026-06-19 18:52:53', 0, NULL, NULL),
(56, 2, 'wfsv', 'qedfvc', 'uploads/lectures/lec_6a413162bba6b.png', 'png', 'uploads/covers/cover_6a413162bbef7.png', 201, 985, 172, 'approved', '2026-06-28 14:36:18', 0, NULL, NULL),
(57, 2, 'wfsv', 'qedfvc', 'uploads/lectures/lec_6a413162bba6b.png', 'png', 'uploads/covers/cover_6a413162bbef7.png', 292, 1541, 172, 'approved', '2026-06-28 14:36:18', 0, NULL, NULL),
(58, 1, 'tuo', 'to', 'uploads/lectures/شعبة_الدراسات_الإسلامية_الفصل_الثاني_الفوج_1_البلاغة_جبري_الظهراوي_tuo_5fff3.pdf', 'pdf', 'uploads/covers/cover_6a4c9b616026a.png', 36, 482, 172, 'approved', '2026-07-07 06:23:29', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(2144, 172, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي.', 'student/profil.php', 1, '2026-06-19 18:38:07'),
(2145, 172, 'تم نشر محاضرة جديدة: شببيسل', 'student/class_lectures.php?id=41', 1, '2026-06-19 18:52:53'),
(2146, 172, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي.', 'student/profil.php', 1, '2026-06-21 11:26:20'),
(2147, 172, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي.', 'student/profil.php', 1, '2026-06-21 11:56:12'),
(2148, 172, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي واعتماد المواد التي اخترتها.', 'student/profil.php', 1, '2026-06-21 12:12:29'),
(2149, 172, 'تم نشر محاضرة جديدة: wfsv', 'student/class_lectures.php?id=201', 1, '2026-06-28 14:36:18'),
(2150, 172, 'تم نشر محاضرة جديدة: wfsv', 'student/class_lectures.php?id=292', 1, '2026-06-28 14:36:18'),
(2151, 172, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي واعتماد المواد التي اخترتها.', 'student/profil.php', 1, '2026-06-28 21:08:37'),
(2152, 172, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي واعتماد المواد التي اخترتها.', 'student/profil.php', 1, '2026-06-28 21:25:50'),
(2153, 7, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي واعتماد المواد التي اخترتها.', 'student/profil.php', 0, '2026-07-05 15:33:38'),
(2154, 189, 'تهانينا! تم قبول منشورك: \'مزيدا من التوفيق وجزاك لله خيرا عن مجهودك...\'', 'student/community/index.php', 0, '2026-07-06 07:35:26'),
(2155, 172, 'تم قبول ونشر محاضرتك: tuo', 'student/class_lectures.php?id=36', 1, '2026-07-07 06:24:14'),
(2156, 167, 'تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي واعتماد المواد التي اخترتها.', 'student/profil.php', 0, '2026-07-07 06:32:17'),
(2157, 167, 'تم رفض محاضرتك \'بلت\' للسبب التالي: هه', '#', 0, '2026-07-08 13:10:36'),
(2158, 172, 'لقد تلقيت رداً من الإدارة على رسالتك.', 'student/contact/index.php', 1, '2026-07-13 13:18:18');

-- --------------------------------------------------------

--
-- بنية الجدول `offering_subjects`
--

DROP TABLE IF EXISTS `offering_subjects`;
CREATE TABLE `offering_subjects` (
  `offering_subject_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `professor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `offering_subjects`
--

INSERT INTO `offering_subjects` (`offering_subject_id`, `offering_id`, `subject_id`, `professor_id`) VALUES
(478, 36, 109, 51),
(479, 36, 114, 52),
(480, 36, 112, 53),
(481, 36, 113, 54),
(482, 36, 111, 55),
(483, 36, 108, 56),
(484, 36, 110, 57),
(485, 37, 109, 51),
(486, 37, 114, 52),
(487, 37, 112, 53),
(488, 37, 113, 54),
(489, 37, 111, 55),
(490, 37, 108, 56),
(491, 37, 110, 57),
(492, 38, 109, 51),
(493, 38, 113, 58),
(494, 38, 114, 59),
(495, 38, 111, 60),
(496, 38, 112, 61),
(497, 38, 108, 56),
(498, 38, 110, 57),
(499, 39, 113, 58),
(500, 39, 114, 59),
(501, 39, 111, 60),
(502, 39, 108, 61),
(503, 39, 112, 61),
(504, 39, 109, 62),
(505, 39, 110, 57),
(506, 40, 113, 58),
(507, 40, 112, 58),
(508, 40, 114, 63),
(509, 40, 111, 64),
(510, 40, 108, 61),
(511, 40, 109, 62),
(512, 40, 110, 57),
(513, 41, 113, 58),
(514, 41, 112, 58),
(515, 41, 114, 63),
(516, 41, 111, 64),
(517, 41, 108, 61),
(518, 41, 109, 62),
(519, 41, 110, 57),
(520, 42, 125, 71),
(521, 42, 128, 72),
(522, 42, 123, 73),
(523, 42, 126, 74),
(524, 42, 124, 75),
(525, 42, 122, 76),
(526, 43, 125, 71),
(527, 43, 128, 72),
(528, 43, 123, 73),
(529, 43, 126, 74),
(530, 43, 124, 75),
(531, 43, 122, 76),
(532, 44, 125, 71),
(533, 44, 128, 72),
(534, 44, 123, 77),
(535, 44, 126, 74),
(536, 44, 124, 75),
(537, 44, 122, 76),
(538, 45, 125, 71),
(539, 45, 123, 77),
(540, 45, 128, 72),
(541, 45, 126, 74),
(542, 45, 122, 76),
(543, 45, 124, 75),
(544, 46, 140, 81),
(545, 46, 139, 82),
(546, 46, 142, 83),
(547, 46, 137, 84),
(548, 46, 138, 85),
(549, 46, 136, 86),
(550, 46, 141, 87),
(551, 47, 140, 81),
(552, 47, 139, 82),
(553, 47, 142, 83),
(554, 47, 137, 84),
(555, 47, 138, 85),
(556, 47, 136, 86),
(557, 47, 141, 87),
(558, 48, 146, 81),
(559, 48, 142, 83),
(560, 48, 143, 88),
(561, 48, 144, 89),
(562, 48, 145, 90),
(563, 48, 136, 86),
(564, 48, 141, 87),
(565, 51, 203, 101),
(566, 51, 202, 102),
(567, 51, 206, 103),
(568, 51, 204, 104),
(569, 51, 207, 105),
(570, 51, 201, 106),
(571, 51, 205, 107),
(572, 52, 203, 101),
(573, 52, 202, 102),
(574, 52, 206, 103),
(575, 52, 204, 104),
(576, 52, 207, 105),
(577, 52, 201, 106),
(578, 52, 205, 107),
(579, 53, 202, 102),
(580, 53, 206, 103),
(581, 53, 204, 109),
(582, 53, 201, 106),
(583, 53, 203, 108),
(584, 53, 205, 107),
(585, 53, 207, 110),
(586, 54, 202, 102),
(587, 54, 204, 109),
(588, 54, 201, 106),
(589, 54, 203, 108),
(590, 54, 205, 107),
(591, 54, 207, 110),
(592, 54, 206, 111),
(593, 55, 203, 101),
(594, 55, 202, 102),
(595, 55, 204, 109),
(596, 55, 201, 106),
(597, 55, 205, 107),
(598, 55, 207, 110),
(599, 55, 206, 111),
(600, 56, 203, 101),
(601, 56, 202, 102),
(602, 56, 204, 109),
(603, 56, 201, 106),
(604, 56, 205, 107),
(605, 56, 207, 112),
(606, 56, 206, 111),
(607, 61, 221, 121),
(608, 61, 222, 122),
(609, 61, 223, 123),
(610, 61, 224, 124),
(611, 61, 225, 125),
(612, 61, 226, 126),
(613, 61, 227, 127),
(614, 62, 221, 121),
(615, 62, 222, 122),
(616, 62, 223, 123),
(617, 62, 224, 124),
(618, 62, 225, 125),
(619, 62, 226, 126),
(620, 62, 227, 127),
(621, 63, 227, 128),
(622, 63, 221, 121),
(623, 63, 223, 123),
(624, 63, 222, 122),
(625, 63, 224, 124),
(626, 63, 226, 126),
(627, 63, 225, 129),
(628, 64, 227, 128),
(629, 64, 221, 121),
(630, 64, 223, 123),
(631, 64, 222, 122),
(632, 64, 224, 124),
(633, 64, 226, 126),
(634, 64, 225, 129),
(635, 71, 251, 151),
(636, 71, 252, 152),
(637, 71, 253, 153),
(638, 71, 254, 154),
(639, 71, 255, 155),
(640, 71, 256, 156),
(641, 71, 257, 157),
(642, 72, 258, 158),
(643, 72, 259, 159),
(644, 72, 260, 160),
(645, 72, 254, 154),
(646, 72, 261, 161),
(647, 72, 262, 161),
(648, 72, 256, 156),
(677, 81, 301, 201),
(678, 81, 302, 202),
(679, 81, 303, 203),
(680, 81, 304, 204),
(681, 81, 305, 205),
(682, 81, 306, 206),
(683, 81, 307, 207),
(684, 82, 301, 201),
(685, 82, 302, 202),
(686, 82, 303, 203),
(687, 82, 304, 204),
(688, 82, 305, 205),
(689, 82, 306, 206),
(690, 82, 307, 207),
(691, 83, 302, 202),
(692, 83, 303, 208),
(693, 83, 304, 204),
(694, 83, 305, 205),
(695, 83, 306, 206),
(696, 83, 301, 209),
(697, 83, 307, 207),
(698, 91, 321, 221),
(699, 91, 322, 222),
(700, 91, 323, 223),
(701, 91, 324, 224),
(702, 91, 325, 225),
(703, 91, 326, 223),
(704, 91, 327, 226),
(705, 92, 321, 221),
(706, 92, 322, 222),
(707, 92, 323, 221),
(708, 92, 324, 223),
(709, 92, 325, 224),
(710, 92, 326, 225),
(711, 92, 327, 226),
(712, 96, 351, 257),
(713, 96, 352, 251),
(714, 96, 353, 252),
(715, 96, 354, 253),
(716, 96, 355, 254),
(717, 96, 356, 255),
(718, 96, 357, 256),
(719, 101, 401, 301),
(720, 101, 407, 302),
(721, 101, 402, 303),
(722, 101, 403, 304),
(723, 101, 404, 305),
(724, 101, 405, 306),
(725, 101, 406, 307),
(726, 102, 401, 301),
(727, 102, 402, 303),
(728, 102, 403, 304),
(729, 102, 407, 311),
(730, 102, 404, 305),
(731, 102, 405, 306),
(732, 102, 406, 307),
(733, 103, 406, 312),
(734, 103, 401, 301),
(735, 103, 402, 317),
(736, 103, 403, 304),
(737, 103, 407, 313),
(738, 103, 404, 305),
(739, 103, 405, 314),
(740, 104, 407, 315),
(741, 104, 401, 316),
(742, 104, 406, 312),
(743, 104, 402, 317),
(744, 104, 403, 304),
(745, 104, 404, 305),
(746, 104, 405, 314),
(747, 105, 402, 319),
(748, 105, 407, 311),
(749, 105, 406, 320),
(750, 105, 401, 316),
(751, 105, 403, 304),
(752, 105, 404, 305),
(753, 105, 405, 321),
(754, 106, 407, 311),
(755, 106, 402, 319),
(756, 106, 406, 312),
(757, 106, 401, 304),
(758, 106, 403, 304),
(759, 106, 404, 305),
(760, 106, 405, 321),
(761, 111, 421, 331),
(762, 111, 422, 332),
(763, 111, 423, 333),
(764, 111, 424, 334),
(765, 111, 425, 335),
(766, 111, 426, 336),
(767, 111, 427, 336),
(768, 112, 421, 331),
(769, 112, 422, 332),
(770, 112, 423, 333),
(771, 112, 424, 334),
(772, 112, 425, 335),
(773, 112, 426, 336),
(774, 112, 427, 336),
(775, 113, 422, 338),
(776, 113, 421, 331),
(777, 113, 423, 339),
(778, 113, 424, 334),
(779, 113, 425, 341),
(780, 113, 426, 336),
(781, 113, 427, 336),
(782, 114, 422, 338),
(783, 114, 421, 331),
(784, 114, 423, 339),
(785, 114, 424, 334),
(786, 114, 425, 341),
(787, 114, 426, 336),
(788, 114, 427, 336),
(789, 121, 451, 361),
(790, 121, 452, 362),
(791, 121, 453, 363),
(792, 121, 454, 364),
(793, 121, 455, 365),
(794, 121, 456, 366),
(795, 121, 457, 367),
(796, 122, 458, 368),
(797, 122, 459, 369),
(798, 122, 452, 362),
(799, 122, 451, 361),
(800, 122, 460, 370),
(801, 122, 461, 371),
(802, 122, 462, 371),
(803, 122, 457, 371),
(819, 131, 502, 401),
(820, 131, 504, 402),
(821, 131, 501, 403),
(822, 131, 503, 404),
(823, 131, 506, 405),
(824, 131, 505, 406),
(825, 131, 507, 407),
(826, 132, 502, 401),
(827, 132, 504, 408),
(828, 132, 501, 403),
(829, 132, 503, 404),
(830, 132, 506, 405),
(831, 132, 505, 406),
(832, 132, 507, 407),
(833, 133, 501, 401),
(834, 133, 502, 401),
(835, 133, 504, 409),
(836, 133, 507, 410),
(837, 133, 503, 404),
(838, 133, 506, 405),
(839, 133, 505, 406),
(840, 134, 501, 401),
(841, 134, 502, 401),
(842, 134, 504, 409),
(843, 134, 507, 410),
(844, 134, 503, 404),
(845, 134, 506, 405),
(846, 134, 505, 406),
(847, 135, 521, 421),
(848, 135, 522, 422),
(849, 135, 523, 423),
(850, 135, 524, 424),
(851, 135, 525, 425),
(852, 135, 526, 426),
(853, 135, 527, 427),
(854, 136, 521, 421),
(855, 136, 522, 422),
(856, 136, 525, 428),
(857, 136, 523, 429),
(858, 136, 526, 426),
(859, 136, 524, 426),
(860, 136, 527, 427),
(861, 141, 551, 451),
(862, 141, 552, 452),
(863, 141, 553, 452),
(864, 141, 554, 453),
(865, 141, 555, 454),
(866, 141, 556, 455),
(867, 141, 557, 456),
(868, 142, 551, 451),
(869, 142, 558, 457),
(870, 142, 559, 456),
(871, 142, 556, 455),
(872, 142, 560, 458),
(873, 142, 561, 457),
(874, 142, 557, 456),
(875, 151, 607, 501),
(876, 151, 605, 502),
(877, 151, 603, 503),
(878, 151, 602, 504),
(879, 151, 606, 503),
(880, 151, 604, 505),
(881, 151, 601, 506),
(882, 152, 607, 501),
(883, 152, 605, 502),
(884, 152, 603, 503),
(885, 152, 602, 504),
(886, 152, 606, 503),
(887, 152, 604, 505),
(888, 152, 601, 506),
(889, 155, 621, 521),
(890, 155, 622, 522),
(891, 155, 623, 523),
(892, 155, 624, 524),
(893, 155, 625, 523),
(894, 155, 626, 525),
(895, 155, 627, 525),
(896, 161, 651, 551),
(897, 161, 652, 552),
(898, 161, 653, 553),
(899, 161, 654, 553),
(900, 161, 655, 554),
(901, 161, 656, 554),
(902, 161, 657, 555),
(903, 171, 701, 601),
(904, 171, 702, 602),
(905, 171, 703, 603),
(906, 171, 704, 604),
(907, 171, 705, 605),
(908, 171, 706, 606),
(909, 171, 707, 607),
(910, 172, 707, 607),
(911, 172, 701, 601),
(912, 172, 702, 602),
(913, 172, 703, 603),
(914, 172, 704, 604),
(915, 172, 705, 605),
(916, 172, 706, 606),
(917, 173, 701, 601),
(918, 173, 702, 602),
(919, 173, 703, 603),
(920, 173, 704, 604),
(921, 173, 706, 608),
(922, 173, 705, 605),
(923, 173, 707, 607),
(924, 174, 701, 601),
(925, 174, 703, 603),
(926, 174, 704, 604),
(927, 174, 706, 608),
(928, 174, 705, 605),
(929, 174, 702, 609),
(930, 174, 707, 607),
(931, 175, 703, 603),
(932, 175, 704, 610),
(933, 175, 701, 601),
(934, 175, 706, 611),
(935, 175, 705, 612),
(936, 175, 702, 609),
(937, 175, 707, 607),
(938, 176, 704, 610),
(939, 176, 701, 601),
(940, 176, 703, 603),
(941, 176, 706, 611),
(942, 176, 705, 612),
(943, 176, 702, 609),
(944, 176, 707, 607),
(945, 181, 723, 621),
(946, 181, 727, 622),
(947, 181, 722, 627),
(948, 181, 725, 624),
(949, 181, 726, 625),
(950, 181, 724, 626),
(951, 182, 723, 621),
(952, 182, 727, 622),
(953, 182, 722, 623),
(954, 182, 725, 624),
(955, 182, 726, 625),
(956, 182, 724, 626),
(957, 182, 721, 627),
(958, 183, 723, 621),
(959, 183, 722, 623),
(960, 183, 725, 624),
(961, 183, 726, 625),
(962, 183, 727, 628),
(963, 183, 724, 626),
(964, 183, 721, 627),
(965, 184, 723, 621),
(966, 184, 722, 623),
(967, 184, 725, 624),
(968, 184, 726, 625),
(969, 184, 727, 628),
(970, 184, 724, 626),
(971, 184, 721, 627),
(973, 191, 751, 651),
(974, 191, 752, 652),
(975, 191, 753, 653),
(976, 191, 754, 654),
(977, 191, 755, 655),
(978, 191, 756, 656),
(979, 191, 757, 657),
(980, 201, 801, 701),
(981, 201, 802, 702),
(982, 201, 803, 703),
(983, 201, 804, 704),
(984, 201, 805, 705),
(985, 201, 806, 706),
(986, 201, 807, 707),
(987, 202, 801, 701),
(988, 202, 807, 701),
(989, 202, 802, 702),
(990, 202, 803, 703),
(991, 202, 804, 704),
(992, 202, 805, 705),
(993, 202, 806, 707),
(994, 203, 803, 708),
(995, 203, 801, 709),
(996, 203, 805, 702),
(997, 203, 804, 710),
(998, 203, 802, 711),
(999, 203, 806, 707),
(1000, 203, 807, 707),
(1001, 204, 803, 708),
(1002, 204, 805, 712),
(1003, 204, 801, 711),
(1004, 204, 807, 701),
(1005, 204, 804, 710),
(1006, 204, 802, 707),
(1007, 204, 806, 713),
(1008, 205, 807, 708),
(1009, 205, 805, 714),
(1010, 205, 804, 715),
(1011, 205, 801, 711),
(1012, 205, 803, 703),
(1013, 205, 806, 713),
(1014, 205, 802, 713),
(1015, 206, 807, 708),
(1016, 206, 805, 714),
(1017, 206, 803, 716),
(1018, 206, 804, 715),
(1019, 206, 801, 709),
(1020, 206, 806, 713),
(1021, 206, 802, 713),
(1022, 211, 821, 721),
(1023, 211, 822, 722),
(1024, 211, 823, 723),
(1025, 211, 824, 724),
(1026, 211, 825, 725),
(1027, 211, 826, 726),
(1028, 211, 827, 727),
(1029, 212, 825, 728),
(1030, 212, 821, 721),
(1031, 212, 822, 722),
(1032, 212, 823, 723),
(1033, 212, 824, 724),
(1034, 212, 826, 726),
(1035, 212, 827, 727),
(1036, 213, 821, 728),
(1037, 213, 827, 729),
(1038, 213, 822, 730),
(1039, 213, 826, 723),
(1040, 213, 824, 724),
(1041, 213, 823, 731),
(1042, 213, 825, 732),
(1043, 214, 821, 728),
(1044, 214, 822, 730),
(1045, 214, 827, 729),
(1046, 214, 824, 724),
(1047, 214, 826, 723),
(1048, 214, 823, 731),
(1049, 214, 825, 732),
(1050, 221, 851, 751),
(1051, 221, 852, 752),
(1052, 221, 853, 753),
(1053, 221, 854, 754),
(1054, 221, 855, 755),
(1055, 221, 856, 756),
(1056, 221, 857, 757),
(1057, 222, 858, 758),
(1058, 222, 859, 759),
(1059, 222, 860, 760),
(1060, 222, 856, 761),
(1061, 222, 861, 762),
(1062, 222, 857, 763),
(1063, 222, 862, 764),
(1064, 223, 851, 751),
(1065, 223, 852, 752),
(1066, 223, 855, 755),
(1067, 223, 854, 754),
(1068, 223, 853, 765),
(1069, 223, 856, 756),
(1070, 223, 857, 757),
(1071, 224, 858, 758),
(1072, 224, 859, 759),
(1073, 224, 860, 760),
(1074, 224, 856, 761),
(1075, 224, 861, 762),
(1076, 224, 857, 763),
(1077, 224, 862, 764),
(1078, 226, 108, 56),
(1079, 226, 109, 51),
(1080, 226, 110, 57),
(1081, 226, 111, 55),
(1082, 226, 112, 53),
(1083, 226, 113, 54),
(1084, 226, 114, 52),
(1085, 227, 108, 56),
(1086, 227, 109, 51),
(1087, 227, 110, 57),
(1088, 227, 111, 55),
(1089, 227, 112, 53),
(1090, 227, 113, 54),
(1091, 227, 114, 52),
(1092, 228, 108, 56),
(1093, 228, 109, 51),
(1094, 228, 110, 57),
(1095, 228, 111, 60),
(1096, 228, 112, 61),
(1097, 228, 113, 58),
(1098, 228, 114, 59),
(1099, 229, 108, 61),
(1100, 229, 109, 62),
(1101, 229, 110, 57),
(1102, 229, 111, 60),
(1103, 229, 112, 61),
(1104, 229, 113, 58),
(1105, 229, 114, 59),
(1106, 230, 108, 61),
(1107, 230, 109, 62),
(1108, 230, 110, 57),
(1109, 230, 111, 64),
(1110, 230, 112, 58),
(1111, 230, 113, 58),
(1112, 230, 114, 63),
(1113, 231, 108, 61),
(1114, 231, 109, 62),
(1115, 231, 110, 57),
(1116, 231, 111, 64),
(1117, 231, 112, 58),
(1118, 231, 113, 58),
(1119, 231, 114, 63),
(1120, 232, 122, 76),
(1121, 232, 123, 73),
(1122, 232, 124, 75),
(1123, 232, 125, 71),
(1124, 232, 126, 74),
(1125, 232, 128, 72),
(1126, 233, 122, 76),
(1127, 233, 123, 73),
(1128, 233, 124, 75),
(1129, 233, 125, 71),
(1130, 233, 126, 74),
(1131, 233, 128, 72),
(1132, 234, 122, 76),
(1133, 234, 123, 77),
(1134, 234, 124, 75),
(1135, 234, 125, 71),
(1136, 234, 126, 74),
(1137, 234, 128, 72),
(1138, 235, 122, 76),
(1139, 235, 123, 77),
(1140, 235, 124, 75),
(1141, 235, 125, 71),
(1142, 235, 126, 74),
(1143, 235, 128, 72),
(1144, 236, 136, 86),
(1145, 236, 137, 84),
(1146, 236, 138, 85),
(1147, 236, 139, 82),
(1148, 236, 140, 81),
(1149, 236, 141, 87),
(1150, 236, 142, 83),
(1151, 237, 136, 86),
(1152, 237, 137, 84),
(1153, 237, 138, 85),
(1154, 237, 139, 82),
(1155, 237, 140, 81),
(1156, 237, 141, 87),
(1157, 237, 142, 83),
(1158, 238, 136, 86),
(1159, 238, 141, 87),
(1160, 238, 142, 83),
(1161, 238, 143, 88),
(1162, 238, 144, 89),
(1163, 238, 145, 90),
(1164, 238, 146, 81),
(1165, 239, 201, 106),
(1166, 239, 202, 102),
(1167, 239, 203, 101),
(1168, 239, 204, 104),
(1169, 239, 205, 107),
(1170, 239, 206, 103),
(1171, 239, 207, 105),
(1172, 240, 201, 106),
(1173, 240, 202, 102),
(1174, 240, 203, 101),
(1175, 240, 204, 104),
(1176, 240, 205, 107),
(1177, 240, 206, 103),
(1178, 240, 207, 105),
(1179, 241, 201, 106),
(1180, 241, 202, 102),
(1181, 241, 203, 108),
(1182, 241, 204, 109),
(1183, 241, 205, 107),
(1184, 241, 206, 103),
(1185, 241, 207, 110),
(1186, 242, 201, 106),
(1187, 242, 202, 102),
(1188, 242, 203, 108),
(1189, 242, 204, 109),
(1190, 242, 205, 107),
(1191, 242, 206, 111),
(1192, 242, 207, 110),
(1193, 243, 201, 106),
(1194, 243, 202, 102),
(1195, 243, 203, 101),
(1196, 243, 204, 109),
(1197, 243, 205, 107),
(1198, 243, 206, 111),
(1199, 243, 207, 110),
(1200, 244, 201, 106),
(1201, 244, 202, 102),
(1202, 244, 203, 101),
(1203, 244, 204, 109),
(1204, 244, 205, 107),
(1205, 244, 206, 111),
(1206, 244, 207, 112),
(1207, 245, 221, 121),
(1208, 245, 222, 122),
(1209, 245, 223, 123),
(1210, 245, 224, 124),
(1211, 245, 225, 125),
(1212, 245, 226, 126),
(1213, 245, 227, 127),
(1214, 246, 221, 121),
(1215, 246, 222, 122),
(1216, 246, 223, 123),
(1217, 246, 224, 124),
(1218, 246, 225, 125),
(1219, 246, 226, 126),
(1220, 246, 227, 127),
(1221, 247, 221, 121),
(1222, 247, 222, 122),
(1223, 247, 223, 123),
(1224, 247, 224, 124),
(1225, 247, 225, 129),
(1226, 247, 226, 126),
(1227, 247, 227, 128),
(1228, 248, 221, 121),
(1229, 248, 222, 122),
(1230, 248, 223, 123),
(1231, 248, 224, 124),
(1232, 248, 225, 129),
(1233, 248, 226, 126),
(1234, 248, 227, 128),
(1235, 249, 251, 151),
(1236, 249, 252, 152),
(1237, 249, 253, 153),
(1238, 249, 254, 154),
(1239, 249, 255, 155),
(1240, 249, 256, 156),
(1241, 249, 257, 157),
(1242, 250, 254, 154),
(1243, 250, 256, 156),
(1244, 250, 258, 158),
(1245, 250, 259, 159),
(1246, 250, 260, 160),
(1247, 250, 261, 161),
(1248, 250, 262, 161),
(1249, 251, 301, 201),
(1250, 251, 302, 202),
(1251, 251, 303, 203),
(1252, 251, 304, 204),
(1253, 251, 305, 205),
(1254, 251, 306, 206),
(1255, 251, 307, 207),
(1256, 252, 301, 201),
(1257, 252, 302, 202),
(1258, 252, 303, 203),
(1259, 252, 304, 204),
(1260, 252, 305, 205),
(1261, 252, 306, 206),
(1262, 252, 307, 207),
(1263, 253, 301, 209),
(1264, 253, 302, 202),
(1265, 253, 303, 208),
(1266, 253, 304, 204),
(1267, 253, 305, 205),
(1268, 253, 306, 206),
(1269, 253, 307, 207),
(1270, 254, 321, 221),
(1271, 254, 322, 222),
(1272, 254, 323, 223),
(1273, 254, 324, 224),
(1274, 254, 325, 225),
(1275, 254, 326, 223),
(1276, 254, 327, 226),
(1277, 255, 321, 221),
(1278, 255, 322, 222),
(1279, 255, 323, 221),
(1280, 255, 324, 223),
(1281, 255, 325, 224),
(1282, 255, 326, 225),
(1283, 255, 327, 226),
(1284, 256, 351, 257),
(1285, 256, 352, 251),
(1286, 256, 353, 252),
(1287, 256, 354, 253),
(1288, 256, 355, 254),
(1289, 256, 356, 255),
(1290, 256, 357, 256),
(1291, 257, 401, 301),
(1292, 257, 402, 303),
(1293, 257, 403, 304),
(1294, 257, 404, 305),
(1295, 257, 405, 306),
(1296, 257, 406, 307),
(1297, 257, 407, 302),
(1298, 258, 401, 301),
(1299, 258, 402, 303),
(1300, 258, 403, 304),
(1301, 258, 404, 305),
(1302, 258, 405, 306),
(1303, 258, 406, 307),
(1304, 258, 407, 311),
(1305, 259, 401, 301),
(1306, 259, 402, 317),
(1307, 259, 403, 304),
(1308, 259, 404, 305),
(1309, 259, 405, 314),
(1310, 259, 406, 312),
(1311, 259, 407, 313),
(1312, 260, 401, 316),
(1313, 260, 402, 317),
(1314, 260, 403, 304),
(1315, 260, 404, 305),
(1316, 260, 405, 314),
(1317, 260, 406, 312),
(1318, 260, 407, 315),
(1319, 261, 401, 316),
(1320, 261, 402, 319),
(1321, 261, 403, 304),
(1322, 261, 404, 305),
(1323, 261, 405, 321),
(1324, 261, 406, 320),
(1325, 261, 407, 311),
(1326, 262, 401, 304),
(1327, 262, 402, 319),
(1328, 262, 403, 304),
(1329, 262, 404, 305),
(1330, 262, 405, 321),
(1331, 262, 406, 312),
(1332, 262, 407, 311),
(1333, 263, 421, 331),
(1334, 263, 422, 332),
(1335, 263, 423, 333),
(1336, 263, 424, 334),
(1337, 263, 425, 335),
(1338, 263, 426, 336),
(1339, 263, 427, 336),
(1340, 264, 421, 331),
(1341, 264, 422, 332),
(1342, 264, 423, 333),
(1343, 264, 424, 334),
(1344, 264, 425, 335),
(1345, 264, 426, 336),
(1346, 264, 427, 336),
(1347, 265, 421, 331),
(1348, 265, 422, 338),
(1349, 265, 423, 339),
(1350, 265, 424, 334),
(1351, 265, 425, 341),
(1352, 265, 426, 336),
(1353, 265, 427, 336),
(1354, 266, 421, 331),
(1355, 266, 422, 338),
(1356, 266, 423, 339),
(1357, 266, 424, 334),
(1358, 266, 425, 341),
(1359, 266, 426, 336),
(1360, 266, 427, 336),
(1361, 267, 451, 361),
(1362, 267, 452, 362),
(1363, 267, 453, 363),
(1364, 267, 454, 364),
(1365, 267, 455, 365),
(1366, 267, 456, 366),
(1367, 267, 457, 367),
(1368, 268, 451, 361),
(1369, 268, 452, 362),
(1370, 268, 457, 371),
(1371, 268, 458, 368),
(1372, 268, 459, 369),
(1373, 268, 460, 370),
(1374, 268, 461, 371),
(1375, 268, 462, 371),
(1376, 269, 501, 403),
(1377, 269, 502, 401),
(1378, 269, 503, 404),
(1379, 269, 504, 402),
(1380, 269, 505, 406),
(1381, 269, 506, 405),
(1382, 269, 507, 407),
(1383, 270, 501, 403),
(1384, 270, 502, 401),
(1385, 270, 503, 404),
(1386, 270, 504, 408),
(1387, 270, 505, 406),
(1388, 270, 506, 405),
(1389, 270, 507, 407),
(1390, 271, 501, 401),
(1391, 271, 502, 401),
(1392, 271, 503, 404),
(1393, 271, 504, 409),
(1394, 271, 505, 406),
(1395, 271, 506, 405),
(1396, 271, 507, 410),
(1397, 272, 501, 401),
(1398, 272, 502, 401),
(1399, 272, 503, 404),
(1400, 272, 504, 409),
(1401, 272, 505, 406),
(1402, 272, 506, 405),
(1403, 272, 507, 410),
(1404, 273, 521, 421),
(1405, 273, 522, 422),
(1406, 273, 523, 423),
(1407, 273, 524, 424),
(1408, 273, 525, 425),
(1409, 273, 526, 426),
(1410, 273, 527, 427),
(1411, 274, 521, 421),
(1412, 274, 522, 422),
(1413, 274, 523, 429),
(1414, 274, 524, 426),
(1415, 274, 525, 428),
(1416, 274, 526, 426),
(1417, 274, 527, 427),
(1418, 275, 551, 451),
(1419, 275, 552, 452),
(1420, 275, 553, 452),
(1421, 275, 554, 453),
(1422, 275, 555, 454),
(1423, 275, 556, 455),
(1424, 275, 557, 456),
(1425, 276, 551, 451),
(1426, 276, 556, 455),
(1427, 276, 557, 456),
(1428, 276, 558, 457),
(1429, 276, 559, 456),
(1430, 276, 560, 458),
(1431, 276, 561, 457),
(1432, 277, 601, 506),
(1433, 277, 602, 504),
(1434, 277, 603, 503),
(1435, 277, 604, 505),
(1436, 277, 605, 502),
(1437, 277, 606, 503),
(1438, 277, 607, 501),
(1439, 278, 601, 506),
(1440, 278, 602, 504),
(1441, 278, 603, 503),
(1442, 278, 604, 505),
(1443, 278, 605, 502),
(1444, 278, 606, 503),
(1445, 278, 607, 501),
(1446, 279, 621, 521),
(1447, 279, 622, 522),
(1448, 279, 623, 523),
(1449, 279, 624, 524),
(1450, 279, 625, 523),
(1451, 279, 626, 525),
(1452, 279, 627, 525),
(1453, 280, 651, 551),
(1454, 280, 652, 552),
(1455, 280, 653, 553),
(1456, 280, 654, 553),
(1457, 280, 655, 554),
(1458, 280, 656, 554),
(1459, 280, 657, 555),
(1460, 281, 701, 601),
(1461, 281, 702, 602),
(1462, 281, 703, 603),
(1463, 281, 704, 604),
(1464, 281, 705, 605),
(1465, 281, 706, 606),
(1466, 281, 707, 607),
(1467, 282, 701, 601),
(1468, 282, 702, 602),
(1469, 282, 703, 603),
(1470, 282, 704, 604),
(1471, 282, 705, 605),
(1472, 282, 706, 606),
(1473, 282, 707, 607),
(1474, 283, 701, 601),
(1475, 283, 702, 602),
(1476, 283, 703, 603),
(1477, 283, 704, 604),
(1478, 283, 705, 605),
(1479, 283, 706, 608),
(1480, 283, 707, 607),
(1481, 284, 701, 601),
(1482, 284, 702, 609),
(1483, 284, 703, 603),
(1484, 284, 704, 604),
(1485, 284, 705, 605),
(1486, 284, 706, 608),
(1487, 284, 707, 607),
(1488, 285, 701, 601),
(1489, 285, 702, 609),
(1490, 285, 703, 603),
(1491, 285, 704, 610),
(1492, 285, 705, 612),
(1493, 285, 706, 611),
(1494, 285, 707, 607),
(1495, 286, 701, 601),
(1496, 286, 702, 609),
(1497, 286, 703, 603),
(1498, 286, 704, 610),
(1499, 286, 705, 612),
(1500, 286, 706, 611),
(1501, 286, 707, 607),
(1502, 287, 722, 627),
(1503, 287, 723, 621),
(1504, 287, 724, 626),
(1505, 287, 725, 624),
(1506, 287, 726, 625),
(1507, 287, 727, 622),
(1508, 288, 721, 627),
(1509, 288, 722, 623);
INSERT INTO `offering_subjects` (`offering_subject_id`, `offering_id`, `subject_id`, `professor_id`) VALUES
(1510, 288, 723, 621),
(1511, 288, 724, 626),
(1512, 288, 725, 624),
(1513, 288, 726, 625),
(1514, 288, 727, 622),
(1515, 289, 721, 627),
(1516, 289, 722, 623),
(1517, 289, 723, 621),
(1518, 289, 724, 626),
(1519, 289, 725, 624),
(1520, 289, 726, 625),
(1521, 289, 727, 628),
(1522, 290, 721, 627),
(1523, 290, 722, 623),
(1524, 290, 723, 621),
(1525, 290, 724, 626),
(1526, 290, 725, 624),
(1527, 290, 726, 625),
(1528, 290, 727, 628),
(1529, 291, 751, 651),
(1530, 291, 752, 652),
(1531, 291, 753, 653),
(1532, 291, 754, 654),
(1533, 291, 755, 655),
(1534, 291, 756, 656),
(1535, 291, 757, 657),
(1536, 292, 801, 701),
(1537, 292, 802, 702),
(1538, 292, 803, 703),
(1539, 292, 804, 704),
(1540, 292, 805, 705),
(1541, 292, 806, 706),
(1542, 292, 807, 707),
(1543, 293, 801, 701),
(1544, 293, 802, 702),
(1545, 293, 803, 703),
(1546, 293, 804, 704),
(1547, 293, 805, 705),
(1548, 293, 806, 707),
(1549, 293, 807, 701),
(1550, 294, 801, 709),
(1551, 294, 802, 711),
(1552, 294, 803, 708),
(1553, 294, 804, 710),
(1554, 294, 805, 702),
(1555, 294, 806, 707),
(1556, 294, 807, 707),
(1557, 295, 801, 711),
(1558, 295, 802, 707),
(1559, 295, 803, 708),
(1560, 295, 804, 710),
(1561, 295, 805, 712),
(1562, 295, 806, 713),
(1563, 295, 807, 701),
(1564, 296, 801, 711),
(1565, 296, 802, 713),
(1566, 296, 803, 703),
(1567, 296, 804, 715),
(1568, 296, 805, 714),
(1569, 296, 806, 713),
(1570, 296, 807, 708),
(1571, 297, 801, 709),
(1572, 297, 802, 713),
(1573, 297, 803, 716),
(1574, 297, 804, 715),
(1575, 297, 805, 714),
(1576, 297, 806, 713),
(1577, 297, 807, 708),
(1578, 298, 821, 721),
(1579, 298, 822, 722),
(1580, 298, 823, 723),
(1581, 298, 824, 724),
(1582, 298, 825, 725),
(1583, 298, 826, 726),
(1584, 298, 827, 727),
(1585, 299, 821, 721),
(1586, 299, 822, 722),
(1587, 299, 823, 723),
(1588, 299, 824, 724),
(1589, 299, 825, 728),
(1590, 299, 826, 726),
(1591, 299, 827, 727),
(1592, 300, 821, 728),
(1593, 300, 822, 730),
(1594, 300, 823, 731),
(1595, 300, 824, 724),
(1596, 300, 825, 732),
(1597, 300, 826, 723),
(1598, 300, 827, 729),
(1599, 301, 821, 728),
(1600, 301, 822, 730),
(1601, 301, 823, 731),
(1602, 301, 824, 724),
(1603, 301, 825, 732),
(1604, 301, 826, 723),
(1605, 301, 827, 729),
(1606, 302, 851, 751),
(1607, 302, 852, 752),
(1608, 302, 853, 753),
(1609, 302, 854, 754),
(1610, 302, 855, 755),
(1611, 302, 856, 756),
(1612, 302, 857, 757),
(1613, 303, 856, 761),
(1614, 303, 857, 763),
(1615, 303, 858, 758),
(1616, 303, 859, 759),
(1617, 303, 860, 760),
(1618, 303, 861, 762),
(1619, 303, 862, 764),
(1620, 304, 851, 751),
(1621, 304, 852, 752),
(1622, 304, 853, 765),
(1623, 304, 854, 754),
(1624, 304, 855, 755),
(1625, 304, 856, 756),
(1626, 304, 857, 757),
(1627, 305, 856, 761),
(1628, 305, 857, 763),
(1629, 305, 858, 758),
(1630, 305, 859, 759),
(1631, 305, 860, 760),
(1632, 305, 861, 762),
(1633, 305, 862, 764);

-- --------------------------------------------------------

--
-- بنية الجدول `password_reset_requests`
--

DROP TABLE IF EXISTS `password_reset_requests`;
CREATE TABLE `password_reset_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `contact_info` varchar(255) NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`request_id`, `user_id`, `username`, `contact_info`, `status`, `request_date`) VALUES
(1, 1, 'admin', 'admin@mo7adaraty.com', 'completed', '2025-08-23 14:08:25'),
(2, 18, 'Rajae', 'واتساب', 'pending', '2025-10-10 21:24:30'),
(3, 50, 'امال المغيلي', '0699872373', 'pending', '2025-10-10 23:51:20'),
(4, 39, 'Khadija', '0652657529', 'pending', '2025-10-11 13:41:40'),
(5, 114, 'حسناء', 'واتساب', 'pending', '2025-10-13 15:11:05'),
(6, 111, 'Selma', 'selmaessami04@gmail.com', 'pending', '2025-10-16 12:01:42'),
(7, 95, 'kaoutar', 'kaoutarzemrani7@gmail.com', 'pending', '2025-10-19 20:22:15'),
(8, 31, 'Ouafa mejli', '0677257611', 'pending', '2025-11-02 22:13:59'),
(9, 145, 'جواد', '0673213425', 'pending', '2025-11-02 22:41:30'),
(10, 20, 'خالد فاضلي', 'بريد', 'pending', '2025-11-04 11:07:38'),
(11, 1, 'admin', '06598565', 'completed', '2026-03-18 14:10:41'),
(12, 172, 'adminirizi', '06598565', 'completed', '2026-04-24 15:02:59');

-- --------------------------------------------------------

--
-- بنية الجدول `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_text` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `background_style` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `post_text`, `image_path`, `background_style`, `status`, `created_at`) VALUES
(18, 11, 'ضي', NULL, 'green', 'approved', '2025-10-10 06:40:03'),
(19, 1, 'أرحب بجميع الطلبة نتمنتا تعجبكم المنصة', NULL, 'purple', 'approved', '2025-10-10 23:48:24'),
(20, 57, 'جزاكم الله خيرا على مجهوداتكم الرائعة', NULL, 'red', 'approved', '2025-10-11 06:12:05'),
(21, 1, 'fyhuolu', NULL, 'none', 'rejected', '2025-10-16 08:58:54'),
(22, 1, 'vhjbnl;', NULL, 'none', 'rejected', '2025-10-16 09:08:52'),
(23, 88, 'جزاك الله خيرا اخي محمد واش بديتو تحطو المحاضرات ولا باقي حيث ملقيتهومش', NULL, 'none', 'approved', '2025-10-16 13:26:50'),
(24, 167, 'Uwueuei', NULL, 'none', 'rejected', '2026-03-04 21:35:44'),
(26, 1, 'فغعèثèع', NULL, 'red', 'rejected', '2026-03-26 15:04:02'),
(28, 172, 'تغفعغن', NULL, 'none', 'approved', '2026-05-06 22:11:35'),
(30, 43, '\\ثصسفلثيق', NULL, 'none', 'approved', '2026-05-11 18:11:45'),
(36, 189, 'مزيدا من التوفيق وجزاك لله خيرا عن مجهودك', NULL, 'purple', 'approved', '2026-07-05 18:28:27');

-- --------------------------------------------------------

--
-- بنية الجدول `professors`
--

DROP TABLE IF EXISTS `professors`;
CREATE TABLE `professors` (
  `professor_id` int(11) NOT NULL,
  `professor_name` varchar(100) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `professors`
--

INSERT INTO `professors` (`professor_id`, `professor_name`, `is_deleted`, `deleted_at`) VALUES
(51, 'حليم', 0, NULL),
(52, 'البكري', 0, NULL),
(53, 'البوعلاوي', 0, NULL),
(54, 'الطاهري', 0, NULL),
(55, 'جبري / الظهراوي', 0, NULL),
(56, 'زهوي الهوير', 0, NULL),
(57, 'البلغيتي', 0, NULL),
(58, 'الحمداوي', 0, NULL),
(59, 'بخداش', 0, NULL),
(60, 'لبريني', 0, NULL),
(61, 'الناصري', 0, NULL),
(62, 'البوعزاوي', 0, NULL),
(63, 'عثماني', 0, NULL),
(64, 'ذ. غانمي / عامر', 0, NULL),
(71, 'اوهنيا', 0, NULL),
(72, 'نشيد', 0, NULL),
(73, 'شهيد', 0, NULL),
(74, 'نصيحي', 0, NULL),
(75, 'العباسي', 0, NULL),
(76, 'زهوي', 0, NULL),
(77, 'رستم', 0, NULL),
(81, 'الناصري', 0, NULL),
(82, 'نافع', 0, NULL),
(83, 'نشيد', 0, NULL),
(84, 'الساهل', 0, NULL),
(85, 'القسيمي', 0, NULL),
(86, 'العباسي', 0, NULL),
(87, 'عمران', 0, NULL),
(88, 'أوقسو', 0, NULL),
(89, 'البوعلاوي', 0, NULL),
(90, 'الطاهري', 0, NULL),
(94, 'د. هلالي', 0, NULL),
(95, 'د. العامري', 0, NULL),
(97, 'د. طلال / هوازين', 0, NULL),
(98, 'د. مغراوي / طاهري', 0, NULL),
(101, 'ادروا', 0, NULL),
(102, 'طلال', 0, NULL),
(103, 'ذ. الحسني / شيات', 0, NULL),
(104, 'غانمي / ايطاوي', 0, NULL),
(105, 'احدوش', 0, NULL),
(106, 'مغراوي', 0, NULL),
(107, 'الهردي', 0, NULL),
(108, 'ادروا جوهري', 0, NULL),
(109, 'غانمي', 0, NULL),
(110, 'هلالي', 0, NULL),
(111, 'العامري', 0, NULL),
(112, 'هلالي / احدوش', 0, NULL),
(121, 'حميدي', 0, NULL),
(122, 'ذة. زغواني', 0, NULL),
(123, 'فسليماني الطاهري', 0, NULL),
(124, 'قاسم', 0, NULL),
(125, 'ذ. الحلحولي / بوهريم', 0, NULL),
(126, 'عقلي', 0, NULL),
(127, 'ايطاوي', 0, NULL),
(128, 'اسماعيلي العلوي', 0, NULL),
(129, 'ذة. أعراب / بوستة', 0, NULL),
(151, 'ادروا', 0, NULL),
(152, 'الحسني', 0, NULL),
(153, 'ميموني', 0, NULL),
(154, 'المهودر', 0, NULL),
(155, 'عقلي', 0, NULL),
(156, 'اكرض', 0, NULL),
(157, 'العامري', 0, NULL),
(158, 'غانمي', 0, NULL),
(159, 'ضريسي', 0, NULL),
(160, 'قاسم', 0, NULL),
(161, 'جبري', 0, NULL),
(201, 'ذة. حسناوي', 0, NULL),
(202, 'ايت الغازي', 0, NULL),
(203, 'ذ. المهداوي / نجيب', 0, NULL),
(204, 'الضعيفي', 0, NULL),
(205, 'العروصي', 0, NULL),
(206, 'احشوش', 0, NULL),
(207, 'اوطال / البوزياني', 0, NULL),
(208, 'ذ. المهداوي / غازي', 0, NULL),
(209, 'ذة. حميد مريم', 0, NULL),
(221, 'الادريسي', 0, NULL),
(222, 'بوحجار', 0, NULL),
(223, 'امعيط', 0, NULL),
(224, 'امحريق', 0, NULL),
(225, 'أقبوش', 0, NULL),
(226, 'بودرقا', 0, NULL),
(251, 'حقي', 0, NULL),
(252, 'احشوش', 0, NULL),
(253, 'ايت الغازي', 0, NULL),
(254, 'بودرقا', 0, NULL),
(255, 'المهودر', 0, NULL),
(256, 'موادين عصام', 0, NULL),
(257, 'قفص', 0, NULL),
(301, 'الغاشي', 0, NULL),
(302, 'قادم / جوهري', 0, NULL),
(303, 'اجعون', 0, NULL),
(304, 'الراضي', 0, NULL),
(305, 'احشوش', 0, NULL),
(306, 'خرازي / عمور', 0, NULL),
(307, 'ذة. بوتي', 0, NULL),
(308, 'حافض / كوميح', 0, NULL),
(309, 'عون/ بوشبوش', 0, NULL),
(310, 'حافض وزني', 0, NULL),
(311, 'ذة. الكبيشي', 0, NULL),
(312, 'التورابي', 0, NULL),
(313, 'بوزلغة', 0, NULL),
(314, 'عاتق / عزالدين', 0, NULL),
(315, 'المبعد الطفي', 0, NULL),
(316, 'الحريشي', 0, NULL),
(317, 'أقداد', 0, NULL),
(318, 'الغاشي / كروان', 0, NULL),
(319, 'حيلي', 0, NULL),
(320, 'طلب زكراوي / فطوش', 0, NULL),
(321, 'حدوي الحبيب', 0, NULL),
(331, 'ذة. جهيد', 0, NULL),
(332, 'جعي', 0, NULL),
(333, 'المبعد', 0, NULL),
(334, 'اجعون', 0, NULL),
(335, 'المبعد / البزكراوي', 0, NULL),
(336, 'ذة. أقداد', 0, NULL),
(337, 'عارف', 0, NULL),
(338, 'حيلي', 0, NULL),
(339, 'المهداوي', 0, NULL),
(340, 'البزكراوي', 0, NULL),
(341, 'لمبعد / البزكراوي', 0, NULL),
(361, 'المحترم عثمان', 0, NULL),
(362, 'البزكراوي المبعد', 0, NULL),
(363, 'الراضي', 0, NULL),
(364, 'المبعد', 0, NULL),
(365, 'ماضي', 0, NULL),
(366, 'المبعد / الحمداوي', 0, NULL),
(367, 'لمبعد', 0, NULL),
(368, 'الغاشi', 0, NULL),
(369, 'حيلي / جعي', 0, NULL),
(370, 'الخالقي', 0, NULL),
(371, 'عارف / البزكراوي', 0, NULL),
(401, 'EL ARNABI', 0, NULL),
(402, 'BOURAIS', 0, NULL),
(403, 'ARAHAL', 0, NULL),
(404, 'BOUHASSOUNE', 0, NULL),
(405, 'OUMEROUCH', 0, NULL),
(406, 'MOUH', 0, NULL),
(407, 'BENHAMOU', 0, NULL),
(408, 'RADIM', 0, NULL),
(409, 'FARQZAID', 0, NULL),
(410, 'FAHMI', 0, NULL),
(421, 'NAB', 0, NULL),
(422, 'OUSSIKOUM A', 0, NULL),
(423, 'TOURCHLI', 0, NULL),
(424, 'MAKAN', 0, NULL),
(425, 'BOURAIS', 0, NULL),
(426, 'JAMA', 0, NULL),
(427, 'GHARIB', 0, NULL),
(428, 'OUSSIKOUM M', 0, NULL),
(429, 'RADIM', 0, NULL),
(451, 'NAFA K', 0, NULL),
(452, 'NOZHI', 0, NULL),
(453, 'OUSSIKOUM M', 0, NULL),
(454, 'TOURCHLI', 0, NULL),
(455, 'ELMEHAWDAR', 0, NULL),
(456, 'ARAHAL', 0, NULL),
(457, 'OUSSIKOUM A', 0, NULL),
(458, 'MAKAN', 0, NULL),
(501, 'لوتار', 0, NULL),
(502, 'إزماون', 0, NULL),
(503, 'امجيديلة', 0, NULL),
(504, 'رشاق', 0, NULL),
(505, 'اجهبلي', 0, NULL),
(506, 'أبي بكر', 0, NULL),
(521, 'ذة. جهيد', 0, NULL),
(522, 'بجبيج', 0, NULL),
(523, 'رشاق', 0, NULL),
(524, 'امجيديلة', 0, NULL),
(525, 'أبي بكر', 0, NULL),
(551, 'ذة. عمران', 0, NULL),
(552, 'رشاق', 0, NULL),
(553, 'امجيديلة', 0, NULL),
(554, 'أبي بكر', 0, NULL),
(555, 'ذة. موادين سلوى', 0, NULL),
(601, 'أعراب', 0, NULL),
(602, 'لحريشي', 0, NULL),
(603, 'خالص', 0, NULL),
(604, 'اوطال', 0, NULL),
(605, 'غزوان', 0, NULL),
(606, 'بوستة', 0, NULL),
(607, 'بجبيج', 0, NULL),
(608, 'د نور الدين فاطمة الزهراء', 0, NULL),
(609, 'الشامي', 0, NULL),
(610, 'حدوي', 0, NULL),
(611, 'ورحمان', 0, NULL),
(612, 'حمي', 0, NULL),
(621, 'حدوي', 0, NULL),
(622, 'غزوان', 0, NULL),
(623, 'المفضلي', 0, NULL),
(624, 'خرازي', 0, NULL),
(625, 'شتيوي', 0, NULL),
(626, 'عاتق', 0, NULL),
(627, 'الحلحولي', 0, NULL),
(628, 'حمي', 0, NULL),
(651, 'المحترم عثمان', 0, NULL),
(652, 'ورحمان', 0, NULL),
(653, 'خرازي', 0, NULL),
(654, 'اجهبلي', 0, NULL),
(655, 'اوطال', 0, NULL),
(656, 'عاتق', 0, NULL),
(657, 'حمي', 0, NULL),
(701, 'CHAKOUR', 0, NULL),
(702, 'AGBALOU', 0, NULL),
(703, 'JALID', 0, NULL),
(704, 'JAQUI', 0, NULL),
(705, 'ECHENNOURI', 0, NULL),
(706, 'JEBBAR', 0, NULL),
(707, 'MAHMI', 0, NULL),
(708, 'BOUTABSSIL', 0, NULL),
(709, 'ZAID', 0, NULL),
(710, 'KHEMART', 0, NULL),
(711, 'BYACHOU', 0, NULL),
(712, 'ELMOHTARIM', 0, NULL),
(713, 'HANI', 0, NULL),
(714, 'ACHAMRAH', 0, NULL),
(715, 'FDIL', 0, NULL),
(716, 'EL HADARI', 0, NULL),
(721, 'ACHAMRAH', 0, NULL),
(722, 'ZAID', 0, NULL),
(723, 'MASFOUR', 0, NULL),
(724, 'BOURHIM', 0, NULL),
(725, 'CHAKOUR', 0, NULL),
(726, 'JALID', 0, NULL),
(727, 'JEBBAR', 0, NULL),
(728, 'ELHADRI', 0, NULL),
(729, 'JAAFARI M', 0, NULL),
(730, 'OUAHIDI', 0, NULL),
(731, 'HANDOUR', 0, NULL),
(732, 'BYCHOU', 0, NULL),
(751, 'RAKII', 0, NULL),
(752, 'CHAOUCH', 0, NULL),
(753, 'MASFOUR', 0, NULL),
(754, 'MAMAOUI', 0, NULL),
(755, 'HANDOUR', 0, NULL),
(756, 'JOUHARI', 0, NULL),
(757, 'JAROUDI', 0, NULL),
(758, 'JAAFARI', 0, NULL),
(759, 'KOUBALI', 0, NULL),
(760, 'ZANZOUN', 0, NULL),
(761, 'MOUTAOAKIL', 0, NULL),
(762, 'ELMOUHTARIM', 0, NULL),
(763, 'MLILA', 0, NULL),
(764, 'EL MOHTARIM', 0, NULL),
(765, 'KARKABA', 0, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `reporter_user_id` int(11) NOT NULL,
  `content_type` enum('lecture','exam','comment','post') NOT NULL,
  `content_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `reports`
--

INSERT INTO `reports` (`report_id`, `reporter_user_id`, `content_type`, `content_id`, `reason`, `status`, `created_at`) VALUES
(16, 172, 'lecture', 49, '_oi)p', 'pending', '2026-05-06 20:08:17'),
(17, 172, 'lecture', 45, '_oi)p', 'dismissed', '2026-05-06 20:08:33'),
(18, 172, 'lecture', 45, '_oi)p', 'dismissed', '2026-05-06 20:09:13'),
(19, 172, 'lecture', 45, '_oi)p', 'dismissed', '2026-05-06 20:09:17'),
(20, 172, 'post', 20, 'fyuj', 'dismissed', '2026-05-06 20:11:11'),
(21, 182, 'post', 30, 'Hrueue', 'pending', '2026-06-22 17:29:15'),
(22, 172, 'post', 30, 'fhukfcghui', 'resolved', '2026-07-13 13:11:26');

-- --------------------------------------------------------

--
-- بنية الجدول `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_division` varchar(255) DEFAULT NULL,
  `review_text` text NOT NULL,
  `status` enum('approved','pending') DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `guest_name`, `guest_division`, `review_text`, `status`, `created_at`) VALUES
(17, NULL, 'مريم عليلي', 'الدراسات الاسلامية', 'لم ادخل اليها بعد بشكل رسمي ولكن انا واثقة انه  جميلة ????', 'approved', '2026-07-05 22:09:33');

-- --------------------------------------------------------

--
-- بنية الجدول `schedules`
--

DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `schedules`
--

INSERT INTO `schedules` (`id`, `student_id`, `file_path`, `file_type`, `status`, `created_at`) VALUES
(1, 172, 'uploads/schedules/schedule_69fbb719229ff1.57031189.png', 'image/png', 'approved', '2026-05-06 21:48:09'),
(2, 172, 'uploads/schedules/schedule_6a332131dbf642.58502298.pdf', 'application/pdf', 'approved', '2026-06-17 22:35:29'),
(3, 172, 'uploads/schedules/schedule_6a3323c2bdfe32.27110773.pdf', 'application/pdf', 'approved', '2026-06-17 22:46:26'),
(4, 172, 'uploads/schedules/schedule_6a3325f24526a9.23514034.png', 'image/png', 'approved', '2026-06-17 22:55:46');

-- --------------------------------------------------------

--
-- بنية الجدول `student_enrollments`
--

DROP TABLE IF EXISTS `student_enrollments`;
CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year_id` int(11) DEFAULT 1,
  `user_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `selected_subjects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`enrollment_id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `user_id` (`user_id`),
  KEY `offering_id` (`offering_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- إرجاع أو استيراد بيانات الجدول `student_enrollments`
--

INSERT INTO `student_enrollments` (`enrollment_id`, `academic_year_id`, `user_id`, `offering_id`, `selected_subjects`) VALUES
(41, 1, 13, 13, NULL),
(42, 1, 14, 13, NULL),
(43, 1, 15, 12, NULL),
(44, 1, 16, 17, NULL),
(45, 1, 17, 8, NULL),
(46, 1, 17, 8, NULL),
(47, 1, 18, 17, NULL),
(48, 1, 19, 13, NULL),
(49, 1, 20, 13, NULL),
(50, 1, 21, 12, NULL),
(51, 1, 22, 12, NULL),
(52, 1, 23, 13, NULL),
(53, 1, 24, 13, NULL),
(54, 1, 25, 8, NULL),
(55, 1, 26, 13, NULL),
(56, 1, 26, 13, NULL),
(57, 1, 27, 13, NULL),
(59, 1, 28, 12, NULL),
(60, 1, 29, 12, NULL),
(61, 1, 30, 12, NULL),
(62, 1, 31, 13, NULL),
(63, 1, 32, 11, NULL),
(64, 1, 32, 11, NULL),
(65, 1, 33, 13, NULL),
(66, 1, 34, 11, NULL),
(67, 1, 35, 8, NULL),
(68, 1, 36, 13, NULL),
(69, 1, 37, 8, NULL),
(70, 1, 38, 11, NULL),
(71, 1, 39, 11, NULL),
(72, 1, 40, 8, NULL),
(73, 1, 41, 11, NULL),
(74, 1, 41, 11, NULL),
(75, 1, 42, 11, NULL),
(76, 1, 43, 8, NULL),
(77, 1, 44, 12, NULL),
(78, 1, 44, 8, NULL),
(79, 1, 45, 8, NULL),
(80, 1, 46, 8, NULL),
(81, 1, 47, 11, NULL),
(85, 1, 48, 8, NULL),
(86, 1, 49, 11, NULL),
(87, 1, 50, 8, NULL),
(88, 1, 51, 11, NULL),
(89, 1, 51, 11, NULL),
(90, 1, 52, 8, NULL),
(91, 1, 53, 8, NULL),
(92, 1, 54, 8, NULL),
(93, 1, 54, 8, NULL),
(94, 1, 55, 11, NULL),
(95, 1, 56, 8, NULL),
(96, 1, 57, 8, NULL),
(97, 1, 58, 8, NULL),
(98, 1, 59, 13, NULL),
(99, 1, 60, 11, NULL),
(100, 1, 60, 11, NULL),
(101, 1, 61, 8, NULL),
(102, 1, 62, 8, NULL),
(103, 1, 63, 11, NULL),
(104, 1, 64, 13, NULL),
(105, 1, 65, 12, NULL),
(106, 1, 66, 8, NULL),
(107, 1, 66, 11, NULL),
(108, 1, 67, 11, NULL),
(109, 1, 68, 8, NULL),
(110, 1, 69, 11, NULL),
(111, 1, 70, 13, NULL),
(112, 1, 70, 15, NULL),
(113, 1, 71, 13, NULL),
(114, 1, 72, 8, NULL),
(115, 1, 72, 8, NULL),
(116, 1, 73, 8, NULL),
(117, 1, 74, 8, NULL),
(118, 1, 75, 13, NULL),
(119, 1, 76, 13, NULL),
(120, 1, 77, 13, NULL),
(121, 1, 78, 12, NULL),
(122, 1, 79, 8, NULL),
(123, 1, 79, 8, NULL),
(124, 1, 80, 8, NULL),
(125, 1, 81, 8, NULL),
(126, 1, 82, 8, NULL),
(127, 1, 82, 8, NULL),
(128, 1, 83, 11, NULL),
(129, 1, 84, 8, NULL),
(130, 1, 85, 11, NULL),
(131, 1, 85, 11, NULL),
(132, 1, 86, 8, NULL),
(133, 1, 87, 8, NULL),
(134, 1, 88, 8, NULL),
(135, 1, 89, 11, NULL),
(136, 1, 90, 13, NULL),
(137, 1, 91, 12, NULL),
(138, 1, 92, 8, NULL),
(139, 1, 93, 13, NULL),
(140, 1, 94, 8, NULL),
(141, 1, 94, 8, NULL),
(142, 1, 95, 13, NULL),
(143, 1, 96, 13, NULL),
(144, 1, 97, 8, NULL),
(145, 1, 98, 12, NULL),
(146, 1, 98, 12, NULL),
(147, 1, 99, 8, NULL),
(148, 1, 100, 12, NULL),
(149, 1, 101, 13, NULL),
(150, 1, 102, 12, NULL),
(151, 1, 102, 12, NULL),
(152, 1, 103, 8, NULL),
(153, 1, 103, 8, NULL),
(154, 1, 104, 8, NULL),
(155, 1, 105, 8, NULL),
(156, 1, 106, 13, NULL),
(157, 1, 107, 11, NULL),
(158, 1, 107, 11, NULL),
(161, 1, 109, 8, NULL),
(162, 1, 109, 8, NULL),
(163, 1, 110, 8, NULL),
(164, 1, 111, 8, NULL),
(165, 1, 112, 12, NULL),
(166, 1, 113, 11, NULL),
(167, 1, 114, 12, NULL),
(168, 1, 115, 11, NULL),
(169, 1, 116, 11, NULL),
(170, 1, 117, 8, NULL),
(171, 1, 118, 12, NULL),
(172, 1, 119, 8, NULL),
(173, 1, 120, 13, NULL),
(175, 1, 122, 13, NULL),
(176, 1, 123, 13, NULL),
(177, 1, 124, 13, NULL),
(178, 1, 125, 13, NULL),
(179, 1, 108, 17, NULL),
(180, 1, 108, 12, NULL),
(181, 1, 126, 13, NULL),
(182, 1, 127, 11, NULL),
(183, 1, 128, 8, NULL),
(184, 1, 129, 21, NULL),
(185, 1, 130, 21, NULL),
(186, 1, 131, 21, NULL),
(187, 1, 132, 8, NULL),
(188, 1, 132, 8, NULL),
(190, 1, 134, 8, NULL),
(191, 1, 135, 8, NULL),
(192, 1, 135, 12, NULL),
(193, 1, 136, 8, NULL),
(194, 1, 137, 12, NULL),
(195, 1, 137, 8, NULL),
(196, 1, 138, 13, NULL),
(197, 1, 139, 13, NULL),
(198, 1, 139, 13, NULL),
(199, 1, 140, 12, NULL),
(200, 1, 140, 12, NULL),
(201, 1, 141, 13, NULL),
(202, 1, 142, 13, NULL),
(205, 1, 144, 13, NULL),
(206, 1, 145, 13, NULL),
(207, 1, 146, 13, NULL),
(208, 1, 147, 12, NULL),
(211, 1, 149, 8, NULL),
(212, 1, 150, 13, NULL),
(213, 1, 151, 11, NULL),
(214, 1, 152, 17, NULL),
(215, 1, 153, 13, NULL),
(216, 1, 133, 11, NULL),
(217, 1, 154, 13, NULL),
(218, 1, 154, 11, NULL),
(219, 1, 155, 13, NULL),
(220, 1, 156, 13, NULL),
(221, 1, 143, 11, NULL),
(222, 1, 143, 13, NULL),
(223, 1, 157, 21, NULL),
(224, 1, 157, 11, NULL),
(225, 1, 158, 21, NULL),
(226, 1, 159, 11, NULL),
(227, 1, 159, 11, NULL),
(228, 1, 160, 11, NULL),
(229, 1, 161, 12, NULL),
(230, 1, 162, 12, NULL),
(231, 1, 163, 21, NULL),
(234, 1, 165, 11, NULL),
(235, 1, 166, 11, NULL),
(236, 1, 166, 21, NULL),
(238, 1, 168, 11, NULL),
(242, 1, 169, 11, NULL),
(244, 2, 169, 12, NULL),
(247, 2, 43, 28, NULL),
(249, 1, 31, 8, NULL),
(250, 1, 169, 8, NULL),
(251, 1, 170, 33, NULL),
(254, 1, 171, 33, NULL),
(255, 2, 171, 33, NULL),
(256, 2, 170, 34, NULL),
(257, 1, 173, 15, NULL),
(258, 1, 173, 8, NULL),
(259, 2, 173, 26, NULL),
(260, 2, 174, 34, NULL),
(261, 1, 174, 35, NULL),
(262, 1, 175, 17, NULL),
(265, 1, 1, 23, NULL),
(266, 1, 1, 13, NULL),
(267, 1, 176, 8, NULL),
(270, 1, 177, 37, NULL),
(272, 1, 179, 36, NULL),
(273, 1, 179, 42, NULL),
(274, 1, 180, 36, '[110, 111, 112, 113, 114]'),
(275, 1, 180, 48, '[136, 142, 143]'),
(281, 1, 181, 37, '[108, 111, 112, 113, 114]'),
(282, 1, 181, 44, '[122, 125]'),
(285, 1, 182, 52, '[201,202,203,204,205,206,207]'),
(286, 1, 182, 62, '[221,222]'),
(287, 1, 183, 39, NULL),
(288, 1, 184, 202, '[801,802,803,804,805,806,807]'),
(289, 2, 184, 293, NULL),
(290, 2, 172, 227, '[108,109,110,111,112,113,114]'),
(291, 2, 172, 232, '[122,123,124,125,126,128]'),
(292, 1, 172, 36, '[108,109,110,111,112,113,114]'),
(293, 1, 172, 42, '[122,123,124,125,126,128]'),
(294, 1, 185, 37, NULL),
(295, 1, 186, 38, NULL),
(296, 1, 187, 36, NULL),
(297, 1, 188, 151, '[601,602,603,604,605,606]'),
(298, 1, 189, 37, '[108,109,110,111,112,113,114]'),
(299, 1, 190, 48, '[136,141,142,143,144,145,146]'),
(301, 1, 7, 45, '[122,123,124,125,126,128]'),
(302, 1, 191, 48, '[136,141,142,143,144,145,146]'),
(303, 1, 192, 71, '[251,252,253,254,255,256,257]'),
(304, 1, 193, 113, '[421,422,423,424,425,426,427]'),
(305, 1, 167, 37, '[108,109,110,111,112,113,114]'),
(306, 1, 194, 45, '[122,123,124,125,126,128]'),
(307, 1, 194, 47, '[136,137,138,139,140,141,142]');

-- --------------------------------------------------------

--
-- بنية الجدول `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `is_deleted`, `deleted_at`) VALUES
(108, 'علوم القرآن', 0, NULL),
(109, 'مدخل لدراسة العقيدة الإسلامية', 0, NULL),
(110, 'فقه العبادات والمعاملات', 0, NULL),
(111, 'البلاغة', 0, NULL),
(112, 'مفاهيم ونصوص فلسفية', 0, NULL),
(113, 'مدخل لدراسة التشريع الإسلامي', 0, NULL),
(114, 'الثقافة الرقمية', 0, NULL),
(122, 'التأويل وعلوم التفسير', 0, NULL),
(123, 'فقه الحديث', 0, NULL),
(124, 'أصول الفقه 2', 0, NULL),
(125, 'علم المواريث', 0, NULL),
(126, 'الفكر الإسلامي', 0, NULL),
(128, 'تاريخ الأديان', 0, NULL),
(136, 'مقاصد الشريعة', 0, NULL),
(137, 'القرآن والسنة مصدران للمعرفة', 0, NULL),
(138, 'فقه النوازل والتكييف الفقهي', 0, NULL),
(139, 'دراسات قرآنية معاصرة', 0, NULL),
(140, 'السياسة الشرعية والفكر السياسي المعاصر', 0, NULL),
(141, 'اللغات الانجليزية', 0, NULL),
(142, 'المشروع المؤطر', 0, NULL),
(143, 'فقه المعاملات المالية', 0, NULL),
(144, 'فلسفة الدين والقيم', 0, NULL),
(145, 'الدرس الاصولي بالغرب الاسلامي', 0, NULL),
(146, 'العلاقات الدولية في الإسلام', 0, NULL),
(201, 'البلاغة', 0, NULL),
(202, 'مدخل إلى الأدب وفنونه', 0, NULL),
(203, 'الصواتة', 0, NULL),
(204, 'نصوص شعرية', 0, NULL),
(205, 'مدخل إلى التراث الثقافي', 0, NULL),
(206, 'دلالة', 0, NULL),
(207, 'الثقافة الرقمية', 0, NULL),
(221, 'اللغة الفرنسية', 0, NULL),
(222, 'مناهج تحليل النصوص', 0, NULL),
(223, 'العروض', 0, NULL),
(224, 'النقد الأدبي والفني', 0, NULL),
(225, 'مدخل إلى علم الاجتماع - علم النفس', 0, NULL),
(226, 'تركيب', 0, NULL),
(227, 'فقه اللغة', 0, NULL),
(251, 'الصرف صواتة', 0, NULL),
(252, 'اللسانيات المعرفية', 0, NULL),
(253, 'لسانيات النص', 0, NULL),
(254, 'اللغة الإنجليزية', 0, NULL),
(255, 'التداوليات', 0, NULL),
(256, 'الثقافة المقاولاتية', 0, NULL),
(257, 'ترجمة لسانية', 0, NULL),
(258, 'السرد الحديث', 0, NULL),
(259, 'المسرح المغربي الحديث', 0, NULL),
(260, 'الأدب الصوفي في المغرب', 0, NULL),
(261, 'البلاغة المغربية الحديثة', 0, NULL),
(262, 'السميائيات 2', 0, NULL),
(301, 'المهارات الرقمية والمعلوميات', 0, NULL),
(302, 'مدخل لدراسة التاريخ الحديث', 0, NULL),
(303, 'مدخل لدراسة جغرافية السكان', 0, NULL),
(304, 'تاريخ الإسلام من صدر الإسلام إلى العصر العباسي', 0, NULL),
(305, 'مدخل لدراسة التاريخ المعاصر والراهن', 0, NULL),
(306, 'مدخل لدراسة تاريخ أمريكا', 0, NULL),
(307, 'الاتجاهات الكلاسيكية والمعاصرة لعلم الاجتماع', 0, NULL),
(321, 'تاريخ المغرب الحديث والمعاصر', 0, NULL),
(322, 'تاريخ شمال المغرب', 0, NULL),
(323, 'تاريخ Mالمماليك والعثمانيين', 0, NULL),
(324, 'اللغة الفرنسية', 0, NULL),
(325, 'المغرب والعالم المتوسطي', 0, NULL),
(326, 'الاستعمار وحركات التحرر بإفريقيا', 0, NULL),
(327, 'الجغرافية التاريخية', 0, NULL),
(351, 'التحافة', 0, NULL),
(352, 'التراث المعماري الجهوي', 0, NULL),
(353, 'تقنيات البحث المكتبي والميداني الجهوي', 0, NULL),
(354, 'تقنيات البحث الأثري والترميم', 0, NULL),
(355, 'اللغات الانجليزية', 0, NULL),
(356, 'الثقافة المقاولاتية', 0, NULL),
(357, 'تقنيات جرد وتصنيف التراث المادي وغير المادي الجهوي', 0, NULL),
(401, 'مناخ', 0, NULL),
(402, 'جغرافية المغرب العامة', 0, NULL),
(403, 'جغرافية الأرياف', 0, NULL),
(404, 'مدخل لدراسة تاريخ المغرب المعاصر والراهن', 0, NULL),
(405, 'الاتجاهات الكلاسيكية والحديثة في علم الاجتماع', 0, NULL),
(406, 'المهارات الرقمية والذكاء الاصطناعي', 0, NULL),
(407, 'قراءة وتحليل الخرائط الطبوغرافية', 0, NULL),
(421, 'اللغة الفرنسية', 0, NULL),
(422, 'النظم البيئية والتربة والنبات', 0, NULL),
(423, 'الخرائطية والسيميولوجيا', 0, NULL),
(424, 'جغرافية البحر المتوسط وإفريقيا جنوب الصحراء', 0, NULL),
(425, 'جغرافية الطاقة', 0, NULL),
(426, 'دينامية المجال الحضري', 0, NULL),
(427, 'الاحصاء والتعبير البياني', 0, NULL),
(451, 'اللغة الانجليزية', 0, NULL),
(452, 'الثقافة المقاولاتية', 0, NULL),
(453, 'تدبير الموارد', 0, NULL),
(454, 'الجماعات الترابية والتنمية المحلية', 0, NULL),
(455, 'التهيئة الريفية', 0, NULL),
(456, 'التهيئة الحضرية', 0, NULL),
(457, 'تدريب ممهنن 2', 0, NULL),
(458, 'الدينامية النهرية', 0, NULL),
(459, 'دينامية تشكيل الاوساط الرسوبية', 0, NULL),
(460, 'تثمين الثراث الطبيعي', 0, NULL),
(461, 'البيئات القديمة', 0, NULL),
(462, 'إعداد الاحواض النهرية', 0, NULL),
(501, 'ÉLEMENTS DE GRAMMAIRE II', 0, NULL),
(502, 'LEXICOGRAPHIE', 0, NULL),
(503, 'TÉCHNIQUES D\'EXPRESSION ET DE COMMUNICATION ORALES', 0, NULL),
(504, 'LECTURE MÉTHODIQUE DES TEXTES LITTÉRAIRES', 0, NULL),
(505, 'HISTOIRE DES IDÉES (18ème Siècle)', 0, NULL),
(506, 'Initiation à la traduction', 0, NULL),
(507, 'Culture digitale', 0, NULL),
(521, 'Langue Française', 0, NULL),
(522, 'Analyse de discours', 0, NULL),
(523, 'Introduction aux littératures francophones', 0, NULL),
(524, 'Morphosyntaxe 2', 0, NULL),
(525, 'Exercice Litteratures II', 0, NULL),
(526, 'Initiation à la linguistique', 0, NULL),
(527, 'Histoire des idées et des arts (XXe siècle)', 0, NULL),
(551, 'CULTURE ENTREPRENERIALE', 0, NULL),
(552, 'Littérature et mythes', 0, NULL),
(553, 'Littérature comparée', 0, NULL),
(554, 'Stylistique', 0, NULL),
(555, 'Littérature négroafricaine', 0, NULL),
(556, 'Langue aNGLAISES', 0, NULL),
(557, 'Introduction aux études patrimoniales', 0, NULL),
(558, 'Syntaxe II: Théorie du gouvernement et du liage (GB)', 0, NULL),
(559, 'Sociolinguistique', 0, NULL),
(560, 'Sémiotique', 0, NULL),
(561, 'Sémantique générative', 0, NULL),
(601, 'الوجيز في تاريخ الفلسفة', 0, NULL),
(602, 'تاريخ العلوم', 0, NULL),
(603, 'مفاهيم ونصوص فلسفية', 0, NULL),
(604, 'الاتجاهات الكلاسيكية والحديثة في علم الاجتماع', 0, NULL),
(605, 'علم النفس الاجتماعي', 0, NULL),
(606, 'الفكر الشرقي القديم', 0, NULL),
(607, 'المهارات الرقمية (الثقافة الرقمية)', 0, NULL),
(621, 'اللغات الاجنبية اللغة الفرنسية', 0, NULL),
(622, 'فلسفة العلوم', 0, NULL),
(623, 'الجماليات وفلسفة الفن', 0, NULL),
(624, 'المنطق المعاصر وفلسفة اللغة', 0, NULL),
(625, 'فلسفة القانون وحقوق الإنسان', 0, NULL),
(626, 'فلسفة الدين', 0, NULL),
(627, 'فلسفة الأخلاق', 0, NULL),
(651, 'الانجليزية', 0, NULL),
(652, 'الفلسفة وأنماط التعبير', 0, NULL),
(653, 'المنطق المعاصر', 0, NULL),
(654, 'إشكاليات في الفكر الفلسفي المغربي المعاصر', 0, NULL),
(655, 'المسؤولية البيئية', 0, NULL),
(656, 'فلسفة التاريخ', 0, NULL),
(657, 'الثقافة المقاولاتية', 0, NULL),
(701, 'السوسيولوجيا المعاصرة', 0, NULL),
(702, 'جغرافية المغرب العامة', 0, NULL),
(703, 'سوسيولوجيا المؤسسات الاجتماعية والسياسية', 0, NULL),
(704, 'السوسيولوجيا الكلاسيكية', 0, NULL),
(705, 'علم النفس الاجتماعي', 0, NULL),
(706, 'المهارات الرقمية (الثقافة الرقمية)', 0, NULL),
(707, 'مناهج ونصوص فلسفية', 0, NULL),
(721, 'البحث الميداني في علم الاجتماع', 0, NULL),
(722, 'علم الاجتماع التربية', 0, NULL),
(723, 'علم الاجتماع الحضري', 0, NULL),
(724, 'علم الاجتماع القروي', 0, NULL),
(725, 'نظريات وتقنيات العمل الاجتماعي', 0, NULL),
(726, 'اللغة الفرنسية', 0, NULL),
(727, 'علم الاجتماع الرقمي', 0, NULL),
(751, 'الإنجليزية', 0, NULL),
(752, 'الثقافة المقاولاتية', 0, NULL),
(753, 'سوسيولوجيا الصحة والمرض', 0, NULL),
(754, 'أبحاث وتطبيقات ميدانية', 0, NULL),
(755, 'سوسيولوجيا البيئة', 0, NULL),
(756, 'التغير والحركات الاجتماعية', 0, NULL),
(757, 'سوسيولوجيا الجريمة والانحراف', 0, NULL),
(801, 'Composition', 0, NULL),
(802, 'Reading Comprehension & Precis 2', 0, NULL),
(803, 'British/American civilization', 0, NULL),
(804, 'Digital Skills', 0, NULL),
(805, 'Grammar 2', 0, NULL),
(806, 'Oral Communication', 0, NULL),
(807, 'Guided Reading 2', 0, NULL),
(821, 'Introduction to research', 0, NULL),
(822, 'Introduction to Linguistics', 0, NULL),
(823, 'Introduction to African Literature and Culture', 0, NULL),
(824, 'Langue etrangers (Langue Français)', 0, NULL),
(825, 'Introduction to Cultural studies', 0, NULL),
(826, 'Introduction to discourse Analyse', 0, NULL),
(827, 'Introduction to translation', 0, NULL),
(851, 'Literary Criticism', 0, NULL),
(852, 'Theatre', 0, NULL),
(853, 'Cultural Theory', 0, NULL),
(854, 'Novel II', 0, NULL),
(855, 'Diaspora Literature', 0, NULL),
(856, 'Arabe', 0, NULL),
(857, 'Culture Entrepreneriale', 0, NULL),
(858, 'Psycholinguistics', 0, NULL),
(859, 'Discourse Analysis', 0, NULL),
(860, 'Semantics and Pragmatics', 0, NULL),
(861, 'Teaching Methods and Educational Psychology', 0, NULL),
(862, 'Morphosyntax II', 0, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `timetables`
--

DROP TABLE IF EXISTS `timetables`;
CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `tracks`
--

DROP TABLE IF EXISTS `tracks`;
CREATE TABLE `tracks` (
  `track_id` int(11) NOT NULL,
  `track_name` varchar(100) NOT NULL,
  `division_id` int(11) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tracks`
--

INSERT INTO `tracks` (`track_id`, `track_name`, `division_id`, `is_deleted`, `deleted_at`) VALUES
(1, 'دراسات القرآنية والحديثية وقضايا العصر', 3, 0, NULL),
(2, 'المنهجية العقدية والأصولية وقضايا العصر', 3, 0, NULL),
(3, 'اللسانيات', 4, 0, NULL),
(4, 'الآداب', 4, 0, NULL),
(5, 'التاريخ والتراث وقضايا التنمية الجهوية', 5, 0, NULL),
(6, 'الإعداد والتنمية', 6, 0, NULL),
(7, 'المجال والبيئة', 6, 0, NULL),
(8, 'Littérature', 7, 0, NULL),
(9, 'Sciences du Langage', 7, 0, NULL),
(10, 'الفلسفة وقضايا الفكر المعاصر', 8, 0, NULL),
(11, 'سوسيولوجيا التنمية الاجتماعية', 9, 0, NULL),
(12, 'Literary and Cultural Studies', 10, 0, NULL),
(13, 'Linguistics', 10, 0, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.png',
  `role` enum('student','admin','provider') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` enum('male','female','unspecified') DEFAULT 'unspecified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `profile_picture`, `role`, `created_at`, `gender`) VALUES
(7, 'مريم سالك', 'meryem2024salek@gmail.com', '$2y$10$lXkKJc1l067fQkKxJTecEeZYsrGn89kRYEuG7cWM2xdygVNM6PQoC', 'user_6a4a7948744b0.jpg', 'admin', '2025-09-28 22:26:05', 'female'),
(13, 'Aymane', 'jojoqribis@icloud.com', '$2y$10$cowTlgnbmJvWpsFfEJpkFu85xTXqn4oD.1R1UTnAcMBm2QIubcrWS', 'default.png', 'student', '2025-10-10 21:02:05', 'unspecified'),
(14, 'Tarik', 'Suzanahb09@gmail.com', '$2y$10$zpUwT4k/QFm/OKkEJQBBauB05gOsLClrjtrHwLHFSNURE2Uv.r/EO', 'default.png', 'student', '2025-10-10 21:05:34', 'unspecified'),
(15, 'Saida', 'saida.assou@usms.ac.ma', '$2y$10$J.V8XLQz6wCX1Md73h4CW.ZjHzLAOyuQUWd7TxZEvKbtRl2I0Whni', 'default.png', 'student', '2025-10-10 21:07:37', 'unspecified'),
(16, 'Khalid', 'boulaaouinekhalid@gmail.com', '$2y$10$CDYiyMHobS5imw32z8nnsO9rLUUOqHUiiUPYOGUVCwiFkwomJ8GJa', 'default.png', 'student', '2025-10-10 21:09:20', 'unspecified'),
(17, 'bilalofficiel_07', 'bilalbenhaddou2007@gmail.com', '$2y$10$p/nzNL2XfnhYsjI3mzfrZuz8Bv1Lnn2b2VFmIst6g/IatD31XW4Le', 'default.png', 'student', '2025-10-10 21:09:44', 'unspecified'),
(18, 'Rajae', 'rajouterai@gmail.com', '$2y$10$ogzks7XGgA5EWmFQeO6/YuYHfyhNKQGrqwrN27a3RWiFBhD693O5G', 'default.png', 'student', '2025-10-10 21:13:35', 'unspecified'),
(19, 'جبيل محمد امبو', 'khadimou.islam13@gmail.com', '$2y$10$r3uKVqoK7.tTrK4fQ6gpUOgrA577kcbiqEL8ho8aUrmRFtY6E.hwq', 'user_68e977161dfc19.05584979.jpg', 'student', '2025-10-10 21:13:58', 'unspecified'),
(20, 'خالد فاضلي', 'khalidfadel795@gmail.com', '$2y$10$YTVVoZAaNCSLnAbUCFDzJu.o0K.LRXsk5FkKDfc5lnLJuor.jyEWu', 'default.png', 'student', '2025-10-10 21:14:18', 'unspecified'),
(21, 'MOHAMMED1985', 'elamranim951@gmail.com', '$2y$10$7lW0lWCgiG6XNKu5rV4HC.vUw6uOnKIwjVYY0hWQ0JSvYzU.KRIVu', 'user_68e977f1308d11.10995960.jpeg', 'student', '2025-10-10 21:17:37', 'unspecified'),
(22, 'Atikabanani', 'fatimahanane315@gmail.com', '$2y$10$BHrhOjWEaMlI4QLDb.cjNuHnB34aKyJPtsHlRnL9jSVoS4e2ykRm.', 'default.png', 'student', '2025-10-10 21:23:03', 'unspecified'),
(23, 'Ismailbakhssas', 'bakhssasismail@gmail.com', '$2y$10$pgMwjOT1ruVBx3DR8FmXkOzjtCekEosGJd6DZx2A.oWZ.4vv8IXLy', 'default.png', 'student', '2025-10-10 21:23:55', 'unspecified'),
(24, 'سمية', 'soumialafkihi@gmail.com', '$2y$10$xQ92nFGcnnUtMuETLhATCOoqhGJEy4Cxg5uW7MeeCMT011dKkiuxO', 'user_68e979c5940f91.82912810.jpg', 'student', '2025-10-10 21:25:25', 'unspecified'),
(25, 'Oussama ELAMRANI', 'elo204372@gmail.com', '$2y$10$KcwKbpMvpKl9012qXwExnuGT5Io5u1GRqsY4eoZsFoT2fZdKpnugy', 'default.png', 'student', '2025-10-10 21:27:15', 'unspecified'),
(26, 'Hiba', 'msaadhiba845@gmail.com', '$2y$10$fv8YNrW2O/PtLRtwXeTfvORZFTz3aFoS76Icyc8TSBfLxfeorTwYu', 'default_female.png', 'student', '2025-10-10 21:28:48', 'female'),
(27, 'Hafida', 'kachmarrachid9211@gmail.com', '$2y$10$33IG99haAFGv4SuacLGLeunYTKH0nYqph2dfd9SqV7fDznwM6.tJS', 'default.png', 'student', '2025-10-10 21:34:11', 'unspecified'),
(28, 'Omo ahmade', 'gmilagamil2711@gmail.com', '$2y$10$jWXztIpHy1Tb.aymoEYRNe1GLNBjKV3RUkpX/ZAkPGr.Iq.1P.5AG', 'default.png', 'student', '2025-10-10 21:45:33', 'unspecified'),
(29, 'Hiba el qouqi', 'hibaelqouqi33@gmail.com', '$2y$10$n2cca1VtzotVZgsgrmKKE.cvu7DGE00D8yWDmtSR0.4yka7YGxMGa', 'default.png', 'student', '2025-10-10 22:07:10', 'unspecified'),
(30, 'La', 'wk@gmail.com', '$2y$10$KcvzK673P94Dtle2jhnGx.InT80rfZvK..Lr9a4AMn0qS2Ww0XGBG', 'default.png', 'student', '2025-10-10 22:18:24', 'unspecified'),
(31, 'Ouafa mejli', 'oumayaoum2.s@gmail.com', '$2y$10$YIgzm/Td8eAP2mECrq6tF.MnuF4apa9udxUPZFTQ2pC/5CZhwr0Ka', 'default.png', 'student', '2025-10-10 22:19:03', 'unspecified'),
(32, 'MOAAD  IBNESSAGHRY', 'moadm43212@gmail.com', '$2y$10$07A5EIk6LdZorSY3xW.cXu8VJYJsWITv.YbzW/D83l/p8cuPXuy6y', 'user_68e98b69acf113.52651335.jpg', 'student', '2025-10-10 22:40:41', 'unspecified'),
(33, 'عبد العلي', 'abdelalikaziz419@gmail.com', '$2y$10$XDONt3foCY0ZrMWD1e3q9.tSdHur2sGqRMkG6eTpS1h6PEpNNF1yK', 'default.png', 'student', '2025-10-10 22:44:31', 'unspecified'),
(34, 'Omar3434', 'loura9103@gmail.com', '$2y$10$jVV7CjO0diGapCrKz1gM5umcc4H.Njmc3jCFHkZuWIQ6MHxEV.YfO', 'default.png', 'student', '2025-10-10 22:51:13', 'unspecified'),
(35, 'Khawla fadili', 'fadilikhawla@2007.com', '$2y$10$aKXIjkLyTbpTLoIafV0ep.ffbhMdfcjOVDUYJ7D1bRJKEa.4YWb22', 'default.png', 'student', '2025-10-10 22:51:22', 'unspecified'),
(36, 'Hayats3', 'hayatblanche7@gmail.com', '$2y$10$LpUQfQEImZRjrWO267MhmOFfcEBPZa0K92he7UEI8L1L7MDlZex3y', 'default.png', 'student', '2025-10-10 22:54:20', 'unspecified'),
(37, 'Lhoucine Atlas', 'lhocineatlas@gmail.com', '$2y$10$XHWWv4ZAtA7rO1kfdwQex.zUYXEHxLtRRLRuaolr4FLqMaR8iqX9O', 'user_68e98ee63ae232.89484612.jpg', 'student', '2025-10-10 22:55:34', 'unspecified'),
(38, 'Meryem', 'sofimeryem98@gmail.com', '$2y$10$MrBdWJTREk/n47ziIi4gGO1JrHQNwnK/nSygzv2/4MYyaF7uEQebe', 'default.png', 'student', '2025-10-10 22:56:28', 'unspecified'),
(39, 'Khadija', 'khadijanomafi@gmail.com', '$2y$10$9S76nDrYHxvfmzShrNZuXOeQ75kSa9IPFKJe2ENHGzgDPmPlBnYOi', 'default.png', 'student', '2025-10-10 22:58:05', 'unspecified'),
(40, 'نهيلة', 'nohayala@gmail.com', '$2y$10$7KdlJ9t1c6Ex4lfdMXtTTOH7Sb6eeMwXXljX4JV8UdaYiZ6CYpgjS', 'user_68e9902695f005.22505559.jpg', 'student', '2025-10-10 23:00:54', 'unspecified'),
(41, 'Admin10', 'nassimmoudrik262@gmail.com', '$2y$10$cystuHL.4pVzHtkZLVcySu.Qt0Le4M3ZdQqsTx0Ir.VsLW3iGYHmO', 'default.png', 'student', '2025-10-10 23:01:14', 'unspecified'),
(42, 'Bouchra123', 'bouchratalib123@gmail.com', '$2y$10$Cl/hokdPr4QZnvNzEUuVFu988erKH06dDEXFcs7e5mU46YxZzsN5W', 'default.png', 'student', '2025-10-10 23:02:25', 'unspecified'),
(43, 'assya', 'assya@gmail.com', '$2y$10$LpZbcHgrqB850eRKAZrq7u2ISCIqopArJW3QqhesEA0pC/08QJ0PW', 'default_female.png', 'student', '2025-10-10 23:10:11', 'female'),
(44, 'سلي', 'boulilasali@gmail.com', '$2y$10$Ax9KP7A0rfImquJFDjY/wOg6cotFnjvwa5qjlIGLfgAvfrx7iNs0S', 'default.png', 'student', '2025-10-10 23:10:43', 'unspecified'),
(45, 'الفيلسوف سارتر', 'yaasin@lama', '$2y$10$D0JgH.gX27qh4cn2UEypjuos8vwSu8cMPaOO3mpZ9rSbTL7u/xA7K', 'default.png', 'student', '2025-10-10 23:12:11', 'unspecified'),
(46, 'L143064693', 'khadijaammou77@gmail.com', '$2y$10$gGyRZwZHF6ZiGb/VfcvbeO6JTVKWyVCKu1SHysSOvn88qTixns5xq', 'default.png', 'student', '2025-10-10 23:13:07', 'unspecified'),
(47, 'Hanane', 'hananesimo791@gmail.com', '$2y$10$zFhBjQq7uTZ6N7X8NfsfveeVD3ETmKtByuhojpQql1p0qZck0cLwy', 'default.png', 'student', '2025-10-10 23:17:28', 'unspecified'),
(48, 'Khadija ammou', 'khdijaammou66@gmail.com', '$2y$10$mXuTtPqSZsVzLd2Q3.Eqn.xHK4eX/Wl5b7u3A/uUNdktgTa5GBoS2', 'default.png', 'student', '2025-10-10 23:27:29', 'unspecified'),
(49, 'Rhita', 'rhitafassih@gmail.com', '$2y$10$abu7QWrGuDwMIrZgsz1KK.EHq5Kkvgz6DbK/ZwfpYiU94fkpxCzp2', 'user_6904aa9a4a5c0.jpg', 'student', '2025-10-10 23:29:20', 'unspecified'),
(50, 'امال المغيلي', 'amalelmghil13@gmail.com', '$2y$10$inxrFxRjKZLzjM.lQgENpuK4Xr5i6p9Dk2swS2sGZ3PqwkKPP2Rl.', 'default.png', 'student', '2025-10-10 23:45:57', 'unspecified'),
(51, 'Ahmed', 'ahmadouchaa34@gmail.com', '$2y$10$zjRvWnpiRN/uaot/cddjqus3LBRlw.2zmfrES9LxiOrdDNUE0Bdbu', 'default.png', 'student', '2025-10-10 23:48:17', 'unspecified'),
(52, 'Wijdane', 'wijdanemadkoury25@gmail.com', '$2y$10$FY8WeEYWrPIEoHBhLkXBdOywvs.2HjCo.gL6uEi.bYfv4iskAiki2', 'default.png', 'student', '2025-10-10 23:54:43', 'unspecified'),
(53, 'Asmaa', 'asmaadrdouri669@gmail.com', '$2y$10$i53IfqjxQtRtymZXo.hBZ.BO9bwME0hoV/Lm6/YkogvThzC/IAK7q', 'default.png', 'student', '2025-10-11 00:28:05', 'unspecified'),
(54, 'Houda ben harkat', 'houdadodo987@gmail.com', '$2y$10$mFrQvi7ef5CayOoO88CK9u1jljwCLS1HQRKoeWNhm9uWwTHfKmPRq', 'default.png', 'student', '2025-10-11 00:43:54', 'unspecified'),
(55, 'Khadija mouslim', 'mouslimk844@gmail.com', '$2y$10$0To/HY8YHyBwVqnzHs7KWO1mv5NsiW4DBFJ.V/2teu/kfMMi0e3f6', 'default.png', 'student', '2025-10-11 01:43:16', 'unspecified'),
(56, 'Khadija15', 'rajawiyakhadijakhadijaelmotara@gmail.com', '$2y$10$vpCPBWRkBNXWeiM2JaQ.JergT0j5jUQdEsiWWoJESJfUAqrC6rRfi', 'default.png', 'student', '2025-10-11 05:30:08', 'unspecified'),
(57, 'Jalila', 'jalilaelansari2@gmail.com', '$2y$10$aQt/OkboO/cZv0kf95raOewFOf.HEkW5CJWWiJKdnH2xNC00oQFp2', 'user_68e9f3807d81a0.61360613.jpeg', 'student', '2025-10-11 06:04:48', 'unspecified'),
(58, 'Taha', 'bensidimhand1968@gmail.com', '$2y$10$tTLWyLVuBVQRPlOrFm4An.MhyP2.eiBiWrtZp4hH9ilswgmLx9DWu', 'default.png', 'student', '2025-10-11 06:05:36', 'unspecified'),
(59, 'zeroualiadil85@gmail.com', 'zeroualiadil85@gmail.com', '$2y$10$O0rNEseCSOsl27PteaaaX.aLlXhtaNdTfZOGM2mRnz5v4awN9yMDm', 'default.png', 'student', '2025-10-11 07:35:28', 'unspecified'),
(60, 'Asmaa kamal', 'kelasmaa398@gmail.com', '$2y$10$yZ.Ae7q57hsEGydDx9X0SO6YzeSmiAUzH2uXnU1lYWr97aZUwbxci', 'default.png', 'student', '2025-10-11 07:53:49', 'unspecified'),
(61, 'Chadi', 'elmoujoudichadi@gmail.com', '$2y$10$iSkhGmiKGsH7v..MZoXhD.3K3yLDc5RadO1lgQmy76dvqI4hv2KDO', 'user_68ea13d8be458.png', 'student', '2025-10-11 08:18:19', 'unspecified'),
(62, 'Elbachaoui layla', 'laylabachaoui@gmail.com', '$2y$10$TFZUGfuxt5UVW.Nwvk8omO/4iyygoUrViD52t9xIulO2h//x/Bhf2', 'default.png', 'student', '2025-10-11 08:38:28', 'unspecified'),
(63, 'Hassannnoutfia@gmail.com', 'Hassannnoutfia@gmail.com', '$2y$10$GUWhre0hqmlTVIYI92KS6eWLk3eXamIb6Nf6.gqoFkcY0f0G197k2', 'default.png', 'student', '2025-10-11 08:49:34', 'unspecified'),
(64, 'étudiante', 'aichafadli2005@gmail.com', '$2y$10$5s9aknFF7nGtQKwX4iYvrud/RFYIUvlqSkEo/93RcG1UbbEm5KbHu', 'default.png', 'student', '2025-10-11 09:01:10', 'unspecified'),
(65, 'محمد', 'kamal066677708@gmail.com', '$2y$10$OBehT7ax48ZdzvSEH5uW0uQVTWgLSxIEGgbZd912sK/FAGMuPLFGi', 'default.png', 'student', '2025-10-11 09:12:59', 'unspecified'),
(66, 'Ait masri hassna', 'amiratabiha@gmail.com', '$2y$10$Jwbl/HDTxkcdo8QTB9aNnOv4mLqFn4hWtIIseVjS3CLLuBJCwruaG', 'default.png', 'student', '2025-10-11 09:33:40', 'unspecified'),
(67, 'Maryam', 'maryam@gimyl.com', '$2y$10$XJvi1PGsqPQ9acai3zfvi.Wzu0vo08gzV0mIHxL7WqiBABr3r5ozi', 'default.png', 'student', '2025-10-11 09:54:18', 'unspecified'),
(68, 'Omar', 'omarbendouhou628@gmail.com', '$2y$10$BTLXRp5AWZbv4JOLbH6z/eF6QrzlMYcjEJp.Jj/SoqO.Q5pEBcgUS', 'default.png', 'student', '2025-10-11 10:25:54', 'unspecified'),
(69, 'Yassmine', 'yassminemasrouf8@gmail.com', '$2y$10$9MVdm4BlIiZJorNllReaCeWpcb7v3fxExbQoEpd9U1DMasSyuhbRu', 'default.png', 'student', '2025-10-11 10:38:42', 'unspecified'),
(70, 'Hiba005', 'hibaharrach4@gmail.com', '$2y$10$ccWqrVFFSSqnDqzuY9KPSOI4YKxi4MYX3DQ8dMMZyQ94LaGAAscfK', 'default.png', 'student', '2025-10-11 10:59:09', 'unspecified'),
(71, 'Ilham', 'ilham.nainia@gmail.com', '$2y$10$kLueQOb8q83ITVKa0BsDi.AhIuhMLvvEf6LWq42A8oB35PqMVxQA.', 'default.png', 'student', '2025-10-11 11:24:26', 'unspecified'),
(72, 'ha8p234 ana', 'hapa82380@gmail.com', '$2y$10$49GkJ0WyCaDGZ4xDG.zLluFG4X53yj.igP41pffswXxkhZeYAQJiq', 'user_68ea3f228e1c57.30408969.jpg', 'student', '2025-10-11 11:27:30', 'unspecified'),
(73, 'Imane', 'imane7700iman@gmail.com', '$2y$10$hGTTbmPiOUOPtXJ2hcNSBOfaWL.w1oJbfMP.7Tk.XTtpPbpFmFuym', 'default.png', 'student', '2025-10-11 11:29:12', 'unspecified'),
(74, 'Arjaby', 'elarjabymohamed@gmail.com', '$2y$10$KDArtqF18jniHJ/MCDYKFehZyTlPKMZN4d22MO1APzoMZKjP1LjyG', 'default.png', 'student', '2025-10-11 11:37:16', 'unspecified'),
(75, 'Hanine meryem', 'Hnmeryeme@gmail.com', '$2y$10$f.SI2tJ1dToE7TKpCVsYEe9EKNUxQO.MD8PhXt6UOC2KC9ihqHL8W', 'default.png', 'student', '2025-10-11 11:38:40', 'unspecified'),
(76, 'Imanemsatfi', 'imanemsatfi2006@gmail.com', '$2y$10$EaF7BVkRyo.hDYXgBe9Qi.QhxvaTE.LdJXgookoGyHU7CoF1Ali.K', 'default.png', 'student', '2025-10-11 12:01:05', 'unspecified'),
(77, 'Meryem hn', 'ayaaa1247@gmail.com', '$2y$10$2vvNHKU9.jA8HIsR/XmPyOzvK3tDw3zmcZZ57zhriPj/SjzDrjwcy', 'default.png', 'student', '2025-10-11 12:22:04', 'unspecified'),
(78, 'hiba elqouqi', 'hibaelqouqi15@gmail.com', '$2y$10$.9t8Iar8fxILhN0G8oXgeutkEXeCBTHTHNuWjVkL2os6XEGzk.u3i', 'default_female.png', 'student', '2025-10-11 12:57:44', 'female'),
(79, 'Fatima 13', 'irezir6@gmail.com', '$2y$10$gtoWlNJREhL3qCiDKlsFM.RK484vzyFv7mv2h8G69I5VOJ3PWCeui', 'default.png', 'student', '2025-10-11 13:18:07', 'unspecified'),
(80, 'نادية بولقرون', 'nadyaboulkroun5@gmail.com', '$2y$10$mNTqVxe6WARZiOQ9VIaPJ.2XwfklFVfdmb8FxMtiEfUAnGU9wLzhq', 'default.png', 'student', '2025-10-11 13:27:09', 'unspecified'),
(81, 'سارة', 'ayguer.bl3@gmail.com', '$2y$10$Z9hsB3fIaV1EenH1VJ1CwOXBYDNMWBHqo8sKku1Rlw6WEROcSNOSq', 'default.png', 'student', '2025-10-11 13:30:22', 'unspecified'),
(82, 'Fatima Zahra El', 'ratyipzs@gmail.com', '$2y$10$0ALjSgCZeAiX9LmLblCr.OIFZwKV8GgrRte9pfNBx7i5xFcRK0jfS', 'default.png', 'student', '2025-10-11 13:31:07', 'unspecified'),
(83, 'Salma', 'salhisalma403@gmail.com', '$2y$10$lf82c/.tDJFJC8OycwfFHuRbB4ybXzhgU4jax6A9RMhNtVvYk5/Fy', 'default.png', 'student', '2025-10-11 13:56:17', 'unspecified'),
(84, 'Abdllah', 'Abdllahballouk5@gmail.com', '$2y$10$6e71B4WjJbXrEKcTkJRyVeLHypFKjlrAWM5Gn5/1H56aPC7t36LL2', 'default.png', 'student', '2025-10-11 14:22:25', 'unspecified'),
(86, 'Maroua', 'marouaamermouch@gmail.com', '$2y$10$ZX76klKfOOun32IAZlKKkecyv7QcQijhdoQjgN2263mKK8uqGKn2e', 'default.png', 'student', '2025-10-11 15:51:29', 'unspecified'),
(87, 'Amal elmghili', 'amalelmghili13@gmail.com', '$2y$10$x0ZANDAdtgqWDamDhDtxI.yNQX1RDEKOLCsYxPhy/B6XnaqkYFOHq', 'default.png', 'student', '2025-10-11 16:09:51', 'unspecified'),
(88, 'Fatima Ait haddou', 'haddadfatima7@gmail.com', '$2y$10$2U/3s1mGPeL2pT2OLMtBBOM.qND7Xc4/FDlbHH/DKJXcjbvS0hv7K', 'default.png', 'student', '2025-10-11 16:18:53', 'unspecified'),
(89, 'Fatima nadir', 'nnadire222@gmail.com', '$2y$10$I3BallzL1MyYpBdiz4iwEOotqZg9JlGvQZpC8c67YjfEcIGiqqgLO', 'default.png', 'student', '2025-10-11 16:21:57', 'unspecified'),
(90, 'Akram', 'akramfaiz290@gmail.com', '$2y$10$e57zBbts0UpottPTo0rFP.wxSy03Wt6worcy.EMdBdgXKBoRBkhR.', 'default.png', 'student', '2025-10-11 16:55:30', 'unspecified'),
(91, 'Mariyam', 'mariyamelhachimi756@gmail.com', '$2y$10$BHOfpxObp62qP7zus65x4.UyF.CgM7QplWZ5zASjha/qffpNZGDW2', 'default.png', 'student', '2025-10-11 17:38:40', 'unspecified'),
(92, 'Abderrahmane el hassany', 'elhassany333@gmail.com', '$2y$10$kOMaLYTSNBf9JAxhP1QD8u9BSA0QcRSlnPo.xWwtu67TqBQBsh0re', 'default.png', 'student', '2025-10-11 18:41:57', 'unspecified'),
(93, 'CPH7T', 'omartimirit6@gmail.com', '$2y$10$5STAixKbDJVxmgpBPEmgfOB0zaJLu6paU6k7UU0kHyVURnCTS5BRG', 'default.png', 'student', '2025-10-11 19:31:19', 'unspecified'),
(94, 'P210479826@TAALIM.MA', 'fatimaezzahraelhaouli@gmail.com', '$2y$10$bmWies.W0xAC91tj7MS06eWAxogdLHjLM98QJx79mHhCJr7u39ij.', 'default.png', 'student', '2025-10-11 19:50:10', 'unspecified'),
(95, 'kaoutar', 'kaoutarzemrani7@gmail.com', '$2y$10$iUPkRq20u/ZENG1LuauK.OygmCyVt35cst9JZzNQH1LAx3vFhJxMW', 'default.png', 'student', '2025-10-11 19:56:52', 'unspecified'),
(96, 'Brahim abou hanm', 'iglibrahim8@gmail.com', '$2y$10$pa1ib7VE82OwLLnkkhkIz.iAxGOoKWIQV.dp1gH49JtOFSsoD1K6G', 'default.png', 'student', '2025-10-11 20:16:27', 'unspecified'),
(97, 'Habiba 18', 'habibaammou33@gmail.com', '$2y$10$TIF4qNbh2T7cOAElpY1v..WpdBZIdMpRo/.49eIzcXsMiZu3hNwpG', 'default.png', 'student', '2025-10-11 20:54:55', 'unspecified'),
(98, 'Maroua el abbassi', 'elabbassimarwa88@gmail.com', '$2y$10$dWbxTnvhtKoO/ECyX2P8JO0IDHTZdDoPZLx7JT84A4TGFCdoyNlVC', 'default.png', 'student', '2025-10-11 21:54:04', 'unspecified'),
(99, 'Fati chakir', 'chakirfati270@gmail.com', '$2y$10$thuRahc9y1AVfJ5Y0JDfoe87bxSLBU2cTH4kL7KM3wc0eKicZjMA2', 'default.png', 'student', '2025-10-11 22:44:42', 'unspecified'),
(100, 'Fatima Fati', 'akadirbnimllal1234@gmail.com', '$2y$10$zclCxs8oD3HD5Lh5p2YC1uBcuBx8VkEDN5Bjm4wh7F9r6/x6gmfbm', 'default.png', 'student', '2025-10-12 10:09:07', 'unspecified'),
(101, 'amine', 'spinozaamine03@gmail.com', '$2y$10$9E8sVAD6k0pVc9kyt/8Dx.z4ptVH.9WOyJ0HeuZRaQds4iNy.ess.', 'default.png', 'student', '2025-10-12 11:33:03', 'unspecified'),
(102, 'Meryam', 'akkaouimeryam54@gmail.com', '$2y$10$rgf7qYZmxv2ZJN65f.1/L.YVMejfYCo.MNihH3RVEFWPEaqvjcWN2', 'default.png', 'student', '2025-10-12 12:32:04', 'unspecified'),
(103, 'SIHAM DRIOUCHE', 'Sihamsiham0@gmail.com', '$2y$10$edB9VTnuKHIGE.We/TD5EOUQ0Uu3XQz2ZgmfT12hAbLgajwWziKdq', 'default.png', 'student', '2025-10-12 13:45:11', 'unspecified'),
(104, 'Hind', 'hindaitelwahid123@gmail.com', '$2y$10$oOWRSLIneUnAQhsPqcMmy.1.d0w.FuqYJ117I2U0R/R8NH2RllLmm', 'default.png', 'student', '2025-10-12 20:13:27', 'unspecified'),
(105, 'Najat', 'azeeeazsd12@gmail.com', '$2y$10$9hV2CMUmYrkcc.0fLg.6CerwoqEfup4jlUxZAJyKWK1QdWHy31v0G', 'default.png', 'student', '2025-10-12 20:19:40', 'unspecified'),
(106, 'fatima', 'amine.spinoza006@gmail.com', '$2y$10$zx9KJ9uk21jCcTc3/37lceUo1i7mxmhjAUZT3doo4VNZUqpyoYrNe', 'default.png', 'student', '2025-10-12 21:26:46', 'unspecified'),
(107, 'Amal oufrid', 'danidani75dugr75@gmail.com', '$2y$10$LVgznXqF0LYN4pd94otRyOFMigxmzPIRK.f/sPcrDTXswGrsXkR6y', 'default.png', 'student', '2025-10-12 22:29:52', 'unspecified'),
(108, 'Mohamed', 'Mohamed@gmail.com', '$2y$10$xcWZAjkEAS3/D.U7ZxrLiu.ccajCNKSp.WW399cv9nflkYhroUmv2', 'default.png', 'student', '2025-10-13 01:02:34', 'unspecified'),
(109, 'ES-SAADIA', 'akajjounesaadia@gmail.com', '$2y$10$fn923nDgCBZxbyARd2JFKes9NopwE9isM67uQ2CocwoG4BVFFUOuG', 'user_68ecc0dbef1198.79463835.jpg', 'student', '2025-10-13 09:05:32', 'unspecified'),
(110, 'Rachida', 'rachidaikhard@gmail.com', '$2y$10$051rL.m.ivVtZ7rCQf171eHzkQCFlcMjpLxPHhZ911HOKABc/oIPu', 'default.png', 'student', '2025-10-13 09:30:49', 'unspecified'),
(111, 'Selma', 'selmaessami04@gmail.com', '$2y$10$LufgJv5sJeTiom2mXf4Cq.TgCR5/jzemgHt/m2luyKfFCmfsYyLHW', 'default.png', 'student', '2025-10-13 09:38:06', 'unspecified'),
(112, 'ghizlane', 'boulmanetighli2020@gmail.com', '$2y$10$xyyt7UXHuzov73dIvlXWO.F9DDoabC2vnHqIgv4JcIgImmJe81OzK', 'default.png', 'student', '2025-10-13 14:24:02', 'unspecified'),
(113, 'Walidwalid', 'khinoabdrhman@gmail.com', '$2y$10$cVo3JVhBB0J0N64rXCIj8.B6gLHn7vDn5CMpgA7PealJXix4hKFri', 'default.png', 'student', '2025-10-13 14:57:27', 'unspecified'),
(114, 'حسناء', 'omar.omar1955omar@gmail.com', '$2y$10$4mwwgxJY3pp/KoCJ12DYgO1zftoZZf/bqHVM3xT7WseNQOMpArcPe', 'default.png', 'student', '2025-10-13 15:05:50', 'unspecified'),
(115, 'Aya oumezough', 'ayaoumezough977@gmail.com', '$2y$10$VklYNsiDvclCe4PObYQjTel9Y7Yy254pND6viRjpA1l65u1U2F6z.', 'default.png', 'student', '2025-10-13 15:47:53', 'unspecified'),
(116, 'Hajar11', 'louraloura326@gmail.com', '$2y$10$oR4xucdUC0hIFTBTyoWWROqXRJzTDRK2VXkUn.3pU.4epy.3DMt1W', 'default.png', 'student', '2025-10-13 17:09:42', 'unspecified'),
(117, 'Bilali khadija12', 'bilalikhadija641@gmail.com', '$2y$10$jpKjRgV2dPO/U15Lh5xag.L.DEwbmIehHhFeuka6WbiC/7bg.PPyy', 'default.png', 'student', '2025-10-13 19:49:07', 'unspecified'),
(118, 'imane95ask', 'imaneaskari95@gmail.com', '$2y$10$L.IuCNwovmqbg5jtEu7BNO/hAsaRaMs9luBKvEF0iqKg/iNPWOxcS', 'default.png', 'student', '2025-10-13 23:16:49', 'unspecified'),
(119, 'Hajar', 'hanifhajar420@gmail.com', '$2y$10$5TYm8ToVVJiAC.cBhuJJn.ZfUK62CzsJGAQsHljJ9PXhIaM0XWisi', 'default.png', 'student', '2025-10-14 12:45:11', 'unspecified'),
(120, 'Abderrahmane', 'abderrahmanetouil3@gmail.com', '$2y$10$Xtd9sxs8jYsI4dvXn8NrHO9QssfV4yNnCl/QWcHHOgFoDeGTHJWCS', 'default.png', 'student', '2025-10-14 18:34:14', 'unspecified'),
(122, 'Yassmine12', 'imaneimane132@gmail.com', '$2y$10$qQVfXWnu3KI.c32K9d/ej./paz7msR..RK97uNlful4jxEqmGsRLG', 'default.png', 'student', '2025-10-15 14:41:50', 'unspecified'),
(123, 'Imane123', 'imaneyassmine123@gmail.com', '$2y$10$z9x0k6muFUhPrZqI4jcJ.eTImrz9NZG8z1MwrDdS9ueLRVEOtHvF2', 'default.png', 'student', '2025-10-15 14:43:55', 'unspecified'),
(124, 'imane msatfi', 'imanemsatfi@gmail.com', '$2y$10$YJAju3qp3cgyqAfBZ3sC8OTqUNv3tWeQPm1WXZq9zw0oA0cnVnNDy', 'default.png', 'student', '2025-10-15 14:56:44', 'unspecified'),
(125, 'meryem meryem', 'meryem@gmail.com', '$2y$10$3EoMuyETihO8qrBuAzQbhuVNm./kDtK01a67ACvzMPwEUxJu1izTq', 'default.png', 'student', '2025-10-15 17:10:48', 'unspecified'),
(126, 'Abdoakil', 'abdoakil513@gmail.com', '$2y$10$8OfNeXg2CB8o9Iral0fxbeXcKzI6nFH.YJp.KyzjR7.MmdzcxLVTa', 'default.png', 'student', '2025-10-16 11:33:53', 'unspecified');
INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `profile_picture`, `role`, `created_at`, `gender`) VALUES
(127, 'Asmaakamalelidrissi', 'asmaake24@gmail.com', '$2y$10$JR4xHZ9HBWWmwYwPnkYnrO0Zru5Q9tVT29o27WAgitE9wWjnnZywG', 'default.png', 'student', '2025-10-18 08:05:34', 'unspecified'),
(128, 'hajar eljaiui', 'hajareljaoui48@gmail.com', '$2y$10$ffGHQGlziwDXuU3TGpfHL.GNGXZ4G/AeEZoBdOMJVGda0DrjFiCwq', 'default.png', 'student', '2025-10-19 12:36:52', 'unspecified'),
(129, 'Warda', 'wardawarda@gmail.com', '$2y$10$d3.nbHSAD7vRysXJAtpaUucpwGAyXq0WIeecrt.PRD5WBKRcRNd9K', 'default.png', 'student', '2025-10-19 18:48:00', 'unspecified'),
(130, 'Widad', 'widadwidad@gmail.com', '$2y$10$yP4XmqZQG0FjKN/JHiQY7OkIWn4.1B.xbLboMiwTLtzpjpEhjrom.', 'default.png', 'student', '2025-10-19 18:52:45', 'unspecified'),
(131, 'AYOUB2026', 'mahaouchiayoub21@gmail.com', '$2y$10$.srIafamJSWJqUMAkOkImu3XYLhCQmBt37xX6jtGma4mO1LONkozS', 'default.png', 'student', '2025-10-19 20:58:24', 'unspecified'),
(132, 'Nisrin13', 'rayanfatiha1999@gmail.com', '$2y$10$U4lGEj9vrxwpTSRRay1AXeYKlCYsn7ADO.sT5Ajbx64/eWrZoc032', 'default.png', 'student', '2025-10-19 21:03:41', 'unspecified'),
(133, 'Fatima Ezzahra12', 'fatimaezzahraibnali@gmail.com', '$2y$10$dWDPZxjyO9Up8wjApe3mS.QCcAPL2XFa.0VUfY8btZSQYylJNzBNK', 'default.png', 'student', '2025-10-19 21:08:21', 'unspecified'),
(134, 'Zakiya13', 'zakiyaahmidoch@gmail.com', '$2y$10$u8fRJpRMKbt0w7vI672qtu6P.RvV/QFTwLFrVqstSDBg.UhoBHliq', 'default.png', 'student', '2025-10-19 21:15:21', 'unspecified'),
(135, 'Aicha', 'aichahassal@gmail.com', '$2y$10$vgFfhCthZIu7zE7feBkPFudJYjAAWf9xwrKCOQaDSF68BNg16fiv6', 'default.png', 'student', '2025-10-20 22:24:23', 'unspecified'),
(136, 'Formateur', 'abderrzzakerrachidi@gmail.com', '$2y$10$Rb8zRXvz/93bcJSviIpfheYjPeOdbNLw8dQoMD23e35LgBsHNVD0y', 'default.png', 'student', '2025-10-24 23:02:50', 'unspecified'),
(137, 'Sound', 'souadelmouate@gmail.com', '$2y$10$8wmnZE24XcTo8n4iDJ1M4.WiI2GFTg1HW406cLSU2BhhJfO201AP2', 'default.png', 'student', '2025-10-25 12:28:41', 'unspecified'),
(138, 'oumayma', 'oumaymazenouari51@gmail.com', '$2y$10$qFSf6eyv9GR8Mmfcd1Q0feUJ5wOy3jQlRpg5fE37/nH1ZC8ulgWgK', 'default.png', 'student', '2025-10-26 19:02:05', 'unspecified'),
(139, 'Safa', 'Maryammaryamaze1@gmail.com', '$2y$10$Nd4DVTtrQ7Rv9y2/TPQMnuN1oNy8ZJ44soY6xFah7B/rzNjzyGSUi', 'default.png', 'student', '2025-10-27 10:27:43', 'unspecified'),
(140, 'مريم', 'allilimaryam5@gmail.com', '$2y$10$M5/L6PoqE.Sp9SownE5DQ.3FpF3oyv64mOBWEONwJFlkRYXut1a1m', 'user_68fff11755396.jpg', 'student', '2025-10-27 22:12:29', 'unspecified'),
(141, 'طه محمد', 'baydabola@gmail.com', '$2y$10$7ONzdImUZ0lKIYcBVIDghumBliScFG1WLSQRS9BoaZjBpTDdr/ItK', 'default.png', 'student', '2025-10-29 06:17:44', 'unspecified'),
(142, 'abderrah,ane', 'abderrahmanetou@gmail.com', '$2y$10$CxTBCiPpQhmN4pM8UJWQ2.G.3LNx25JADb085cuBivIjQNryvDX9e', 'default.png', 'student', '2025-10-31 15:11:45', 'unspecified'),
(143, 'Zemrani', 'kaoutarzemrani674@gmail.com', '$2y$10$Fqox/Wyceo5SKHW9TG6MLu01YAnc5wSttS.JzxRXJ5ZnBAL1vHkDK', 'default.png', 'student', '2025-11-01 12:10:56', 'unspecified'),
(144, 'Malika', 'ouaissamalika77@gmail.com', '$2y$10$fLjuKGIcyxJoGdSjPULEae13Tg9.oSmrySuYxYK7qgg7oLiEgDTk.', 'default.png', 'student', '2025-11-02 22:13:54', 'unspecified'),
(145, 'جواد', 'jaouadlbazlbaz@gmail.com', '$2y$10$hRaa.W53tV5p65mYlNLXie/fPV/Y7KPW0RyVh64BVhgAdFdveUh86', 'default.png', 'student', '2025-11-02 22:38:49', 'unspecified'),
(146, 'Fatima Ezahra', 'rhedjam833@gmail.com', '$2y$10$QF.xBtDNaMox.Lw4cy9hwezhxvcA6GqyabG8urtlZAPtEd2/xUaWC', 'default.png', 'student', '2025-11-02 23:19:38', 'unspecified'),
(147, 'Bouchra Elghazi', 'bshryalghazy33@gmail.com', '$2y$10$g2o8vbsatCUgsbGzACO7XehOL5O8EKKbjSacDRh9akCZkrXND3pZe', 'default.png', 'student', '2025-11-06 15:31:51', 'unspecified'),
(149, 'l145050935', 'zakiahmiddouch@gmail.com', '$2y$10$nPYf/FncHmvDFprY5ci1O.eGV7iDg676jGA5Mr6VGfIQYCGhrtYHi', 'default.png', 'student', '2025-11-10 11:16:15', 'unspecified'),
(150, '24006689', 'MOSAIDABDELHAKIM44@gmail.com', '$2y$10$r3C4gaK9O2QSzamjKEwFv.pLRGdJtmbx/63KDGTaq4CZPN/XuMxpy', 'default.png', 'student', '2025-11-13 10:11:24', 'unspecified'),
(151, 'فاطمة الزهراء', 'rachidaassili05@gmail.com', '$2y$10$YUhRVORfpidbrxET3SfF1O9qqC7uXBWsgbnAjuT8uWZqO.GnvbrSK', 'default.png', 'student', '2025-11-21 23:14:15', 'unspecified'),
(152, 'irizi', 'iriziirizi7@gmail.com', '$2y$10$CRlQaVTa9cDrSoZdcHE5mu6aKwCSI.g75jQoUHH8fWU/eoYr9WoyC', 'default.png', 'student', '2025-11-22 22:40:55', 'unspecified'),
(153, 'Abderahman', 'abderrahmanetouil2006@gmail.com', '$2y$10$fHfp6CB41YuPzQbRWI57yuX0Z8JIxZjPdQgMBAxZL8y8LOtfX05Hy', 'default.png', 'student', '2025-11-23 13:12:42', 'unspecified'),
(154, 'MONEY', 'yaagoubiabobakr20@gmail.com', '$2y$10$o33tqx8i1Z/YObhzUovDIevRnDx4MGr4daTkQGrIKogS38YuaSkme', 'default.png', 'student', '2025-12-02 06:53:31', 'unspecified'),
(155, 'Uh ho', 'itsssyoupa@gmail.com', '$2y$10$3gEk7s66OotTlMONM3c8j.FyF0zOhMec/yP0t5.kgMuVVnLbC21Ny', 'default.png', 'student', '2025-12-02 12:30:06', 'unspecified'),
(156, 'Heba', 'haichiheba@gmail.com', '$2y$10$o4BRC6qwGFqOiLkc1nYlWu1HrhKeeV4UEhca0u8ihR.mIzJrEVQ8S', 'default.png', 'student', '2025-12-08 12:16:28', 'unspecified'),
(157, 'khalidlam', 'khalid.lam.21@gmail.com', '$2y$10$or4sfDwD0WnVoPJlu0wUYuZEmMqEc5lt9TtQougyHH592nZf9eoXm', 'default.png', 'student', '2026-01-17 19:30:34', 'unspecified'),
(158, 'Abdo', 'abdelhadiarsalan4@gmail.com', '$2y$10$Sw38pKSSc.j645irOtxiK.9.GIB4H3o6G0wpe8bbx7x.BEQ9ZY1E6', 'default.png', 'student', '2026-01-17 20:48:21', 'unspecified'),
(159, 'Gg', 'mahjoubfarkli8@gmail.com', '$2y$10$OTjti0MS9oePjq6y4NulXuFVdBkJjewn8CDdyuaGFEI8uJQE2wsL.', 'default.png', 'student', '2026-01-17 20:48:53', 'unspecified'),
(160, 'selwi', 'arsalaneabdelhadi30@gmail.com', '$2y$10$Oun0uzIfZDTHblyjvlR1PObgRhQc/aVeURp.WBA/91wSFiVRmHzl6', 'default.png', 'student', '2026-01-17 21:35:51', 'unspecified'),
(161, 'qwerty', 'admin@example.com', '$2y$10$GDYT5D1n4NFmkTU1rqJrq.PNZ3G0sc2AOeeo78sfj9gtmWJFDhajC', 'default.png', 'student', '2026-01-18 06:03:01', 'unspecified'),
(162, 'zozyjit', 'lekopaheho@mailinator.com', '$2y$10$ve2fDUccrN1rEZ8NVjYvjeHpCqjVu6apfxENQucjmHIaNZFKtjBgS', 'default.png', 'student', '2026-01-18 06:24:59', 'unspecified'),
(163, '5wy', 'elbaroudiomar454@gmail.com', '$2y$10$714wuPEhotCNoc1/FZkSuu4.jvFxQfrxN9m8J1ofl87zQ2G3q/TbC', 'default.png', 'student', '2026-01-18 15:12:49', 'unspecified'),
(165, 'ayoub', 'ayoub.maatala.1999@gmail.com', '$2y$10$sJGCWhUNwgbIj7FF5IpRWetQSTPzWAxh4tDi9nrTooVW/W/Bfof6e', 'default.png', 'student', '2026-01-18 16:51:49', 'unspecified'),
(166, 'aaaa', 'azdhsd@gmail.com', '$2y$10$zGro1jNYyTFuAOVrGcX8feQE2iwsFk4ArP39lXk9/YTW.sDowmsR6', 'default.png', 'student', '2026-01-21 11:59:32', 'unspecified'),
(167, 'Qwert', 'mohamedirizi@gmail.com', '$2y$10$8cI4ZMCsgKIVnAwOUDLc5.e0g/1WFbaN0G2zFGABRLl5tE9E6rbGi', 'default_male.png', 'student', '2026-03-04 21:32:16', 'male'),
(168, 'salah', 'salah@gmail.com', '$2y$10$VCNCd3CLUtqL4zr13SYDb.JUdcAHl7D5dJ5DYPK5CuI0gBJ2H1Duy', 'default.png', 'student', '2026-03-11 11:30:04', 'unspecified'),
(169, 'irizi123', 'irizi12310@gmail.com', '$2y$10$TGMmU6dBM/SttpqUpTuzPe4l4dMBrc1H.68kCeTqg5tl04JueFCWG', 'user_169_69c2b7d8a4cb5.png', 'student', '2026-03-17 16:03:57', 'male'),
(170, 'irizi1234', 'iriziirizi1113@gmail.com', '$2y$10$ZUviAS8K4iSx9.JXkauy9OD6nG9YwnqfrIbnNdaOL9mZsvqzsF7Li', 'default_male.png', 'student', '2026-03-26 11:35:17', 'male'),
(171, 'testtest', 'testtest5@gmail.com', '$2y$10$XJLytkArU7PDEWHdxenti.lfR/zRftPqgbUS3ff/XQf5oc1/94mN.', 'default_male.png', 'student', '2026-03-26 18:00:30', 'male'),
(172, 'محمد إيرزي', 'adminirizi@gmail.com', '$2y$10$gUuAZ7Zu7lirYe66fF9qEeJwey.kziqsdIzEp2AZVrG8/UWQhtcRW', 'user_69fb840cd6e68.jpg', 'admin', '2026-03-27 12:20:46', 'male'),
(173, 'irizi123444', 'iriziirizi13111111@gmail.com', '$2y$10$46EEWFzeyXAN2SvigyzNzeYkGSv4yc7HrLU0PSvQ2f7/E15Ygn6U2', 'default_male.png', 'student', '2026-03-27 23:26:34', 'male'),
(174, 'irizi12311', 'irizi12311@gmail.com', '$2y$10$HSU4Bt2dQwvor1nlwon4bOKfld/S1nsqhaqChUNp1HqUTlxCu5Mii', 'default_male.png', 'student', '2026-03-28 00:14:15', 'male'),
(175, '&asxws', 'zddz@asw.co', '$2y$10$4SlfOL4MsQfBvTLhU77Tc.XNxgc80b83efeBY7be/48CzpTDjI0SK', 'default_unspecified.png', 'student', '2026-05-01 01:17:52', 'unspecified'),
(176, '123', '123@gmail.com', '$2y$10$Xajs8.LsebMsbmDe6c7bOu36E5prYVwhlF1UkB1/IEWc.2Vik52wy', 'default_male.png', 'student', '2026-05-14 12:10:45', 'male'),
(177, 'assya11', 'mohamedirizi1555555555555@gmail.com', '$2y$10$ZahzMgdovskgwi5nzptCoO7SqOOaESIOngoCHHuuoRiUWWESNU73W', 'default_male.png', 'student', '2026-06-19 15:00:43', 'male'),
(178, 'prov_6a35ba3baf924', 'adminirizi11@gmail.com', '$2y$10$fbkgAFtZ2C2Y4HhQ.ysK0O/iKUN3C/eXcbYi0.e/EySi27eYpe4UK', 'prov_6a35ba3b8e946.png', 'provider', '2026-06-19 21:52:59', 'unspecified'),
(179, 'prov_6a35ba6c29890', 'adminirizi55@gmail.com', '$2y$10$zOXaslyNayWzSif/oo7RbecPNUgx/pn3jC.Lsw7mkhAiu8Z968Kre', 'prov_6a35ba6be6f3f.png', 'provider', '2026-06-19 21:53:48', 'male'),
(180, 'سيبفال', 'adminirizi555@gmail.com', '$2y$10$vNB9vQ9MWkWB9CUnr.iIs.bi47Mej7Tq7CEjrKYMqgUUbd2.e0.Ee', 'default_male.png', 'student', '2026-06-21 10:10:30', 'male'),
(181, '125788', '123445@gmail.com', '$2y$10$uiNehQ/dtaa0U.LBepI2SOH80r2DFId8CY83ouhX.t9LSf6KOwnx2', 'default_male.png', 'student', '2026-06-21 12:01:36', 'male'),
(182, 'مصطفى شمشام', 'mostapha1@gmail.com', '$2y$10$J5ccBiK9ZskME48tCuUgfuF0oeOR4doEIwSR8U3j/IFvQ6.16m7nO', 'default_male.png', 'student', '2026-06-22 17:24:47', 'male'),
(183, 'Ilham1', 'ilhameaamoum@gmail.com', '$2y$10$nP.jNrnfUbAm89EZ9T8JBOQKihC1d7XIYK6V.NnSll.D8DEt9ZYbG', 'default_female.png', 'student', '2026-06-23 22:21:51', 'female'),
(184, 'irizi1234555555', 'iriziirizi13gf@gmail.com', '$2y$10$Dpp2L0yXo9a4Dp7xZcITnOuXwJkgMVlQsHSO4M8lkrN3tNhkdvSyS', 'default_male.png', 'student', '2026-06-28 14:33:33', 'male'),
(185, 'Siham', 'siham@mohadaraty.com', '$2y$10$3jB4A9WlzIVjBeAv5Upvlu9djGAbvll7k4r/YdPDL6cXVlji2EKwC', 'default_female.png', 'student', '2026-07-02 12:11:49', 'female'),
(186, 'ilhamilham', 'ilhamilham@gmail.com', '$2y$10$HVCGmePnENKYSYCUyNpxLueEGp2ZW557WgurbV4Bi/vob3JHBR13.', 'default_female.png', 'student', '2026-07-03 17:01:20', 'female'),
(187, 'nabil', 'nabil@nabil.com', '$2y$10$WP.PadBWQt7XMtxjDAKlAeID3VzY97GR5y7fMjdAzNkSkhjUcq4dy', 'default_male.png', 'student', '2026-07-04 00:23:30', 'male'),
(188, 'صمت  يضج', 'abderrahimbaha39@gmail.com', '$2y$10$ghgMbk2.JrMRcbLY6Fp1aejP/wBZj.ZPTW2SXYvTxwAKxg3SP0qay', 'default_male.png', 'student', '2026-07-05 13:06:31', 'male'),
(189, 'Nisrine', 'vhg394897@gmail.com', '$2y$10$2MZLrFxOSqdmv0IuT4psf.HIWEd9pE1stzZ/q7eJVLWFokKrvOkoC', 'user_6a4a669abbf864.18239210.jpg', 'student', '2026-07-05 14:13:47', 'female'),
(190, 'bouraadc5@.gmail.com', 'bouraadac5@gmail.com', '$2y$10$YT.5sG6ut44MPqz/WmuDbuLjJ34HAnIC1jTdOglF2ziG3dFc78gti', 'default_female.png', 'student', '2026-07-05 14:48:24', 'female'),
(191, 'youness chtaibi', 'younesschtaibi7@gmail.com', '$2y$10$ikw5KdF09EU2NDmK78Fv3eFeVSywTeYjdw90b1vGySv/nnoWw3rhO', 'default_male.png', 'student', '2026-07-05 15:56:42', 'male'),
(192, 'HAMOUNA', 'hamounamoustahi93@gmail.com', '$2y$10$xHp5lg/g4HMmwXX8y8HtV.EtxUXSuy67RcDQw/VUwACAFn7XQz2dC', 'default_female.png', 'student', '2026-07-06 11:47:31', 'female'),
(193, 'GeoGeo', 'flshgeo01@gmail.com', '$2y$10$FaeccqXAub.iFCEtB1gXpuTQH/Ypu.Dp52Zh3gs35Th3yUMHH4zoG', 'default_male.png', 'student', '2026-07-06 18:01:40', 'male'),
(194, 'عادل', 'ajamroudi@gmail.com', '$2y$10$JSnK2FqKSMM446EAbGx3s./ym3gqvRqyIpYpdfEwRUYJwu0RoTHuW', 'default_male.png', 'student', '2026-07-11 09:15:22', 'male');

-- --------------------------------------------------------

--
-- بنية الجدول `visitor_messages`
--

DROP TABLE IF EXISTS `visitor_messages`;
CREATE TABLE `visitor_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `whatsapp_groups`
--

DROP TABLE IF EXISTS `whatsapp_groups`;
CREATE TABLE `whatsapp_groups` (
  `link_id` int(11) NOT NULL,
  `offering_id` int(11) DEFAULT NULL,
  `group_link` varchar(255) NOT NULL,
  `group_name` varchar(255) DEFAULT NULL,
  `added_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year_id` int(11) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `whatsapp_groups`
--

INSERT INTO `whatsapp_groups` (`link_id`, `offering_id`, `group_link`, `group_name`, `added_by_user_id`, `created_at`, `academic_year_id`, `division_id`, `class_id`, `group_id`, `subject_id`) VALUES
(13, 28, 'https://chat.whatsapp.com/GSV6Nxw49oKFLzkPW1XwZD?mode=ems_copy_t', '55555', NULL, '2026-03-24 14:30:31', NULL, NULL, NULL, NULL, NULL),
(16, NULL, 'https://chat.whatsapp.com/JdclAoRiyJGGMSJYNOXDEc', 'مجموعة : منصة محاضراتي Mo7adaraty', NULL, '2026-07-06 23:23:18', 1, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_messages`
--
ALTER TABLE `admin_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_comment_user` (`user_id`),
  ADD KEY `fk_comment_parent` (`parent_id`);

--
-- Indexes for table `community_items`
--
ALTER TABLE `community_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `content_reactions`
--
ALTER TABLE `content_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `user_content_reaction` (`user_id`,`content_id`,`content_type`);

--
-- Indexes for table `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD PRIMARY KEY (`offering_id`),
  ADD KEY `division_id` (`division_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `track_id` (`track_id`),
  ADD KEY `fk_offering_year` (`academic_year_id`);

--
-- Indexes for table `design_assets`
--
ALTER TABLE `design_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asset` (`page_section`,`asset_key`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`division_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `fk_exam_offering_subject` (`offering_subject_id`),
  ADD KEY `fk_exam_class` (`class_id`),
  ADD KEY `fk_exam_uploader` (`uploader_user_id`),
  ADD KEY `fk_exam_year` (`academic_year_id`),
  ADD KEY `fk_exam_creator` (`created_by`);

--
-- Indexes for table `global_announcements`
--
ALTER TABLE `global_announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `admin_user_id` (`admin_user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `independent_whatsapp_groups`
--
ALTER TABLE `independent_whatsapp_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `added_by_user_id` (`added_by_user_id`);

--
-- Indexes for table `lectures`
--
ALTER TABLE `lectures`
  ADD PRIMARY KEY (`lecture_id`),
  ADD KEY `uploader_user_id` (`uploader_user_id`),
  ADD KEY `offering_id` (`offering_id`),
  ADD KEY `lectures_ibfk_os` (`offering_subject_id`),
  ADD KEY `fk_lecture_year` (`academic_year_id`),
  ADD KEY `fk_lec_creator` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notification_user` (`user_id`);

--
-- Indexes for table `offering_subjects`
--
ALTER TABLE `offering_subjects`
  ADD PRIMARY KEY (`offering_subject_id`),
  ADD UNIQUE KEY `offering_subject_unique` (`offering_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`professor_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_user` (`user_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student_schedule` (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_timetable` (`division_id`,`class_id`,`group_id`),
  ADD KEY `fk_timetable_class` (`class_id`),
  ADD KEY `fk_timetable_group` (`group_id`);

--
-- Indexes for table `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`track_id`),
  ADD KEY `division_id` (`division_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visitor_messages`
--
ALTER TABLE `visitor_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_groups`
--
ALTER TABLE `whatsapp_groups`
  ADD PRIMARY KEY (`link_id`),
  ADD UNIQUE KEY `offering_id` (`offering_id`),
  ADD KEY `added_by_user_id` (`added_by_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `admin_messages`
--
ALTER TABLE `admin_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `community_items`
--
ALTER TABLE `community_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `content_reactions`
--
ALTER TABLE `content_reactions`
  MODIFY `reaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

--
-- AUTO_INCREMENT for table `course_offerings`
--
ALTER TABLE `course_offerings`
  MODIFY `offering_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=306;

--
-- AUTO_INCREMENT for table `design_assets`
--
ALTER TABLE `design_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=348;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrollment_change_requests`
--
ALTER TABLE `enrollment_change_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `global_announcements`
--
ALTER TABLE `global_announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `independent_whatsapp_groups`
--
ALTER TABLE `independent_whatsapp_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lectures`
--
ALTER TABLE `lectures`
  MODIFY `lecture_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2159;

--
-- AUTO_INCREMENT for table `offering_subjects`
--
ALTER TABLE `offering_subjects`
  MODIFY `offering_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1634;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `professors`
--
ALTER TABLE `professors`
  MODIFY `professor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=766;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_enrollments`
--

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=863;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tracks`
--
ALTER TABLE `tracks`
  MODIFY `track_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT for table `visitor_messages`
--
ALTER TABLE `visitor_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `whatsapp_groups`
--
ALTER TABLE `whatsapp_groups`
  MODIFY `link_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- قيود الجداول المحفوظة
--

--
-- القيود للجدول `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- القيود للجدول `content_reactions`
--
ALTER TABLE `content_reactions`
  ADD CONSTRAINT `fk_reaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- القيود للجدول `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD CONSTRAINT `fk_co_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_co_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`division_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_co_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_co_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offering_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- القيود للجدول `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `fk_exam_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_exam_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- القيود للجدول `lectures`
--
ALTER TABLE `lectures`
  ADD CONSTRAINT `fk_lec_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lecture_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- القيود للجدول `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- القيود للجدول `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_student_schedule` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- القيود للجدول `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `fk_timetable_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_timetable_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`division_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_timetable_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
