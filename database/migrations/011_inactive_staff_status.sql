ALTER TABLE users
    MODIFY COLUMN status ENUM('pending','active','suspended','inactive','deleted') NOT NULL DEFAULT 'pending';
