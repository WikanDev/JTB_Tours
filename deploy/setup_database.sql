CREATE DATABASE IF NOT EXISTS jtb_tours;
CREATE USER IF NOT EXISTS 'jtb_admin_db'@'localhost' IDENTIFIED BY 'jtb_tours_db_2025';
GRANT ALL PRIVILEGES ON jtb_tours.* TO 'jtb_admin_db'@'localhost';
FLUSH PRIVILEGES;
