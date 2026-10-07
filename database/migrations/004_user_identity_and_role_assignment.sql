ALTER TABLE users ADD COLUMN username VARCHAR(80) NULL AFTER public_id;
UPDATE users SET username = CASE WHEN email='admin@noir.test' THEN 'admin' ELSE CONCAT('customer_', id) END WHERE username IS NULL;
ALTER TABLE users MODIFY username VARCHAR(80) NOT NULL, ADD UNIQUE KEY uq_users_username (username);
