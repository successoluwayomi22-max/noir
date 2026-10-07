UPDATE categories
SET is_active=FALSE
WHERE slug='clothing' AND NOT EXISTS (
    SELECT 1 FROM products WHERE products.category_id=categories.id AND products.status='published'
);
