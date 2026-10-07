import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../providers/cart_provider.dart';
import '../../providers/currency_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';
import 'product_detail_screen.dart';

class AiStylistScreen extends StatefulWidget {
  const AiStylistScreen({super.key});

  @override
  State<AiStylistScreen> createState() => _AiStylistScreenState();
}

class _StylistMessage {
  final String text;
  final bool isAi;
  final String? recommendationType;
  final double? confidence;
  final String? occasion;
  final String? styleProfile;
  final double? totalPrice;
  final String? budgetStatus;
  final int? winnerProductId;
  final List<Map<String, dynamic>>? recommendations;
  final List<Map<String, dynamic>>? alternatives;
  final List<String>? followUpSuggestions;
  final DateTime timestamp;

  _StylistMessage({
    required this.text,
    required this.isAi,
    this.recommendationType,
    this.confidence,
    this.occasion,
    this.styleProfile,
    this.totalPrice,
    this.budgetStatus,
    this.winnerProductId,
    this.recommendations,
    this.alternatives,
    this.followUpSuggestions,
    DateTime? timestamp,
  }) : timestamp = timestamp ?? DateTime.now();
}

class _AiStylistScreenState extends State<AiStylistScreen> {
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = false;

  // Personal Style Profile State
  String _selectedGender = 'Men\'s Tailoring';
  String _selectedOccasion = 'Executive & Gala';
  String _selectedPalette = 'Obsidian Monochrome';
  String _selectedFit = 'Tailored & Structured';
  String _selectedBudget = '₦70,000 - ₦150,000';
  bool _profileCompleted = false;

  late final List<_StylistMessage> _messages;
  List<String> _currentSuggestions = [
    'Dinner tonight under ₦80,000',
    'Smart casual work ensemble',
    'What colors match black and olive?',
    'Which wardrobe staples am I missing?',
  ];

  @override
  void initState() {
    super.initState();
    _messages = [
      _StylistMessage(
        text: 'Bonjour! I am your Noir Personal Stylist & Outfit Decision-Maker. Tell me what occasion you are dressing for, your preferred colors, or budget, and I will curate a bespoke ensemble from our live atelier catalog.',
        isAi: true,
      ),
    ];
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

  void _openConsultationModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => DraggableScrollableSheet(
          initialChildSize: 0.85,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          expand: false,
          builder: (_, scrollCtrl) => SingleChildScrollView(
            controller: scrollCtrl,
            padding: const EdgeInsets.fromLTRB(24, 16, 24, 32),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: Colors.grey.withValues(alpha: 0.4), borderRadius: BorderRadius.circular(2)),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: NoirTheme.primary.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(Icons.auto_awesome_rounded, color: NoirTheme.primary, size: 20),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Bespoke Style Profile', style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800)),
                        Text('Personalize your fashion decisions', style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                  ],
                ),
                const Divider(height: 32),

                // 1. Gender / Silhouette
                _buildSectionTitle('1. Style Silhouette'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    'Men\'s Tailoring',
                    'Women\'s Haute Couture',
                    'Genderless Minimalist',
                  ].map((g) => _buildChoiceChip(g, _selectedGender == g, () {
                    setModalState(() => _selectedGender = g);
                    setState(() => _selectedGender = g);
                  })).toList(),
                ),
                const SizedBox(height: 20),

