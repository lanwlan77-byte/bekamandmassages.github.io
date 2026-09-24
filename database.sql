CREATE DATABASE IF NOT EXISTS bekam_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bekam_db;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(24) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    notes TEXT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id),
    INDEX idx_bookings_schedule (booking_date, booking_time),
    INDEX idx_bookings_phone (phone),
    INDEX idx_bookings_status (status)
) ENGINE=InnoDB;

INSERT INTO services (name, description, duration_minutes, price)
SELECT 'Bekam', 'Bekam dengan proses yang nyaman dan memperhatikan kebersihan.', 60, 0
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Bekam');

INSERT INTO services (name, description, duration_minutes, price)
SELECT 'Massage', 'Pijat relaksasi untuk membantu mengurangi rasa pegal.', 60, 0
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Massage');

INSERT INTO services (name, description, duration_minutes, price)
SELECT 'Relaksasi', 'Perawatan untuk memberikan suasana tenang dan nyaman.', 60, 0
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Relaksasi');