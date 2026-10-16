-- Test-only SQLite structural fixture derived from dairynex.sql. No business data.

CREATE TABLE `audit_logs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER DEFAULT NULL,
  `user_id` INTEGER DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` INTEGER DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `clients` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(150) NOT NULL,
  `legal_name` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `client_subscriptions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `plan_id` INTEGER NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `customers` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` INTEGER DEFAULT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `credit_limit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_mode_id` INTEGER DEFAULT NULL,
  `customer_type` INTEGER NOT NULL DEFAULT '1',
  `status` INTEGER NOT NULL DEFAULT '1',
  `supply_preference` INTEGER NOT NULL DEFAULT '1',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `customer_addresses` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
  `address_type` INTEGER NOT NULL DEFAULT '1',
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `landmark` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_default` INTEGER NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `customer_ledger` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
  `transaction_type_id` INTEGER NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` INTEGER DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `debit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) DEFAULT NULL,
  `created_by_user_id` INTEGER DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `deliveries` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
  `subscription_id` INTEGER DEFAULT NULL,
  `route_id` INTEGER DEFAULT NULL,
  `delivery_boy_id` INTEGER DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `delivery_slot_id` INTEGER NOT NULL DEFAULT '1',
  `source_type` INTEGER NOT NULL DEFAULT '1',
  `status_id` INTEGER NOT NULL DEFAULT '1',
  `delivery_pin_hash` varchar(255) DEFAULT NULL,
  `delivery_pin_verified_at` datetime DEFAULT NULL,
  `pin_attempts` INTEGER NOT NULL DEFAULT '0',
  `delivered_at` datetime DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_status` INTEGER NOT NULL DEFAULT '1',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `delivery_items` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `delivery_id` INTEGER NOT NULL,
  `product_id` INTEGER NOT NULL,
  `subscription_item_id` INTEGER DEFAULT NULL,
  `planned_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `actual_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `quantity_change_type` INTEGER NOT NULL DEFAULT '1',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `delivery_pin_attempts` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `delivery_id` INTEGER NOT NULL,
  `attempted_by_user_id` INTEGER DEFAULT NULL,
  `entered_pin_hash` varchar(255) DEFAULT NULL,
  `success` INTEGER NOT NULL DEFAULT '0',
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL
);

