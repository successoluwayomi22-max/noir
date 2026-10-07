<?php

declare(strict_types=1);

namespace Noir\App\Services;

use PDO;

/**
 * Master Intelligent Personal Fashion Decision-Maker, Shopping Assistant & Demand Engine.
 *
 * Capabilities:
 * - Natural human-friendly multi-turn conversation & varied greetings
 * - Deep capability and assistant identity explanations (no unsolicited products)
 * - Category-specific product searches (returns 4-6 matching items)
 * - Complete outfit builder with real-time budget calculation & Shop This Look
 * - Knowledge base retrieval for FAQs, policies, and navigation actions
 * - Color intelligence (Color wheel, harmony, user dislike avoidance)
 * - Rejection & refinement intelligence (too expensive, different color, something else)
 * - Product comparison with definitive Top Pick vs Alternative reasoning
 * - Customer Market Demand extraction (logs unmet desires for production forecasting)
 */
final class FashionDecisionEngine
{
    private const DEFAULT_WEIGHTS = [
        'occasion' => 0.25,
        'preference' => 0.20,
        'color_harmony' => 0.15,
        'style_personality' => 0.15,
        'budget' => 0.15,
        'availability' => 0.10,
    ];

    private const OCCASIONS = [
        'gala' => ['formality' => 'ultra_formal', 'categories' => ['Dresses', 'Suits', 'Blazers', 'Footwear', 'Accessories'], 'styles' => ['Formal', 'Luxury', 'Elegant'], 'neutrals' => ['Black', 'Obsidian', 'Gold', 'Midnight Navy', 'Emerald']],
        'dinner' => ['formality' => 'formal', 'categories' => ['Dresses', 'Blazers', 'Shirts', 'Trousers', 'Footwear'], 'styles' => ['Smart casual', 'Formal', 'Elegant', 'Minimalist'], 'neutrals' => ['Black', 'Navy', 'Burgundy', 'Charcoal', 'Cream']],
        'date' => ['formality' => 'smart_casual', 'categories' => ['Shirts', 'Trousers', 'Dresses', 'Knitwear', 'Outerwear', 'Footwear'], 'styles' => ['Smart casual', 'Minimalist', 'Trendy', 'Elegant'], 'neutrals' => ['Black', 'Olive', 'Beige', 'Navy', 'White']],
        'work' => ['formality' => 'business', 'categories' => ['Blazers', 'Shirts', 'Trousers', 'Footwear', 'Bags'], 'styles' => ['Classic', 'Smart casual', 'Minimalist'], 'neutrals' => ['Navy', 'Charcoal', 'Grey', 'Black', 'White', 'Beige']],
        'interview' => ['formality' => 'business_formal', 'categories' => ['Suits', 'Blazers', 'Shirts', 'Trousers', 'Footwear'], 'styles' => ['Classic', 'Formal', 'Minimalist'], 'neutrals' => ['Navy', 'Charcoal', 'White', 'Black']],
        'party' => ['formality' => 'cocktail', 'categories' => ['Dresses', 'Shirts', 'Outerwear', 'Footwear', 'Accessories'], 'styles' => ['Trendy', 'Streetwear', 'Luxury', 'Elegant'], 'neutrals' => ['Black', 'Silver', 'Gold', 'Obsidian', 'Bold']],
        'wedding' => ['formality' => 'formal', 'categories' => ['Suits', 'Dresses', 'Blazers', 'Footwear', 'Accessories'], 'styles' => ['Formal', 'Elegant', 'Luxury', 'Classic'], 'neutrals' => ['Navy', 'Pastel', 'Emerald', 'Beige', 'Charcoal']],
        'casual' => ['formality' => 'casual', 'categories' => ['T-Shirts', 'Hoodies', 'Jeans', 'Trousers', 'Sneakers', 'Outerwear'], 'styles' => ['Casual', 'Streetwear', 'Minimalist', 'Relaxed'], 'neutrals' => ['White', 'Black', 'Grey', 'Olive', 'Beige', 'Denim']],
        'streetwear' => ['formality' => 'street', 'categories' => ['Hoodies', 'Oversized Tees', 'Cargo Pants', 'Outerwear', 'Sneakers', 'Caps'], 'styles' => ['Streetwear', 'Trendy', 'Minimalist'], 'neutrals' => ['Obsidian', 'Charcoal', 'Earth Tones', 'Olive', 'Cream']],
        'beach' => ['formality' => 'resort', 'categories' => ['Linen Shirts', 'Shorts', 'Sandals', 'Sunglasses', 'Tees'], 'styles' => ['Relaxed', 'Casual', 'Minimalist'], 'neutrals' => ['White', 'Beige', 'Sand', 'Sky Blue', 'Sage']],
        'vacation' => ['formality' => 'resort', 'categories' => ['Linen Shirts', 'Trousers', 'Shorts', 'Footwear', 'Bags'], 'styles' => ['Relaxed', 'Smart casual', 'Minimalist'], 'neutrals' => ['Cream', 'Terracotta', 'Beige', 'White', 'Navy']],
        'travel' => ['formality' => 'comfortable', 'categories' => ['Hoodies', 'Joggers', 'Sneakers', 'Outerwear', 'Tote Bags'], 'styles' => ['Relaxed', 'Minimalist', 'Sporty'], 'neutrals' => ['Grey', 'Black', 'Olive', 'Navy', 'Cream']],
        'gym' => ['formality' => 'active', 'categories' => ['Activewear', 'Tees', 'Shorts', 'Sneakers'], 'styles' => ['Sporty', 'Casual'], 'neutrals' => ['Black', 'Grey', 'Navy', 'White']],
    ];

    private const COLOR_WHEEL = [
        'black' => ['matches' => ['white', 'grey', 'beige', 'olive', 'gold', 'silver', 'red', 'cream', 'navy'], 'type' => 'neutral'],
        'white' => ['matches' => ['black', 'navy', 'olive', 'charcoal', 'beige', 'brown', 'terracotta', 'denim'], 'type' => 'neutral'],
        'charcoal' => ['matches' => ['white', 'black', 'cream', 'olive', 'burgundy', 'light blue'], 'type' => 'neutral'],
        'grey' => ['matches' => ['white', 'black', 'navy', 'burgundy', 'pink', 'emerald'], 'type' => 'neutral'],
        'beige' => ['matches' => ['brown', 'white', 'olive', 'navy', 'cream', 'black', 'terracotta'], 'type' => 'neutral'],
        'cream' => ['matches' => ['black', 'brown', 'olive', 'navy', 'terracotta', 'gold'], 'type' => 'neutral'],
        'navy' => ['matches' => ['white', 'beige', 'grey', 'cream', 'tan', 'burgundy', 'gold'], 'type' => 'neutral'],
        'olive' => ['matches' => ['black', 'white', 'beige', 'cream', 'gold', 'orange', 'charcoal'], 'type' => 'earth'],
        'brown' => ['matches' => ['beige', 'cream', 'white', 'blue', 'gold', 'olive'], 'type' => 'earth'],
        'burgundy' => ['matches' => ['navy', 'charcoal', 'black', 'white', 'grey', 'gold'], 'type' => 'deep'],
        'emerald' => ['matches' => ['gold', 'black', 'cream', 'navy', 'white'], 'type' => 'deep'],
    ];

    public function __construct(private readonly PDO $db) {}

