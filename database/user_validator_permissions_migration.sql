ALTER TABLE sdopang1_user
    ADD COLUMN IF NOT EXISTS can_validate_201 TINYINT(1) NOT NULL DEFAULT 0 AFTER office_role,
    ADD COLUMN IF NOT EXISTS can_validate_opcrf TINYINT(1) NOT NULL DEFAULT 0 AFTER can_validate_201,
    ADD COLUMN IF NOT EXISTS can_validate_ipcrf TINYINT(1) NOT NULL DEFAULT 0 AFTER can_validate_opcrf,
    ADD COLUMN IF NOT EXISTS can_validate_leave TINYINT(1) NOT NULL DEFAULT 0 AFTER can_validate_ipcrf;
