-- Migration 044: Add High-Performance MySQL Full-Text Search Index on Products

CREATE FULLTEXT INDEX idx_products_fulltext ON products (name, short_description, brand, category);
