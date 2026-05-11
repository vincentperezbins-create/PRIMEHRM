-- Stores the CTO earned-credit transaction IDs selected by the employee
-- when filing a CTO leave application.

ALTER TABLE leave_applications
  ADD COLUMN IF NOT EXISTS cto_transaction_ids TEXT DEFAULT NULL AFTER reason;

