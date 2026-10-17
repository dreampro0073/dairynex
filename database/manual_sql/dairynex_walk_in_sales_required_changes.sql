-- Dairynex walk-in counter sales.
-- Execute after reviewing the current schema and confirming these tables do not exist.
-- No existing delivery, invoice, payment, or product records are changed.
-- A sale is intentionally independent of subscriptions and scheduled deliveries.

CREATE TABLE IF NOT EXISTS `walk_in_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `receipt_no` VARCHAR(60) NOT NULL,
  `customer_name` VARCHAR(150) NULL,
  `customer_mobile` VARCHAR(20) NULL,
  `sold_at` DATETIME NOT NULL,
  `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` TINYINT UNSIGNED NULL COMMENT 'NULL means credit; otherwise references ref_payment_methods',
  `paid_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `due_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 completed; financial history is not silently deleted',
  `idempotency_key` CHAR(36) NOT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `walk_in_sales_receipt_unique` (`client_id`,`receipt_no`),
  UNIQUE KEY `walk_in_sales_idempotency_unique` (`client_id`,`idempotency_key`),
  KEY `walk_in_sales_customer_date` (`client_id`,`customer_id`,`sold_at`),
  KEY `walk_in_sales_date` (`client_id`,`sold_at`,`status`),
  KEY `walk_in_sales_method_date` (`client_id`,`payment_method_id`,`sold_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `walk_in_sale_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `product_name` VARCHAR(150) NOT NULL COMMENT 'Name snapshot preserves the printed receipt',
  `unit_type` TINYINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  `rate` DECIMAL(12,2) NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `walk_in_sale_items_sale` (`client_id`,`sale_id`),
  KEY `walk_in_sale_items_product` (`client_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
