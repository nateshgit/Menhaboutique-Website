-- Add home_reviews table for customer testimonials displayed on homepage
CREATE TABLE IF NOT EXISTS `home_reviews` (
    `id` VARCHAR(36) PRIMARY KEY,
    `reviewer_name` VARCHAR(255) NOT NULL,
    `review_text` TEXT DEFAULT NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `media_url` VARCHAR(500) DEFAULT NULL,
    `media_type` VARCHAR(10) NOT NULL DEFAULT 'image',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sequence` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
