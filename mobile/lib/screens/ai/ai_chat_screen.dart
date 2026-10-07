import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';

class AiChatScreen extends StatefulWidget {
  final String title;
  final String initialMessage;

  const AiChatScreen({
    super.key,
    this.title = 'Concierge',
    this.initialMessage = '',
  });

  @override
  State<AiChatScreen> createState() => _AiChatScreenState();
}

class _Message {
  final String text;
  final bool isUser;
  final DateTime timestamp;

  _Message(this.text, this.isUser, {DateTime? timestamp})
      : timestamp = timestamp ?? DateTime.now();
}

class _AiChatScreenState extends State<AiChatScreen> {
  final List<_Message> _messages = [];
  final List<Map<String, String>> _history = [];
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = false;

  final List<String> _quickTopics = [
    '📦 Order Tracking',
    '✨ VIP Perks & Rewards',
    '🧵 Fabrics & Sizing Guide',
    '🚚 Shipping & Delivery Times',
    '💳 Payment Gateways',
    '🔄 Returns & Exchange Policy',
    '🛡️ Authenticity Verification',
    '🎁 Digital Gift Cards',
  ];

  @override
  void initState() {
    super.initState();
    // Defer accessing context to first frame
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        final storeName = context.read<SettingsProvider>().brandName;
        final greeting = widget.initialMessage.isNotEmpty
            ? widget.initialMessage
            : 'Bonjour! I am your $storeName Luxury Concierge. How may I assist you today with orders, boutique styling, bespoke sizing, or VIP rewards?';
        setState(() {
          _messages.add(_Message(greeting, false));
        });
      }
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  String _generateIntelligentNoirSupportReply(String query) {
    final q = query.toLowerCase();
    final storeName = context.read<SettingsProvider>().brandName;

    // Greetings & Introductions
    if (q.contains('hi') || q.contains('hello') || q.contains('hey') || q.contains('bonjour') || q.contains('help') || q == 'ai') {
      return 'Bonjour and welcome to $storeName Atelier Concierge! ✨\n\n'
          'I am your bespoke AI Stylist & Customer Experience Director. Here are a few ways I can assist you right now:\n\n'
          '• 📦 **Track & Manage Orders** — Live status & white-glove courier timeline\n'
          '• 👗 **Runway Outfit Architecture** — Curated looks for Galas, Weddings, Dinners, and Boardrooms\n'
          '• 💵 **Currencies & International Payments** — Switch to USD, EUR, GBP, or NGN\n'
          '• 🌐 **Multi-Language Support** — English, French, Yorùbá, Hausa, Igbo, German, Arabic\n'
          '• 🎁 **Promo Codes & VIP Perks** — Claim WELCOME10 for 10% off\n'
          '• 🧵 **Made-to-Measure & Sizing** — Bespoke measurement guidelines\n\n'
          'What would you like assistance with today?';
    }

    // Currency & Exchange
    if (q.contains('currency') || q.contains('dollar') || q.contains('usd') || q.contains('naira') || q.contains('euro') || q.contains('pounds') || q.contains('exchange') || q.contains('rate')) {
      return '💵 **Multi-Currency & Exchange Rates**:\n\n'
          'We support automatic live multi-currency conversions:\n'
          '• **NGN (₦)**: Nigerian Naira (Base Store Currency)\n'
          '• **USD (\$)**: US Dollars (~₦1,550/\$1)\n'
          '• **EUR (€)**: Euros (~₦1,680/€1)\n'
          '• **GBP (£)**: British Pounds (~₦1,980/£1)\n\n'
          'To switch your active currency:\n'
          '1. Go to **Profile ➔ Settings ➔ Active Currency**\n'
          '2. Or on the website footer, click the **Currency Selector** to switch instantly across the entire store.';
    }

    // Language & Translations
    if (q.contains('language') || q.contains('french') || q.contains('yoruba') || q.contains('hausa') || q.contains('igbo') || q.contains('german') || q.contains('arabic') || q.contains('translate')) {
      return '🌐 **Multi-Language Atelier Support**:\n\n'
          'Our mobile boutique and web storefront support 8 international & regional languages:\n'
          '• 🇬🇧 **English (Default)**\n'
          '• 🇫🇷 **Français (French)**\n'
          '• 🇳🇬 **Yorùbá (Yoruba)**\n'
          '• 🇳🇬 **Hausa**\n'
          '• 🇳🇬 **Asụsụ Igbo**\n'
          '• 🇩🇪 **Deutsch (German)**\n'
          '• 🇪🇸 **Español (Spanish)**\n'
          '• 🇸🇦 **العربية (Arabic)**\n\n'
          'You can switch your language anytime in **Profile ➔ Settings ➔ Language**. All buttons, menus, and guides update immediately.';
    }

    // Discounts & Promo Codes
    if (q.contains('discount') || q.contains('promo') || q.contains('coupon') || q.contains('code') || q.contains('offer') || q.contains('sale')) {
      return '🏷️ **Active Atelier Promo Codes**:\n\n'
          '• **WELCOME10**: Enjoy **10% OFF** your first order.\n'
          '• **NOIRVIP**: Complimentary express shipping & private packaging on orders over ₦100,000.\n'
          '• **LUXE20**: Exclusive **20% OFF** when purchasing any 2 or more tailored pieces.\n\n'
          'Simply enter the promo code at checkout in your Shopping Bag to apply the reduction.';
    }

    // Order Tracking & Delivery
    if (q.contains('track') || q.contains('order') || q.contains('status') || q.contains('where is my') || q.contains('shipping') || q.contains('delivery')) {
      return '📦 **Order Tracking & White-Glove Logistics**:\n\n'
          '• **Lagos Metro**: Same-day dispatch (delivered within 4–8 hours via Noir White-Glove Courier).\n'
          '• **Nationwide (Nigeria)**: 48–72 hours via DHL Express.\n'
          '• **International**: 3–5 business days via DHL International Luxe.\n\n'
          'You can check live tracking on the **Order Tracking** screen by entering your Order Number (e.g. #1042). Every step from atelier cutting to dispatch is logged in real-time.';
    }

    // Styling & Occasions
    if (q.contains('outfit') || q.contains('wear') || q.contains('style') || q.contains('dinner') || q.contains('gala') || q.contains('wedding') || q.contains('casual') || q.contains('suit') || q.contains('dress')) {
      return '👗 **Runway Outfit Architecture & Styling Guide**:\n\n'
          '• **Black Tie & Gala**: Our *Obsidian Double-Breasted Wool Crepe Blazer* paired with silk trousers and patent leather loafers.\n'
          '• **Romantic Dinner Date**: *Mulberry Silk Evening Shirt* draped over tailored trousers with subtle gold accessories.\n'
          '• **High-Impact Boardroom**: *Biella Super 130s Structured Suit* in olive charcoal.\n'
          '• **Weekend Luxe**: *520gsm Heavyweight Egyptian Cotton Tee* with tailored linen trousers.\n\n'
          'Would you like me to open our **AI Stylist & Visual Match** screen to compose a customized outfit for your specific event?';
    }

    // VIP Perks & Rewards
    if (q.contains('perk') || q.contains('reward') || q.contains('point') || q.contains('loyalty') || q.contains('vip')) {
      return '✨ **$storeName VIP Club & Loyalty Privileges**:\n\n'
          '1. **Earning Points**: You earn **1 Loyalty Point for every ₦100 spent**.\n'
          '2. **Daily Check-In**: Open the app daily to claim complimentary **+50 VIP Points**.\n'
          '3. **Voucher Redemption**:\n'
          '   • 500 Pts ➔ **₦5,000 Voucher**\n'
          '   • 1,000 Pts ➔ **₦10,000 Voucher**\n'
          '   • 2,500 Pts ➔ **₦30,000 Atelier Credit**\n'
          '   • 5,000 Pts ➔ **₦75,000 Bespoke Haute Voucher**\n'
          '4. **Digital NFC/QR Pass**: Access private VIP fitting appointments and runway champagne previews.';
    }

    // Returns & Refunds
    if (q.contains('return') || q.contains('refund') || q.contains('exchange') || q.contains('policy')) {
      return '🔄 **Complimentary Boutique Returns & Exchanges**:\n\n'
          '• **14-Day Window**: Complimentary returns within 14 days of delivery.\n'
          '• **Condition**: Pieces must be unworn, unwashed, with security tags and cedar garment packaging intact.\n'
          '• **Bespoke Tailoring**: Made-to-measure pieces receive free fitting adjustments at our atelier.\n'
          '• **Refund Processing**: Credited directly to your original payment method in 3–5 business days.';
    }

    // Contact Human Agent
    if (q.contains('agent') || q.contains('human') || q.contains('call') || q.contains('phone') || q.contains('whatsapp') || q.contains('talk')) {
      return '📞 **Direct Human Atelier Concierge**:\n\n'
          'Prefer to speak directly with our senior styling team?\n'
          '• **WhatsApp Live**: +234 901 234 5678\n'
          '• **Email**: support@noirandco.com\n'
          '• **Atelier Hours**: Monday – Saturday, 9:00 AM – 8:00 PM (WAT)\n\n'
          'You can also tap the **WhatsApp** or **Call** button on the Contact screen for immediate connection.';
    }

    // Sizing & Measurements
    if (q.contains('size') || q.contains('sizing') || q.contains('fit') || q.contains('measurement')) {
      return '📐 **$storeName Sizing Matrix**:\n\n'
          '• **S (38)**: Chest 36–38" | Waist 29–31"\n'
          '• **M (40)**: Chest 39–41" | Waist 32–34"\n'
          '• **L (42)**: Chest 42–44" | Waist 35–37"\n'
          '• **XL (44)**: Chest 45–47" | Waist 38–40"\n'
          '• **XXL (46)**: Chest 48–50" | Waist 41–43"\n\n'
          'Our silhouettes feature an architectural modern drop. If you prefer a relaxed drape, choose your true size; for a structured sharp fit, size down.';
    }

    return 'Thank you for your question! As your $storeName Luxury Concierge, I can assist with:\n\n'
        '1. **Live Order Tracking & Courier Verification**\n'
        '2. **VIP Club Rewards & Promo Codes** (`WELCOME10`)\n'
        '3. **Personalized Runway Styling for Any Occasion**\n'
        '4. **Multi-Currency (USD, EUR, GBP, NGN) & Languages**\n'
        '5. **Bespoke Made-to-Measure Tailoring Advice**\n\n'
        'Please feel free to ask any question or tap a quick topic above!';
  }

