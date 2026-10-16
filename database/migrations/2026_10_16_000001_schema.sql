-- ─────────────────────────────────────────────────────────────────────────────
-- DOSHost — database schema.
-- Applied automatically on the first request when the tables are missing
-- (or with: php bin/migrate.php). It can also be imported as-is with phpMyAdmin.
-- Future changes go in new files in this folder (e.g. 2026_11_01_000001_something.sql),
-- applied in name order, once each (tracked in the `migrations` table).
-- ─────────────────────────────────────────────────────────────────────────────



CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `max_instances` int unsigned DEFAULT NULL,
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keyboard_layout` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'us',
  `suspended_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `instances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cycles` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `keyboard_layout` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'us',
  `show_cursor` tinyint(1) NOT NULL DEFAULT '1',
  `start_fullscreen` tinyint(1) NOT NULL DEFAULT '0',
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0',
  `disk_bytes` bigint unsigned NOT NULL DEFAULT '0',
  `disk_full` tinyint(1) NOT NULL DEFAULT '0',
  `suspended_at` datetime DEFAULT NULL,
  `last_saved_at` datetime DEFAULT NULL,
  `last_played_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_instances_user` (`user_id`),
  KEY `idx_instances_last_played` (`last_played_at`),
  CONSTRAINT `fk_instances_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


