-- Adds pay status for approved leave applications.
-- Run once before using the With Pay / Without Pay dropdown.

ALTER TABLE `leave_applications`
  ADD COLUMN `pay_status` enum('with_pay','without_pay') NOT NULL DEFAULT 'with_pay' AFTER `status`;