                // 2. Primary Occasion
                _buildSectionTitle('2. Primary Occasion'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    'Executive & Gala',
                    'Dinner & Date Night',
                    'Smart Casual & Office',
                    'Streetwear & Casual',
                    'Resort & Beach',
                  ].map((o) => _buildChoiceChip(o, _selectedOccasion == o, () {
                    setModalState(() => _selectedOccasion = o);
                    setState(() => _selectedOccasion = o);
                  })).toList(),
                ),
                const SizedBox(height: 20),

                // 3. Color Palette
                _buildSectionTitle('3. Signature Palette'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    'Obsidian Monochrome',
                    'Warm Earth & Camel',
                    'Navy & Minimalist Blue',
                    'Emerald & Deep Jewel',
                  ].map((p) => _buildChoiceChip(p, _selectedPalette == p, () {
                    setModalState(() => _selectedPalette = p);
                    setState(() => _selectedPalette = p);
                  })).toList(),
                ),
                const SizedBox(height: 20),

                // 4. Preferred Fit
                _buildSectionTitle('4. Silhouette Fit'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    'Tailored & Structured',
                    'Oversized & Relaxed',
                    'Slim European Cut',
                  ].map((f) => _buildChoiceChip(f, _selectedFit == f, () {
                    setModalState(() => _selectedFit = f);
                    setState(() => _selectedFit = f);
                  })).toList(),
                ),
                const SizedBox(height: 20),

                // 5. Budget Target
                _buildSectionTitle('5. Target Investment Range'),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    'Under ₦50,000',
                    '₦50,000 - ₦100,000',
                    '₦100,000 - ₦250,000',
                    '₦250,000+',
                  ].map((b) => _buildChoiceChip(b, _selectedBudget == b, () {
                    setModalState(() => _selectedBudget = b);
                    setState(() => _selectedBudget = b);
                  })).toList(),
                ),
                const SizedBox(height: 32),

                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: () {
                      setState(() => _profileCompleted = true);
                      Navigator.pop(ctx);
                      _sendMessage('Curate my bespoke $_selectedOccasion ensemble in $_selectedPalette with $_selectedFit fit');
                    },
                    child: Text('Curate Bespoke Capsule', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 15)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(title, style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 13, color: Colors.grey.shade400)),
    );
  }

  Widget _buildChoiceChip(String label, bool isSelected, VoidCallback onSelected) {
    return InkWell(
      onTap: onSelected,
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? NoirTheme.primary : Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? NoirTheme.primary : Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
          ),
        ),
        child: Text(
          label,
          style: GoogleFonts.urbanist(
            color: isSelected ? Colors.white : Theme.of(context).colorScheme.onSurface,
            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
            fontSize: 12,
          ),
        ),
      ),
    );
  }

  Future<void> _sendMessage(String query) async {
    final text = query.trim();
    if (text.isEmpty) return;

    setState(() {
      _messages.add(_StylistMessage(text: text, isAi: false));
      _isLoading = true;
    });
    _controller.clear();
    _scrollToBottom();

    // Prepare message history for multi-turn context
    final historyList = _messages
        .take(_messages.length - 1)
        .map((m) => {
              'role': m.isAi ? 'assistant' : 'user',
              'content': m.text,
            })
        .toList();

    try {
      final api = context.read<NoirApiClient>();
      final res = await api.getAiStyling(text, history: historyList);
      final replyText = res['reply']?.toString() ?? res['message']?.toString() ?? 'Here is my curated recommendation for you.';
      final recType = res['recommendation_type']?.toString() ?? 'outfit';
      final confidence = (res['confidence'] as num?)?.toDouble() ?? 0.92;
      final occasion = res['occasion']?.toString();
      final styleProfile = res['style_profile']?.toString();
      final totalPrice = (res['total_price'] as num?)?.toDouble();
      final budgetStatus = res['budget_status']?.toString();
      final winnerId = (res['winner_product_id'] as num?)?.toInt();

      final List<Map<String, dynamic>> recs = [];
      if (res['products'] is List) {
        for (final p in res['products']) {
          if (p is Map<String, dynamic>) recs.add(p);
        }
      }

      final List<Map<String, dynamic>> alts = [];
      if (res['alternatives'] is List) {
        for (final a in res['alternatives']) {
          if (a is Map<String, dynamic>) alts.add(a);
        }
      }

      final List<String> followUps = [];
      if (res['follow_up_suggestions'] is List) {
        for (final s in res['follow_up_suggestions']) {
          followUps.add(s.toString());
        }
      }

      if (mounted) {
        setState(() {
          _isLoading = false;
          if (followUps.isNotEmpty) {
            _currentSuggestions = followUps;
          }
          _messages.add(
            _StylistMessage(
              text: replyText,
              isAi: true,
              recommendationType: recType,
              confidence: confidence,
              occasion: occasion,
              styleProfile: styleProfile,
              totalPrice: totalPrice,
              budgetStatus: budgetStatus,
              winnerProductId: winnerId,
              recommendations: recs,
              alternatives: alts,
              followUpSuggestions: followUps,
            ),
          );
        });
        _scrollToBottom();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _messages.add(
            _StylistMessage(
              text: 'Our styling atelier is momentarily busy. Please ask again or explore our signature collection.',
              isAi: true,
            ),
          );
        });
        _scrollToBottom();
      }
    }
  }

  void _addEntireCapsuleToBag(List<Map<String, dynamic>> items) {
    final cart = context.read<CartProvider>();
    for (final it in items) {
      final pid = (it['product_id'] ?? it['id'] as num?)?.toInt() ?? 1;
      cart.addItem(
        CartItem(
          productId: pid,
          name: it['name']?.toString() ?? 'Noir Atelier Garment',
          price: (it['price'] as num?)?.toDouble() ?? 50000.0,
          image: it['image']?.toString(),
        ),
      );
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('✨ Added ${items.length} pieces to your shopping bag!'),
        backgroundColor: NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  void _handleFeedback(String action, {int? productId}) {
    context.read<NoirApiClient>().sendStylistFeedback(action, productId: productId);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(action == 'like' ? '✨ Saved to your style profile preferences!' : 'Preference updated for future recommendations.'),
        backgroundColor: NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  void _saveOutfitToWardrobe(_StylistMessage message) {
    if (message.recommendations == null || message.recommendations!.isEmpty) return;

    final pids = message.recommendations!
        .map((p) => (p['product_id'] ?? p['id'] as num?)?.toInt() ?? 1)
        .toList()
        .cast<int>();

    context.read<NoirApiClient>().saveAiOutfit(
      title: '${message.occasion ?? "Curated"} Ensemble',
      occasion: message.occasion ?? 'General',
      totalPrice: message.totalPrice ?? 0.0,
      productIds: pids,
      notes: message.text,
    );

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text('✨ Curated ensemble saved to your Noir Wardrobe!'),
        backgroundColor: NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final brandName = context.read<SettingsProvider>().brandName;

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
              child: Icon(Icons.auto_awesome_rounded, color: NoirTheme.primary, size: 18),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$brandName AI Stylist',
                  style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 16),
                ),
                Text(
                  'Decision-Maker & Outfit Architect',
                  style: GoogleFonts.urbanist(fontSize: 11, color: Colors.grey),
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.tune_rounded),
            tooltip: 'Style Profile',
            onPressed: _openConsultationModal,
          ),
        ],
      ),
      body: Column(
        children: [
          // Banner for Bespoke Profile
          InkWell(
            onTap: _openConsultationModal,
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              decoration: BoxDecoration(
                color: NoirTheme.primary.withValues(alpha: 0.08),
                border: Border(bottom: BorderSide(color: NoirTheme.primary.withValues(alpha: 0.2))),
              ),
              child: Row(
                children: [
                  Icon(Icons.palette_outlined, color: NoirTheme.primary, size: 18),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      _profileCompleted
                          ? 'Style Profile: $_selectedGender • $_selectedOccasion'
                          : '✨ Set Custom Fashion Profile (Occasion, Colors & Budget)',
                      style: GoogleFonts.urbanist(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Theme.of(context).colorScheme.onSurface,
                      ),
                    ),
                  ),
                  Icon(Icons.chevron_right_rounded, size: 18, color: Colors.grey.shade400),
                ],
              ),
            ),
          ),

          // Messages
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
              itemCount: _messages.length + (_isLoading ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == _messages.length && _isLoading) {
                  return _buildLoadingBubble();
                }
                return _buildChatBubble(_messages[index]);
              },
            ),
          ),

          // Suggested Prompts
          if (_currentSuggestions.isNotEmpty && !_isLoading)
            Container(
              height: 38,
              margin: const EdgeInsets.only(bottom: 8),
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: _currentSuggestions.length,
                separatorBuilder: (_, _) => const SizedBox(width: 8),
                itemBuilder: (context, i) {
                  final sug = _currentSuggestions[i];
                  return ActionChip(
                    label: Text(sug, style: GoogleFonts.urbanist(fontSize: 11, fontWeight: FontWeight.w600)),
                    backgroundColor: Theme.of(context).colorScheme.surface,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(20),
                      side: BorderSide(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.2)),
                    ),
                    onPressed: () => _sendMessage(sug),
                  );
                },
              ),
            ),

          // Input Bar
          Container(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 20),
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              border: Border(top: BorderSide(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.15))),
            ),
            child: SafeArea(
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.25)),
                      ),
                      child: TextField(
                        controller: _controller,
                        textInputAction: TextInputAction.send,
                        onSubmitted: _sendMessage,
                        style: GoogleFonts.urbanist(fontSize: 14),
                        decoration: InputDecoration(
                          hintText: 'Ask your stylist (e.g. Gala dinner in obsidian)...',
                          hintStyle: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13),
                          border: InputBorder.none,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  CircleAvatar(
                    backgroundColor: NoirTheme.primary,
                    radius: 22,
                    child: IconButton(
                      icon: const Icon(Icons.arrow_upward_rounded, color: Colors.white, size: 20),
                      onPressed: () => _sendMessage(_controller.text),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLoadingBubble() {
    return Container(
      margin: const EdgeInsets.only(bottom: 18),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            backgroundColor: NoirTheme.primary.withValues(alpha: 0.15),
            radius: 16,
            child: Icon(Icons.auto_awesome_rounded, color: NoirTheme.primary, size: 16),
          ),
          const SizedBox(width: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.25)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                SizedBox(
                  width: 14,
                  height: 14,
                  child: CircularProgressIndicator(strokeWidth: 2, color: NoirTheme.primary),
                ),
                const SizedBox(width: 10),
                Text(
                  'Evaluating catalog & color harmony...',
                  style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChatBubble(_StylistMessage message) {
    final isAi = message.isAi;
    return Container(
      margin: const EdgeInsets.only(bottom: 18),
      child: Column(
        crossAxisAlignment: isAi ? CrossAxisAlignment.start : CrossAxisAlignment.end,
        children: [
          Row(
            mainAxisAlignment: isAi ? MainAxisAlignment.start : MainAxisAlignment.end,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (isAi) ...[
                CircleAvatar(
                  backgroundColor: NoirTheme.primary.withValues(alpha: 0.15),
                  radius: 16,
                  child: Icon(Icons.auto_awesome_rounded, color: NoirTheme.primary, size: 16),
                ),
                const SizedBox(width: 10),
              ],
              Flexible(
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: isAi ? Theme.of(context).colorScheme.surface : NoirTheme.primary,
                    borderRadius: BorderRadius.circular(18),
                    border: isAi ? Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.25)) : null,
                  ),
                  child: Text(
                    message.text,
                    style: GoogleFonts.urbanist(
                      fontSize: 14,
                      height: 1.4,
                      color: isAi ? Theme.of(context).colorScheme.onSurface : Colors.white,
                      fontWeight: isAi ? FontWeight.w500 : FontWeight.w700,
                    ),
                  ),
                ),
              ),
            ],
          ),

          // Structured Outfit / Decision Card
          if (message.recommendations != null && message.recommendations!.isNotEmpty) ...[
            const SizedBox(height: 12),
            Padding(
              padding: const EdgeInsets.only(left: 42),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Meta Header: Confidence score and occasion badge
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFF2E7D32).withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.check_circle_rounded, size: 12, color: Color(0xFF2E7D32)),
                              const SizedBox(width: 4),
                              Text(
                                '${((message.confidence ?? 0.92) * 100).toInt()}% Confidence Match',
                                style: GoogleFonts.urbanist(fontSize: 10, fontWeight: FontWeight.w800, color: const Color(0xFF2E7D32)),
                              ),
                            ],
                          ),
                        ),
                        if (message.occasion != null)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              message.occasion!,
                              style: GoogleFonts.urbanist(fontSize: 10, fontWeight: FontWeight.w700),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Products list
                    ...message.recommendations!.map((item) => _buildRecommendationCard(
                          item,
                          isWinner: message.winnerProductId != null && ((item['product_id'] ?? item['id']) == message.winnerProductId),
                        )),

                    // Total price and CTA
                    if (message.totalPrice != null && message.totalPrice! > 0) ...[
                      const Divider(height: 24),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Ensemble Total', style: GoogleFonts.urbanist(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.w700)),
                              Text(
                                context.read<CurrencyProvider>().format(message.totalPrice!),
                                style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w900, color: NoirTheme.primary),
                              ),
                            ],
                          ),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: NoirTheme.primary,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            onPressed: () => _addEntireCapsuleToBag(message.recommendations!),
                            icon: const Icon(Icons.shopping_bag_outlined, size: 16),
                            label: Text('Add Ensemble', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 12)),
                          ),
                        ],
                      ),
                    ],

                    // Interactive Feedback & Save Bar
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Text('Helpful?', style: GoogleFonts.urbanist(fontSize: 10, color: Colors.grey)),
                        const Spacer(),
                        InkWell(
                          onTap: () => _handleFeedback('like'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: Colors.grey.withValues(alpha: 0.3)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.thumb_up_alt_outlined, size: 12),
                                const SizedBox(width: 4),
                                Text('Yes', style: GoogleFonts.urbanist(fontSize: 10, fontWeight: FontWeight.w700)),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        InkWell(
                          onTap: () => _handleFeedback('not_my_style'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: Colors.grey.withValues(alpha: 0.3)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.thumb_down_alt_outlined, size: 12),
                                const SizedBox(width: 4),
                                Text('Not my style', style: GoogleFonts.urbanist(fontSize: 10, fontWeight: FontWeight.w700)),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        InkWell(
                          onTap: () => _saveOutfitToWardrobe(message),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(6),
                              color: NoirTheme.primary.withValues(alpha: 0.1),
                            ),
                            child: Row(
                              children: [
                                Icon(Icons.bookmark_outline_rounded, size: 12, color: NoirTheme.primary),
                                const SizedBox(width: 4),
                                Text('Save', style: GoogleFonts.urbanist(fontSize: 10, fontWeight: FontWeight.w800, color: NoirTheme.primary)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildRecommendationCard(Map<String, dynamic> item, {bool isWinner = false}) {
    final name = item['name']?.toString() ?? 'Noir Garment';
    final price = (item['price'] as num?)?.toDouble() ?? 50000.0;
    final image = item['image']?.toString() ?? '';
    final reason = item['reason']?.toString() ?? 'Signature curated piece';
    final category = item['category']?.toString() ?? 'Apparel';
    final id = (item['product_id'] ?? item['id'] as num?)?.toInt() ?? 1;

    return InkWell(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => ProductDetailScreen(productId: id)),
        );
      },
      borderRadius: BorderRadius.circular(14),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: isWinner ? NoirTheme.primary.withValues(alpha: 0.06) : Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isWinner ? NoirTheme.primary : Theme.of(context).colorScheme.outline.withValues(alpha: 0.2),
            width: isWinner ? 1.5 : 1.0,
          ),
        ),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: CachedNetworkImage(
                imageUrl: image.startsWith('http') ? image : 'http://10.0.2.2:8000$image',
                width: 54,
                height: 54,
                fit: BoxFit.cover,
                errorWidget: (_, _, _) => Container(
                  width: 54,
                  height: 54,
                  color: Colors.grey.shade900,
                  child: const Icon(Icons.checkroom_outlined, size: 20),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                        decoration: BoxDecoration(
                          color: isWinner ? NoirTheme.primary : NoirTheme.primary.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          isWinner ? '👑 TOP PICK' : category.toUpperCase(),
                          style: TextStyle(
                            color: isWinner ? Colors.white : NoirTheme.primary,
                            fontSize: 8,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 12),
                  ),
                  Text(
                    reason,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.urbanist(fontSize: 10, color: Colors.grey, height: 1.2),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    context.read<CurrencyProvider>().format(price),
                    style: GoogleFonts.urbanist(color: NoirTheme.primary, fontWeight: FontWeight.w800, fontSize: 12),
                  ),
                ],
              ),
            ),
            IconButton(
              icon: Icon(Icons.add_shopping_cart_rounded, color: NoirTheme.primary, size: 18),
              onPressed: () {
                context.read<CartProvider>().addItem(
                      CartItem(
                        productId: id,
                        name: name,
                        price: price,
                        image: image,
                      ),
                    );
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Added $name to your bag!'),
                    backgroundColor: NoirTheme.primary,
                    behavior: SnackBarBehavior.floating,
                    duration: const Duration(seconds: 1),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