    /**
     * Main decision-making entrypoint. Processes user request, maintains context,
     * evaluates live catalog candidates, and returns a structured AI recommendation.
     */
    public function evaluateRequest(string $prompt, ?int $userId, string $sessionId, array $history = []): array
    {
        $profile = $this->getOrCreateFashionProfile($userId);
        $weights = $this->getScoringWeights();
        $intent = $this->detectIntent($prompt, $history);
        $budget = $this->extractBudget($prompt, $profile);
        $occasion = $this->extractOccasion($prompt, $history);
        $colors = $this->extractColors($prompt);
        $styles = $this->extractStyles($prompt, $profile);
        $category = $this->extractCategory($prompt);

        // Update profile dynamically if user explicitly states color / style dislikes or likes
        $this->updateProfileFromPrompt($profile, $prompt);

        // 1. Assistant Identity & Capabilities
        if ($intent['type'] === 'AI_CAPABILITIES') {
            return $this->handleAssistantCapabilities();
        }

        // 2. Natural Greeting Variations
        if ($intent['type'] === 'GREETING') {
            return $this->handleGreeting($prompt);
        }

        // 3. Conversational Doubts & Questions ("are you sure?", "how do you know?")
        if ($intent['type'] === 'DOUBT_CONFIDENCE') {
            return $this->handleDoubtConfidence($prompt);
        }

        // 4. Underspecified Styling / Consultation Requests ("I have a party", "what should I wear?")
        if ($intent['type'] === 'UNDERSPECIFIED_STYLING') {
            return $this->handleUnderspecifiedStyling($prompt);
        }

        // 5. Order Tracking & Status
        if ($intent['type'] === 'ORDER_TRACKING') {
            return $this->handleOrderTracking($prompt, $userId);
        }

        // 6. Discount & Coupon Codes
        if ($intent['type'] === 'DISCOUNT_PROMO') {
            return $this->handleDiscountPromo();
        }

        // 7. Platform Knowledge, Policies, FAQs & Direct Navigation
        if ($intent['type'] === 'KNOWLEDGE_NAV' || $intent['type'] === 'FAQ_POLICY') {
            $kbResponse = $this->handleKnowledgeBase($prompt);
            if ($kbResponse !== null) {
                return $kbResponse;
            }
        }

        // 8. Popular & Runway Trending
        if ($intent['type'] === 'POPULAR_TRENDING') {
            return $this->handlePopularTrending();
        }

        // 9. Product Head-to-Head Comparison & Decision
        if ($intent['type'] === 'COMPARISON') {
            return $this->handleComparison($prompt, $profile, $weights, $occasion, $budget);
        }

        // 10. Wardrobe Gap & Missing Essentials
        if ($intent['type'] === 'WARDROBE_GAP') {
            return $this->handleWardrobeGap($profile, $userId);
        }

        // 11. Color Palette & Harmony Advice
        if ($intent['type'] === 'COLOR_ADVICE') {
            return $this->handleColorAdvice($prompt, $colors, $profile);
        }

        // 12. Specific Category / Product Search (e.g. "Find me shirts", "Show dresses", "Black trousers")
        if ($intent['type'] === 'PRODUCT_SEARCH' && $category !== null) {
            $searchResult = $this->handleProductSearch($prompt, $category, $colors, $budget, $styles, $profile);
            $this->recordDemandSignal($sessionId, $userId, $category, $styles[0] ?? null, $colors[0] ?? null, $budget, $occasion, $prompt, !empty($searchResult['products']), empty($searchResult['products']) ? 1 : 0);
            return $searchResult;
        }

        // 14. Conversational Stylist Discussion (No clothes dumped on general chat!)
        if ($intent['type'] === 'STYLIST_CHAT') {
            return $this->handleStylistChat($prompt, $profile, $colors, $styles);
        }

        // 15. Rejection & Refinement (Too expensive, different color, not that)
        $isRefinement = preg_match('/\b(not that|show (me )?something else|too expensive|cheaper|different colou?r|another option|don\'t like)\b/i', $prompt);

        // Primary: Intelligent Catalog Search & Complete Outfit Recommendation
        $result = $this->generateOutfitRecommendation($prompt, $profile, $weights, $occasion, $budget, $styles, $colors, $userId, $sessionId, $isRefinement ? true : false);

        // Record customer demand signal for market intelligence & production forecasting
        $hadMatch = !empty($result['products']);
        $this->recordDemandSignal($sessionId, $userId, $category, $styles[0] ?? null, $colors[0] ?? null, $budget, $occasion, $prompt, $hadMatch, $isRefinement ? 1 : ($hadMatch ? 0 : 1));

        return $result;
    }

    /**
     * Detects conversational intent and user requirements.
     */
    private function detectIntent(string $prompt, array $history): array
    {
        $lower = strtolower(trim($prompt));

        // 1. Assistant Identity & Purpose ("why are you here", "what can you do", "what do you do", "who are you", "who are u", "who r u")
        if (preg_match('/\b(why are (you|u) here|what is your (job|role|purpose)|what can (you|u) (do|help with|help me with)|how can (you|u) help|what do (you|u) do|who (are|r) (you|u)|how do (you|u) work|what are your capabilities|what can i ask)\b/i', $lower)) {
            return ['type' => 'AI_CAPABILITIES'];
        }

        // 2. Conversational Doubts & Questions ("are you sure", "how do you know", "without my sex", "really")
        if (preg_match('/\b(are (you|u) sure|how (do|would|will) (you|u|it) know|really\??|without my|can i trust|why this|is that all|how do you decide)\b/i', $lower)) {
            return ['type' => 'DOUBT_CONFIDENCE'];
        }

        // 3. Underspecified Styling Requests ("what should i wear", "i have a party", "i need an outfit", "dress me")
        if (preg_match('/^(i need (an outfit|something to wear|clothes)|what should i wear|dress me|help me dress|i have a party|i have an event|style me|i want an outfit|what can i wear)\b/i', $lower) && strlen($lower) < 55) {
            return ['type' => 'UNDERSPECIFIED_STYLING'];
        }

        // 4. Simple greetings
        if (preg_match('/^(hi|hello|hey|good\s*(morning|afternoon|evening)|bonjour|salut|howdy)\b/i', $lower) && strlen($lower) < 30) {
            return ['type' => 'GREETING'];
        }

        // 5. Order Tracking
        if (preg_match('/\b(track|where is my (order|package|delivery)|order status|shipping status|track order)\b/i', $lower)) {
            return ['type' => 'ORDER_TRACKING'];
        }

        // 6. Discounts
        if (preg_match('/\b(discount|coupon|promo|voucher|code|promo code|sale discount|offer)\b/i', $lower)) {
            return ['type' => 'DISCOUNT_PROMO'];
        }

        // 7. Store navigation
        if (preg_match('/\b(where can i (find|see|view)|how do i (go to|open|navigate|find)|take me to|navigate to|open cart|open orders|open wishlist|where is the (cart|size guide|wishlist|profile|wallet))\b/i', $lower)) {
            return ['type' => 'KNOWLEDGE_NAV'];
        }

        // 8. FAQs, Policies, Shipping, Returns
        if (preg_match('/\b(returns?|refunds?|exchange|policy|policies|size guide|how do i return|delivery|shipping|dispatch|payment|paystack|flutterwave|apple pay|contact support|customer care|what is noir|about us)\b/i', $lower) || preg_match('/\bhow (do|does) (.*) work\b/i', $lower)) {
            return ['type' => 'FAQ_POLICY'];
        }

        // 9. Popular / Trending
        if (preg_match('/\b(best seller|trending|popular|new arrival|what is hot|latest piece|signature pieces)\b/i', $lower)) {
            return ['type' => 'POPULAR_TRENDING'];
        }

        // 10. Comparisons
        if (preg_match('/\b(vs|versus|compare|which one|which should i (buy|pick|choose)|which looks better|difference between)\b/i', $lower)) {
            return ['type' => 'COMPARISON'];
        }

        // 11. Wardrobe Gap
        if (preg_match('/\b(wardrobe|closet|missing|gap|what do i need|already own|my clothes|pieces i am missing)\b/i', $lower)) {
            return ['type' => 'WARDROBE_GAP'];
        }

        // 13. Specific Category / Piece Search (e.g. "find me shirts", "i need a silk shirt", "black denim jeans", "show hoodies")
        $cat = $this->extractCategory($prompt);
        if ($cat !== null && !preg_match('/\b(outfit|ensemble|look for (a|an|the) (dinner|wedding|party|gala|date|work|interview))\b/i', $lower)) {
            if (preg_match('/\b(find|show|search|browse|get|recommend|look for|looking for|buy|need|want|have|sell|give me|check)\b/i', $lower) || strlen($lower) < 40) {
                return ['type' => 'PRODUCT_SEARCH'];
            }
        }

        // 14. Explicit Outfit / Dressing Request
        if (preg_match('/\b(outfit|ensemble|dress me for|curate a look|wear for|attire for|style an outfit|look for (a|my)|dressing for)\b/i', $lower) || ($this->extractOccasion($prompt, []) !== 'casual' && preg_match('/\b(for|to|at)\b/i', $lower))) {
            return ['type' => 'OUTFIT_RECOMMENDATION'];
        }

        // Default: Conversational Stylist Discussion (Engages in rich dialogue with 0 clothes dumped!)
        return ['type' => 'STYLIST_CHAT'];
    }

