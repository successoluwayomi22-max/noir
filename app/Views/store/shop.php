<?php
declare(strict_types=1);
$publicPath=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
$auth=\Noir\Core\Container::get('auth');$user=$auth->user()??[];$isCustomer=($user['account_type']??null)==='customer'||in_array('customer',$user['roles']??[],true);$profilePath=$publicPath.($isCustomer?'/customer':'/login');
$cartCount=array_sum(array_map(static fn(array $item):int=>(int)($item['quantity']??0),$_SESSION['cart']??[]));
$card=static function(array $product)use($publicPath):void{require BASE_PATH.'/app/Views/store/product-card.php';};
$section=static function(string $label,string $title,array $products)use($card):void{if(!$products)return;echo '<section class="shop-section"><div class="shop-section-heading"><div><p class="shop-eyebrow">'.e($label).'</p><h2>'.e($title).'</h2></div></div><div class="shop-product-grid">';foreach($products as $product)$card($product);echo '</div></section>';};
$heroImage=$collectionHero??$categoryHero??($hero['featured_image']??'');$heroTitle=$pageTitle??'Shop the collection';$storeCss=$publicPath.'/assets/css/store.min.css?v=1.2.5';$storeName=$storeName??store_setting('store_name','Noir & Co.');$catalogueOnly=!empty($catalogueOnly);$showHero=!$catalogueOnly||!empty($showCatalogueHero);$activeSort=(string)($sort??($_GET['sort']??'newest'));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?= store_seo_meta(title: $heroTitle, description: $pageDescription ?? (store_setting('store_name', 'Noir & Co.') . ' collection'), image: $heroImage) ?><link rel="preload" as="style" href="<?= e(($publicPath?:'').'/assets/css/app.min.css?v=1.2.5') ?>"><link rel="preload" as="style" href="<?= e($storeCss) ?>"><link rel="preload" as="style" href="<?= e(($publicPath?:'').'/assets/css/responsive.min.css?v=1.2.5') ?>"><script defer src="<?= e(($publicPath?:'').'/assets/js/lucide.min.js?v=1.0.0') ?>"></script><?= store_brand_style() ?><link rel="stylesheet" href="<?= e(($publicPath?:'').'/assets/css/app.min.css?v=1.2.5') ?>"><link rel="stylesheet" href="<?= e($storeCss) ?>"><link rel="stylesheet" href="<?= e(($publicPath?:'').'/assets/css/responsive.min.css?v=1.2.5') ?>"><?= ai_assistant_assets($publicPath) ?></head><body class="shop-page<?= $catalogueOnly ? ' catalogue-only-page' : '' ?>">
<?php require __DIR__ . '/components/header.php'; ?>
<main><?php if($showHero): ?><?php if($heroImage): ?><section class="shop-hero"><img src="<?= e(media_url($publicPath,$heroImage)) ?>" alt="<?= e($heroTitle) ?>" loading="eager" onerror="this.onerror=null;this.src='<?= e($publicPath . '/uploads/store/hero-editorial.jpg') ?>'"><div class="shop-hero-overlay"><p class="shop-eyebrow">The latest edit</p><h1><?= e($heroTitle) ?></h1><p><?= e($pageDescription??'Considered silhouettes and quiet details for the life you actually live.') ?></p><a href="#catalogue" class="shop-button">Shop collection </a></div></section><?php else: ?><section class="shop-hero shop-hero-empty"><div><p class="shop-eyebrow"><?= e(store_setting('store_name', 'Noir & Co.')) ?></p><h1><?= e($heroTitle) ?></h1><p><?= e($pageDescription??'Considered silhouettes and quiet details.') ?></p><a href="#catalogue" class="shop-button">Explore pieces </a></div></section><?php endif; ?><?php endif; ?><?php if(!$catalogueOnly): ?>
<?php if(!empty($collections)): ?><section id="collections" class="shop-section"><div class="shop-section-heading"><div><p class="shop-eyebrow">The edits</p><h2>Find your point of view.</h2></div></div><div class="shop-collection-grid"><?php foreach($collections as $collection): $collectionImage = !empty($collection['image']) ? $collection['image'] : collection_image_for((string) $collection['name']); ?><a href="<?= e($publicPath.'/collections/'.$collection['slug']) ?>" class="shop-collection-card"><div><?php if($collectionImage): ?><img src="<?= e(media_url($publicPath,$collectionImage)) ?>" alt="<?= e($collection['name']) ?>" loading="lazy"><?php else: ?><span><?= e(strtoupper(substr($collection['name'],0,1))) ?></span><?php endif; ?></div><strong><?= e($collection['name']) ?></strong><small><?= number_format((int)$collection['product_count']) ?> pieces </small></a><?php endforeach; ?></div></section><?php endif; ?>
<?php if(!empty($categories)): ?><section id="categories" class="shop-category-section"><div><p class="shop-eyebrow">Shop by category</p><h2>Build your uniform.</h2><p>Explore the collection by form, function, and feeling.</p></div><div class="shop-category-grid"><?php foreach($categories as $category): $categoryImage = catalogue_image_for(['name' => $category['name']]); ?><a href="<?= e($publicPath.'/categories/'.$category['slug']) ?>"><div class="shop-category-image"><?php if($categoryImage): ?><img src="<?= e(media_url($publicPath,$categoryImage)) ?>" alt="<?= e($category['name']) ?>" loading="lazy"><?php else: ?><span><?= e(strtoupper(substr($category['name'],0,1))) ?></span><?php endif; ?></div><div><strong><?= e($category['name']) ?></strong><small><?= number_format((int)$category['product_count']) ?> pieces </small></div></a><?php endforeach; ?></div></section><?php endif; ?>
<?php $section('New arrivals','The latest pieces',$newArrivals??[]);$section('Best sellers','Most wanted',$bestSellers??[]);$section('Featured','Selected for you',$featuredProducts??[]);$section('On offer','A considered price',$saleProducts??[]); ?><?php endif; ?>
<section id="catalogue" class="shop-catalogue" tabindex="-1"><div class="shop-catalogue-heading"><div><p class="shop-eyebrow"><?= __('The catalogue') ?></p><h2><?= e($heroTitle) ?></h2><p><?= number_format((int)$pagination['total']) ?> <?= __('pieces in this edit') ?></p></div><button class="shop-filter-toggle" type="button" onclick="document.body.classList.toggle('filters-open')" aria-controls="shop-filters"><?= __('Filters') ?></button></div><form id="shop-filters" class="shop-filters" method="get" action="<?= e($publicPath.$contextUrl) ?>#catalogue"><label class="shop-search"><i data-lucide="search"></i><input name="search" value="<?= e((string)($_GET['search']??'')) ?>" placeholder="<?= e(__('Search tees, hoodies, bags...')) ?>" aria-label="Search products"></label><label for="shop-category-select" class="sr-only"><?= __('Filter by category') ?></label><select id="shop-category-select" name="category" aria-label="Filter by category"><option value=""><?= __('All categories') ?></option><?php foreach($categories as $category): $categoryImage = catalogue_image_for(['name' => $category['name']]); ?><option value="<?= e($category['id']) ?>" <?= (string)($_GET['category']??'')===(string)$category['id']?'selected':'' ?>><?= e(__($category['name'])) ?></option><?php endforeach; ?></select><label for="shop-brand-select" class="sr-only"><?= __('Filter by brand') ?></label><select id="shop-brand-select" name="brand" aria-label="Filter by brand"><option value=""><?= __('All brands') ?></option><?php foreach($brands as $brand): ?><option value="<?= e($brand['id']) ?>" <?= (string)($_GET['brand']??'')===(string)$brand['id']?'selected':'' ?>><?= e($brand['name']) ?></option><?php endforeach; ?></select><label for="shop-gender-select" class="sr-only"><?= __('Filter by fit') ?></label><select id="shop-gender-select" name="gender" aria-label="Filter by fit"><option value=""><?= __('All genders') ?></option><?php foreach($genders as $gender): ?><option value="<?= e($gender) ?>" <?= ($_GET['gender']??'')===$gender?'selected':'' ?>><?= e(__($gender)) ?></option><?php endforeach; ?></select><label for="shop-sort-select" class="sr-only"><?= __('Sort products') ?></label><select id="shop-sort-select" name="sort" aria-label="Sort products"><option value="newest" <?= $activeSort==='newest'?'selected':'' ?>><?= __('Newest') ?></option><option value="popular" <?= $activeSort==='popular'?'selected':'' ?>><?= __('Best selling') ?></option><option value="price_low" <?= $activeSort==='price_low'?'selected':'' ?>><?= __('Price: low to high') ?></option><option value="price_high" <?= $activeSort==='price_high'?'selected':'' ?>><?= __('Price: high to low') ?></option></select><button class="shop-filter-button" type="submit"><?= __('Apply') ?></button></form><div class="recent-searches" data-recent-searches data-search-base="<?= e($publicPath.$contextUrl) ?>" hidden></div><?php if($catalog): ?><div class="shop-product-grid shop-catalogue-grid"><?php foreach($catalog as $product)$card($product); ?></div><?php else: ?><div class="shop-empty"><?= __('No pieces match your selection.') ?></div><?php endif; ?><?php if($pagination['pages']>1): ?><div class="shop-pagination"><span>Page <?= $pagination['page'] ?> of <?= $pagination['pages'] ?></span><div><?php if($pagination['page']>1): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$pagination['page']-1])) ?>#catalogue"><?= __('Previous') ?></a><?php endif; ?><?php if($pagination['page']<$pagination['pages']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$pagination['page']+1])) ?>#catalogue"><?= __('Next') ?></a><?php endif; ?></div></div><?php endif; ?></section><?php if(!$catalogueOnly): ?><div class="sticky-mobile-cta"><a href="#catalogue" class="shop-button"><?= __('Shop collection') ?></a></div><?php endif; ?></main>
<?php require __DIR__ . '/components/footer.php'; ?>
<style>.sticky-mobile-cta{display:none}@media(max-width:700px){body{padding-bottom:70px}.sticky-mobile-cta{display:block;position:fixed;bottom:0;left:0;right:0;padding:15px;background:var(--brand-paper);backdrop-filter:blur(10px);z-index:100;border-top:1px solid var(--brand-border)}.sticky-mobile-cta .shop-button{width:100%;display:flex;justify-content:center}}</style>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', () => {
    const filtersForm = document.getElementById('shop-filters');
    const catalogueSection = document.getElementById('catalogue');
    if (filtersForm && catalogueSection) {
        // Auto-submit form when selects change
        filtersForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => filtersForm.dispatchEvent(new Event('submit')));
        });
        
        // Debounce search input
        let searchTimeout;
        const searchInput = filtersForm.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => filtersForm.dispatchEvent(new Event('submit')), 400);
            });
        }
        
        filtersForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const url = new URL(filtersForm.action);
            const formData = new FormData(filtersForm);
            
            // Build query string
            for (const [key, value] of formData.entries()) {
                if (value) url.searchParams.append(key, value);
            }
            
            catalogueSection.style.opacity = '0.5';
            catalogueSection.style.pointerEvents = 'none';
            
            try {
                const response = await fetch(url);
                if (response.ok) {
                    const text = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(text, 'text/html');
                    const newCatalogue = doc.getElementById('catalogue');
                    
                    if (newCatalogue) {
                        catalogueSection.innerHTML = newCatalogue.innerHTML;
                        // Update URL without page reload
                        window.history.pushState({}, '', url);
                    }
                }
            } catch (error) {
                console.error('Filter fetch error:', error);
            } finally {
                catalogueSection.style.opacity = '1';
                catalogueSection.style.pointerEvents = 'auto';
            }
        });

        // Intercept pagination clicks
        catalogueSection.addEventListener('click', async (e) => {
            const link = e.target.closest('.shop-pagination a');
            if (link) {
                e.preventDefault();
                catalogueSection.style.opacity = '0.5';
                catalogueSection.style.pointerEvents = 'none';
                
                try {
                    const response = await fetch(link.href);
                    if (response.ok) {
                        const text = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(text, 'text/html');
                        const newCatalogue = doc.getElementById('catalogue');
                        
                        if (newCatalogue) {
                            catalogueSection.innerHTML = newCatalogue.innerHTML;
                            window.history.pushState({}, '', link.href);
                            
                            // Scroll to top of catalogue smoothly
                            catalogueSection.scrollIntoView({ behavior: 'smooth' });
                        }
                    }
                } catch (error) {
                    console.error('Pagination fetch error:', error);
                } finally {
                    catalogueSection.style.opacity = '1';
                    catalogueSection.style.pointerEvents = 'auto';
                }
            }
        });
    }
});
</script>
</body></html>
