-- MySQL equivalent of 2026_11_13_000100_create_offers_table::up().
-- Run once after all earlier migrations using a SQL client that stops on errors.
-- MySQL DDL is not transactional. The insert records the successful migration.

CREATE TABLE `offers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `restaurant_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `discount_type` VARCHAR(20) NOT NULL,
    `discount_value` DECIMAL(10, 2) NOT NULL,
    `starts_at` DATETIME NULL,
    `ends_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT '1',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';

ALTER TABLE `offers`
    ADD CONSTRAINT `offers_restaurant_id_foreign`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;
ALTER TABLE `offers` ADD INDEX `offers_restaurant_id_is_active_index` (`restaurant_id`, `is_active`);

SET @menu_migration_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_13_000100_create_offers_table', @menu_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_13_000100_create_offers_table'
);
