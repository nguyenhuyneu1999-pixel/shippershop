-- ============================================
-- WALLET & PAYMENT - DATABASE UPDATES
-- ============================================
-- Run this SQL AFTER importing social-tables.sql
-- Adds wallet-specific fields to transaction_history table

-- ============================================
-- UPDATE TRANSACTION_HISTORY TABLE
-- ============================================

-- Add bank account fields for withdrawals
ALTER TABLE `transaction_history`
ADD COLUMN `bank_name` varchar(100) DEFAULT NULL COMMENT 'Tên ngân hàng (rút tiền)' AFTER `payment_method`,
ADD COLUMN `account_number` varchar(50) DEFAULT NULL COMMENT 'Số tài khoản (rút tiền)' AFTER `bank_name`,
ADD COLUMN `account_name` varchar(200) DEFAULT NULL COMMENT 'Tên chủ tài khoản (rút tiền)' AFTER `account_number`,
ADD COLUMN `proof_url` varchar(500) DEFAULT NULL COMMENT 'URL ảnh chứng từ (nạp tiền)' AFTER `account_name`,
ADD COLUMN `approved_by` int(11) DEFAULT NULL COMMENT 'Admin duyệt giao dịch' AFTER `completed_at`;

-- Add foreign key for approved_by
ALTER TABLE `transaction_history`
ADD CONSTRAINT `fk_transaction_approved_by` 
FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for better performance
ALTER TABLE `transaction_history`
ADD INDEX `idx_user_type_status` (`user_id`, `type`, `status`),
ADD INDEX `idx_status_created` (`status`, `created_at`),
ADD INDEX `idx_type_created` (`type`, `created_at`);

-- ============================================
-- CREATE ADMIN STATISTICS VIEW
-- ============================================

CREATE OR REPLACE VIEW `v_wallet_admin_stats` AS
SELECT 
    -- Pending transactions
    (SELECT COUNT(*) FROM transaction_history WHERE status = 'pending') as pending_transactions,
    (SELECT COALESCE(SUM(amount), 0) FROM transaction_history WHERE status = 'pending' AND type = 'deposit') as pending_deposits_amount,
    (SELECT COALESCE(SUM(amount), 0) FROM transaction_history WHERE status = 'pending' AND type = 'withdraw') as pending_withdraws_amount,
    
    -- Completed today
    (SELECT COUNT(*) FROM transaction_history WHERE DATE(completed_at) = CURDATE() AND status = 'completed') as completed_today,
    (SELECT COALESCE(SUM(amount), 0) FROM transaction_history WHERE DATE(completed_at) = CURDATE() AND status = 'completed' AND type = 'deposit') as deposits_today,
    (SELECT COALESCE(SUM(amount), 0) FROM transaction_history WHERE DATE(completed_at) = CURDATE() AND status = 'completed' AND type = 'withdraw') as withdraws_today,
    
    -- Total wallet balance
    (SELECT COALESCE(SUM(balance), 0) FROM wallet) as total_wallet_balance,
    
    -- Total users with wallet
    (SELECT COUNT(*) FROM wallet WHERE balance > 0) as users_with_balance;

-- ============================================
-- CREATE USER WALLET SUMMARY VIEW
-- ============================================

CREATE OR REPLACE VIEW `v_user_wallet_summary` AS
SELECT 
    u.id as user_id,
    u.fullname,
    u.email,
    w.balance,
    
    -- Deposit stats
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transaction_history 
     WHERE user_id = u.id AND type = 'deposit' AND status = 'completed') as total_deposited,
    
    (SELECT COUNT(*) 
     FROM transaction_history 
     WHERE user_id = u.id AND type = 'deposit' AND status = 'completed') as deposit_count,
    
    -- Withdraw stats
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transaction_history 
     WHERE user_id = u.id AND type = 'withdraw' AND status = 'completed') as total_withdrawn,
    
    (SELECT COUNT(*) 
     FROM transaction_history 
     WHERE user_id = u.id AND type = 'withdraw' AND status = 'completed') as withdraw_count,
    
    -- Purchase stats
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transaction_history 
     WHERE user_id = u.id AND type = 'purchase' AND status = 'completed') as total_spent,
    
    (SELECT COUNT(*) 
     FROM transaction_history 
     WHERE user_id = u.id AND type = 'purchase' AND status = 'completed') as purchase_count,
    
    -- Pending transactions
    (SELECT COUNT(*) 
     FROM transaction_history 
     WHERE user_id = u.id AND status = 'pending') as pending_transactions,
    
    -- Last transaction
    (SELECT MAX(created_at) 
     FROM transaction_history 
     WHERE user_id = u.id) as last_transaction_date

