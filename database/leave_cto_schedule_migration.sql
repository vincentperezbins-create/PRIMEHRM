-- Stores non-continuous CTO date selections and AM/PM half-day entries.

ALTER TABLE leave_applications
  ADD COLUMN IF NOT EXISTS cto_schedule TEXT DEFAULT NULL AFTER cto_transaction_ids;

