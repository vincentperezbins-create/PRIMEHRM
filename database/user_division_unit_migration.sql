ALTER TABLE sdopang1_user
    ADD COLUMN IF NOT EXISTS division_unit VARCHAR(30) DEFAULT 'School' AFTER office_role;
