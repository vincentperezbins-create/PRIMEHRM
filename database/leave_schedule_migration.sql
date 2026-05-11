-- Stores date schedules for leave applications with non-continuous dates.
-- AM/PM entries should only be used for CTO leave.

ALTER TABLE leave_applications
  ADD COLUMN IF NOT EXISTS leave_schedule TEXT DEFAULT NULL AFTER cto_schedule;

