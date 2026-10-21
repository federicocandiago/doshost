-- Rate limits (App\Core\Throttle): attempts per hashed key in a time window. No IP addresses.
CREATE TABLE IF NOT EXISTS `throttle` (
  `key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hits` int unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`key`),
  KEY `idx_throttle_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
