-- ============================================================
-- Church Facility Manager — Reservations Schema
-- Run this against the `facilitymanager` database
-- ============================================================

-- Organizations / Ministries (creatable combobox source)
CREATE TABLE IF NOT EXISTS organizations (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservations
CREATE TABLE IF NOT EXISTS reservations (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    title                 VARCHAR(255)        NULL,
    organization_id       INT                 NULL,
    start_datetime        DATETIME            NOT NULL,
    end_datetime          DATETIME            NOT NULL,
    notes                 TEXT                NULL,
    is_recurring          TINYINT(1)          NOT NULL DEFAULT 0,
    recurrence_rule       VARCHAR(50)         NULL COMMENT 'weekly | biweekly | monthly | daily',
    recurrence_end_date   DATE                NULL,
    parent_reservation_id INT                 NULL COMMENT 'for generated instances, points to the parent',
    created_by            INT                 NULL,
    created_at            TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_start (start_datetime),
    INDEX idx_org   (organization_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservation ↔ Rooms (many-to-many)
CREATE TABLE IF NOT EXISTS reservation_rooms (
    reservation_id INT NOT NULL,
    room_id        INT NOT NULL,
    PRIMARY KEY (reservation_id, room_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id)        REFERENCES rooms(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
