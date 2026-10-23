-- DairyNex database migration log
-- Append every database schema change at the bottom of this file.
-- Keep entries in chronological order and reference the Laravel migration filename.
-- Include the corresponding MySQL SQL and any data-preservation notes.
-- This file is a review/manual reference; do not execute it in addition to
-- Laravel migrations that have already applied the same changes.
--
-- 2026-10-16: Log initialized. No database migrations added or executed yet.

-- 2026-10-16: 2026_10_16_000001_add_authentication_support.php
-- Purpose: password reset token rotation and Laravel database sessions.
-- Prerequisite: existing DairyNex business schema, including users.user_type_id.
-- Run only statements for missing structures; Laravel migration checks each one.
-- Do NOT replay this section if the Laravel migration has already run.
-- Existing users, role IDs, passwords and business rows are preserved.
-- Rollback intentionally preserves these shared authentication structures.
ALTER TABLE `users` ADD COLUMN `remember_token` VARCHAR(100) NULL;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    INDEX `sessions_user_id_index` (`user_id`),
    INDEX `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2026-10-16: 2026_10_16_000002_add_delivery_pin_policies.php
-- Run each ALTER only if the column is absent (Laravel checks this).
-- Existing customers default to PIN enabled; application default for NEW customers
-- is configurable with DAIRYNEX_DELIVERY_PIN_REQUIRED=true.
-- Existing deliveries retain NULL (unknown legacy policy); never fabricate past
-- verification requirements or results. New Delivery models snapshot customer policy.
-- Rollback intentionally preserves policy history. No data is deleted/backfilled.
ALTER TABLE `customers` ADD COLUMN `delivery_pin_required` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `deliveries` ADD COLUMN `pin_required` TINYINT(1) NULL DEFAULT NULL;

-- 2026-10-16: Business structural baseline: 2026_10_15_000000_create_dairynex_business_schema.php

-- Also reconciles guarded starter users/cache/jobs migrations.

-- Only absent tables are created. No supplied business rows, passwords or role upgrades are imported.

-- Existing starter identity columns: add only missing fields; nullable roles grant no access.

-- Apply through Laravel. Raw statements are reference only, never blindly replay this log.

CREATE TABLE IF NOT EXISTS `audit_logs` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `client_id` (`client_id`, `table_name`, `record_id`, `created_at`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `clients` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `legal_name` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `status` (`status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `client_subscriptions` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `plan_id` (`plan_id`),
  KEY `client_id` (`client_id`, `status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `customers` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '1 male, 2 female, 3 other',
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `credit_limit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_mode_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `customer_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 regular, 2 walk-in, 3 both',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `supply_preference` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 morning, 2 evening, 3 both',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_customer_code` (`client_id`, `customer_code`),
  KEY `payment_mode_id` (`payment_mode_id`),
  KEY `client_id` (`client_id`, `mobile`),
  KEY `client_id_2` (`client_id`, `status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `customer_addresses` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `address_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `landmark` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_default` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `customer_id` (`customer_id`),
  KEY `client_id` (`client_id`, `customer_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `customer_ledger` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_type_id` tinyint(3) UNSIGNED NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `debit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `customer_id` (`customer_id`),
  KEY `transaction_type_id` (`transaction_type_id`),
  KEY `created_by_user_id` (`created_by_user_id`),
  KEY `client_id` (`client_id`, `customer_id`, `transaction_date`),
  KEY `client_id_2` (`client_id`, `reference_type`, `reference_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `deliveries` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `route_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_boy_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `delivery_slot_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `source_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 subscription, 2 on-hand, 3 manual',
  `status_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `delivery_pin_hash` varchar(255) DEFAULT NULL,
  `delivery_pin_verified_at` datetime DEFAULT NULL,
  `pin_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `delivered_at` datetime DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 unpaid, 2 partial, 3 paid',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `customer_id` (`customer_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `route_id` (`route_id`),
  KEY `delivery_boy_id` (`delivery_boy_id`),
  KEY `status_id` (`status_id`),
  KEY `client_id` (`client_id`, `delivery_date`, `status_id`),
  KEY `client_id_2` (`client_id`, `customer_id`, `delivery_date`),
  KEY `client_id_3` (`client_id`, `delivery_boy_id`, `delivery_date`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `delivery_items` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `planned_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `actual_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `quantity_change_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 normal, 2 increased, 3 decreased, 4 not_required',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `delivery_id` (`delivery_id`),
  KEY `product_id` (`product_id`),
  KEY `subscription_item_id` (`subscription_item_id`),
  KEY `client_id` (`client_id`, `delivery_id`),
  KEY `client_id_2` (`client_id`, `product_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `delivery_pin_attempts` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `attempted_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entered_pin_hash` varchar(255) DEFAULT NULL,
  `success` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  KEY `delivery_id` (`delivery_id`),
  KEY `attempted_by_user_id` (`attempted_by_user_id`),
  KEY `client_id` (`client_id`, `delivery_id`, `attempted_at`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `delivery_routes` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_boy_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `area_locality` varchar(255) DEFAULT NULL,
  `expected_stops` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `route_date` date DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `delivery_boy_id` (`delivery_boy_id`),
  KEY `client_id` (`client_id`, `route_date`, `delivery_boy_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `delivery_route_customers` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `route_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `sequence_no` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_route_customer` (`route_id`, `customer_id`),
  KEY `customer_id` (`customer_id`),
  KEY `client_id` (`client_id`, `route_id`, `sequence_no`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `inventory_locations` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `location_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 main, 2 van, 3 shop',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_location` (`client_id`, `name`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `inventory_transactions` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_type_id` tinyint(3) UNSIGNED NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `quantity` decimal(12,3) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `location_id` (`location_id`),
  KEY `product_id` (`product_id`),
  KEY `transaction_type_id` (`transaction_type_id`),
  KEY `created_by_user_id` (`created_by_user_id`),
  KEY `client_id` (`client_id`, `location_id`, `product_id`, `transaction_date`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `invoices` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_no` varchar(60) NOT NULL,
  `invoice_date` date NOT NULL,
  `period_from` date DEFAULT NULL,
  `period_to` date DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `due_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 draft, 2 issued, 3 partial, 4 paid, 5 cancelled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_invoice_no` (`client_id`, `invoice_no`),
  KEY `customer_id` (`customer_id`),
  KEY `client_id` (`client_id`, `customer_id`, `invoice_date`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `invoice_items` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `invoice_id` (`invoice_id`),
  KEY `delivery_id` (`delivery_id`),
  KEY `product_id` (`product_id`),
  KEY `client_id` (`client_id`, `invoice_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `invoice_payments` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_invoice_payment` (`invoice_id`, `payment_id`),
  KEY `client_id` (`client_id`),
  KEY `payment_id` (`payment_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `notifications` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `is_read` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`),
  KEY `client_id` (`client_id`, `user_id`, `is_read`, `created_at`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payments` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `payment_no` varchar(60) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` decimal(14,2) NOT NULL,
  `payment_method_id` tinyint(3) UNSIGNED NOT NULL,
  `transaction_reference` varchar(150) DEFAULT NULL,
  `collected_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_payment_no` (`client_id`, `payment_no`),
  KEY `customer_id` (`customer_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `collected_by_user_id` (`collected_by_user_id`),
  KEY `client_id` (`client_id`, `customer_id`, `payment_date`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_plans` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` decimal(14,2) NOT NULL,
  `down_payment` decimal(14,2) NOT NULL DEFAULT '0.00',
  `installment_count` int(10) UNSIGNED NOT NULL,
  `frequency_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 daily, 2 weekly, 3 monthly',
  `start_date` date NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 active, 2 completed, 3 cancelled',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `customer_id` (`customer_id`),
  KEY `client_id` (`client_id`, `customer_id`, `status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payment_plan_installments` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `installment_no` int(10) UNSIGNED NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 pending, 2 partial, 3 paid, 4 overdue',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_installment` (`payment_plan_id`, `installment_no`),
  KEY `client_id` (`client_id`, `due_date`, `status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `plans` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `monthly_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `max_customers` int(10) UNSIGNED DEFAULT NULL,
  `max_users` int(10) UNSIGNED DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `products` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `unit_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 liter, 2 ml, 3 kg, 4 gram, 5 piece, 6 packet',
  `base_rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_sku` (`client_id`, `sku`),
  KEY `category_id` (`category_id`),
  KEY `client_id` (`client_id`, `status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `product_categories` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_category` (`client_id`, `name`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `product_rates` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `rate` decimal(12,2) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `product_id` (`product_id`),
  KEY `client_id` (`client_id`, `product_id`, `effective_from`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ref_delivery_statuses` (
`id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  UNIQUE KEY `name` (`name`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ref_payment_methods` (
`id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  UNIQUE KEY `name` (`name`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ref_transaction_types` (
`id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  UNIQUE KEY `name` (`name`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ref_user_types` (
`id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  UNIQUE KEY `name` (`name`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `subscriptions` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_no` varchar(60) NOT NULL,
  `billing_cycle_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 daily, 2 weekly, 3 monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 active, 2 paused, 3 expired, 4 cancelled',
  `auto_generate_deliveries` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_subscription_no` (`client_id`, `subscription_no`),
  KEY `customer_id` (`customer_id`),
  KEY `client_id` (`client_id`, `customer_id`, `status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `subscription_change_requests` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `old_quantity` decimal(12,3) NOT NULL,
  `requested_quantity` decimal(12,3) NOT NULL,
  `effective_from` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 pending, 2 approved, 3 rejected, 4 cancelled',
  `approved_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `subscription_id` (`subscription_id`),
  KEY `requested_by_user_id` (`requested_by_user_id`),
  KEY `product_id` (`product_id`),
  KEY `approved_by_user_id` (`approved_by_user_id`),
  KEY `client_id` (`client_id`, `status`, `effective_from`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `subscription_items` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `default_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `subscription_id` (`subscription_id`),
  KEY `product_id` (`product_id`),
  KEY `client_id` (`client_id`, `subscription_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `subscription_pauses` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `pause_from` date NOT NULL,
  `pause_to` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `subscription_id` (`subscription_id`),
  KEY `client_id` (`client_id`, `subscription_id`, `pause_from`, `pause_to`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `subscription_schedules` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_item_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL COMMENT '1 Monday ... 7 Sunday',
  `delivery_slot_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 morning, 2 evening, 3 both',
  `planned_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `active_from` date NOT NULL,
  `active_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `subscription_item_id` (`subscription_item_id`),
  KEY `client_id` (`client_id`, `subscription_item_id`, `day_of_week`, `active_from`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_type_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_mobile_client` (`client_id`, `mobile`),
  KEY `user_type_id` (`user_type_id`, `status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user_roles` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `client_id` (`client_id`, `user_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- For an existing incompatible starter users table ONLY, add each field only if absent:

-- ALTER TABLE users ADD client_id BIGINT UNSIGNED NULL;

-- ALTER TABLE users ADD user_type_id TINYINT UNSIGNED NULL;

-- ALTER TABLE users ADD mobile VARCHAR(20) NULL;

-- ALTER TABLE users ADD status TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- ALTER TABLE users ADD last_login_at DATETIME NULL;

-- If email_verified_at identifies a starter schema: ALTER TABLE users MODIFY email VARCHAR(150) NULL;

-- 2026-10-16: 2026_10_16_000003_add_customer_identity_and_delivery_controls.php
-- Apply each ALTER only if the column is missing. No automatic identity matching.
-- Nullable fields preserve existing rows and unknown historical verification actors.
ALTER TABLE customers ADD user_id BIGINT UNSIGNED NULL, ADD UNIQUE KEY customers_user_login_unique (user_id);
ALTER TABLE deliveries ADD generation_key VARCHAR(100) NULL, ADD UNIQUE KEY deliveries_generation_key_unique (generation_key);
ALTER TABLE deliveries ADD pin_verified_by_user_id BIGINT UNSIGNED NULL;
ALTER TABLE deliveries ADD completed_by_user_id BIGINT UNSIGNED NULL;

-- ReferenceDataSeeder: insert missing reference values only; conflict => abort.
-- Existing manager/accountant reference rows and business users are preserved.
INSERT IGNORE INTO ref_user_types (id, name) VALUES
    (1, 'super_admin'),
    (2, 'client_admin'),
    (5, 'delivery_boy'),
    (6, 'customer');
INSERT IGNORE INTO ref_delivery_statuses (id, name) VALUES
    (1, 'pending'),
    (2, 'pin_verified'),
    (3, 'delivered'),
    (4, 'skipped'),
    (5, 'cancelled'),
    (6, 'failed'),
    (7, 'customer_unavailable'),
    (8, 'partial');
INSERT IGNORE INTO ref_payment_methods (id, name) VALUES
    (1, 'cash'),
    (2, 'upi'),
    (3, 'bank_transfer'),
    (4, 'card'),
    (5, 'online'),
    (6, 'cheque'),
    (7, 'wallet');
INSERT IGNORE INTO ref_transaction_types (id, name) VALUES
    (1, 'purchase'),
    (2, 'sale'),
    (3, 'delivery'),
    (4, 'return'),
    (5, 'wastage'),
    (6, 'adjustment'),
    (7, 'transfer'),
    (8, 'payment'),
    (9, 'refund'),
    (10, 'opening_balance'),
    (11, 'credit_note'),
    (12, 'debit_note');

-- 2026-10-16: 2026_10_16_000004_protect_ledger_references.php
-- Preflight: resolve duplicates manually; Laravel aborts if any exist, never deletes them.
-- SELECT client_id, reference_type, reference_id, COUNT(*) FROM customer_ledger
-- WHERE reference_type IS NOT NULL AND reference_id IS NOT NULL
-- GROUP BY client_id, reference_type, reference_id HAVING COUNT(*) > 1;
-- Add only if the index is absent. NULL references remain valid for legacy entries.
ALTER TABLE customer_ledger ADD UNIQUE KEY ledger_reference_unique (client_id, reference_type, reference_id);

-- Guarded starter framework table definitions (0001_01_01_000000/1/2 migrations).
-- Missing tables only. SQL baseline indexes use original names; Laravel prefixes
-- business index names with their table for SQLite portability, same columns/semantics.
CREATE TABLE IF NOT EXISTS cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
);
CREATE TABLE IF NOT EXISTS cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
);
CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX jobs_queue_index (queue)
);
CREATE TABLE IF NOT EXISTS job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids LONGTEXT NOT NULL,
    options MEDIUMTEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL
);
CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
-- password_reset_tokens and sessions definitions are in the earlier auth-support section.

-- 2026-10-16: Migration file consolidation into database/migrations/daatabase_migrations.php.
-- Includes the business baseline, authentication, PIN policy, identity/delivery
-- controls and ledger reference protection previously split across five dated files.
-- This is a code/file organization change; no additional schema SQL is required.
-- Earlier SQL sections remain historical references and are now implemented by
-- daatabase_migrations.php. Do not replay already applied SQL or alter old history rows.
-- Run php artisan migrate normally; existing structures are checked before changes.

-- 2026-10-16: database/migrations/daatabase_super_admin.php
-- Append-only review reference. Laravel migration is authoritative and checks
-- columns/tables before adding them. Do not replay ALTERs already applied.
ALTER TABLE users ADD must_change_password TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD temporary_password_expires_at TIMESTAMP NULL;
ALTER TABLE client_subscriptions ADD monthly_price DECIMAL(12,2) NULL;
UPDATE client_subscriptions SET monthly_price =
    (SELECT monthly_price FROM plans WHERE plans.id = client_subscriptions.plan_id)
    WHERE monthly_price IS NULL;

CREATE TABLE IF NOT EXISTS saas_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    client_subscription_id BIGINT UNSIGNED NOT NULL UNIQUE,
    plan_name VARCHAR(100) NOT NULL,
    period_from DATE NOT NULL, period_to DATE NOT NULL, due_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL, paid_at TIMESTAMP NULL,
    payment_reference VARCHAR(150) NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
    INDEX saas_invoices_client_id_index (client_id),
    INDEX saas_invoices_due_date_index (due_date)
);
CREATE TABLE IF NOT EXISTS platform_settings (
    `key` VARCHAR(255) PRIMARY KEY, value TEXT NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
);
CREATE TABLE IF NOT EXISTS support_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(200) NOT NULL, description TEXT NOT NULL,
    priority VARCHAR(10) NOT NULL DEFAULT 'medium',
    status VARCHAR(15) NOT NULL DEFAULT 'open', resolution TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
    INDEX support_tickets_client_id_index (client_id),
    INDEX support_tickets_status_index (status)
);
