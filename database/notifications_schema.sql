CREATE TABLE IF NOT EXISTS sdopang1_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    role_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    notification_type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    INDEX idx_notifications_user_read (user_id, is_read, created_at),
    INDEX idx_notifications_role_read (role_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
