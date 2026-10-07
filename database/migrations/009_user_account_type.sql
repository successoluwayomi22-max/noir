ALTER TABLE users ADD COLUMN account_type ENUM('customer','staff') NOT NULL DEFAULT 'customer' AFTER status;
UPDATE users u SET account_type='staff' WHERE EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=u.id AND r.slug<>'customer');