FROM users u
LEFT JOIN wallet w ON u.id = w.user_id
WHERE u.status = 'active';

-- ============================================
-- CREATE UPLOAD DIRECTORY TABLE
-- ============================================

-- Track uploaded payment proofs
CREATE TABLE IF NOT EXISTS `payment_proofs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_payment_proofs_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transaction_history` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_proofs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE TRANSACTIONS FOR TESTING
-- ============================================

-- Insert sample deposit transaction (pending)
INSERT INTO `transaction_history` 
(`user_id`, `type`, `amount`, `balance_before`, `balance_after`, `payment_method`, `status`, `description`, `created_at`) 
VALUES
(1, 'deposit', 500000, 0, 0, 'bank_transfer', 'pending', 'Nạp tiền vào ví', DATE_SUB(NOW(), INTERVAL 10 MINUTE));

-- Insert sample completed deposit
INSERT INTO `transaction_history` 
(`user_id`, `type`, `amount`, `balance_before`, `balance_after`, `payment_method`, `status`, `description`, `created_at`, `completed_at`) 
VALUES
(1, 'deposit', 1000000, 0, 1000000, 'bank_transfer', 'completed', 'Nạp tiền vào ví', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert sample withdraw
INSERT INTO `transaction_history` 
(`user_id`, `type`, `amount`, `balance_before`, `balance_after`, `payment_method`, `bank_name`, `account_number`, `account_name`, `status`, `description`, `created_at`) 
VALUES
(1, 'withdraw', 200000, 1000000, 800000, 'bank_transfer', 'Vietcombank', '1234567890', 'NGUYEN VAN A', 'pending', 'Rút tiền từ ví', DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Update wallet balance for user 1 (for testing)
UPDATE `wallet` SET `balance` = 800000 WHERE `user_id` = 1;

-- ============================================
-- STORED PROCEDURES FOR WALLET OPERATIONS
-- ============================================

DELIMITER //

-- Procedure: Process deposit approval
CREATE PROCEDURE `sp_approve_deposit`(
    IN p_transaction_id INT,
    IN p_admin_id INT
)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_amount DECIMAL(15,2);
    DECLARE v_current_balance DECIMAL(15,2);
    DECLARE v_new_balance DECIMAL(15,2);
    
    -- Get transaction details
    SELECT user_id, amount INTO v_user_id, v_amount
    FROM transaction_history
    WHERE id = p_transaction_id AND type = 'deposit' AND status = 'pending';
    
    -- Get current balance
    SELECT balance INTO v_current_balance
    FROM wallet
    WHERE user_id = v_user_id;
    
    -- Calculate new balance
    SET v_new_balance = v_current_balance + v_amount;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Update wallet
    UPDATE wallet
    SET balance = v_new_balance
    WHERE user_id = v_user_id;
    
    -- Update transaction
    UPDATE transaction_history
    SET status = 'completed',
        balance_after = v_new_balance,
        completed_at = NOW(),
        approved_by = p_admin_id
    WHERE id = p_transaction_id;
    
    COMMIT;
    
    SELECT 'Success' as result, v_new_balance as new_balance;
END //

-- Procedure: Get wallet statistics for date range
CREATE PROCEDURE `sp_wallet_stats`(
    IN p_from_date DATE,
    IN p_to_date DATE
)
BEGIN
    SELECT 
        -- Deposits
        COUNT(CASE WHEN type = 'deposit' AND status = 'completed' THEN 1 END) as deposits_count,
        COALESCE(SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN amount END), 0) as deposits_total,
        
        -- Withdrawals
        COUNT(CASE WHEN type = 'withdraw' AND status = 'completed' THEN 1 END) as withdraws_count,
        COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'completed' THEN amount END), 0) as withdraws_total,
        
        -- Purchases
        COUNT(CASE WHEN type = 'purchase' AND status = 'completed' THEN 1 END) as purchases_count,
        COALESCE(SUM(CASE WHEN type = 'purchase' AND status = 'completed' THEN amount END), 0) as purchases_total,
        
        -- Pending
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount END), 0) as pending_total
    
    FROM transaction_history
    WHERE DATE(created_at) BETWEEN p_from_date AND p_to_date;
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger: Validate transaction amount
DELIMITER //