CREATE TABLE `delivery_routes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `delivery_boy_id` INTEGER NOT NULL,
  `name` varchar(100) NOT NULL,
  `area_locality` varchar(255) DEFAULT NULL,
  `expected_stops` INTEGER NOT NULL DEFAULT '0',
  `route_date` date DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `delivery_route_customers` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `route_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
  `sequence_no` INTEGER NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `inventory_locations` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `name` varchar(150) NOT NULL,
  `location_type` INTEGER NOT NULL DEFAULT '1',
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `inventory_transactions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `location_id` INTEGER NOT NULL,
  `product_id` INTEGER NOT NULL,
  `transaction_type_id` INTEGER NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `quantity` decimal(12,3) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` INTEGER DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by_user_id` INTEGER DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `invoices` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
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
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `invoice_items` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `invoice_id` INTEGER NOT NULL,
  `delivery_id` INTEGER DEFAULT NULL,
  `product_id` INTEGER NOT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `invoice_payments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `invoice_id` INTEGER NOT NULL,
  `payment_id` INTEGER NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `notifications` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER DEFAULT NULL,
  `user_id` INTEGER DEFAULT NULL,
  `customer_id` INTEGER DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` INTEGER NOT NULL DEFAULT '1',
  `is_read` INTEGER NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `payments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
  `payment_no` varchar(60) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` decimal(14,2) NOT NULL,
  `payment_method_id` INTEGER NOT NULL,
  `transaction_reference` varchar(150) DEFAULT NULL,
  `collected_by_user_id` INTEGER DEFAULT NULL,
  `notes` text,
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `payment_plans` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
  `total_amount` decimal(14,2) NOT NULL,
  `down_payment` decimal(14,2) NOT NULL DEFAULT '0.00',
  `installment_count` INTEGER NOT NULL,
  `frequency_id` INTEGER NOT NULL DEFAULT '1',
  `start_date` date NOT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `payment_plan_installments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `payment_plan_id` INTEGER NOT NULL,
  `installment_no` INTEGER NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` INTEGER NOT NULL DEFAULT '1',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `plans` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `monthly_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `max_customers` INTEGER DEFAULT NULL,
  `max_users` INTEGER DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `products` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `category_id` INTEGER DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `unit_type` INTEGER NOT NULL DEFAULT '1',
  `base_rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `product_categories` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `product_rates` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `product_id` INTEGER NOT NULL,
  `rate` decimal(12,2) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `ref_delivery_statuses` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(50) NOT NULL
);

CREATE TABLE `ref_payment_methods` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(50) NOT NULL
);

CREATE TABLE `ref_transaction_types` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(50) NOT NULL
);

CREATE TABLE `ref_user_types` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(50) NOT NULL
);

CREATE TABLE `subscriptions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `customer_id` INTEGER NOT NULL,
  `subscription_no` varchar(60) NOT NULL,
  `billing_cycle_id` INTEGER NOT NULL DEFAULT '1',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `auto_generate_deliveries` INTEGER NOT NULL DEFAULT '1',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `subscription_change_requests` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `subscription_id` INTEGER NOT NULL,
  `requested_by_user_id` INTEGER DEFAULT NULL,
  `product_id` INTEGER NOT NULL,
  `old_quantity` decimal(12,3) NOT NULL,
  `requested_quantity` decimal(12,3) NOT NULL,
  `effective_from` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `approved_by_user_id` INTEGER DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `subscription_items` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `subscription_id` INTEGER NOT NULL,
  `product_id` INTEGER NOT NULL,
  `default_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `subscription_pauses` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `subscription_id` INTEGER NOT NULL,
  `pause_from` date NOT NULL,
  `pause_to` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `subscription_schedules` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER NOT NULL,
  `subscription_item_id` INTEGER NOT NULL,
  `day_of_week` INTEGER NOT NULL,
  `delivery_slot_id` INTEGER NOT NULL DEFAULT '1',
  `planned_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `active_from` date NOT NULL,
  `active_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER DEFAULT NULL,
  `user_type_id` INTEGER NOT NULL,
  `name` varchar(150) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `user_roles` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `client_id` INTEGER DEFAULT NULL,
  `user_id` INTEGER NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX `customers_uq_customer_code` ON `customers` (`client_id`,`customer_code`);

CREATE UNIQUE INDEX `delivery_route_customers_uq_route_customer` ON `delivery_route_customers` (`route_id`,`customer_id`);

CREATE UNIQUE INDEX `inventory_locations_uq_location` ON `inventory_locations` (`client_id`,`name`);

CREATE UNIQUE INDEX `invoices_uq_invoice_no` ON `invoices` (`client_id`,`invoice_no`);

CREATE UNIQUE INDEX `invoice_payments_uq_invoice_payment` ON `invoice_payments` (`invoice_id`,`payment_id`);

CREATE UNIQUE INDEX `payments_uq_payment_no` ON `payments` (`client_id`,`payment_no`);

CREATE UNIQUE INDEX `payment_plan_installments_uq_installment` ON `payment_plan_installments` (`payment_plan_id`,`installment_no`);

CREATE UNIQUE INDEX `products_uq_sku` ON `products` (`client_id`,`sku`);

CREATE UNIQUE INDEX `product_categories_uq_category` ON `product_categories` (`client_id`,`name`);

CREATE UNIQUE INDEX `ref_delivery_statuses_name` ON `ref_delivery_statuses` (`name`);

CREATE UNIQUE INDEX `ref_payment_methods_name` ON `ref_payment_methods` (`name`);

CREATE UNIQUE INDEX `ref_transaction_types_name` ON `ref_transaction_types` (`name`);

CREATE UNIQUE INDEX `ref_user_types_name` ON `ref_user_types` (`name`);

CREATE UNIQUE INDEX `subscriptions_uq_subscription_no` ON `subscriptions` (`client_id`,`subscription_no`);

CREATE UNIQUE INDEX `users_uq_user_mobile_client` ON `users` (`client_id`,`mobile`);
