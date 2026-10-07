ALTER TABLE users
    ADD COLUMN is_guest BOOLEAN NOT NULL DEFAULT FALSE AFTER account_type,
    ADD COLUMN guest_token_hash CHAR(64) NULL AFTER is_guest,
    ADD COLUMN guest_expires_at DATETIME NULL AFTER guest_token_hash,
    ADD COLUMN guest_promoted_at DATETIME NULL AFTER guest_expires_at,
    ADD INDEX idx_users_guest_cleanup (is_guest, guest_expires_at),
    ADD UNIQUE KEY uq_users_guest_token_hash (guest_token_hash);
