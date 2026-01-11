-- ============================================
-- Database Schema for Trading System (v10)
-- ============================================

-- ============================================
-- CREATE TABLE trading_positions (v10)
-- ============================================

DROP TABLE IF EXISTS `trading_positions`;

CREATE TABLE IF NOT EXISTS `trading_positions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_number` VARCHAR(50) NOT NULL,
  `ticket` BIGINT(20) NOT NULL,
  `symbol` VARCHAR(20) NOT NULL,
  `position_type` VARCHAR(10) NOT NULL COMMENT 'BUY atau SELL',
  `volume` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `profit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `position_time` DATETIME NOT NULL,
  `comment` TEXT NULL DEFAULT NULL,
  `sync_time` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_account_ticket` (`account_number`, `ticket`),
  KEY `idx_account` (`account_number`),
  KEY `idx_ticket` (`ticket`),
  KEY `idx_position_time` (`position_time`),
  KEY `idx_sync_time` (`sync_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CREATE TABLE ea_control (EA ON/OFF Control v10)
-- ============================================

CREATE TABLE IF NOT EXISTS `ea_control` (
  `account_number` VARCHAR(50) NOT NULL PRIMARY KEY,
  `status` ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON' COMMENT 'Status EA Global: ON atau OFF',
  `schedule_s1`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s2`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s3`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s4`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s5`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s6`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s7`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s8`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_s9`  ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `schedule_sx` ENUM('ON', 'OFF') NOT NULL DEFAULT 'ON',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Optional: siapa yang update (bisa IP atau username)',
  KEY `idx_status` (`status`),
  KEY `idx_schedule_s1` (`schedule_s1`),
  KEY `idx_schedule_s2` (`schedule_s2`),
  KEY `idx_schedule_s3` (`schedule_s3`),
  KEY `idx_schedule_s4` (`schedule_s4`),
  KEY `idx_schedule_s5` (`schedule_s5`),
  KEY `idx_schedule_s6` (`schedule_s6`),
  KEY `idx_schedule_s7` (`schedule_s7`),
  KEY `idx_schedule_s8` (`schedule_s8`),
  KEY `idx_schedule_s9` (`schedule_s9`),
  KEY `idx_schedule_sx` (`schedule_sx`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CREATE TABLE lot_sizes (Lot Sizes Management v10)
-- ============================================

CREATE TABLE IF NOT EXISTS `lot_sizes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `schedule` VARCHAR(10) NOT NULL DEFAULT 'S1' COMMENT 'Schedule ID: S1, S2, S3, S4, S5, S6, S7, S8, S9, SX',
  `size` DECIMAL(10,2) NOT NULL COMMENT 'Lot size value (0.01, 0.02, etc)',
  `order_index` INT(11) NOT NULL DEFAULT 0 COMMENT 'Urutan dalam sequence martingale',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = aktif, 0 = nonaktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schedule` (`schedule`),
  KEY `idx_order_index` (`order_index`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_schedule_active` (`schedule`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MIGRATION: Add schedule column to lot_sizes table
-- ============================================

-- Add schedule column if not exists
ALTER TABLE `lot_sizes` 
ADD COLUMN IF NOT EXISTS `schedule` VARCHAR(10) NOT NULL DEFAULT 'S1' 
COMMENT 'Schedule ID: S1, S2, S3, S4, S5, S6, S7, S8, S9, SX' 
AFTER `id`;

-- Update existing data to S1 (for backward compatibility)
UPDATE `lot_sizes` 
SET `schedule` = 'S1' 
WHERE `schedule` IS NULL OR `schedule` = '' OR `schedule` = 'S1';

-- Add indexes if not exists
ALTER TABLE `lot_sizes` 
ADD INDEX IF NOT EXISTS `idx_schedule` (`schedule`);

ALTER TABLE `lot_sizes` 
ADD INDEX IF NOT EXISTS `idx_schedule_active` (`schedule`, `is_active`);

-- ============================================
-- DEFAULT DATA INSERTIONS
-- ============================================

-- Insert default EA control data
INSERT IGNORE INTO `ea_control` (
  `account_number`,
  `status`,
  `schedule_s1`,
  `schedule_s2`,
  `schedule_s3`,
  `schedule_s4`,
  `schedule_s5`,
  `schedule_s6`,
  `schedule_s7`,
  `schedule_s8`,
  `schedule_s9`,
  `schedule_sx`
) 
VALUES ('263264939','ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON', 'ON');

-- Insert default lot sizes for all schedules
-- S1
INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`) VALUES
  ('S1', '0.03', 1, 1),
  ('S1', '0.06', 2, 1),
  ('S1', '0.10', 3, 1),
  ('S1', '0.15', 4, 1),
  ('S1', '0.24', 5, 1),
  ('S1', '0.29', 6, 1),
  ('S1', '0.39', 7, 1),
  ('S1', '0.51', 8, 1),
  ('S1', '0.67', 9, 1),
  ('S1', '0.86', 10, 1),
  ('S1', '1.10', 11, 1),
  ('S1', '1.35', 12, 1),
  ('S1', '1.76', 13, 1),
  ('S1', '2.22', 14, 1),
  ('S1', '2.80', 15, 1),
  ('S1', '3.52', 16, 1);

-- Copy S1 data to all other schedules
INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S2', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S3', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S4', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S5', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S6', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S7', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S8', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'S9', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

INSERT IGNORE INTO `lot_sizes` (`schedule`, `size`, `order_index`, `is_active`)
SELECT 'SX', `size`, `order_index`, `is_active`
FROM `lot_sizes`
WHERE `schedule` = 'S1';

-- ============================================
-- VERIFICATION QUERY (Optional)
-- ============================================
/*
-- Uncomment to verify data count per schedule
SELECT `schedule`, COUNT(*) as count 
FROM `lot_sizes` 
GROUP BY `schedule` 
ORDER BY `schedule`;
*/