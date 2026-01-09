-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.25-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.5.0.6677
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping data for table dealdulu_cms.failed_jobs: ~0 rows (approximately)

-- Dumping data for table dealdulu_cms.jobs: ~0 rows (approximately)

-- Dumping data for table dealdulu_cms.migrations: ~6 rows (approximately)
REPLACE INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '2014_10_12_000000_create_users_table', 1),
	(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
	(3, '2019_08_19_000000_create_failed_jobs_table', 1),
	(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
	(5, '2023_03_16_100403_create_permission_tables', 1),
	(6, '2023_03_21_151254_create_jobs_table', 1);

-- Dumping data for table dealdulu_cms.model_has_permissions: ~0 rows (approximately)

-- Dumping data for table dealdulu_cms.model_has_roles: ~0 rows (approximately)
REPLACE INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
	(1, 'App\\Models\\User', 1);

-- Dumping data for table dealdulu_cms.password_reset_tokens: ~0 rows (approximately)

-- Dumping data for table dealdulu_cms.permissions: ~69 rows (approximately)
REPLACE INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'user_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(2, 'user_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(3, 'user_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(4, 'user_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(5, 'role_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(6, 'role_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(7, 'role_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(8, 'role_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(9, 'permission_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(10, 'permission_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(11, 'permission_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(12, 'permission_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(13, 'product_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(14, 'product_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(15, 'product_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(16, 'product_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(17, 'category_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(18, 'category_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(19, 'category_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(20, 'category_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(21, 'history-payment_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(22, 'history-payment_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(23, 'history-payment_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(24, 'history-payment_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(25, 'app-live-streaming_list', 'web', '2023-03-30 08:23:37', '2023-04-01 05:56:53'),
	(29, 'app-stories_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(30, 'app-stories_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(31, 'app-stories_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(32, 'app-stories_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(33, 'app-notification_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(34, 'app-notification_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(35, 'app-notification_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(36, 'app-notification_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(37, 'app-banner_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(38, 'app-banner_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(39, 'app-banner_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(40, 'app-banner_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(41, 'web-content_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(42, 'web-content_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(43, 'web-content_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(44, 'web-content_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(45, 'web-banner_list', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(46, 'web-banner_create', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(47, 'web-banner_edit', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(48, 'web-banner_delete', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37'),
	(49, 'app-user_create', 'web', '2023-03-30 08:29:06', '2023-03-30 08:29:06'),
	(50, 'app-user_list', 'web', '2023-03-30 08:29:06', '2023-03-30 08:29:06'),
	(51, 'app-user_edit', 'web', '2023-03-30 08:29:06', '2023-03-30 08:29:06'),
	(52, 'app-user_delete', 'web', '2023-03-30 08:29:06', '2023-03-30 08:29:06'),
	(53, 'app-user_show', 'web', '2023-03-30 09:43:46', '2023-03-30 09:49:19'),
	(55, 'app-user-banned_create', 'web', '2023-03-31 21:49:45', '2023-03-31 21:49:45'),
	(56, 'app-user-banned_list', 'web', '2023-03-31 21:49:45', '2023-03-31 21:49:45'),
	(57, 'app-user-banned_edit', 'web', '2023-03-31 21:49:45', '2023-03-31 21:49:45'),
	(58, 'app-user-banned_delete', 'web', '2023-03-31 21:49:45', '2023-03-31 21:49:45'),
	(59, 'app-user-banned_show', 'web', '2023-03-31 21:50:13', '2023-03-31 21:50:13'),
	(60, 'app-live-streaming_show', 'web', '2023-04-01 06:35:32', '2023-04-01 06:35:32'),
	(61, 'app-live-streaming_live', 'web', '2023-04-10 07:14:17', '2023-04-10 07:14:17'),
	(62, 'app-live-streaming_banned', 'web', '2023-04-10 07:17:59', '2023-04-10 07:17:59'),
	(64, 'app-bid_list', 'web', '2023-04-18 10:23:41', '2023-04-18 10:23:41'),
	(66, 'app-bid_delete', 'web', '2023-04-18 10:23:41', '2023-04-18 10:23:41'),
	(67, 'app-bid_show', 'web', '2023-04-18 10:33:31', '2023-04-18 10:33:31'),
	(68, 'app-payment-histories_create', 'web', '2023-05-02 08:33:14', '2023-05-02 08:33:14'),
	(69, 'app-payment-histories_list', 'web', '2023-05-02 08:33:14', '2023-05-02 08:33:14'),
	(70, 'app-payment-histories_edit', 'web', '2023-05-02 08:33:14', '2023-05-02 08:33:14'),
	(72, 'app-user_complaints_create', 'web', '2023-05-02 08:33:26', '2023-05-02 08:33:26'),
	(73, 'app-user_complaints_list', 'web', '2023-05-02 08:33:26', '2023-05-02 08:33:26'),
	(74, 'app-user_complaints_edit', 'web', '2023-05-02 08:33:26', '2023-05-02 08:33:26'),
	(75, 'app-user_complaints_delete', 'web', '2023-05-02 08:33:26', '2023-05-02 08:33:26'),
	(76, 'app-payment-histories_show', 'web', '2023-05-04 02:17:29', '2023-05-04 02:17:29'),
	(77, 'product_show', 'web', '2023-05-09 07:13:20', '2023-05-09 07:13:20');

-- Dumping data for table dealdulu_cms.personal_access_tokens: ~0 rows (approximately)

-- Dumping data for table dealdulu_cms.roles: ~0 rows (approximately)
REPLACE INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'super-admin', 'web', '2023-03-30 08:23:37', '2023-03-30 08:23:37');

-- Dumping data for table dealdulu_cms.role_has_permissions: ~70 rows (approximately)
REPLACE INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
	(1, 1),
	(2, 1),
	(3, 1),
	(4, 1),
	(5, 1),
	(6, 1),
	(7, 1),
	(8, 1),
	(9, 1),
	(10, 1),
	(11, 1),
	(12, 1),
	(13, 1),
	(14, 1),
	(15, 1),
	(16, 1),
	(17, 1),
	(18, 1),
	(19, 1),
	(20, 1),
	(21, 1),
	(22, 1),
	(23, 1),
	(24, 1),
	(25, 1),
	(29, 1),
	(30, 1),
	(31, 1),
	(32, 1),
	(33, 1),
	(34, 1),
	(35, 1),
	(36, 1),
	(37, 1),
	(38, 1),
	(39, 1),
	(40, 1),
	(41, 1),
	(42, 1),
	(43, 1),
	(44, 1),
	(45, 1),
	(46, 1),
	(47, 1),
	(48, 1),
	(49, 1),
	(50, 1),
	(51, 1),
	(52, 1),
	(53, 1),
	(55, 1),
	(56, 1),
	(57, 1),
	(58, 1),
	(59, 1),
	(60, 1),
	(61, 1),
	(62, 1),
	(64, 1),
	(66, 1),
	(67, 1),
	(68, 1),
	(69, 1),
	(70, 1),
	(72, 1),
	(73, 1),
	(74, 1),
	(75, 1),
	(76, 1),
	(77, 1);

-- Dumping data for table dealdulu_cms.users: ~0 rows (approximately)
REPLACE INTO `users` (`id`, `fullname`, `email`, `role`, `last_login`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
	(1, 'Super Admin', 'admin@admin.com', 'super-admin', '2023-05-10 06:54:24', '2023-03-30 15:27:09', '$2y$10$ttewNa6ky/B4r8qTEtyOD.ef/SoerEcyDHMPqfGSwir6f084/m3QC', NULL, '2023-03-30 08:23:37', '2023-05-10 06:54:24');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
