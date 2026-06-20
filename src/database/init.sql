SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- 1. Struktura tabeli `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` int NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `role` enum('user','admin') DEFAULT 'user',
    `theme_preference` varchar(20) DEFAULT 'light',
    `must_change_password` tinyint(1) DEFAULT '0',
    `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    `bio` varchar(255) DEFAULT NULL,
    `avatar_path` varchar(255) DEFAULT '/uploads/avatars/default.png',
    `birth_date` date DEFAULT NULL,
    `project_evaluation` varchar(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- 2. Struktura tabeli `posts`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `last_activity_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Struktura tabeli `audit_log`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int DEFAULT NULL,
    `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Struktura tabeli `comments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
    `id` int NOT NULL AUTO_INCREMENT,
    `post_id` int NOT NULL,
    `user_id` int NOT NULL,
    `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `post_id` (`post_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Struktura tabeli `messages`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
    `id` int NOT NULL AUTO_INCREMENT,
    `sender_id` int NOT NULL,
    `receiver_id` int NOT NULL,
    `content` text NOT NULL,
    `is_read` tinyint(1) DEFAULT '0',
    `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sender_id` (`sender_id`),
    KEY `receiver_id` (`receiver_id`),
    CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- 6. Struktura tabeli `post_images`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_images` (
    `id` int NOT NULL AUTO_INCREMENT,
    `post_id` int NOT NULL,
    `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    KEY `post_id` (`post_id`),
    CONSTRAINT `post_images_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Struktura tabeli `post_likes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_likes` (
    `user_id` int NOT NULL,
    `post_id` int NOT NULL,
    `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`,`post_id`),
    KEY `post_id` (`post_id`),
    CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;