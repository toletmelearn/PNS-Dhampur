-- Database Security Setup Script
-- This script creates a dedicated database user for the PNS-Dhampur application
-- Run this script as MySQL root user to improve database security

-- Create dedicated database user
CREATE USER IF NOT EXISTS 'pns_user'@'localhost' IDENTIFIED BY 'PNS_Secure_2025!@#';

-- Grant necessary privileges to the application database only
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES 
ON pns_dhampur.* TO 'pns_user'@'localhost';

-- Remove any global privileges (security best practice)
REVOKE ALL PRIVILEGES ON *.* FROM 'pns_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify user creation
SELECT User, Host FROM mysql.user WHERE User = 'pns_user';

-- Show granted privileges
SHOW GRANTS FOR 'pns_user'@'localhost';

-- Instructions:
-- 1. Run this script in MySQL as root user
-- 2. Update your .env file with the new credentials:
--    DB_USERNAME=pns_user
--    DB_PASSWORD=PNS_Secure_2025!@#
-- 3. Test the connection with: php artisan migrate:status