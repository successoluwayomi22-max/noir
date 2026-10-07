CREATE TABLE video_commerce (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    user VARCHAR(100) NOT NULL,
    likes_count INT NOT NULL DEFAULT 0,
    comments_count INT NOT NULL DEFAULT 0,
    products_count INT NOT NULL DEFAULT 0,
    url VARCHAR(500) NOT NULL,
    status ENUM('active', 'draft') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert some dummy data
INSERT INTO video_commerce (title, user, likes_count, comments_count, products_count, url, status) VALUES
('Summer Collection Drop 1 ☀️', '@noir.official', 12400, 342, 3, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 'active'),
('Styling the new Sage Green pieces', '@noir.styling', 8100, 156, 1, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 'active'),
('Behind the scenes: Editorial shoot', '@noir.official', 24500, 890, 4, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4', 'active');
