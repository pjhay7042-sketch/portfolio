-- ============================================================
--  biodataiot_db  —  MERGED schema
--  Combines: personal biodataiot accounts + OTP login + skills
--            with the IoT Monitor's sensor data + user roles.
--  One database. One accounts table. One login.
--
--  Run this entire file in phpMyAdmin > SQL tab.
--  Safe to re-run: uses IF NOT EXISTS guards throughout.
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS biodataiot_db
-- CHARACTER SET utf8mb4
-- COLLATE utf8mb4_unicode_ci;

-- USE biodataiot_db;

-- ------------------------------------------------------------
--  accounts
--  Added `role` so the merged site can gate IoT user-management
--  the same way the old IoT Monitor did (admin vs viewer).
--  Every existing account defaults to 'viewer'; promote the
--  first one to 'admin' manually after import (see note below).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS accounts (
  id          INT          AUTO_INCREMENT PRIMARY KEY,
  first_name  VARCHAR(50)  NOT NULL,
  middle_name VARCHAR(50)  NULL,
  last_name   VARCHAR(50)  NOT NULL,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  email       VARCHAR(255) NOT NULL DEFAULT '',
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- If you already have an `accounts` table from before this merge
-- (no `role` column yet), run this once — harmless if it already exists:
-- ALTER TABLE accounts ADD COLUMN role ENUM('admin','viewer') NOT NULL DEFAULT 'viewer' AFTER password;

-- If you're migrating from the old standalone IoT Monitor's `users`
-- table and want to keep those accounts, map them in like this
-- (run manually, adjust values as needed — usernames must be unique
-- across the merged accounts table):
-- INSERT INTO accounts (first_name, last_name, username, email, password, role)
-- SELECT 'IoT', 'User', username, '', password, role FROM old_iot_db.users;

-- ------------------------------------------------------------
--  Promote your own account to admin once you've registered
--  through the site (replace 'your_username'):
-- ------------------------------------------------------------
-- UPDATE accounts SET role = 'admin' WHERE username = 'your_username';

-- ------------------------------------------------------------
--  otp_tokens  —  stores 6-digit codes + 10-minute expiry
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS otp_tokens (
  id         INT          AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NOT NULL,
  otp_code   VARCHAR(6)   NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  skills
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skills (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  category   VARCHAR(100) NOT NULL DEFAULT 'Other',
  level      ENUM('Beginner','Basic','Intermediate','Advanced','Expert') NOT NULL DEFAULT 'Basic',
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO skills (id, name, category, level) VALUES
  (1, 'Network Configuration & Troubleshooting', 'Networking & Security',          'Basic'),
  (2, 'Firewall Setup & Management',             'Networking & Security',          'Basic'),
  (3, 'VPN Configuration',                       'Networking & Security',          'Basic'),
  (4, 'HTML',                                    'Programming & Web Technologies', 'Basic'),
  (5, 'CSS',                                     'Programming & Web Technologies', 'Basic'),
  (6, 'Java Programming',                        'Programming & Web Technologies', 'Basic'),
  (7, 'MySQL / Database Management',             'Programming & Web Technologies', 'Basic');

-- ------------------------------------------------------------
--  sensor_data  —  from the IoT Temperature & Humidity Monitor
--  Populated by API/iot_insert.php, called by the Python bridge
--  (sensor_sender.py), which itself reads from the Arduino over
--  serial. No login involved in this path — the sensor doesn't
--  authenticate, it just posts readings.
--
--  `alert` (added for the high-temperature LED/buzzer feature):
--  1 if this reading was at or above HIGH_TEMP_THRESHOLD_C
--  (see API/iot_config.php and temperature_sender.ino), else 0.
--  Drives the red-LED/buzzer state on the device and the
--  warning banner/popup on the dashboard.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sensor_data (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  temperature DECIMAL(5,2) NOT NULL,
  humidity    DECIMAL(5,2) NOT NULL,
  alert       TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at),
  INDEX idx_alert (alert)
) ENGINE=InnoDB;

-- If you already have a `sensor_data` table from before this feature
-- (no `alert` column yet), run this once — harmless if it already exists:
-- ALTER TABLE sensor_data ADD COLUMN alert TINYINT(1) NOT NULL DEFAULT 0 AFTER humidity;
-- ALTER TABLE sensor_data ADD INDEX idx_alert (alert);