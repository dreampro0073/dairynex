ALTER TABLE `users` ADD `check_password` TEXT NULL DEFAULT NULL AFTER `password`;

ALTER TABLE `customers`
ADD COLUMN `rate_type` VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER `customer_type`;
