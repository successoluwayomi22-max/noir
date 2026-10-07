-- Extend activity_logs with actor display name, subject label, platform, and user-agent.
-- These columns let us display rich log entries without joins and distinguish web vs mobile.

ALTER TABLE activity_logs
    ADD COLUMN actor_name  VARCHAR(120) NULL AFTER user_id,
    ADD COLUMN subject_label VARCHAR(255) NULL AFTER subject_id,
    ADD COLUMN platform    ENUM('web','mobile','system','cron') NOT NULL DEFAULT 'web' AFTER metadata,
    ADD COLUMN user_agent  VARCHAR(500) NULL AFTER platform;

-- Index for filtering by platform and event category
ALTER TABLE activity_logs
    ADD INDEX idx_platform_created (platform, created_at),
    ADD INDEX idx_event_created    (event, created_at);
