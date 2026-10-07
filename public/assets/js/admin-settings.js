(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('.settings-form');
        var palette = document.querySelector('.palette-samples');
        var adminBrand = document.querySelector('.admin-sidebar .brand');
        var storeName = (document.title.replace(/^Settings\s*-\s*/i, '').trim() || 'Noir & Co.');
        if (adminBrand) adminBrand.textContent = storeName;
        var saveLabel = document.querySelector('.settings-actions .button span');
        if (saveLabel) saveLabel.remove();
        if (!form || !palette || form.querySelector('[name="hero_established"]')) return;
        var meta = document.querySelector('meta[name="hero-established"]');
        var card = document.createElement('section');
        card.className = 'settings-card';
        card.innerHTML = '<div class="settings-heading"><p class="eyebrow">Homepage</p><h2>Store details</h2><p>Edit the establishment year shown beside the homepage artwork.</p></div><div class="settings-fields"><label>Established year<input type="text" name="hero_established" maxlength="20" value="' + (meta ? meta.content : '2026') + '"></label></div>';
        form.insertBefore(card, palette);

        var palettes = [
            ['Default Noir', '#687158', '#252820', '#b9c1a4', 'Sage Green', 'Charcoal Black', 'Soft Olive'],
            ['Slate & Sand', '#52616b', '#1f2933', '#d7b98e', 'Slate Blue', 'Deep Slate', 'Warm Sand'],
            ['Rosewood', '#7b5e57', '#302522', '#d8b4a0', 'Dusty Rosewood', 'Espresso Brown', 'Blush Sand'],
            ['Forest', '#496b5b', '#1f3028', '#b9d0b7', 'Forest Green', 'Deep Pine', 'Pale Sage'],
            ['Ocean', '#3d6680', '#1d2b36', '#a9c8d8', 'Muted Ocean Blue', 'Deep Navy', 'Mist Blue'],
            ['Terracotta', '#a65f45', '#38251f', '#e4b29b', 'Burnt Terracotta', 'Cocoa Brown', 'Peach Clay'],
            ['Plum', '#6a5b86', '#2c263b', '#c6b7df', 'Muted Plum', 'Aubergine', 'Soft Lilac'],
            ['Monochrome', '#3f474b', '#171a1c', '#c5c9c7', 'Graphite', 'Ink Black', 'Silver Grey']
        ];
        var buttons = Array.from(palette.querySelectorAll('button'));
        var guide = document.createElement('div');
        guide.className = 'palette-usage';
        palette.insertAdjacentElement('afterend', guide);
        function syncPalette() {
            var values = [
                document.querySelector('[name="brand_primary_color"]').value.toLowerCase(),
                document.querySelector('[name="brand_dark_color"]').value.toLowerCase(),
                document.querySelector('[name="brand_accent_color"]').value.toLowerCase()
            ];
            var active = palettes.findIndex(function (item) { return item[1] === values[0] && item[2] === values[1] && item[3] === values[2]; });
            buttons.forEach(function (button, index) {
                button.classList.toggle('palette-active', index === active);
                button.setAttribute('aria-pressed', index === active ? 'true' : 'false');
            });
            var item = palettes[active >= 0 ? active : 0];
            guide.innerHTML = '<strong>Active palette: ' + item[0] + '</strong><span><i style="background:' + item[1] + '"></i>' + item[4] + ' — buttons, links and active controls</span><span><i style="background:' + item[2] + '"></i>' + item[5] + ' — headers, hero sections and dark surfaces</span><span><i style="background:' + item[3] + '"></i>' + item[6] + ' — accents, badges and secondary actions</span>';
        }
        buttons.forEach(function (button) { button.addEventListener('click', function () { setTimeout(syncPalette, 0); }); });
        ['brand_primary_color', 'brand_dark_color', 'brand_accent_color'].forEach(function (name) {
            document.querySelector('[name="' + name + '"]').addEventListener('input', syncPalette);
        });
        syncPalette();
    });
}());
