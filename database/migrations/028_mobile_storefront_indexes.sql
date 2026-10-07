SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_newest');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_newest ON products (status, created_at, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_featured');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_featured ON products (status, featured, updated_at, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_category');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_category ON products (status, category_id, created_at, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_brand');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_brand ON products (status, brand_id, created_at, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_gender');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_gender ON products (status, gender, created_at, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_price');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_price ON products (status, price, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_sale');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_sale ON products (status, compare_at_price, price, updated_at, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='products' AND index_name='idx_products_mobile_slug_status');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_products_mobile_slug_status ON products (slug, status)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='product_collections' AND index_name='idx_product_collections_mobile_lookup');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_product_collections_mobile_lookup ON product_collections (collection_id, product_id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='product_variants' AND index_name='idx_product_variants_mobile_attributes');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_product_variants_mobile_attributes ON product_variants (product_id, quantity)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='order_items' AND index_name='idx_order_items_mobile_sales');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_order_items_mobile_sales ON order_items (product_id, quantity)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='orders' AND index_name='idx_orders_mobile_customer_status');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_orders_mobile_customer_status ON orders (user_id, status, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='orders' AND index_name='idx_orders_mobile_customer_payment');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_orders_mobile_customer_payment ON orders (user_id, payment_status, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='customer_addresses' AND index_name='idx_customer_addresses_mobile_default');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_customer_addresses_mobile_default ON customer_addresses (user_id, is_default, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='payment_methods' AND index_name='idx_payment_methods_mobile_default');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_payment_methods_mobile_default ON payment_methods (user_id, is_default, id)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='customer_reviews' AND index_name='idx_customer_reviews_mobile_user_date');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_customer_reviews_mobile_user_date ON customer_reviews (user_id, created_at)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='customer_reviews' AND index_name='idx_customer_reviews_mobile_product_status');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_customer_reviews_mobile_product_status ON customer_reviews (product_id, status, created_at)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='support_tickets' AND index_name='idx_support_tickets_mobile_user_date');
SET @sql=IF(@idx_exists=0,'CREATE INDEX idx_support_tickets_mobile_user_date ON support_tickets (user_id, created_at)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
