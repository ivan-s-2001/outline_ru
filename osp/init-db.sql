CREATE DATABASE IF NOT EXISTS `outline`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'outline'@'127.0.0.1' IDENTIFIED BY 'outline';
CREATE USER IF NOT EXISTS 'outline'@'localhost' IDENTIFIED BY 'outline';

GRANT ALL PRIVILEGES ON `outline`.* TO 'outline'@'127.0.0.1';
GRANT ALL PRIVILEGES ON `outline`.* TO 'outline'@'localhost';
FLUSH PRIVILEGES;
