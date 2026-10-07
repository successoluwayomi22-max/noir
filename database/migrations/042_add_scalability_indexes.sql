-- Migration 042: Add Scalability & Concurrency Composite Indexes

-- Optimize stock availability queries
CREATE INDEX IF NOT EXISTS idx_prod_status_qty ON products (status, quantity);
CREATE INDEX IF NOT EXISTS idx_prod_sku ON products (sku);

-- Optimize cart lookup and sorting
CREATE INDEX IF NOT EXISTS idx_cart_user_updated ON customer_cart_items (user_id, updated_at);

-- Optimize wishlist lookup
CREATE INDEX IF NOT EXISTS idx_wishlist_user_created ON customer_wishlist (user_id, created_at);

-- Optimize firewall IP lookup
CREATE INDEX IF NOT EXISTS idx_block_ip_until ON blocked_ips (ip_address, blocked_until);

-- Optimize audit log ordering
CREATE INDEX IF NOT EXISTS idx_logs_created_id ON activity_logs (created_at, id);
