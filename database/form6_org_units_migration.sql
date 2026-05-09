CREATE TABLE IF NOT EXISTS division_units (
    division_unit_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_code VARCHAR(30) NOT NULL UNIQUE,
    unit_name VARCHAR(150) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS office_units (
    office_unit_id INT AUTO_INCREMENT PRIMARY KEY,
    division_unit_id INT NOT NULL,
    unit_code VARCHAR(50) NOT NULL UNIQUE,
    unit_name VARCHAR(180) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_office_units_division (division_unit_id),
    CONSTRAINT fk_office_units_division
        FOREIGN KEY (division_unit_id) REFERENCES division_units(division_unit_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO division_units (unit_code, unit_name, sort_order) VALUES
('School', 'School', 10),
('OSDS', 'Office of the Schools Division Superintendent', 20),
('SGOD', 'School Governance and Operations Division', 30),
('CID', 'Curriculum Implementation Division', 40),
('District', 'District Offices', 50),
('AOV', 'Administrative Services / AOV', 60)
ON DUPLICATE KEY UPDATE unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'SCHOOL', 'School', 10 FROM division_units WHERE unit_code = 'School'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'SDS', 'Schools Division Superintendent', 10 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'ASDS', 'Assistant Schools Division Superintendent', 20 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'HRMPS', 'Human Resource Management Personnel Section', 30 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'RECORDS', 'Records Section', 40 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'CASHIER', 'Cashier Section', 50 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'ACCOUNTING', 'Accounting Section', 60 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'BUDGET', 'Budget Section', 70 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'SUPPLY_PROPERTY', 'Supply / Property Section', 80 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'GENERAL_SERVICES', 'General Services', 90 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'LEGAL', 'Legal Unit', 100 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'ICT', 'ICT Unit', 110 FROM division_units WHERE unit_code = 'OSDS'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'SMME', 'School Management Monitoring and Evaluation', 10 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'HRD', 'Human Resource Development', 20 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'SMN', 'Social Mobilization and Networking', 30 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'DRRM', 'Disaster Risk Reduction and Management', 40 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'YOUTH_FORMATION', 'Youth Formation', 50 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'SHN', 'School Health and Nutrition', 60 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'PLANNING_RESEARCH', 'Planning and Research', 70 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'EDUCATION_FACILITIES', 'Education Facilities', 80 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'PRIVATE_SCHOOLS', 'Private Schools Section', 90 FROM division_units WHERE unit_code = 'SGOD'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'EPS', 'Education Program Supervisors', 10 FROM division_units WHERE unit_code = 'CID'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'LRMS', 'Learning Resource Management Section', 20 FROM division_units WHERE unit_code = 'CID'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'ASSESSMENT', 'Testing and Assessment', 30 FROM division_units WHERE unit_code = 'CID'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'RESEARCH_INNOVATION', 'Research and Innovation', 40 FROM division_units WHERE unit_code = 'CID'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'PSDS', 'Public Schools District Supervisor', 10 FROM division_units WHERE unit_code = 'District'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

INSERT INTO office_units (division_unit_id, unit_code, unit_name, sort_order)
SELECT division_unit_id, 'AOV_ADMIN', 'Administrative Services / AOV', 10 FROM division_units WHERE unit_code = 'AOV'
ON DUPLICATE KEY UPDATE division_unit_id = VALUES(division_unit_id), unit_name = VALUES(unit_name), sort_order = VALUES(sort_order), is_active = 1;

ALTER TABLE sdopang1_user
    ADD COLUMN IF NOT EXISTS division_unit_id INT DEFAULT NULL AFTER division_unit,
    ADD COLUMN IF NOT EXISTS office_unit_id INT DEFAULT NULL AFTER division_unit_id;

UPDATE sdopang1_user u
JOIN division_units du ON du.unit_code = COALESCE(NULLIF(u.division_unit, ''), 'School')
SET u.division_unit_id = du.division_unit_id
WHERE u.division_unit_id IS NULL;

UPDATE sdopang1_user u
JOIN office_units ou ON ou.unit_code = 'SCHOOL'
SET u.office_unit_id = ou.office_unit_id
WHERE u.office_unit_id IS NULL AND COALESCE(NULLIF(u.division_unit, ''), 'School') = 'School';
