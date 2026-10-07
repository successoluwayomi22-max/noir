-- Remove the two original blue-background portrait assets from all catalog records.
SET @legacy_woman = '/uploads/products/de0b224c304be0f556090189.png';
SET @legacy_man = '/uploads/products/098a7d0e542fe1ff393a121a.png';

UPDATE brands SET image = NULL WHERE image IN (@legacy_woman, @legacy_man);
UPDATE categories SET image = NULL WHERE image IN (@legacy_woman, @legacy_man);
UPDATE collections SET image = NULL WHERE image IN (@legacy_woman, @legacy_man);

UPDATE products p
SET p.featured_image = CASE
    WHEN LOWER(p.name) LIKE '%watch%' THEN 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%sunglasses%' THEN 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%beanie%' THEN 'https://images.unsplash.com/photo-1575428652377-a2d80e2277fc?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%scarf%' THEN 'https://images.unsplash.com/photo-1520903920243-00d872a2d1c9?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%cap%' THEN 'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%glove%' THEN 'https://images.unsplash.com/photo-1548883354-94bcfe321cbb?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%card holder%' THEN 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%key case%' THEN 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%belt%' THEN 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.name) LIKE '%tie%' THEN 'https://images.unsplash.com/photo-1598032895397-b9472444bf93?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(CONCAT(COALESCE(p.category, ''), ' ', p.name)) LIKE '%tee%'
      OR LOWER(p.category) LIKE '%t-shirt%' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%shirt%' THEN 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%hoodie%' THEN 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%sweatshirt%' THEN 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%jacket%' THEN 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%coat%' THEN 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%jean%' THEN 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%trouser%' THEN 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%short%' THEN 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%sneaker%' THEN 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=80'
    WHEN LOWER(p.category) LIKE '%bag%' THEN 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1200&q=80'
    ELSE 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=1200&q=80'
END
WHERE p.slug LIKE 'noir-%';

UPDATE product_images pi
JOIN products p ON p.id = pi.product_id
SET pi.path = p.featured_image,
    pi.original_name = CONCAT(p.slug, '-catalogue.jpg')
WHERE p.slug LIKE 'noir-%';
