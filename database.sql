-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user'
);

-- Create ftp_settings table
CREATE TABLE ftp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host VARCHAR(255) NOT NULL,
    port INT NOT NULL DEFAULT 21,
    ftp_username VARCHAR(50) NOT NULL,
    ftp_password VARCHAR(255) NOT NULL,
    secure BOOLEAN DEFAULT FALSE
);

-- Insert admin user (password: 123123)
INSERT INTO users (username, password, role) 
VALUES ('can', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');