  Future<void> _sendMessage(String query) async {
    final text = query.trim();
    if (text.isEmpty) return;

    setState(() {
      _messages.add(_Message(text, true));
      _isLoading = true;
    });
    _controller.clear();
    _scrollToBottom();

    // Try backend AI API first
    try {
      final api = context.read<AuthProvider>().api;
      final baseUrl = api.baseUrl;
      final response = await http.post(
        Uri.parse('$baseUrl/api/v1/mobile/assistant/chat'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          if (api.token != null) 'Authorization': 'Bearer ${api.token}',
        },
        body: jsonEncode({'message': text, 'history': _history}),
      ).timeout(const Duration(seconds: 4));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final reply = data['reply'] ?? data['response'];
        if (reply != null && reply.toString().trim().isNotEmpty) {
          _history.add({'role': 'user', 'content': text});
          _history.add({'role': 'assistant', 'content': reply.toString()});
          setState(() {
            _messages.add(_Message(reply.toString(), false));
            _isLoading = false;
          });
          _scrollToBottom();
          return;
        }
      }
    } catch (_) {}

    // Instant comprehensive offline concierge engine
    Future.delayed(const Duration(milliseconds: 600), () {
      if (!mounted) return;
      final reply = _generateIntelligentNoirSupportReply(text);
      _history.add({'role': 'user', 'content': text});
      _history.add({'role': 'assistant', 'content': reply});
      setState(() {
        _messages.add(_Message(reply, false));
        _isLoading = false;
      });
      _scrollToBottom();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: NoirTheme.primary.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(Icons.support_agent_rounded, color: NoirTheme.primary, size: 20),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.title, style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w800)),
                Row(
                  children: [
                    Container(width: 6, height: 6, decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle)),
                    const SizedBox(width: 4),
                    Text('Live Support', style: GoogleFonts.urbanist(fontSize: 11, color: Colors.grey)),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          // Quick Topics Bar
          SizedBox(
            height: 48,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              scrollDirection: Axis.horizontal,
              itemCount: _quickTopics.length,
              separatorBuilder: (_, _) => const SizedBox(width: 8),
              itemBuilder: (context, index) {
                final topic = _quickTopics[index];
                return ActionChip(
                  label: Text(
                    topic,
                    style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                  backgroundColor: Theme.of(context).colorScheme.surface,
                  side: BorderSide(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  onPressed: () => _sendMessage(topic),
                );
              },
            ),
          ),
          const Divider(height: 1),

          // Message List
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final m = _messages[index];
                return _buildMessageBubble(m);
              },
            ),
          ),

          // Loading indicator
          if (_isLoading)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
              child: Row(
                children: [
                  SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2, color: NoirTheme.primary),
                  ),
                  const SizedBox(width: 10),
                  Text('Noir Concierge is typing...', style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ),

          // Input Bar
          Container(
            padding: EdgeInsets.fromLTRB(16, 8, 16, MediaQuery.of(context).viewInsets.bottom + 12),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              border: Border(top: BorderSide(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.2))),
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    onSubmitted: _sendMessage,
                    decoration: InputDecoration(
                      hintText: 'Ask concierge (e.g. How do VIP rewards work?)...',
                      hintStyle: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.send_rounded, color: NoirTheme.primary),
                  onPressed: () => _sendMessage(_controller.text),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMessageBubble(_Message m) {
    final isUser = m.isUser;
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      child: Row(
        mainAxisAlignment: isUser ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (!isUser) ...[
            CircleAvatar(
              backgroundColor: NoirTheme.primary.withValues(alpha: 0.15),
              radius: 16,
              child: Icon(Icons.support_agent_rounded, color: NoirTheme.primary, size: 18),
            ),
            const SizedBox(width: 10),
          ],
          Flexible(
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: isUser ? NoirTheme.primary : Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(18),
                border: isUser ? null : Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.25)),
              ),
              child: Text(
                m.text,
                style: GoogleFonts.urbanist(
                  fontSize: 14,
                  height: 1.4,
                  color: isUser ? Colors.white : Theme.of(context).colorScheme.onSurface,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
