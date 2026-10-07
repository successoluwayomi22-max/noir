-- Replace the two generic seed images with category-appropriate, reusable Unsplash images.
-- Unsplash images are used under the Unsplash License: https://unsplash.com/license
SET @img_tshirts = 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80';
SET @img_shirts = 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=1200&q=80';
SET @img_hoodies = 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=1200&q=80';
SET @img_sweatshirts = 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=1200&q=80';
SET @img_jackets = 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1200&q=80';
SET @img_coats = 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?auto=format&fit=crop&w=1200&q=80';
SET @img_jeans = 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=1200&q=80';
SET @img_trousers = 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=1200&q=80';
SET @img_shorts = 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=1200&q=80';
SET @img_sneakers = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=80';
SET @img_bags = 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1200&q=80';
SET @img_accessories = 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=1200&q=80';

UPDATE products p
JOIN categories c ON c.id = p.category_id
SET p.featured_image = CASE c.slug
    WHEN 't-shirts' THEN @img_tshirts
    WHEN 'shirts' THEN @img_shirts
    WHEN 'hoodies' THEN @img_hoodies
    WHEN 'sweatshirts' THEN @img_sweatshirts
    WHEN 'jackets' THEN @img_jackets
    WHEN 'coats' THEN @img_coats
    WHEN 'jeans' THEN @img_jeans
    WHEN 'trousers' THEN @img_trousers
    WHEN 'shorts' THEN @img_shorts
    WHEN 'sneakers' THEN @img_sneakers
    WHEN 'bags' THEN @img_bags
    WHEN 'accessories' THEN @img_accessories
    ELSE p.featured_image
END
WHERE p.slug LIKE 'noir-%';

UPDATE product_images pi
JOIN products p ON p.id = pi.product_id
SET pi.path = p.featured_image,
    pi.original_name = CONCAT(p.slug, '-catalogue.jpg')
WHERE p.slug LIKE 'noir-%';
