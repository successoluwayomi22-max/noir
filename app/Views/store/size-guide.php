<?php
declare(strict_types=1);
$publicPath = $publicPath ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$storeName  = $storeName ?? store_setting('store_name', 'Noir & Co.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Size Guide — <?= e($storeName) ?></title>
    <link rel="stylesheet" href="<?= e($publicPath . '/assets/css/app.min.css') ?>">
    <?= store_seo_meta('Size Guide — ' . $storeName, 'Find your perfect fit with the ' . $storeName . ' size guide. Detailed measurements for clothing, dresses, outerwear, and accessories.') ?>
    <?= store_brand_style() ?>
</head>
<body class="store-page">
<?php require __DIR__ . '/components/header.php'; ?>
<main class="size-guide-page">
    <?= store_breadcrumbs([['name' => 'Home', 'url' => $publicPath . '/'], ['name' => 'Size Guide', 'url' => '']]) ?>

    <div class="size-guide-hero">
        <p class="eyebrow">Fit & Sizing</p>
        <h1>Find your size.</h1>
        <p>All measurements are in centimetres (cm) unless stated. If you are between sizes, we recommend sizing up for a relaxed fit or sizing down for a closer cut.</p>
    </div>

    <div class="size-guide-tabs" id="size-tabs">
        <button class="size-tab active" data-tab="women">Women</button>
        <button class="size-tab" data-tab="men">Men</button>
        <button class="size-tab" data-tab="shoes">Shoes</button>
        <button class="size-tab" data-tab="accessories">Accessories</button>
    </div>

    <!-- Women -->
    <section class="size-section active" id="tab-women">
        <h2>Women's Clothing</h2>
        <div class="size-table-wrap">
            <table class="size-table">
                <thead><tr><th>Size</th><th>UK</th><th>EU</th><th>US</th><th>Bust (cm)</th><th>Waist (cm)</th><th>Hips (cm)</th></tr></thead>
                <tbody>
                    <tr><td>XS</td><td>6</td><td>34</td><td>2</td><td>81–84</td><td>63–66</td><td>88–91</td></tr>
                    <tr><td>S</td><td>8</td><td>36</td><td>4</td><td>85–88</td><td>67–70</td><td>92–95</td></tr>
                    <tr><td>M</td><td>10</td><td>38</td><td>6</td><td>89–92</td><td>71–74</td><td>96–99</td></tr>
                    <tr><td>L</td><td>12</td><td>40</td><td>8</td><td>93–96</td><td>75–78</td><td>100–103</td></tr>
                    <tr><td>XL</td><td>14</td><td>42</td><td>10</td><td>97–100</td><td>79–82</td><td>104–107</td></tr>
                    <tr><td>XXL</td><td>16</td><td>44</td><td>12</td><td>101–106</td><td>83–88</td><td>108–113</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Men -->
    <section class="size-section" id="tab-men">
        <h2>Men's Clothing</h2>
        <div class="size-table-wrap">
            <table class="size-table">
                <thead><tr><th>Size</th><th>UK/EU</th><th>US</th><th>Chest (cm)</th><th>Waist (cm)</th><th>Hips (cm)</th></tr></thead>
                <tbody>
                    <tr><td>XS</td><td>44</td><td>34</td><td>84–88</td><td>72–76</td><td>84–88</td></tr>
                    <tr><td>S</td><td>46</td><td>36</td><td>89–93</td><td>77–81</td><td>89–93</td></tr>
                    <tr><td>M</td><td>48</td><td>38</td><td>94–98</td><td>82–86</td><td>94–98</td></tr>
                    <tr><td>L</td><td>50</td><td>40</td><td>99–103</td><td>87–91</td><td>99–103</td></tr>
                    <tr><td>XL</td><td>52</td><td>42</td><td>104–108</td><td>92–96</td><td>104–108</td></tr>
                    <tr><td>XXL</td><td>54</td><td>44</td><td>109–114</td><td>97–102</td><td>109–114</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Shoes -->
    <section class="size-section" id="tab-shoes">
        <h2>Footwear Sizing</h2>
        <div class="size-table-wrap">
            <table class="size-table">
                <thead><tr><th>EU</th><th>UK</th><th>US (W)</th><th>US (M)</th><th>Foot length (cm)</th></tr></thead>
                <tbody>
                    <tr><td>36</td><td>3</td><td>5.5</td><td>4</td><td>22.5</td></tr>
                    <tr><td>37</td><td>4</td><td>6.5</td><td>5</td><td>23.5</td></tr>
                    <tr><td>38</td><td>5</td><td>7.5</td><td>6</td><td>24.0</td></tr>
                    <tr><td>39</td><td>6</td><td>8.5</td><td>7</td><td>25.0</td></tr>
                    <tr><td>40</td><td>6.5</td><td>9</td><td>7.5</td><td>25.5</td></tr>
                    <tr><td>41</td><td>7</td><td>10</td><td>8</td><td>26.0</td></tr>
                    <tr><td>42</td><td>8</td><td>10.5</td><td>9</td><td>27.0</td></tr>
                    <tr><td>43</td><td>9</td><td>11.5</td><td>10</td><td>27.5</td></tr>
                    <tr><td>44</td><td>9.5</td><td>12</td><td>10.5</td><td>28.0</td></tr>
                    <tr><td>45</td><td>10.5</td><td>13</td><td>11.5</td><td>29.0</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Accessories -->
    <section class="size-section" id="tab-accessories">
        <h2>Accessories</h2>
        <div class="size-cards">
            <div class="size-card">
                <h3>Belts</h3>
                <table class="size-table">
                    <thead><tr><th>Label</th><th>Waist range (cm)</th></tr></thead>
                    <tbody>
                        <tr><td>S</td><td>70–80</td></tr>
                        <tr><td>M</td><td>80–90</td></tr>
                        <tr><td>L</td><td>90–100</td></tr>
                        <tr><td>XL</td><td>100–110</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="size-card">
                <h3>Hats & Caps</h3>
                <table class="size-table">
                    <thead><tr><th>Label</th><th>Head circumference (cm)</th></tr></thead>
                    <tbody>
                        <tr><td>S/M</td><td>54–57</td></tr>
                        <tr><td>L/XL</td><td>58–61</td></tr>
                        <tr><td>One Size</td><td>Adjustable 52–62</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="size-card">
                <h3>Gloves</h3>
                <table class="size-table">
                    <thead><tr><th>Label</th><th>Hand circumference (cm)</th></tr></thead>
                    <tbody>
                        <tr><td>S</td><td>17–18</td></tr>
                        <tr><td>M</td><td>19–20</td></tr>
                        <tr><td>L</td><td>21–22</td></tr>
                        <tr><td>XL</td><td>23–24</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="size-measure-guide">
        <h2>How to measure</h2>
        <div class="measure-steps">
            <div><span>1</span><strong>Bust / Chest</strong><p>Measure around the fullest part of your chest, keeping the tape horizontal.</p></div>
            <div><span>2</span><strong>Waist</strong><p>Measure around your natural waistline, the narrowest part of your torso.</p></div>
            <div><span>3</span><strong>Hips</strong><p>Measure around the fullest part of your hips, approximately 20 cm below your waist.</p></div>
            <div><span>4</span><strong>Foot length</strong><p>Stand on paper, trace your foot, and measure from heel to longest toe.</p></div>
        </div>
    </div>

    <div class="size-need-help">
        <p>Still unsure? <a href="<?= e($publicPath . '/contact') ?>">Message our styling team</a> — we respond within 1 business day.</p>
    </div>
</main>
<?php require __DIR__ . '/components/footer.php'; ?>
<style>
.size-guide-page { padding: clamp(40px,6vw,80px) clamp(16px,5vw,80px); background: var(--brand-paper); color: var(--brand-dark); }
.size-guide-hero { max-width: 700px; margin-bottom: 48px; }
.size-guide-hero h1 { margin: 12px 0; font-size: clamp(48px,7vw,100px); font-weight: 500; letter-spacing: -0.05em; line-height: 1; }
.size-guide-hero p { font-size: 15px; color: var(--brand-muted); line-height: 1.7; max-width: 540px; }
.size-guide-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 32px; background: var(--brand-surface); padding: 6px; border-radius: 14px; max-width: 500px; }
.size-tab { border: 0; background: transparent; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; font-family: inherit; color: var(--brand-muted); transition: all 0.2s; }
.size-tab.active { background: var(--brand-dark); color: #fff; }
.size-section { display: none; }
.size-section.active { display: block; }
.size-section h2 { font-size: 18px; font-weight: 700; margin-bottom: 20px; }
.size-table-wrap { overflow-x: auto; border-radius: 14px; border: 1px solid var(--brand-border); }
.size-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.size-table thead th { background: var(--brand-dark); color: #fff; padding: 12px 16px; text-align: left; font-weight: 700; letter-spacing: 0.04em; font-size: 11px; text-transform: uppercase; white-space: nowrap; }
.size-table tbody tr:nth-child(even) { background: var(--brand-surface); }
.size-table tbody td { padding: 12px 16px; border-bottom: 1px solid var(--brand-border); white-space: nowrap; }
.size-table tbody tr:last-child td { border-bottom: 0; }
.size-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 20px; }
.size-card { border: 1px solid var(--brand-border); border-radius: 14px; overflow: hidden; }
.size-card h3 { padding: 14px 16px; font-size: 14px; font-weight: 700; border-bottom: 1px solid var(--brand-border); margin: 0; }
.size-measure-guide { margin-top: 64px; padding-top: 48px; border-top: 1px solid var(--brand-border); }
.size-measure-guide h2 { font-size: 22px; font-weight: 700; margin-bottom: 28px; }
.measure-steps { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 24px; }
.measure-steps > div { display: grid; gap: 8px; }
.measure-steps span { width: 32px; height: 32px; border-radius: 50%; background: var(--brand-dark); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; }
.measure-steps strong { font-size: 14px; font-weight: 700; }
.measure-steps p { font-size: 13px; color: var(--brand-muted); line-height: 1.6; margin: 0; }
.size-need-help { margin-top: 48px; text-align: center; padding: 32px; background: var(--brand-surface); border-radius: 16px; }
.size-need-help p { font-size: 15px; margin: 0; }
</style>
<script>
document.querySelectorAll('.size-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.size-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.size-section').forEach(s => s.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});
</script>
</body>
</html>
