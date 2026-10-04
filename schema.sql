-- Official Letter Workflow (Demo) - database schema
-- MySQL / MariaDB. All sample data in seed.sql is fictitious.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Users with role-based access control.
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    username      VARCHAR(60)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','verifikator','staff') NOT NULL DEFAULT 'staff',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-year running number allocator for letter numbering (atomic increment).
CREATE TABLE IF NOT EXISTS letter_counters (
    year      SMALLINT UNSIGNED NOT NULL,
    last_seq  INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Letters move through a multi-level workflow:
--   draft -> submitted -> verified -> approved
--   (verifikator or admin may send back to 'rejected')
CREATE TABLE IF NOT EXISTS letters (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    number      VARCHAR(40)  DEFAULT NULL,
    year        SMALLINT UNSIGNED NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    recipient   VARCHAR(160) NOT NULL,
    body        TEXT         NOT NULL,
    status      ENUM('draft','submitted','verified','approved','rejected') NOT NULL DEFAULT 'draft',
    created_by  INT UNSIGNED NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_letters_number (number),
    KEY idx_letters_status (status),
    KEY idx_letters_created_by (created_by),
    CONSTRAINT fk_letters_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable audit log of every workflow action (disposition trail).
CREATE TABLE IF NOT EXISTS letter_events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    letter_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    action      VARCHAR(40)  NOT NULL,
    from_status VARCHAR(20)  DEFAULT NULL,
    to_status   VARCHAR(20)  DEFAULT NULL,
    note        VARCHAR(500) DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_letter (letter_id),
    CONSTRAINT fk_events_letter FOREIGN KEY (letter_id) REFERENCES letters (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_user   FOREIGN KEY (user_id)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
