-- Give each tee finish its own coloured catalog image instead of reusing one white tee.
UPDATE products
SET featured_image = CASE
    WHEN LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%obsidian%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80&blend=111827&blend-mode=color&blend-alpha=58'
    WHEN LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%ink%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80&blend=1f2a3a&blend-mode=color&blend-alpha=58'
    WHEN LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%olive%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80&blend=758064&blend-mode=color&blend-alpha=58'
    WHEN LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%tobacco%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80&blend=8b5e3c&blend-mode=color&blend-alpha=58'
    WHEN LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%mushroom%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80&blend=9a8e7a&blend-mode=color&blend-alpha=58'
    WHEN LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%stone%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80&blend=b8b2a4&blend-mode=color&blend-alpha=58'
    WHEN LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%ash%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80&blend=71747a&blend-mode=color&blend-alpha=58'
    ELSE featured_image
END
WHERE LOWER(CONCAT_WS(' ', name, tags, short_description, description)) LIKE '%tee%';

UPDATE product_images pi
JOIN products p ON p.id = pi.product_id
SET pi.path = p.featured_image,
    pi.original_name = CONCAT(p.slug, '-catalogue.jpg')
WHERE LOWER(CONCAT_WS(' ', p.name, p.tags, p.short_description, p.description)) LIKE '%tee%';
