-- Restore collection/category tile images after removing the legacy blue portraits.
UPDATE categories SET image = CASE slug
    WHEN 't-shirts' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80'
    WHEN 'shirts' THEN 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=1200&q=80'
    WHEN 'hoodies' THEN 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=1200&q=80'
    WHEN 'sweatshirts' THEN 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=1200&q=80'
    WHEN 'jackets' THEN 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1200&q=80'
    WHEN 'coats' THEN 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?auto=format&fit=crop&w=1200&q=80'
    WHEN 'jeans' THEN 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=1200&q=80'
    WHEN 'trousers' THEN 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=1200&q=80'
    WHEN 'shorts' THEN 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=1200&q=80'
    WHEN 'sneakers' THEN 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=80'
    WHEN 'bags' THEN 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1200&q=80'
    WHEN 'accessories' THEN 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=1200&q=80'
    ELSE image
END
WHERE slug IN ('t-shirts','shirts','hoodies','sweatshirts','jackets','coats','jeans','trousers','shorts','sneakers','bags','accessories');

UPDATE collections SET image = CASE slug
    WHEN 'essentials' THEN 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80'
    WHEN 'new-arrivals' THEN 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=1200&q=80'
    WHEN 'premium-collection' THEN 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1200&q=80'
    WHEN 'streetwear' THEN 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=1200&q=80'
    WHEN 'limited-edition' THEN 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=1200&q=80'
    WHEN 'summer-collection' THEN 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=1200&q=80'
    WHEN 'winter-collection' THEN 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?auto=format&fit=crop&w=1200&q=80'
    WHEN 'best-sellers' THEN 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=80'
    ELSE image
END
WHERE slug IN ('essentials','new-arrivals','premium-collection','streetwear','limited-edition','summer-collection','winter-collection','best-sellers');
