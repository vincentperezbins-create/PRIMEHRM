CREATE TABLE IF NOT EXISTS sdopang1_offices (
    office_id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_type VARCHAR(100) DEFAULT NULL,
    parent_office_id INT DEFAULT NULL,
    office_head INT DEFAULT NULL,
    unit_head INT DEFAULT NULL,
    office_category ENUM('Division Office','School') DEFAULT 'Division Office',
    school_id VARCHAR(50) DEFAULT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_offices_parent (parent_office_id),
    INDEX idx_offices_head (office_head),
    INDEX idx_offices_unit_head (unit_head),
    INDEX idx_offices_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE sdopang1_user
    ADD COLUMN IF NOT EXISTS office_id INT DEFAULT NULL;

ALTER TABLE sdopang1_user
    ADD COLUMN IF NOT EXISTS is_office_head TINYINT(1) DEFAULT 0;

ALTER TABLE sdopang1_user
    ADD COLUMN IF NOT EXISTS office_role ENUM('Head','Assistant Head','Staff') DEFAULT 'Staff';

CREATE TABLE IF NOT EXISTS sdopang1_opcrf (
    opcrf_id INT AUTO_INCREMENT PRIMARY KEY,
    office_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    school_year VARCHAR(50) NOT NULL,
    quarter VARCHAR(50) NOT NULL,
    prepared_by INT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    date_prepared DATE DEFAULT NULL,
    date_reviewed DATE DEFAULT NULL,
    date_approved DATE DEFAULT NULL,
    status ENUM('Draft','For Review','Reviewed','Approved','Returned') DEFAULT 'Draft',
    overall_rating DECIMAL(5,2) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    uploaded_pdf VARCHAR(255) DEFAULT NULL,
    uploaded_excel VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_opcrf_office (office_id),
    INDEX idx_opcrf_status (status),
    INDEX idx_opcrf_period (school_year, quarter),
    CONSTRAINT fk_opcrf_office
        FOREIGN KEY (office_id) REFERENCES sdopang1_offices(office_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sdopang1_opcrf_indicators (
    indicator_id INT AUTO_INCREMENT PRIMARY KEY,
    opcrf_id INT NOT NULL,
    kra VARCHAR(255) DEFAULT NULL,
    objective TEXT NOT NULL,
    success_indicator TEXT DEFAULT NULL,
    actual_accomplishment TEXT DEFAULT NULL,
    quality VARCHAR(255) DEFAULT NULL,
    efficiency VARCHAR(255) DEFAULT NULL,
    timeliness VARCHAR(255) DEFAULT NULL,
    rating DECIMAL(5,2) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_opcrf_indicators_opcrf (opcrf_id),
    CONSTRAINT fk_opcrf_indicators_opcrf
        FOREIGN KEY (opcrf_id) REFERENCES sdopang1_opcrf(opcrf_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sdopang1_opcrf_movs (
    mov_id INT AUTO_INCREMENT PRIMARY KEY,
    opcrf_id INT NOT NULL,
    indicator_id INT DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_opcrf_movs_opcrf (opcrf_id),
    INDEX idx_opcrf_movs_indicator (indicator_id),
    CONSTRAINT fk_opcrf_movs_opcrf
        FOREIGN KEY (opcrf_id) REFERENCES sdopang1_opcrf(opcrf_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_opcrf_movs_indicator
        FOREIGN KEY (indicator_id) REFERENCES sdopang1_opcrf_indicators(indicator_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sdopang1_opcrf_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    opcrf_id INT NOT NULL,
    action_taken VARCHAR(255) NOT NULL,
    action_by INT DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_opcrf_logs_opcrf (opcrf_id),
    CONSTRAINT fk_opcrf_logs_opcrf
        FOREIGN KEY (opcrf_id) REFERENCES sdopang1_opcrf(opcrf_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