    /**
     * Handles doubt and confidence questions without dumping unsolicited products.
     */
    private function handleDoubtConfidence(string $prompt): array
    {
        $message = "You make an essential point! Styling is deeply personal and depends on your **gender line, taste, venue, and vibe**.\n\n"
            . "To curate the exact look for you without assumptions, please tell me:\n\n"
            . "1. 👤 **Wardrobe Line:** Men's Tailoring, Women's Capsule, or Unisex?\n"
            . "2. 📍 **The Event & Venue:** Is it a formal dinner, rooftop party, wedding, night club, or casual day outing?\n"
            . "3. 🎨 **Aesthetic & Budget:** Do you prefer minimalist monochrome, bold luxury, or relaxed earth tones?\n\n"
            . "Tell me your preferences or tap one of the prompts below to get started!";

        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'consultation',
            'confidence' => 1.0,
            'confidence_level' => 'high',
            'products' => [],
            'alternatives' => [],
            'actions' => [],
            'follow_up_suggestions' => [
                "👔 Men's Smart Casual Dinner",
                "👗 Women's Cocktail Party",
                "🔥 Unisex Streetwear",
                "✨ Formal Black-Tie Gala",
            ],
        ];
    }

    /**
     * Handles underspecified requests ("I have a party", "what should I wear?") by asking clarifying questions.
     */
    private function handleUnderspecifiedStyling(string $prompt): array
    {
        $message = "I'd love to curate the perfect ensemble for you! To make sure it fits your taste and event, please tell me:\n\n"
            . "1. 👤 **Wardrobe:** Are you looking for Men's or Women's wear?\n"
            . "2. 📍 **The Occasion:** What type of party or event is it (e.g. rooftop cocktail, dinner date, birthday, wedding)?\n"
            . "3. 💰 **Budget & Taste:** Any budget target (e.g. *under ₦50,000*) or color preferences?\n\n"
            . "You can reply with your details or tap any option below to begin:";

        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'consultation',
            'confidence' => 1.0,
            'confidence_level' => 'high',
            'products' => [],
            'alternatives' => [],
            'actions' => [],
            'follow_up_suggestions' => [
                "👔 Men's Dinner Date Outfit",
                "👗 Women's Evening Party Dress",
                "🎉 Casual Birthday Party Look",
                "✨ Gala / Formal Black-Tie",
            ],
        ];
    }

    /**
     * Handles assistant capabilities and identity without dumping unsolicited products.
     */
    private function handleAssistantCapabilities(): array
    {
        $message = "I am your **Noir & Co. Personal Fashion Stylist & Shopping Concierge**.\n\n"
            . "Here is how I can assist you:\n\n"
            . "👔 **Curate Bespoke Outfits:** Build complete, color-coordinated looks tailored to your occasion (dinners, weddings, work, galas, or streetwear).\n"
            . "🛍️ **Find Specific Pieces:** Search our catalog by style, color, fabric, or budget (e.g. *'Show me silk shirts under ₦40,000'*).\n"
            . "⚖️ **Compare Products:** Tell you which piece offers better versatility, drape, and value.\n"
            . "🎨 **Color Harmony Advice:** Suggest ideal palette pairings and avoid colors you dislike.\n"
            . "📦 **Store Guide & Tracking:** Help you track your orders, check return policies, look up sizing measurements, and navigate the store.\n\n"
            . "What would you like to explore today?";

        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'capabilities',
            'confidence' => 1.0,
            'confidence_level' => 'high',
            'products' => [],
            'alternatives' => [],
            'actions' => [],
            'follow_up_suggestions' => [
                "✨ Build an outfit for an event",
                "👔 Show me shirts & tops",
                "👗 Browse evening dresses",
                "📦 Track my order",
                "🎨 Color harmony advice",
            ],
        ];
    }

    /**
     * Handles free-form conversational stylist chat.
     * Engages the user in a rich, interactive discussion WITHOUT dumping product cards.
     * Responds contextually to fashion topics, style preferences, and general conversation.
     */
    private function handleStylistChat(string $prompt, array $profile, array $colors, array $styles): array
    {
        $lower = strtolower(trim($prompt));
        $primaryStyle = $profile['primary_style'] ?? 'Minimalist';

        // Fashion topic discussions
        if (preg_match('/\b(fashion|style|aesthetic|trend|minimalist|streetwear|luxury|vintage|classic|elegant|sporty|athleisure|preppy|bohemian|grunge|retro|modern)\b/i', $lower)) {
            $responses = [
                "That's a wonderful aesthetic direction! **{topic}** is all about intentional silhouettes and curated restraint. Do you lean more towards structured tailoring or relaxed drape? And what occasions are you typically dressing for — work, evenings out, or weekend leisure?",
                "Great taste! **{topic}** has been evolving beautifully this season. The key is balancing proportion and texture. Tell me — what does your current wardrobe look like? Are there any gaps you feel, or are you looking to refresh a particular category (tops, outerwear, footwear)?",
                "I love that you're drawn to **{topic}**! The foundation of any strong wardrobe starts with understanding your lifestyle. What's a typical week like for you — office meetings, social dinners, gym sessions? That helps me recommend pieces you'll actually reach for.",
            ];
            // Extract the topic mentioned
            preg_match('/\b(fashion|minimalist|streetwear|luxury|vintage|classic|elegant|sporty|athleisure|preppy|bohemian|grunge|retro|modern|style|aesthetic|trend)\b/i', $lower, $topicMatch);
            $topic = ucfirst($topicMatch[1] ?? 'Fashion');
            $message = str_replace('{topic}', $topic, $responses[array_rand($responses)]);

            return $this->chatResponse($message, [
                "I dress mostly for work & dinners",
                "I want a capsule wardrobe refresh",
                "Show me {$topic} pieces from Noir",
                "What colors work best for {$topic}?",
            ]);
        }

        // Color & preference discussions
        if (preg_match('/\b(i (love|like|prefer|enjoy|wear|always wear|usually wear)|my favou?rite|i go for|i lean towards)\b/i', $lower)) {
            $message = "That tells me a lot about your personal style DNA! I'll keep that preference in mind as we build your look together.\n\n"
                . "Now let me understand a bit more:\n"
                . "- **What occasions** do you dress up for most often?\n"
                . "- **Any colors or patterns** you actively avoid?\n"
                . "- **Budget range** — are you looking for investment pieces or affordable everyday staples?";

            return $this->chatResponse($message, [
                "I dress for work meetings mostly",
                "I avoid bright colors",
                "Budget is around ₦50,000 per outfit",
                "Curate an outfit for me now",
            ]);
        }

        // Body type, fit, and sizing discussions
        if (preg_match('/\b(body|fit|size|sizing|slim|oversized|baggy|tight|loose|tailored|regular|petite|plus size|curvy|tall|short)\b/i', $lower)) {
            $message = "Great question! Finding the right fit is everything — a well-fitted ₦15,000 piece can look more expensive than an ill-fitting ₦100,000 one.\n\n"
                . "Our sizing guide covers exact chest, waist, and hip measurements for every piece. Do you prefer:\n\n"
                . "- **Tailored / Slim** — clean lines that follow your frame\n"
                . "- **Regular** — comfortable with subtle structure\n"
                . "- **Relaxed / Oversized** — contemporary loose drape\n\n"
                . "Tell me your preference and I'll filter pieces accordingly!";

            return $this->chatResponse($message, [
                "I prefer tailored / slim fit",
                "Relaxed oversized look",
                "Open the Size Guide",
                "Curate a tailored outfit for me",
            ]);
        }

        // Occasion exploration
        if (preg_match('/\b(occasion|event|going|attend|invited|wedding|dinner|party|date|interview|meeting|travel|vacation|beach|gym|church|funeral|graduation)\b/i', $lower)) {
            $message = "Tell me more about this! Understanding the setting helps me curate exactly the right pieces:\n\n"
                . "- **Venue & vibe** — is it indoor/outdoor, casual or formal?\n"
                . "- **Time of day** — daytime brunch vs evening cocktail needs different tones\n"
                . "- **Dress code** — any specific requirements (black tie, smart casual, etc.)?\n\n"
                . "Once I know these details, I'll put together a head-to-toe ensemble with alternatives.";

            return $this->chatResponse($message, [
                "It's a formal evening dinner",
                "Smart casual daytime event",
                "Outdoor rooftop party at night",
                "Beach / resort vacation",
            ]);
        }

        // Budget discussions
        if (preg_match('/\b(budget|afford|expensive|cheap|cost|price|spend|money|worth|investment)\b/i', $lower)) {
            $message = "Budget is an essential part of smart styling — and I respect every range.\n\n"
                . "To curate the best possible look within your means, tell me:\n\n"
                . "- 💰 **Total outfit budget** (e.g. *under ₦50,000* or *around ₦100,000*)\n"
                . "- 🎯 **Priority** — would you rather invest in one hero piece, or get a complete 3-4 piece look?\n\n"
                . "I can work with any range and still make it look premium.";

            return $this->chatResponse($message, [
                "Under ₦30,000 total",
                "Around ₦50,000 - ₦80,000",
                "₦100,000+ investment pieces",
                "Just browsing for now",
            ]);
        }

        // Thank you / positive feedback
        if (preg_match('/\b(thanks?|thank you|appreciate|great|awesome|perfect|nice|love it|amazing|cool|good job|well done)\b/i', $lower)) {
            $message = "You're welcome! 😊 I'm here whenever you need styling help. Is there anything else you'd like to explore — a new outfit, tracking an order, or browsing our latest drops?";

            return $this->chatResponse($message, [
                "✨ Curate a new look for me",
                "🛍️ Browse new arrivals",
                "📦 Track my order",
                "🎨 Color advice",
            ]);
        }

        // Default conversational fallback — engage warmly without products
        $fallbacks = [
            "I'd love to help you with that! To give you the best styling advice, could you tell me a bit more? For example — are you looking for a specific piece, dressing for an occasion, or just exploring what's new?",
            "Great question! I'm your personal fashion concierge at Noir. I can discuss style, curate outfits, compare pieces, or help navigate the store. What would you like to dive into?",
            "I'm here to make your shopping experience effortless! Tell me what's on your mind — whether it's an upcoming event, a wardrobe refresh, or just finding that perfect piece.",
        ];

        return $this->chatResponse($fallbacks[array_rand($fallbacks)], [
            "✨ Build me an outfit",
            "👔 I need help with my style",
            "🛍️ What's new in store?",
            "📦 Track my order",
        ]);
    }

    /**
     * Helper: Returns a structured conversational response with zero products.
     */
    private function chatResponse(string $message, array $suggestions): array
    {
        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'conversation',
            'confidence' => 1.0,
            'confidence_level' => 'high',
            'products' => [],
            'alternatives' => [],
            'actions' => [],
            'follow_up_suggestions' => $suggestions,
        ];
    }

    /**
     * Handles specific product searches (e.g. "Find me shirts", "Show jackets", "Black boots") and returns 4-6 matching candidates.
     */
    private function handleProductSearch(
        string $prompt,
        string $category,
        array $colors,
        ?float $budget,
        array $styles,
        array $profile
    ): array {
        $colorQuery = !empty($colors[0]) ? $colors[0] : '';
        $lower = strtolower($prompt);

        // Extract meaningful search tokens from prompt (excluding filler words)
        $words = preg_split('/[\s,\.\?!]+/', $lower);
        $stopWords = ['i', 'want', 'need', 'find', 'me', 'show', 'a', 'an', 'the', 'for', 'in', 'with', 'and', 'or', 'some', 'please', 'recommend', 'look', 'looking', 'buy', 'get', 'any', 'good', 'nice', 'best'];
        $searchTokens = array_filter($words, fn($w) => strlen($w) >= 3 && !in_array($w, $stopWords, true));

        // Fetch candidate products
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.slug, p.price, p.category, p.subcategory, p.material, p.tags, p.brand,
                   p.featured_image, p.short_description, p.description, p.quantity, p.allow_backorders,
                   COALESCE(c.name, p.category, 'Atelier') AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.status = 'published' AND (p.quantity > 0 OR p.allow_backorders = 1)
        ");
        $stmt->execute();
        $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Score each product based on category keywords + token matches + color + budget
        $scored = [];
        $catKeywords = $this->getCategoryKeywords($category);

        foreach ($allProducts as $p) {
            $searchBlob = strtolower((string)$p['name'] . ' ' . (string)$p['category'] . ' ' . (string)$p['category_name'] . ' ' . (string)$p['subcategory'] . ' ' . (string)$p['material'] . ' ' . (string)$p['tags'] . ' ' . (string)$p['description']);
            $score = 0.0;

            // 1. Category keyword match
            foreach ($catKeywords as $kw) {
                if (str_contains($searchBlob, $kw)) {
                    $score += 3.0;
                }
            }

            // 2. Specific prompt search tokens match
            foreach ($searchTokens as $token) {
                if (str_contains($searchBlob, $token)) {
                    $score += 4.0;
                    if (str_contains(strtolower((string)$p['name']), $token)) {
                        $score += 3.0; // Name match gets priority
                    }
                }
            }

            // 3. Color match
            if ($colorQuery !== '' && str_contains($searchBlob, strtolower($colorQuery))) {
                $score += 3.0;
            }

            // 4. Budget fit
            $price = (float)$p['price'];
            if ($budget !== null && $budget > 0) {
                if ($price <= $budget) {
                    $score += 2.0;
                } elseif ($price <= $budget * 1.25) {
                    $score += 0.5;
                } else {
                    $score -= 2.0;
                }
            }

            if ($score > 0) {
                $p['match_score'] = $score;
                $scored[] = $p;
            }
        }

        // Sort by match score DESC
        usort($scored, fn($a, $b) => ($b['match_score'] <=> $a['match_score']));
        $topMatches = array_slice($scored, 0, 6);

        // Fallback: If no products matched specifically, take top featured products
        if (empty($topMatches)) {
            $topMatches = array_slice($allProducts, 0, 4);
        }

        $formatted = [];
        foreach ($topMatches as $idx => $p) {
            $mat = !empty($p['material']) ? (string)$p['material'] : 'signature fabric';
            $rationale = match ($idx) {
                0 => "Top Recommendation: Handcrafted in {$mat}, offering clean architectural proportions.",
                1 => "Key Alternative: Exceptional silhouette versatility across occasions.",
                2 => "Elevated Staple: Refined drape and premium tactile finish.",
                default => "Curated Option: Seamlessly complements your wardrobe palette.",
            };
            $formatted[] = $this->formatProductRecommendation($p, $rationale);
        }

        $colorText = $colorQuery !== '' ? " in {$colorQuery}" : "";
        $budgetText = ($budget !== null && $budget > 0) ? " within ₦" . number_format($budget, 0) : "";
        $message = "I have curated these signature **{$category}**{$colorText}{$budgetText} tailored directly to what you asked for:";

        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'product_search',
            'confidence' => 0.96,
            'confidence_level' => 'high',
            'products' => $formatted,
            'alternatives' => [],
            'actions' => [
                ['type' => 'navigate', 'destination' => '/store#catalogue', 'label' => 'Browse Full Catalog']
            ],
            'follow_up_suggestions' => [
                "Build an outfit with the first piece",
                "What accessories or shoes match these?",
                "Show pieces in other colors",
            ],
        ];
    }

    /**
     * Returns keyword array for a given category.
     */
    private function getCategoryKeywords(string $category): array
    {
        $map = [
            'Shirts & Tops' => ['shirt', 'tee', 'top', 'blouse', 'polo', 'button up', 'poplin', 'tencel'],
            'Trousers & Denim' => ['trouser', 'pant', 'jean', 'denim', 'cargo', 'chinos', 'short', 'jogger', 'gabardine'],
            'Jackets & Outerwear' => ['jacket', 'coat', 'trench', 'hoodie', 'sweater', 'knitwear', 'cardigan', 'bomber', 'overshirt', 'suede', 'blazer', 'mac', 'topcoat'],
            'Footwear' => ['shoe', 'sneaker', 'loafer', 'boot', 'heel', 'sandal', 'trainer', 'runner'],
            'Accessories' => ['bag', 'tote', 'belt', 'sunglasses', 'watch', 'cap', 'beanie', 'scarf', 'wallet', 'holder'],
            'Suits & Tailoring' => ['suit', 'blazer', 'tuxedo'],
        ];
        return $map[$category] ?? [strtolower($category)];
    }

    /**
     * Extracts category from natural language query.
     */
    private function extractCategory(string $prompt): ?string
    {
        $lower = strtolower($prompt);
        $categories = [
            'Jackets & Outerwear' => ['jacket', 'jackets', 'coat', 'coats', 'trench', 'hoodie', 'hoodies', 'sweater', 'sweaters', 'knitwear', 'cardigan', 'bomber', 'overshirt', 'suede jacket', 'denim jacket', 'mac', 'topcoat', 'outerwear'],
            'Shirts & Tops' => ['shirt', 'shirts', 'tee', 'tees', 't-shirt', 't-shirts', 'top', 'tops', 'blouse', 'polo', 'button up', 'linen shirt', 'poplin shirt', 'silk shirt', 'sweatshirt'],
            'Trousers & Denim' => ['trouser', 'trousers', 'pant', 'pants', 'jean', 'jeans', 'denim', 'cargo', 'cargos', 'chinos', 'short', 'shorts', 'jogger', 'joggers', 'bottom', 'bottoms'],
            'Footwear' => ['shoe', 'shoes', 'sneaker', 'sneakers', 'loafer', 'loafers', 'boot', 'boots', 'heel', 'heels', 'sandal', 'sandals', 'trainer', 'trainers', 'runner', 'runners', 'footwear'],
            'Accessories' => ['bag', 'bags', 'tote', 'totes', 'belt', 'belts', 'sunglasses', 'watch', 'watches', 'cap', 'caps', 'hat', 'beanie', 'scarf', 'wallet', 'card holder', 'accessories', 'accessory'],
            'Suits & Tailoring' => ['suit', 'suits', 'blazer', 'blazers', 'tuxedo'],
        ];

        foreach ($categories as $catName => $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $lower)) {
                    return $catName;
                }
            }
        }

        return null;
    }

    /**
     * Extracts budget limit from prompt or user profile.
     */
    private function extractBudget(string $prompt, array $profile): ?float
    {
        if (preg_match('/(?:under|below|budget of|within|max|around|for)\s*(?:₦|ngn|n)?\s*([\d,]+)/i', $prompt, $m)) {
            $cleaned = (float)str_replace(',', '', $m[1]);
            if ($cleaned > 0) return $cleaned;
        }

        if (preg_match('/(?:₦|ngn)\s*([\d,]+)/i', $prompt, $m)) {
            $cleaned = (float)str_replace(',', '', $m[1]);
            if ($cleaned > 0) return $cleaned;
        }

        if (preg_match('/\b(\d+)\s*(?:k|thousand)\b/i', $prompt, $m)) {
            $val = (float)$m[1] * 1000;
            if ($val > 0) return $val;
        }

        return !empty($profile['budget_max']) ? (float)$profile['budget_max'] : null;
    }

    /**
     * Identifies the occasion from prompt or history.
     */
    private function extractOccasion(string $prompt, array $history): string
    {
        $combined = strtolower($prompt);
        foreach ($history as $h) {
            $combined .= ' ' . strtolower($h['content'] ?? '');
        }

        foreach (array_keys(self::OCCASIONS) as $occ) {
            if (preg_match('/\b' . preg_quote($occ, '/') . '\b/', $combined)) {
                return $occ;
            }
        }

        if (preg_match('/\b(meeting|office|client|corporate|business|at work|for work)\b/', $combined)) return 'work';
        if (preg_match('/\b(night|club|party|lounge|cocktail)\b/', $combined)) return 'party';
        if (preg_match('/\b(beach|resort|summer|pool)\b/', $combined)) return 'beach';
        if (preg_match('/\b(gala|ball|ceremony|red carpet)\b/', $combined)) return 'gala';
        if (preg_match('/\b(date|anniversary|romantic)\b/', $combined)) return 'date';
        if (preg_match('/\b(wedding|reception|bridal)\b/', $combined)) return 'wedding';
        if (preg_match('/\b(birthday|celebration)\b/', $combined)) return 'party';
        if (preg_match('/\b(gym|training|workout|fitness)\b/', $combined)) return 'gym';

        return 'casual';
    }

    /**
     * Extracts color references.
     */
    private function extractColors(string $prompt): array
    {
        $found = [];
        $lower = strtolower($prompt);
        foreach (array_keys(self::COLOR_WHEEL) as $color) {
            if (preg_match('/\b' . preg_quote($color, '/') . '\b/i', $lower)) {
                $found[] = ucfirst($color);
            }
        }
        return $found;
    }

    /**
     * Extracts style keywords.
     */
    private function extractStyles(string $prompt, array $profile): array
    {
        $styles = [];
        $knownStyles = ['Minimalist', 'Streetwear', 'Classic', 'Smart casual', 'Formal', 'Sporty', 'Trendy', 'Elegant', 'Casual', 'Vintage', 'Luxury', 'Relaxed'];
        $lower = strtolower($prompt);

        foreach ($knownStyles as $s) {
            if (str_contains($lower, strtolower($s))) {
                $styles[] = $s;
            }
        }

        if (empty($styles) && !empty($profile['primary_style'])) {
            $styles[] = $profile['primary_style'];
        }

        return $styles;
    }

    /**
     * Knowledge Base Retrieval for FAQs, Shipping, Returns, and Direct Navigation.
     */
    private function handleKnowledgeBase(string $prompt): ?array
    {
        try {
            $lower = strtolower($prompt);
            $stmt = $this->db->query("SELECT * FROM ai_knowledge_base WHERE is_active = 1 ORDER BY sort_order ASC");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $keywords = array_map('trim', explode(',', strtolower((string)$item['keywords'])));
                foreach ($keywords as $kw) {
                    if ($kw !== '' && str_contains($lower, $kw)) {
                        $actions = [];
                        if (!empty($item['action_type']) && !empty($item['action_destination'])) {
                            $actions[] = [
                                'type' => $item['action_type'],
                                'destination' => $item['action_destination'],
                                'label' => 'Open ' . $item['title']
                            ];
                        }

                        return [
                            'success' => true,
                            'message' => $item['content'],
                            'recommendation_type' => 'knowledge_support',
                            'confidence' => 0.98,
                            'confidence_level' => 'high',
                            'products' => [],
                            'alternatives' => [],
                            'actions' => $actions,
                            'follow_up_suggestions' => [
                                "Curate an outfit for me",
                                "Show latest new arrivals",
                                "Contact Concierge Support"
                            ],
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('Knowledge base retrieval error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Builds a comprehensive, multi-piece coordinated outfit (Top + Bottom + Layer + Shoes).
     */
    private function generateOutfitRecommendation(
        string $prompt,
        array $profile,
        array $weights,
        string $occasionKey,
        ?float $budget,
        array $styles,
        array $colors,
        ?int $userId,
        string $sessionId,
        bool $isRefinement = false
    ): array {
        $occConfig = self::OCCASIONS[$occasionKey] ?? self::OCCASIONS['casual'];
        $occasionName = ucfirst(str_replace('_', ' ', $occasionKey));
        $primaryStyle = !empty($styles[0]) ? $styles[0] : ($profile['primary_style'] ?? 'Minimalist Streetwear');

        // Fetch candidate in-stock products from DB
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.slug, p.price, p.category, p.subcategory, p.material, p.tags, p.brand,
                    p.featured_image, p.short_description, p.description, p.quantity, p.allow_backorders,
                    COALESCE(c.name, p.category, 'Unspecified') AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND (p.quantity > 0 OR p.allow_backorders = 1)
             ORDER BY p.featured DESC, p.view_count DESC, p.created_at DESC
             LIMIT 50"
        );
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) {
            return [
                'success' => true,
                'message' => 'Our atelier catalog is currently being refreshed with upcoming capsule drops. Please browse our collections or check back shortly.',
                'recommendation_type' => 'advice',
                'confidence' => 0.60,
                'confidence_level' => 'low',
                'products' => [],
                'total_price' => 0,
                'alternatives' => [],
                'follow_up_suggestions' => ['Browse new arrivals', 'View signature collection'],
            ];
        }

        // Categorize candidates
        $tops = [];
        $bottoms = [];
        $outerwear = [];
        $footwear = [];
        $accessories = [];

        foreach ($candidates as $product) {
            $cat = strtolower((string)$product['category_name'] . ' ' . (string)$product['subcategory'] . ' ' . (string)$product['name'] . ' ' . (string)$product['tags']);
            $score = $this->scoreProduct($product, $occConfig, $profile, $weights, $budget, $styles, $colors);
            $product['score'] = $score;

            if (preg_match('/\b(shoe|sneaker|boot|loafer|heel|sandal|footwear)\b/', $cat)) {
                $footwear[] = $product;
            } elseif (preg_match('/\b(pant|trouser|jean|short|skirt|bottom|cargo|chinos)\b/', $cat)) {
                $bottoms[] = $product;
            } elseif (preg_match('/\b(jacket|coat|blazer|outerwear|trench|hoodie|cardigan|sweater)\b/', $cat)) {
                $outerwear[] = $product;
            } elseif (preg_match('/\b(bag|watch|belt|jewel|cap|hat|scarf|sunglasses|accessory)\b/', $cat)) {
                $accessories[] = $product;
            } else {
                $tops[] = $product;
            }
        }

        // Sort each category by score DESC
        $sortFn = static fn($a, $b) => ($b['score'] <=> $a['score']);
        usort($tops, $sortFn);
        usort($bottoms, $sortFn);
        usort($outerwear, $sortFn);
        usort($footwear, $sortFn);
        usort($accessories, $sortFn);

        // Select complete multi-piece look (Always build a complete 3-4 piece look)
        $selectedItems = [];
        $totalPrice = 0.0;

        $topIndex = $isRefinement && count($tops) > 1 ? 1 : 0;
        $bottomIndex = $isRefinement && count($bottoms) > 1 ? 1 : 0;

        // 1. Foundation Top
        $top = $tops[$topIndex] ?? ($candidates[0] ?? null);
        if ($top) {
            $selectedItems[] = $this->formatProductRecommendation($top, 'Foundation Top: Tailored silhouette and premium drape.');
            $totalPrice += (float)$top['price'];
        }

        // 2. Tailored Bottom
        $bottom = $bottoms[$bottomIndex] ?? ($candidates[1] ?? null);
        if ($bottom && $bottom['id'] !== ($top['id'] ?? 0)) {
            $selectedItems[] = $this->formatProductRecommendation($bottom, 'Tailored Bottom: Proportions and monochrome balance.');
            $totalPrice += (float)$bottom['price'];
        }

        // 3. Statement Layer (Outerwear / Knitwear)
        $layer = $outerwear[0] ?? null;
        if ($layer && !in_array($layer['id'], array_column($selectedItems, 'product_id'), true)) {
            $selectedItems[] = $this->formatProductRecommendation($layer, 'Statement Layer: Structured architectural outerwear.');
            $totalPrice += (float)$layer['price'];
        }

        // 4. Footwear Accent
        $shoes = $footwear[0] ?? null;
        if ($shoes && !in_array($shoes['id'], array_column($selectedItems, 'product_id'), true)) {
            $selectedItems[] = $this->formatProductRecommendation($shoes, 'Footwear Accent: Anchors the ensemble with refined luxury.');
            $totalPrice += (float)$shoes['price'];
        }

        // Fallback: If catalog has limited categories, fill up to 3 pieces from candidates
        if (count($selectedItems) < 3) {
            foreach ($candidates as $c) {
                if (count($selectedItems) >= 3) break;
                if (!in_array((int)$c['id'], array_column($selectedItems, 'product_id'), true)) {
                    $selectedItems[] = $this->formatProductRecommendation($c, 'Curated Capsule Piece: Elevated craftsmanship.');
                    $totalPrice += (float)$c['price'];
                }
            }
        }

        // Generate 2 distinct Alternatives
        $alternatives = [];
        if (!empty($tops[$topIndex + 1])) $alternatives[] = $this->formatProductRecommendation($tops[$topIndex + 1], 'Alternative Top: Slightly more relaxed drape.');
        if (!empty($bottoms[$bottomIndex + 1])) $alternatives[] = $this->formatProductRecommendation($bottoms[$bottomIndex + 1], 'Alternative Bottom: Clean relaxed tailoring.');

        // Compute overall confidence
        $avgScore = 0.0;
        foreach ($selectedItems as $item) {
            $avgScore += (float)($item['match_score'] ?? 0.90);
        }
        $confidence = count($selectedItems) > 0 ? round($avgScore / count($selectedItems), 2) : 0.92;
        $confidence = min(0.98, max(0.75, $confidence));
        $confidenceLevel = $confidence >= 0.85 ? 'high' : ($confidence >= 0.70 ? 'medium' : 'low');

        // Check budget status
        $budgetStatus = 'within_budget';
        $budgetAdvice = '';
        if ($budget !== null && $budget > 0) {
            if ($totalPrice <= $budget) {
                $budgetStatus = 'within_budget';
                $budgetAdvice = " Total outfit investment is ₦" . number_format($totalPrice, 2) . ", comfortably within your ₦" . number_format($budget, 2) . " target.";
            } else {
                $budgetStatus = 'exceeds_budget';
                $budgetAdvice = " Total ensemble comes to ₦" . number_format($totalPrice, 2) . " (individual runway pieces; explore alternatives for lower price points).";
            }
        }

        $introPrefix = $isRefinement ? "Here is a refined ensemble with adjusted styling: " : "Top Pick: ";
        $decisionReason = $introPrefix . "I have curated this complete {$primaryStyle} look for your {$occasionName}."
            . " It balances structured lines with monochrome color harmony."
            . $budgetAdvice;

        // Structured Actions
        $actions = [
            [
                'type' => 'shop_this_look',
                'label' => 'Shop This Look (₦' . number_format($totalPrice, 0) . ')',
                'product_ids' => array_column($selectedItems, 'product_id'),
            ],
            [
                'type' => 'save_outfit',
                'label' => 'Save Outfit',
            ]
        ];

        // Persist interaction to conversation memory
        $this->recordConversation($sessionId, $userId, $prompt, $decisionReason, $selectedItems);

        return [
            'success' => true,
            'message' => $decisionReason,
            'recommendation_type' => 'outfit',
            'confidence' => $confidence,
            'confidence_level' => $confidenceLevel,
            'occasion' => $occasionName,
            'style_profile' => $primaryStyle,
            'total_price' => $totalPrice,
            'budget_status' => $budgetStatus,
            'products' => $selectedItems,
            'alternatives' => $alternatives,
            'actions' => $actions,
            'follow_up_suggestions' => [
                "Show me alternatives under ₦" . number_format(max(20000, $totalPrice * 0.8), 0),
                "Which shoes match this best?",
                "How can I style this for daytime?",
                "Shop This Look",
            ],
        ];
    }

    /**
     * Handles product head-to-head comparison and declares a definitive winner.
     */
    private function handleComparison(string $prompt, array $profile, array $weights, string $occasion, ?float $budget): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.slug, p.price, p.category, p.subcategory, p.material, p.tags, p.brand,
                    p.featured_image, p.short_description, p.description, p.quantity, p.allow_backorders,
                    COALESCE(c.name, p.category, 'Unspecified') AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND (p.quantity > 0 OR p.allow_backorders = 1)
             ORDER BY p.featured DESC, p.view_count DESC
             LIMIT 6"
        );
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($products) < 2) {
            return [
                'success' => true,
                'message' => 'Both pieces in our collection are exquisite. For maximum versatility, choose the piece with neutral tone detailing.',
                'recommendation_type' => 'comparison',
                'confidence' => 0.85,
                'confidence_level' => 'high',
                'products' => [],
                'alternatives' => [],
            ];
        }

        $itemA = $products[0];
        $itemB = $products[1];

        $scoreA = $this->scoreProduct($itemA, self::OCCASIONS[$occasion] ?? self::OCCASIONS['casual'], $profile, $weights, $budget, [], []);
        $scoreB = $this->scoreProduct($itemB, self::OCCASIONS[$occasion] ?? self::OCCASIONS['casual'], $profile, $weights, $budget, [], []);

        $winner = $scoreA >= $scoreB ? $itemA : $itemB;
        $runnerUp = $scoreA >= $scoreB ? $itemB : $itemA;

        $winnerFormatted = $this->formatProductRecommendation($winner, "Top Pick: Superior versatility, fabric weight, and silhouette balance.");
        $runnerUpFormatted = $this->formatProductRecommendation($runnerUp, "Alternative: A great choice if you prefer a more relaxed drape.");

        $message = "🏆 **Top Pick:** {$winner['name']} (₦" . number_format((float)$winner['price'], 2) . "). I recommend this because it offers higher silhouette versatility and effortless pairing across occasions.\n\n"
            . "✨ **Alternative:** {$runnerUp['name']} (₦" . number_format((float)$runnerUp['price'], 2) . ") is a strong second option if you want a bolder aesthetic.";

        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'comparison',
            'confidence' => 0.94,
            'confidence_level' => 'high',
            'winner_product_id' => (int)$winner['id'],
            'products' => [$winnerFormatted, $runnerUpFormatted],
            'alternatives' => [],
            'follow_up_suggestions' => [
                "What can I pair with the top pick?",
                "Show more pieces in this price range",
            ],
        ];
    }

    /**
     * Wardrobe Gap and Missing Essentials analysis.
     */
    private function handleWardrobeGap(array $profile, ?int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.slug, p.price, p.category, p.subcategory, p.material, p.tags, p.brand,
                    p.featured_image, p.short_description, p.description, p.quantity, p.allow_backorders,
                    COALESCE(c.name, p.category, 'Unspecified') AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND (p.quantity > 0 OR p.allow_backorders = 1)
             ORDER BY p.view_count DESC, p.featured DESC
             LIMIT 3"
        );
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = [];
        foreach ($products as $p) {
            $formatted[] = $this->formatProductRecommendation($p, "Wardrobe Cornerstone: Multi-outfit utility.");
        }

        $message = "Based on your style preferences, every well-curated capsule needs three core foundation pieces: an architectural tailored blazer, a structured neutral trouser, and clean leather footwear. Adding these unlocks over 12 distinct outfit combinations.";

        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'wardrobe_gap',
            'confidence' => 0.92,
            'confidence_level' => 'high',
            'products' => $formatted,
            'alternatives' => [],
            'follow_up_suggestions' => [
                "Build a complete capsule wardrobe",
                "Show pieces under ₦50,000",
            ],
        ];
    }

    /**
     * Natural Greeting: Pure conversational greeting without dumping unsolicited products.
     */
    private function handleGreeting(string $prompt): array
    {
        $hour = (int)date('H');
        $timeGreeting = $hour < 12 ? "Good morning!" : ($hour < 17 ? "Good afternoon!" : "Good evening!");

        $greetings = [
            "{$timeGreeting} 👋 Welcome to Noir & Co. I'm your Personal Stylist and Shopping Assistant. What would you like help with today? You can tell me an occasion you're dressing for, your budget, or ask about our latest capsule.",
            "Hello! 😊 Welcome to Noir. I can help you put together a complete look, find pieces within your budget, compare products, or answer questions about our store. What are you looking for today?",
            "{$timeGreeting} ✨ Great to have you at Noir Atelier. Tell me what kind of event or style you have in mind, and I'll curate the perfect look for you."
        ];
        $selectedGreeting = $greetings[array_rand($greetings)];

        return [
            'success' => true,
            'message' => $selectedGreeting,
            'recommendation_type' => 'greeting',
            'confidence' => 1.0,
            'confidence_level' => 'high',
            'products' => [],
            'alternatives' => [],
            'actions' => [],
            'follow_up_suggestions' => [
                "✨ Curate a look for a Dinner Date",
                "👔 Smart Casual Office Outfit",
                "👗 Find me a dress under ₦50,000",
                "📦 Where is my order?",
                "🎨 What colors match Black & Olive?",
            ],
        ];
    }

    /**
     * Order Tracking & Shipment Status.
     */
    private function handleOrderTracking(string $prompt, ?int $userId): array
    {
        $orderNumber = null;
        if (preg_match('/#?([a-zA-Z0-9_-]{4,20})/', $prompt, $m)) {
            $orderNumber = trim($m[1]);
        }

        if ($orderNumber) {
            $stmt = $this->db->prepare("SELECT order_number, status, payment_status, total, created_at FROM orders WHERE order_number LIKE ? OR id = ? LIMIT 1");
            $stmt->execute(['%' . $orderNumber . '%', (int)$orderNumber]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $status = ucfirst((string)$order['status']);
                $total = format_currency($order['total']);
                $date = date('M d, Y', strtotime((string)$order['created_at']));
                return [
                    'success' => true,
                    'message' => "Order #{$order['order_number']} is currently **{$status}** (Payment: " . ucfirst((string)$order['payment_status']) . "). Total: {$total}, placed on {$date}.",
                    'recommendation_type' => 'order_status',
                    'confidence' => 1.0,
                    'confidence_level' => 'high',
                    'products' => [],
                    'alternatives' => [],
                    'actions' => [
                        ['type' => 'navigate', 'destination' => '/track-order', 'label' => 'Open Track Order']
                    ],
                    'follow_up_suggestions' => ["View order tracking details", "Return or Exchange piece"],
                ];
            }
        }

        return [
            'success' => true,
            'message' => "You can track your real-time shipment status instantly using your Order Number on our dedicated Track Order portal.",
            'recommendation_type' => 'order_tracking',
            'confidence' => 0.95,
            'confidence_level' => 'high',
            'products' => [],
            'alternatives' => [],
            'actions' => [
                ['type' => 'navigate', 'destination' => '/track-order', 'label' => 'Open Track Order']
            ],
            'follow_up_suggestions' => [
                "Go to Track Order page",
                "Need help with returns?",
            ],
        ];
    }

    private function handleDiscountPromo(): array
    {
        return [
            'success' => true,
            'message' => "Exclusive Atelier Privileges: Enjoy 10% off your curated selection with code **WELCOME10**, or use **NOIRVIP** for complimentary express courier handling on premier orders.",
            'recommendation_type' => 'discount_promo',
            'confidence' => 0.99,
            'confidence_level' => 'high',
            'products' => [],
            'alternatives' => [],
            'follow_up_suggestions' => [
                "Shop New Arrivals",
                "Explore Silk & Outerwear",
            ],
        ];
    }

    private function handlePopularTrending(): array
    {
        $stmt = $this->db->query("SELECT p.id, p.name, p.slug, p.price, p.category, p.subcategory, p.material, p.tags, p.brand, p.featured_image, p.short_description, p.description, p.quantity, p.allow_backorders, COALESCE(c.name, p.category, 'Trending') AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 'published' AND (p.quantity > 0 OR p.allow_backorders = 1) ORDER BY p.price DESC LIMIT 4");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $formatted = array_map(fn($p) => $this->formatProductRecommendation($p, "Trending Atelier Piece"), $products);

        return [
            'success' => true,
            'message' => "Here are the most celebrated pieces from our latest runway edit, curated for timeless elegance and impeccable drape.",
            'recommendation_type' => 'popular_trending',
            'confidence' => 0.96,
            'confidence_level' => 'high',
            'products' => $formatted,
            'alternatives' => [],
            'follow_up_suggestions' => [
                "How should I style these?",
                "Show pieces under ₦50,000",
            ],
        ];
    }

    /**
     * Color Harmony advice.
     */
    private function handleColorAdvice(string $prompt, array $colors, array $profile): array
    {
        $targetColor = !empty($colors[0]) ? strtolower($colors[0]) : 'black';
        $rules = self::COLOR_WHEEL[$targetColor] ?? self::COLOR_WHEEL['black'];
        $matches = implode(', ', array_map('ucfirst', $rules['matches']));

        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.slug, p.price, p.category, p.subcategory, p.material, p.tags, p.brand,
                    p.featured_image, p.short_description, p.description, p.quantity, p.allow_backorders,
                    COALESCE(c.name, p.category, 'Unspecified') AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND (p.quantity > 0 OR p.allow_backorders = 1)
             LIMIT 3"
        );
        $stmt->execute();
        $colorProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = [];
        foreach ($colorProducts as $p) {
            $formatted[] = $this->formatProductRecommendation($p, "Color Harmony Partner: Beautifully complements " . ucfirst($targetColor) . ".");
        }

        $message = ucfirst($targetColor) . " works harmoniously with {$matches}. For a refined, modern look, anchor " . ucfirst($targetColor) . " with neutral monochrome tones or warm earth accents.";

        return [
            'success' => true,
            'message' => $message,
            'recommendation_type' => 'color_advice',
            'confidence' => 0.96,
            'confidence_level' => 'high',
            'products' => $formatted,
            'alternatives' => [],
            'follow_up_suggestions' => [
                "Build an outfit with {$targetColor}",
                "Show me matching shoes and accessories",
            ],
        ];
    }

    /**
     * Calculates composite score for a product.
     */
    private function scoreProduct(
        array $product,
        array $occConfig,
        array $profile,
        array $weights,
        ?float $budget,
        array $styles,
        array $colors
    ): float {
        $score = 0.50; // base score

        $tags = strtolower((string)$product['tags'] . ' ' . (string)$product['category_name'] . ' ' . (string)$product['name'] . ' ' . (string)$product['description']);

        // 1. Occasion Score
        $occMatch = 0.5;
        foreach ($occConfig['styles'] as $st) {
            if (str_contains($tags, strtolower($st))) $occMatch += 0.25;
        }
        foreach ($occConfig['categories'] as $cat) {
            if (str_contains($tags, strtolower($cat))) $occMatch += 0.25;
        }
        $score += min(1.0, $occMatch) * ($weights['occasion'] ?? self::DEFAULT_WEIGHTS['occasion']);

        // 2. User Style Personality
        $styleMatch = 0.5;
        $userStyle = $profile['primary_style'] ?? 'Minimalist';
        if (str_contains($tags, strtolower($userStyle))) $styleMatch += 0.4;
        $score += min(1.0, $styleMatch) * ($weights['style_personality'] ?? self::DEFAULT_WEIGHTS['style_personality']);

        // 3. Color Harmony
        $colorMatch = 0.7;
        $avoided = !empty($profile['avoided_colors']) ? (array)json_decode((string)$profile['avoided_colors'], true) : [];
        foreach ($avoided as $av) {
            if (str_contains($tags, strtolower((string)$av))) {
                $colorMatch -= 0.5;
            }
        }
        $score += max(0.1, min(1.0, $colorMatch)) * ($weights['color_harmony'] ?? self::DEFAULT_WEIGHTS['color_harmony']);

        // 4. Budget Score
        if ($budget !== null && $budget > 0) {
            $price = (float)$product['price'];
            if ($price <= $budget) {
                $score += 1.0 * ($weights['budget'] ?? self::DEFAULT_WEIGHTS['budget']);
            } else {
                $score += 0.3 * ($weights['budget'] ?? self::DEFAULT_WEIGHTS['budget']);
            }
        } else {
            $score += 0.8 * ($weights['budget'] ?? self::DEFAULT_WEIGHTS['budget']);
        }

        // 5. Stock Availability
        $qty = (int)$product['quantity'];
        $avail = $qty > 5 ? 1.0 : ($qty > 0 ? 0.7 : 0.3);
        $score += $avail * ($weights['availability'] ?? self::DEFAULT_WEIGHTS['availability']);

        return round(min(1.0, max(0.1, $score)), 2);
    }

    /**
     * Formats recommendation object for API response.
     */
    private function formatProductRecommendation(array $product, string $stylingRationale): array
    {
        $price = (float)$product['price'];
        return [
            'product_id' => (int)$product['id'],
            'id' => (int)$product['id'],
            'name' => (string)$product['name'],
            'slug' => (string)$product['slug'],
            'price' => $price,
            'formatted_price' => format_currency($price),
            'category' => (string)$product['category_name'],
            'image' => (string)($product['featured_image'] ?: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=600'),
            'styling_rationale' => $stylingRationale,
            'match_score' => (float)($product['score'] ?? 0.90),
            'url' => '/products/' . (int)$product['id'],
        ];
    }

    /**
     * Gets or creates user style profile.
     */
    public function getOrCreateFashionProfile(?int $userId): array
    {
        if ($userId === null || $userId <= 0) {
            return [
                'user_id' => null,
                'primary_style' => 'Minimalist Streetwear',
                'secondary_style' => 'Smart Casual',
                'preferred_colors' => json_encode(['Black', 'Charcoal', 'Navy', 'Olive']),
                'avoided_colors' => json_encode([]),
                'budget_min' => 15000.0,
                'budget_max' => 150000.0,
                'fit_preference' => 'Tailored',
            ];
        }

        $stmt = $this->db->prepare("SELECT * FROM user_fashion_profiles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            return $profile;
        }

        // Create default profile
        $stmt = $this->db->prepare(
            "INSERT INTO user_fashion_profiles (user_id, primary_style, secondary_style, preferred_colors, avoided_colors, budget_min, budget_max, fit_preference)
             VALUES (?, 'Minimalist', 'Streetwear', ?, ?, 15000.00, 150000.00, 'Tailored')"
        );
        $stmt->execute([$userId, json_encode(['Black', 'Navy', 'Olive', 'Cream']), json_encode([])]);

        return $this->getOrCreateFashionProfile($userId);
    }

    /**
     * Dynamically updates user preferences from prompt context.
     */
    private function updateProfileFromPrompt(array &$profile, string $prompt): void
    {
        $lower = strtolower($prompt);

        if (preg_match('/(?:don\'t like|hate|avoid|no|dislike)\s+([a-z\s]+)(?:colors?|tones?)/i', $lower, $m)) {
            $avoidColor = trim($m[1]);
            $currentAvoided = !empty($profile['avoided_colors']) ? (array)json_decode((string)$profile['avoided_colors'], true) : [];
            if (!in_array($avoidColor, $currentAvoided, true)) {
                $currentAvoided[] = ucfirst($avoidColor);
                $profile['avoided_colors'] = json_encode($currentAvoided);
            }
        }
    }

    /**
     * Gets configurable scoring weights.
     */
    private function getScoringWeights(): array
    {
        try {
            $stmt = $this->db->query("SELECT value FROM ai_stylist_config WHERE key_name = 'weights' LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($row && !empty($row['value'])) {
                $decoded = json_decode((string)$row['value'], true);
                if (is_array($decoded)) return array_merge(self::DEFAULT_WEIGHTS, $decoded);
            }
        } catch (\Throwable) {}

        return self::DEFAULT_WEIGHTS;
    }

    /**
     * Records customer demand signals for market analytics and "What to Produce Next".
     */
    private function recordDemandSignal(
        string $sessionId,
        ?int $userId,
        ?string $category,
        ?string $style,
        ?string $color,
        ?float $budget,
        ?string $occasion,
        string $rawQuery,
        bool $hadCatalogMatch,
        int $unmetDemandFlag
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_demand_signals (
                    conversation_id, user_id, session_id, category, style, color, budget, occasion, raw_query, had_catalog_match, unmet_demand_flag, status, created_at
                ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'detected', NOW())
            ");
            $stmt->execute([
                $userId,
                $sessionId,
                $category,
                $style,
                $color,
                $budget,
                $occasion,
                $rawQuery,
                $hadCatalogMatch ? 1 : 0,
                $unmetDemandFlag
            ]);
        } catch (\Throwable $e) {
            error_log('Record demand signal error: ' . $e->getMessage());
        }
    }

    /**
     * Records conversation message.
     */
    private function recordConversation(string $sessionId, ?int $userId, string $prompt, string $reply, array $products): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM ai_stylist_conversations WHERE session_id = ? LIMIT 1");
            $stmt->execute([$sessionId]);
            $convId = $stmt->fetchColumn();

            if (!$convId) {
                $stmt = $this->db->prepare("INSERT INTO ai_stylist_conversations (session_id, user_id, title, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$sessionId, $userId, mb_substr($prompt, 0, 50)]);
                $convId = $this->db->lastInsertId();
            }

            // User message
            $stmt = $this->db->prepare("INSERT INTO ai_stylist_messages (conversation_id, sender, content, created_at) VALUES (?, 'user', ?, NOW())");
            $stmt->execute([$convId, $prompt]);

            // Assistant message
            $stmt = $this->db->prepare("INSERT INTO ai_stylist_messages (conversation_id, sender, content, structured_payload, created_at) VALUES (?, 'assistant', ?, ?, NOW())");
            $stmt->execute([$convId, $reply, json_encode($products)]);
        } catch (\Throwable) {}
    }

    /**
     * Records explicit feedback (Like, Dislike, Save, etc.)
     */
    public function recordFeedback(?int $userId, string $sessionId, ?int $productId, string $action, array $metadata = []): bool
    {
        try {
            if ($userId !== null && $userId > 0) {
                $check = $this->db->prepare("SELECT 1 FROM users WHERE id = ?");
                $check->execute([$userId]);
                if (!$check->fetchColumn()) {
                    $userId = null;
                }
            }

            if ($productId !== null && $productId > 0) {
                $checkProd = $this->db->prepare("SELECT 1 FROM products WHERE id = ?");
                $checkProd->execute([$productId]);
                if (!$checkProd->fetchColumn()) {
                    $productId = null;
                }
            }

            $stmt = $this->db->prepare(
                "INSERT INTO ai_stylist_feedback (user_id, session_id, product_id, recommendation_type, action, metadata, created_at)
                 VALUES (?, ?, ?, 'outfit', ?, ?, NOW())"
            );
            return $stmt->execute([$userId, $sessionId, $productId, $action, json_encode($metadata)]);
        } catch (\Throwable $e) {
            error_log('AI Stylist Feedback error: ' . $e->getMessage());
            return false;
        }
    }
}
