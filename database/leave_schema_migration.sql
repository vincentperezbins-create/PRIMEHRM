-- PRIMEHR leave module schema hardening.
-- Run this after importing D:\DIVISION\monitoring.sql.

CREATE TABLE IF NOT EXISTS `system_runs` (
  `run_id` int(11) NOT NULL AUTO_INCREMENT,
  `process_name` varchar(100) NOT NULL,
  `run_key` varchar(30) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`run_id`),
  UNIQUE KEY `unique_process_run` (`process_name`, `run_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: annual fixed entitlements are reset through accounts/cron/leave_run_yearly_entitlements.php.
-- It currently covers SPL, SOLO, VAWC, and WL once per year using system_runs key yearly_entitlements.

ALTER TABLE `leave_types`
  ADD UNIQUE KEY `unique_leave_code` (`leave_code`);

ALTER TABLE `leave_transactions`
  ADD COLUMN `earned_at` datetime DEFAULT NULL AFTER `remarks`,
  ADD COLUMN `expires_at` datetime DEFAULT NULL AFTER `earned_at`,
  ADD KEY `idx_leave_tx_user_type_date` (`user_id`, `leave_type_id`, `created_at`),
  ADD KEY `idx_leave_tx_expiry` (`leave_type_id`, `expires_at`),
  ADD KEY `idx_leave_tx_reference` (`source`, `reference_id`);

UPDATE leave_transactions t
JOIN leave_types lt ON lt.leave_type_id = t.leave_type_id
SET
  t.earned_at = COALESCE(t.earned_at, t.created_at),
  t.expires_at = COALESCE(t.expires_at, DATE_ADD(t.created_at, INTERVAL 1 YEAR))
WHERE lt.leave_code = 'CTO'
  AND t.transaction_type IN ('earn', 'adjust');

ALTER TABLE `leave_applications`
  ADD KEY `idx_leave_app_user_type_status_date` (`user_id`, `leave_type_id`, `status`, `date_from`),
  ADD KEY `idx_leave_app_status_created` (`status`, `created_at`);

-- Backfill balance_after for older monthly accrual rows that were inserted before running balances were stored.
SET @prev_user := NULL;
SET @prev_type := NULL;
SET @running_balance := 0.000;

UPDATE leave_transactions t
JOIN (
  SELECT
    transaction_id,
    @running_balance := IF(
      @prev_user = user_id AND @prev_type = leave_type_id,
      @running_balance,
      0.000
    ) AS opening_marker,
    @running_balance := @running_balance + CASE
      WHEN transaction_type IN ('earn', 'adjust') THEN days
      ELSE -days
    END AS computed_balance,
    @prev_user := user_id AS user_marker,
    @prev_type := leave_type_id AS type_marker
  FROM leave_transactions
  ORDER BY user_id, leave_type_id, created_at, transaction_id
) x ON x.transaction_id = t.transaction_id
SET t.balance_after = x.computed_balance
WHERE t.balance_after IS NULL;

-- Initialize current balances from latest transaction where rows are missing.
INSERT INTO leave_balances (user_id, leave_type_id, balance)
SELECT latest.user_id, latest.leave_type_id, latest.balance_after
FROM (
  SELECT t.user_id, t.leave_type_id, t.balance_after
  FROM leave_transactions t
  JOIN (
    SELECT user_id, leave_type_id, MAX(transaction_id) AS latest_transaction_id
    FROM leave_transactions
    GROUP BY user_id, leave_type_id
  ) x ON x.latest_transaction_id = t.transaction_id
) latest
LEFT JOIN leave_balances lb
  ON lb.user_id = latest.user_id
 AND lb.leave_type_id = latest.leave_type_id
WHERE lb.balance_id IS NULL;
