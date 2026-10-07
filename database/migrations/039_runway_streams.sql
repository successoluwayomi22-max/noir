-- Migration 039: VIP Runway Live Stream & Video Commerce
CREATE TABLE IF NOT EXISTS runway_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    host_name VARCHAR(120) DEFAULT 'NOIR Creative Director',
    description TEXT DEFAULT NULL,
    stream_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255) DEFAULT NULL,
    status ENUM('live', 'upcoming', 'replay') DEFAULT 'upcoming',
    scheduled_at DATETIME DEFAULT NULL,
    live_viewers INT DEFAULT 0,
    total_views INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS runway_stream_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stream_id INT NOT NULL,
    product_id INT NOT NULL,
    timestamp_seconds INT DEFAULT 0,
    discount_percentage INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (stream_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS runway_stream_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stream_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    reaction_type ENUM('comment', 'heart', 'fire', 'gem', 'clap') DEFAULT 'comment',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (stream_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default live runway stream and upcoming session
INSERT IGNORE INTO runway_streams (id, title, slug, host_name, description, stream_url, thumbnail_url, status, scheduled_at, live_viewers, total_views) VALUES
(1, 'Milan Fashion Week FW26: The Obsidian Collection', 'milan-fw26-obsidian', 'Alessandro Rossi', 'Live from Milan. Introducing bespoke architectural silhouettes, raw Japanese denim, and 24K gold accents.', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=1200&q=80', 'live', NOW(), 142, 3890),
(2, 'Paris Haute Couture Preview: Silk & Structural Tailoring', 'paris-couture-silk', 'Valerie Dupont', 'An intimate salon preview of handcrafted silk outerwear and tailored evening gowns.', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=1200&q=80', 'upcoming', DATE_ADD(NOW(), INTERVAL 2 DAY), 0, 0);

-- Seed pinned products for the live stream
INSERT IGNORE INTO runway_stream_products (stream_id, product_id, timestamp_seconds, discount_percentage, is_featured, sort_order) VALUES
(1, 1, 30, 15, 1, 1),
(1, 2, 90, 10, 1, 2),
(1, 3, 180, 20, 1, 3);
