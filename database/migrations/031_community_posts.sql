CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_handle VARCHAR(255) NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption TEXT NOT NULL,
    likes_count INT DEFAULT 0,
    products_count INT DEFAULT 0,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO community_posts (user_handle, image_url, caption, likes_count, products_count) VALUES
('@alexa_styles', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800', 'Loving the new Noir summer collection! The Sage Green is everything. 🌿 #NoirStyle', 1245, 2),
('@minimalist_guy', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&q=80&w=800', 'Charcoal essentials for the weekend.', 842, 1),
('@sophia.wears', 'https://images.unsplash.com/photo-1434389678232-04db801b67cd?auto=format&fit=crop&q=80&w=800', 'Soft olive tones matching the sunset. 🌅', 2109, 3);
