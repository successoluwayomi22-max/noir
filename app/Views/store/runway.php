<?php
$publicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$products = $products ?? [];
$card = static function (array $product) use ($publicPath): void { require BASE_PATH . '/app/Views/store/product-card.php'; };
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VIP Runway Live Stream | <?= e(store_setting('store_name', 'Noir & Co.')) ?></title>
    <link rel="stylesheet" href="<?= e(($base ?: '') . '/assets/css/app.min.css') ?>">
    <script src="<?= e(($base ?: '') . '/assets/js/lucide.min.js?v=1.0.0') ?>"></script>
    <?= store_brand_style() ?>
    <style>
        body, html { height: 100%; margin: 0; overflow: hidden; background: #000; }
        .glass-panel { background: rgba(22, 24, 20, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        .video-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .video-container video { width: 100%; height: 100%; object-fit: cover; opacity: 0.85; }
        
        /* Product Card overrides for dark mode */
        .store-card { background: rgba(20, 20, 20, 0.7); border-color: rgba(255,255,255,0.1); }
        .store-card-title { color: #fff; }
        .store-card-price strong { color: #fff; }
        .store-card-media { background: #000; }
        .store-card:hover, .store-card:focus-within { border-color: rgba(255,255,255,0.3); }
        .store-card-cart { background: #fff; color: #000; }
        .store-card-cart:hover { background: #e0e0e0; }
    </style>
</head>
<body class="text-white relative">
    <!-- Background Video -->
    <div class="video-container">
        <!-- Using a placeholder luxury fashion video URL. In production, this would be the actual livestream HLS stream. -->
        <video autoplay loop muted playsinline>
            <source src="https://cdn.coverr.co/videos/coverr-fashion-model-posing-in-neon-lights-5632/1080p.mp4" type="video/mp4">
        </video>
    </div>

    <!-- Overlay Gradient for Readability -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/40 z-10 pointer-events-none"></div>

    <!-- Header -->
    <header class="absolute top-0 left-0 w-full p-6 z-20 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= e($publicPath . '/store') ?>" class="flex h-10 w-10 items-center justify-center rounded-full glass-panel hover:bg-white/10 transition">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold tracking-widest uppercase"><?= e(store_setting('store_name', 'Noir & Co.')) ?> Runway</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    <span class="text-xs font-semibold text-red-400 tracking-wider">LIVE</span>
                    <span class="text-xs text-white/60">• 12,408 watching</span>
                </div>
            </div>
        </div>
        <div class="flex gap-3">
            <button class="h-10 w-10 rounded-full glass-panel flex items-center justify-center hover:bg-white/10 transition">
                <i data-lucide="cast" class="h-4 w-4"></i>
            </button>
            <button class="h-10 px-4 rounded-full glass-panel flex items-center justify-center text-xs font-bold tracking-wider hover:bg-white hover:text-black transition">
                VIP BACKSTAGE
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="absolute inset-0 z-20 flex pt-24 pb-6 px-6 pointer-events-none">
        <!-- Left Side: Interactive Area -->
        <div class="flex-1 flex flex-col justify-end gap-6 pointer-events-auto">
            
            <!-- Shop the Look Carousel -->
            <div class="w-full max-w-2xl">
                <h2 class="text-sm font-bold uppercase tracking-widest text-white/80 mb-3 flex items-center gap-2">
                    <i data-lucide="shopping-bag" class="h-4 w-4"></i> Shop The Look
                </h2>
                <div class="flex gap-4 overflow-x-auto pb-4 snap-x chat-scroll">
                    <?php if (empty($products)): ?>
                        <div class="glass-panel rounded-xl p-6 text-center w-64 text-sm text-white/60">
                            Looks will appear here when the model steps on the runway.
                        </div>
                    <?php else: ?>
                        <?php foreach($products as $product): ?>
                            <div class="w-48 shrink-0 snap-start">
                                <?php $card($product); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Live Chat -->
        <div class="w-80 ml-6 flex flex-col glass-panel rounded-2xl overflow-hidden pointer-events-auto h-[calc(100vh-140px)] self-end">
            <div class="p-4 border-b border-white/10 flex justify-between items-center bg-black/20">
                <h3 class="text-sm font-semibold tracking-wider">VIP Chat</h3>
                <i data-lucide="users" class="h-4 w-4 text-white/60"></i>
            </div>
            
            <!-- Chat Messages -->
            <div id="chat-messages" class="flex-1 p-4 overflow-y-auto chat-scroll flex flex-col gap-4 text-sm">
                <div class="flex gap-2">
                    <span class="font-bold text-gray-300">Noir_Admin</span>
                    <span class="text-white">Welcome to the SS26 presentation.</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-bold text-[#e1c17e]">Elena</span>
                    <span class="text-white">The tailoring is impeccable.</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-bold text-gray-300">Marcus_T</span>
                    <span class="text-white">Need that jacket immediately!</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-bold text-[#e1c17e]">SarahK</span>
                    <span class="text-white">Obsessed with these textures.</span>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="p-3 border-t border-white/10 bg-black/40">
                <form id="chat-form" onsubmit="sendChatMessage(event)" class="relative">
                    <input type="text" id="chat-input" placeholder="Join the conversation..." 
                           class="w-full bg-white/10 border border-white/20 rounded-full py-2.5 pl-4 pr-10 text-sm text-white placeholder-white/40 focus:outline-none focus:border-white/50 transition">
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 h-7 w-7 flex items-center justify-center rounded-full bg-white text-black hover:bg-gray-200 transition">
                        <i data-lucide="send" class="h-3 w-3"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function sendChatMessage(e) {
            e.preventDefault();
            const input = document.getElementById('chat-input');
            const text = input.value.trim();
            if (!text) return;
            
            const chatBox = document.getElementById('chat-messages');
            const msgHtml = `
                <div class="flex gap-2 animate-fade-in">
                    <span class="font-bold text-white">You</span>
                    <span class="text-white">${text}</span>
                </div>
            `;
            chatBox.insertAdjacentHTML('beforeend', msgHtml);
            chatBox.scrollTop = chatBox.scrollHeight;
            input.value = '';
        }

        // Simulate incoming chat messages
        const simulatedMessages = [
            "Wait, is that leather or silk?",
            "The drape on that coat...",
            "Incredible collection.",
            "Buying the whole look tbh.",
            "Wow."
        ];
        
        let msgIndex = 0;
        setInterval(() => {
            if (msgIndex >= simulatedMessages.length) msgIndex = 0;
            const chatBox = document.getElementById('chat-messages');
            const usernames = ["Alex99", "StyleIcon", "J_Doe", "FashionCrit", "VIP_Guest"];
            const colors = ["text-blue-300", "text-pink-300", "text-purple-300", "text-green-300", "text-yellow-300"];
            
            const msgHtml = `
                <div class="flex gap-2 animate-fade-in transition-all">
                    <span class="font-bold ${colors[msgIndex % colors.length]}">${usernames[msgIndex % usernames.length]}</span>
                    <span class="text-white">${simulatedMessages[msgIndex]}</span>
                </div>
            `;
            chatBox.insertAdjacentHTML('beforeend', msgHtml);
            chatBox.scrollTop = chatBox.scrollHeight;
            msgIndex++;
        }, 4000);
    </script>
</body>
</html>
