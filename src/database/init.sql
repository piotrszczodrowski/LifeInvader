DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `post_likes`;
DROP TABLE IF EXISTS `post_images`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
                         `id` INT AUTO_INCREMENT PRIMARY KEY,
                         `username` VARCHAR(50) NOT NULL UNIQUE,
                         `email` VARCHAR(100) NOT NULL UNIQUE,
                         `password_hash` VARCHAR(255) NOT NULL,
                         `role` ENUM('user', 'admin') DEFAULT 'user',
                         `theme_preference` VARCHAR(20) DEFAULT 'light',
                         `must_change_password` TINYINT(1) DEFAULT 0,
                         `bio` VARCHAR(255) NULL,
                         `avatar_path` VARCHAR(255) DEFAULT '/uploads/avatars/default.png',
                         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
                                       `id` INT AUTO_INCREMENT PRIMARY KEY,
                                       `user_id` INT NOT NULL,
                                       `content` TEXT NOT NULL,
                                       `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                       `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                                       `last_activity_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                       FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_images` (
                                             `id` INT AUTO_INCREMENT PRIMARY KEY,
                                             `post_id` INT NOT NULL,
                                             `image_path` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_likes` (
                                            `user_id` INT NOT NULL,
                                            `post_id` INT NOT NULL,
                                            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                            PRIMARY KEY (`user_id`, `post_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comments` (
                                          `id` INT AUTO_INCREMENT PRIMARY KEY,
                                          `post_id` INT NOT NULL,
                                          `user_id` INT NOT NULL,
                                          `content` TEXT NOT NULL,
                                          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                          FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          sender_id INT NOT NULL,
                          receiver_id INT NOT NULL,
                          content TEXT NOT NULL,
                          is_read TINYINT(1) DEFAULT 0,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                          FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);