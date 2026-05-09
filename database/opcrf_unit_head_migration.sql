ALTER TABLE sdopang1_offices
    ADD COLUMN IF NOT EXISTS unit_head INT DEFAULT NULL AFTER office_head;

UPDATE sdopang1_offices
SET unit_head = office_head
WHERE unit_head IS NULL;

CREATE INDEX IF NOT EXISTS idx_offices_unit_head ON sdopang1_offices (unit_head);
