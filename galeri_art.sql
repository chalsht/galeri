-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 31, 2025 at 06:06 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `galeri_art`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `IncrementArtworkView` (IN `artwork_id` INT, IN `user_id` INT, IN `ip_addr` VARCHAR(45), IN `user_agent_str` TEXT)   BEGIN
    DECLARE view_exists INT DEFAULT 0;
    
    -- Check if view already exists from same IP in last hour
    SELECT COUNT(*) INTO view_exists
    FROM artwork_views 
    WHERE artwork_id = artwork_id 
    AND ip_address = ip_addr 
    AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
    
    -- Only increment if no recent view from same IP
    IF view_exists = 0 THEN
        -- Insert view record
        INSERT INTO artwork_views (artwork_id, user_id, ip_address, user_agent, viewed_at)
        VALUES (artwork_id, user_id, ip_addr, user_agent_str, NOW());
        
        -- Update artwork view count
        UPDATE artworks SET views = views + 1 WHERE id = artwork_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ToggleArtworkLike` (IN `artwork_id` INT, IN `user_id` INT)   BEGIN
    DECLARE like_exists INT DEFAULT 0;
    
    -- Check if like already exists
    SELECT COUNT(*) INTO like_exists
    FROM likes 
    WHERE artwork_id = artwork_id AND user_id = user_id;
    
    IF like_exists > 0 THEN
        -- Remove like
        DELETE FROM likes WHERE artwork_id = artwork_id AND user_id = user_id;
        UPDATE artworks SET likes = likes - 1 WHERE id = artwork_id;
        SELECT 'unliked' as action;
    ELSE
        -- Add like
        INSERT INTO likes (artwork_id, user_id) VALUES (artwork_id, user_id);
        UPDATE artworks SET likes = likes + 1 WHERE id = artwork_id;
        SELECT 'liked' as action;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `artworks`
--

CREATE TABLE `artworks` (
  `id` int NOT NULL,
  `judul` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `views` int DEFAULT '0',
  `likes` int DEFAULT '0',
  `featured` tinyint(1) DEFAULT '0',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `artworks`
--

INSERT INTO `artworks` (`id`, `judul`, `deskripsi`, `image_path`, `thumbnail_path`, `user_id`, `category_id`, `views`, `likes`, `featured`, `status`, `created_at`, `updated_at`) VALUES
(1, 'The Starry Night', 'A swirling night sky over a French village, painted in 1889. One of the most recognizable pieces in the history of Western culture.', 'uploads/starry_night.jpg', NULL, 2, 1, 1250, 89, 1, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(2, 'Moonrise, Hernandez', 'A photograph of a moonrise over the village of Hernandez, New Mexico, taken on November 1, 1941.', 'uploads/moonrise_hernandez.jpg', NULL, 3, 3, 892, 67, 1, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(3, 'Les Demoiselles d\'Avignon', 'A large oil painting created in 1907, considered a seminal work in the development of both Cubism and modern art.', 'uploads/demoiselles.jpg', NULL, 4, 1, 1034, 78, 0, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(4, 'Digital Dreams', 'An abstract digital composition exploring the intersection of technology and imagination.', 'uploads/digital_dreams.jpg', NULL, 2, 2, 445, 32, 0, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(5, 'Urban Geometry', 'A photographic study of architectural forms and shadows in the modern city.', 'uploads/urban_geometry.jpg', NULL, 3, 3, 623, 41, 0, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(6, 'Color Study #1', 'An exploration of color relationships and emotional resonance through abstract forms.', 'uploads/color_study.jpg', NULL, 4, 5, 334, 28, 0, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58');

-- --------------------------------------------------------

--
-- Stand-in structure for view `artwork_stats_by_category`
-- (See below for the actual view)
--
CREATE TABLE `artwork_stats_by_category` (
`nama_kategori` varchar(50)
,`total_karya` bigint
,`avg_views` decimal(14,4)
,`avg_likes` decimal(14,4)
,`total_views` decimal(32,0)
,`total_likes` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `artwork_views`
--

CREATE TABLE `artwork_views` (
  `id` int NOT NULL,
  `artwork_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `viewed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `nama_kategori` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `nama_kategori`, `deskripsi`, `icon`, `created_at`) VALUES
(1, 'painting', 'Lukisan tradisional dan modern', '🎨', '2025-08-31 06:03:58'),
(2, 'digital', 'Karya seni digital dan ilustrasi', '💻', '2025-08-31 06:03:58'),
(3, 'photography', 'Fotografi artistik dan dokumenter', '📷', '2025-08-31 06:03:58'),
(4, 'sculpture', 'Patung dan karya tiga dimensi', '🗿', '2025-08-31 06:03:58'),
(5, 'abstract', 'Seni abstrak dan eksperimental', '🌀', '2025-08-31 06:03:58'),
(6, 'portrait', 'Potret dan karya figuratif', '👤', '2025-08-31 06:03:58');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `artwork_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `status` enum('approved','pending','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `artwork_id`, `user_id`, `comment`, `parent_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'Absolutely breathtaking! The swirling sky captures such emotion and movement.', NULL, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(2, 1, 3, 'Van Gogh\'s use of color here is revolutionary. A true masterpiece.', NULL, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(3, 2, 2, 'The composition and timing of this photograph is perfect. Ansel Adams was a master.', NULL, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(4, 3, 5, 'Picasso\'s boldness in breaking from traditional representation changed art forever.', NULL, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(5, 4, 3, 'Beautiful digital work! The blend of colors creates such a dreamlike quality.', NULL, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58'),
(6, 5, 4, 'Love the geometric patterns and play of light and shadow.', NULL, 'approved', '2025-08-31 06:03:58', '2025-08-31 06:03:58');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `artwork_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `artwork_id`, `created_at`) VALUES
(1, 5, 1, '2025-08-31 06:03:58'),
(2, 5, 2, '2025-08-31 06:03:58'),
(3, 5, 3, '2025-08-31 06:03:58'),
(4, 2, 4, '2025-08-31 06:03:58'),
(5, 2, 6, '2025-08-31 06:03:58'),
(6, 3, 1, '2025-08-31 06:03:58'),
(7, 3, 5, '2025-08-31 06:03:58'),
(8, 4, 2, '2025-08-31 06:03:58'),
(9, 4, 3, '2025-08-31 06:03:58'),
(10, 4, 5, '2025-08-31 06:03:58');

-- --------------------------------------------------------

--
-- Stand-in structure for view `popular_artists`
-- (See below for the actual view)
--
CREATE TABLE `popular_artists` (
`id` int
,`nama` varchar(100)
,`email` varchar(150)
,`total_karya` bigint
,`total_views` decimal(32,0)
,`total_likes` decimal(32,0)
,`avg_views_per_artwork` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `popular_artworks`
-- (See below for the actual view)
--
CREATE TABLE `popular_artworks` (
`id` int
,`judul` varchar(200)
,`deskripsi` text
,`image_path` varchar(255)
,`views` int
,`likes` int
,`featured` tinyint(1)
,`created_at` timestamp
,`artist_name` varchar(100)
,`nama_kategori` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int NOT NULL,
  `artwork_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `visitor_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` int NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_sessions`
--

CREATE TABLE `visitor_sessions` (
  `id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age_verified` tinyint(1) DEFAULT '0',
  `age_category` enum('under_17','17_plus') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('seniman','pengunjung','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pengunjung',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `tanggal_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','banned') COLLATE utf8mb4_unicode_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `profile_image`, `bio`, `tanggal_daftar`, `last_login`, `status`) VALUES
(1, 'Administrator', 'admin@galeriari.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, '2025-08-31 06:03:58', NULL, 'active'),
(2, 'Vincent van Gogh', 'vincent@galeriari.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seniman', NULL, 'Post-Impressionist painter known for bold colors and dramatic brushwork.', '2025-08-31 06:03:58', NULL, 'active'),
(3, 'Ansel Adams', 'ansel@galeriari.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seniman', NULL, 'Landscape photographer and environmentalist known for black-and-white images.', '2025-08-31 06:03:58', NULL, 'active'),
(4, 'Pablo Picasso', 'pablo@galeriari.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seniman', NULL, 'Spanish painter, sculptor, printmaker, ceramicist and theatre designer.', '2025-08-31 06:03:58', NULL, 'active'),
(5, 'Art Lover', 'lover@galeriari.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pengunjung', NULL, 'Passionate art enthusiast and collector.', '2025-08-31 06:03:58', NULL, 'active');

-- --------------------------------------------------------

--
-- Structure for view `artwork_stats_by_category`
--
DROP TABLE IF EXISTS `artwork_stats_by_category`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `artwork_stats_by_category`  AS SELECT `c`.`nama_kategori` AS `nama_kategori`, count(`a`.`id`) AS `total_karya`, avg(`a`.`views`) AS `avg_views`, avg(`a`.`likes`) AS `avg_likes`, sum(`a`.`views`) AS `total_views`, sum(`a`.`likes`) AS `total_likes` FROM (`categories` `c` left join `artworks` `a` on(((`c`.`id` = `a`.`category_id`) and (`a`.`status` = 'approved')))) GROUP BY `c`.`id`, `c`.`nama_kategori``nama_kategori`  ;

-- --------------------------------------------------------

--
-- Structure for view `popular_artists`
--
DROP TABLE IF EXISTS `popular_artists`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `popular_artists`  AS SELECT `u`.`id` AS `id`, `u`.`nama` AS `nama`, `u`.`email` AS `email`, count(`a`.`id`) AS `total_karya`, sum(`a`.`views`) AS `total_views`, sum(`a`.`likes`) AS `total_likes`, avg(`a`.`views`) AS `avg_views_per_artwork` FROM (`users` `u` join `artworks` `a` on((`u`.`id` = `a`.`user_id`))) WHERE ((`u`.`role` = 'seniman') AND (`a`.`status` = 'approved')) GROUP BY `u`.`id`, `u`.`nama`, `u`.`email` ORDER BY `total_views` AS `DESCdesc` ASC  ;

-- --------------------------------------------------------

--
-- Structure for view `popular_artworks`
--
DROP TABLE IF EXISTS `popular_artworks`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `popular_artworks`  AS SELECT `a`.`id` AS `id`, `a`.`judul` AS `judul`, `a`.`deskripsi` AS `deskripsi`, `a`.`image_path` AS `image_path`, `a`.`views` AS `views`, `a`.`likes` AS `likes`, `a`.`featured` AS `featured`, `a`.`created_at` AS `created_at`, `u`.`nama` AS `artist_name`, `c`.`nama_kategori` AS `nama_kategori` FROM ((`artworks` `a` join `users` `u` on((`a`.`user_id` = `u`.`id`))) join `categories` `c` on((`a`.`category_id` = `c`.`id`))) WHERE (`a`.`status` = 'approved') ORDER BY ((`a`.`views` * 0.3) + (`a`.`likes` * 0.7)) AS `DESCdesc` ASC  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artworks`
--
ALTER TABLE `artworks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_artworks_search` (`status`,`featured`,`created_at`),
  ADD KEY `idx_artworks_user_category` (`user_id`,`category_id`,`status`);
ALTER TABLE `artworks` ADD FULLTEXT KEY `idx_search` (`judul`,`deskripsi`);

--
-- Indexes for table `artwork_views`
--
ALTER TABLE `artwork_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_artwork_id` (`artwork_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_viewed_at` (`viewed_at`),
  ADD KEY `idx_ip_address` (`ip_address`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`),
  ADD KEY `idx_nama_kategori` (`nama_kategori`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_artwork_id` (`artwork_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_comments_artwork_status` (`artwork_id`,`status`,`created_at`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_artwork` (`user_id`,`artwork_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_artwork_id` (`artwork_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_artwork_id` (`artwork_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_visitor_ip` (`visitor_ip`),
  ADD KEY `idx_rating` (`rating`),
  ADD UNIQUE KEY `unique_user_artwork_rating` (`user_id`,`artwork_id`),
  ADD UNIQUE KEY `unique_visitor_artwork_rating` (`visitor_ip`,`artwork_id`);

--
-- Indexes for table `visitor_sessions`
--
ALTER TABLE `visitor_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_age_verified` (`age_verified`),
  ADD KEY `idx_age_category` (`age_category`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artworks`
--
ALTER TABLE `artworks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `artwork_views`
--
ALTER TABLE `artwork_views`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_sessions`
--
ALTER TABLE `visitor_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `artworks`
--
ALTER TABLE `artworks`
  ADD CONSTRAINT `artworks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `artworks_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `artwork_views`
--
ALTER TABLE `artwork_views`
  ADD CONSTRAINT `artwork_views_ibfk_1` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `artwork_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
