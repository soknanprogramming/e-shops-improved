-- Create Database with Khmer support
CREATE DATABASE khmer24_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE khmer24_db;

-- 1. Table: User
CREATE TABLE `User` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(50) NULL,
    `password` VARCHAR(255) NULL,
    `provider` VARCHAR(200) NULL,
    `provider_id` VARCHAR(200) NULL,
    `avatar` BOOLEAN NOT NULL,
    `is_admin` BOOLEAN NOT NULL,
    `can_post` BOOLEAN DEFAULT 0,
    `request_post_permission` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Table: gender
CREATE TABLE `gender` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(10) NOT NULL -- e.g., ប្រុស, ស្រី
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3. Table: user_profile
CREATE TABLE `user_profile` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `gender_id` INT NULL,
    `bio` VARCHAR(200) NULL,
    `user_image` VARCHAR(200) NULL,
    `background_image` VARCHAR(200) NULL,
    `phone1` VARCHAR(15) NOT NULL,
    `phone2` VARCHAR(15) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`id`),
    FOREIGN KEY (`gender_id`) REFERENCES `gender`(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 4. Table: product_image
CREATE TABLE `product_image` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `main_image` VARCHAR(200) NOT NULL,
    `image1` VARCHAR(200) NULL,
    `image2` VARCHAR(200) NULL,
    `image3` VARCHAR(200) NULL,
    `image4` VARCHAR(200) NULL,
    `image5` VARCHAR(200) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 5. Table: category
CREATE TABLE `category` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL, -- e.g., គ្រឿងអេឡិចត្រូនិច
    `category_image` VARCHAR(200) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 6. Table: liked
CREATE TABLE `liked` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- 7. Table: comment
CREATE TABLE `comment` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `comment` VARCHAR(200) NOT NULL, -- Supporting Khmer comments
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- 8. Table: Product
CREATE TABLE `Product` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL, -- Khmer product name
    `product_image_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `owner_id` INT NOT NULL,
    `profile_id` INT NOT NULL,
    `location` VARCHAR(200) NULL, -- e.g., ភ្នំពេញ
    `prices` DECIMAL(10, 2) NULL,
    `discounts` DECIMAL(10, 2) NULL,
    `showed` BOOLEAN NOT NULL,
    `description` TEXT NULL, -- Changed to TEXT for longer Khmer descriptions
    `liked_id` INT NOT NULL,
    `comment_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (`product_image_id`) REFERENCES `product_image`(`id`),
    FOREIGN KEY (`category_id`) REFERENCES `category`(`id`),
    FOREIGN KEY (`owner_id`) REFERENCES `User`(`id`),
    FOREIGN KEY (`profile_id`) REFERENCES `user_profile`(`id`),
    FOREIGN KEY (`liked_id`) REFERENCES `liked`(`id`),
    FOREIGN KEY (`comment_id`) REFERENCES `comment`(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 9. Table: product_likes
CREATE TABLE `product_likes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `Product`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_like` (`user_id`, `product_id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 10. Table: product_comments
CREATE TABLE `product_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `rating` TINYINT(1) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `Product`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;