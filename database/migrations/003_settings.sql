CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string','email','integer','boolean','textarea') NOT NULL DEFAULT 'string',
    setting_group VARCHAR(60) NOT NULL DEFAULT 'general',
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(setting_group),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value, setting_type, setting_group) VALUES
('store_name','Noir & Co.','string','general'),('store_email','admin@noir.test','email','general'),('store_phone','','string','general'),('store_address','','textarea','general'),('currency','NGN','string','general'),('timezone','Africa/Lagos','string','general'),
('maintenance_mode','0','boolean','store'),('customer_registration','1','boolean','store'),('guest_checkout','1','boolean','store'),
('email_notifications','1','boolean','notifications'),('sms_notifications','0','boolean','notifications'),('order_notifications','1','boolean','notifications'),('low_stock_notifications','1','boolean','notifications'),
('session_timeout','120','integer','security')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
