CREATE TABLE IF NOT EXISTS `posts` (
                                       `id` INT AUTO_INCREMENT PRIMARY KEY,
                                       `user_id` INT NOT NULL,
                                       `content` TEXT NOT NULL,
                                       `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                       `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                                       `last_activity_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                       FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_images` (
                                             `id` INT AUTO_INCREMENT PRIMARY KEY,
                                             `post_id` INT NOT NULL,
                                             `image_path` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_likes` (
                                            `user_id` INT NOT NULL,
                                            `post_id` INT NOT NULL,
                                            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                            PRIMARY KEY (`user_id`, `post_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comments` (
                                          `id` INT AUTO_INCREMENT PRIMARY KEY,
                                          `post_id` INT NOT NULL,
                                          `user_id` INT NOT NULL,
                                          `content` TEXT NOT NULL,
                                          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                          FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;