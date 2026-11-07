CREATE DATABASE IF NOT EXISTS terra_verde CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE terra_verde;

CREATE USER IF NOT EXISTS 'terra_user'@'localhost' IDENTIFIED BY 'terra_pass_change_me';
GRANT SELECT, INSERT, UPDATE, DELETE ON terra_verde.* TO 'terra_user'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  skin_concern ENUM('clarify','glow','calm','nourish') NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
