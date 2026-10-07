ALTER TABLE staff_applications
    ADD COLUMN resume_path VARCHAR(255) NULL AFTER message,
    ADD COLUMN resume_original_name VARCHAR(255) NULL AFTER resume_path;
