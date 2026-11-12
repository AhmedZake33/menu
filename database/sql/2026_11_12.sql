-- MySQL equivalent of the up() methods in the 2026_11_12 migrations.
-- Run once on the application's database after all earlier migrations.
-- Use a SQL client configured to stop on errors; MySQL DDL is not transactional.
-- The final inserts record both migrations so Laravel will not run them again.

-- 2026_11_12_000100_add_client_modules_and_cashier
ALTER TABLE `restaurants` ADD `modules` JSON NULL;
ALTER TABLE `restaurants` ADD `settings` JSON NULL;

ALTER TABLE `menu_orders` ADD `source` VARCHAR(20) NOT NULL DEFAULT 'menu';
ALTER TABLE `menu_orders` ADD `order_type` VARCHAR(20) NULL;
ALTER TABLE `menu_orders` ADD `discount` DECIMAL(10, 2) NOT NULL DEFAULT '0';
ALTER TABLE `menu_orders` ADD `service` DECIMAL(10, 2) NOT NULL DEFAULT '0';
ALTER TABLE `menu_orders` ADD `tax` DECIMAL(10, 2) NOT NULL DEFAULT '0';
ALTER TABLE `menu_orders` ADD `payment_status` VARCHAR(20) NOT NULL DEFAULT 'unpaid';
ALTER TABLE `menu_orders` ADD `payment_method` VARCHAR(20) NULL;
ALTER TABLE `menu_orders` ADD `paid_at` TIMESTAMP NULL;
ALTER TABLE `menu_orders` ADD `cashier_id` BIGINT UNSIGNED NULL;
ALTER TABLE `menu_orders`
    ADD CONSTRAINT `menu_orders_cashier_id_foreign`
    FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `menu_orders` ADD INDEX `menu_orders_source_index` (`source`);
ALTER TABLE `menu_orders` ADD INDEX `menu_orders_payment_status_index` (`payment_status`);
ALTER TABLE `menu_orders` ADD INDEX `menu_orders_paid_at_index` (`paid_at`);
ALTER TABLE `menu_orders` MODIFY `customer_email` VARCHAR(255) NULL;

-- 2026_11_12_000200_create_reservations_table
CREATE TABLE `reservations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `restaurant_id` BIGINT UNSIGNED NOT NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(30) NOT NULL,
    `guests` SMALLINT UNSIGNED NOT NULL,
    `reserved_at` DATETIME NOT NULL,
    `notes` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';

ALTER TABLE `reservations`
    ADD CONSTRAINT `reservations_restaurant_id_foreign`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;
ALTER TABLE `reservations`
    ADD INDEX `reservations_restaurant_id_reserved_at_index` (`restaurant_id`, `reserved_at`);
ALTER TABLE `reservations` ADD INDEX `reservations_status_index` (`status`);

-- Record the successfully applied migrations in one Laravel batch.
SET @menu_migration_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_12_000100_add_client_modules_and_cashier', @menu_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_12_000100_add_client_modules_and_cashier'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_12_000200_create_reservations_table', @menu_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_12_000200_create_reservations_table'
);
