CREATE TABLE analytics_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(80) NOT NULL,
    page_path VARCHAR(255) NULL,
    session_key VARCHAR(80) NULL,
    user_id BIGINT UNSIGNED NULL,
    attribution_source VARCHAR(120) NULL,
    attribution_campaign VARCHAR(120) NULL,
    payload JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(event_name, created_at),
    INDEX(page_path),
    INDEX(session_key),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE security_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    severity ENUM('info','low','medium','high','critical') NOT NULL DEFAULT 'info',
    event VARCHAR(120) NOT NULL,
    subject_type VARCHAR(80) NULL,
    subject_id BIGINT UNSIGNED NULL,
    status ENUM('open','investigating','resolved') NOT NULL DEFAULT 'open',
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    INDEX(severity, status),
    INDEX(event, created_at)
) ENGINE=InnoDB;

CREATE TABLE upload_scan_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    disk_path VARCHAR(500) NOT NULL,
    public_path VARCHAR(500) NOT NULL,
    source VARCHAR(80) NOT NULL,
    status ENUM('pending','clean','flagged','failed') NOT NULL DEFAULT 'pending',
    scanner VARCHAR(120) NULL,
    scan_result TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    scanned_at DATETIME NULL,
    INDEX(status, created_at)
) ENGINE=InnoDB;

CREATE TABLE order_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    return_number VARCHAR(40) NOT NULL UNIQUE,
    status ENUM('requested','approved','received','refunded','rejected') NOT NULL DEFAULT 'requested',
    reason VARCHAR(255) NULL,
    refund_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX(status, created_at)
) ENGINE=InnoDB;

CREATE TABLE order_shipments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    courier VARCHAR(120) NULL,
    tracking_number VARCHAR(160) NULL,
    tracking_url VARCHAR(500) NULL,
    status ENUM('pending','packed','shipped','out_for_delivery','delivered','failed') NOT NULL DEFAULT 'pending',
    shipped_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX(status, created_at),
    INDEX(tracking_number)
) ENGINE=InnoDB;

CREATE TABLE order_fulfillments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('reserved','picked','packed','shipped','cancelled') NOT NULL DEFAULT 'reserved',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY(order_item_id) REFERENCES order_items(id) ON DELETE SET NULL,
    INDEX(order_id, status)
) ENGINE=InnoDB;

CREATE TABLE inventory_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    status ENUM('active','released','fulfilled') NOT NULL DEFAULT 'active',
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX(product_id, status),
    INDEX(expires_at)
) ENGINE=InnoDB;

CREATE TABLE secret_rotation_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secret_name VARCHAR(120) NOT NULL,
    owner VARCHAR(120) NULL,
    status ENUM('due','scheduled','rotated','blocked') NOT NULL DEFAULT 'due',
    due_at DATE NOT NULL,
    rotated_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(status, due_at)
) ENGINE=InnoDB;

CREATE TABLE performance_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_path VARCHAR(255) NOT NULL,
    device VARCHAR(40) NOT NULL DEFAULT 'unknown',
    lcp_ms INT UNSIGNED NULL,
    inp_ms INT UNSIGNED NULL,
    cls DECIMAL(8,4) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(page_path, created_at)
) ENGINE=InnoDB;

CREATE TABLE accessibility_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_path VARCHAR(255) NOT NULL,
    keyboard_status ENUM('pass','partial','fail') NOT NULL DEFAULT 'partial',
    focus_status ENUM('pass','partial','fail') NOT NULL DEFAULT 'partial',
    contrast_status ENUM('pass','partial','fail') NOT NULL DEFAULT 'partial',
    screen_reader_status ENUM('pass','partial','fail') NOT NULL DEFAULT 'partial',
    reduced_motion_status ENUM('pass','partial','fail') NOT NULL DEFAULT 'partial',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(page_path, created_at)
) ENGINE=InnoDB;