CREATE TRIGGER `tr_validate_transaction_amount` 
BEFORE INSERT ON `transaction_history`
FOR EACH ROW
BEGIN
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Transaction amount must be greater than 0';
    END IF;
    
    -- Validate minimum deposit
    IF NEW.type = 'deposit' AND NEW.amount < 10000 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Minimum deposit amount is 10,000 VND';
    END IF;
    
    -- Validate minimum withdrawal
    IF NEW.type = 'withdraw' AND NEW.amount < 50000 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Minimum withdrawal amount is 50,000 VND';
    END IF;
END //

DELIMITER ;

-- ============================================
-- INDEXES FOR PAYMENT PROOFS
-- ============================================

ALTER TABLE `payment_proofs`
ADD INDEX `idx_uploaded_at` (`uploaded_at`),
ADD INDEX `idx_user_transaction` (`user_id`, `transaction_id`);

-- ============================================
-- SECURITY: PREVENT DIRECT BALANCE UPDATES
-- ============================================

-- Create audit log for wallet changes
CREATE TABLE IF NOT EXISTS `wallet_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_balance` decimal(15,2) NOT NULL,
  `new_balance` decimal(15,2) NOT NULL,
  `change_amount` decimal(15,2) NOT NULL,
  `change_type` enum('increase','decrease') NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wallet_id` (`wallet_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: Log wallet balance changes
DELIMITER //

CREATE TRIGGER `tr_log_wallet_changes` 
AFTER UPDATE ON `wallet`
FOR EACH ROW
BEGIN
    IF OLD.balance != NEW.balance THEN
        INSERT INTO wallet_audit_log 
        (wallet_id, user_id, old_balance, new_balance, change_amount, change_type, reason)
        VALUES (
            NEW.id,
            NEW.user_id,
            OLD.balance,
            NEW.balance,
            ABS(NEW.balance - OLD.balance),
            IF(NEW.balance > OLD.balance, 'increase', 'decrease'),
            'Wallet balance updated'
        );
    END IF;
END //

DELIMITER ;

-- ============================================
-- GRANT PERMISSIONS (if needed)
-- ============================================

-- Grant SELECT on views to application user
-- GRANT SELECT ON v_wallet_admin_stats TO 'app_user'@'localhost';
-- GRANT SELECT ON v_user_wallet_summary TO 'app_user'@'localhost';

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Check if all tables exist
SELECT 
    'transaction_history' as table_name,
    COUNT(*) as record_count
FROM transaction_history
UNION ALL
SELECT 
    'payment_proofs' as table_name,
    COUNT(*) as record_count
FROM payment_proofs
UNION ALL
SELECT 
    'wallet_audit_log' as table_name,
    COUNT(*) as record_count
FROM wallet_audit_log;

-- Check wallet balances
SELECT 
    u.id,
    u.fullname,
    w.balance,
    (SELECT COUNT(*) FROM transaction_history WHERE user_id = u.id) as total_transactions
FROM users u
LEFT JOIN wallet w ON u.id = w.user_id
WHERE u.status = 'active'
LIMIT 10;

-- Done!
SELECT 'Wallet & Payment tables updated successfully!' as status;
