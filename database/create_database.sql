-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `pns_dhampur` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `pns_dhampur`;

-- Grant privileges (for XAMPP default setup)
GRANT ALL PRIVILEGES ON `pns_dhampur`.* TO 'root'@'localhost';
FLUSH PRIVILEGES;