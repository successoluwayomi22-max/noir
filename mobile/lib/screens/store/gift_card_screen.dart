import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';
import 'dart:math';

import '../../providers/auth_provider.dart';
import '../../providers/currency_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';

class GiftCardScreen extends StatefulWidget {
  const GiftCardScreen({super.key});

  @override
  State<GiftCardScreen> createState() => _GiftCardScreenState();
}

class _GiftCardScreenState extends State<GiftCardScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  double _selectedAmountValue = 25000;
  final _emailController = TextEditingController();
  final _messageController = TextEditingController();
  final _checkCodeController = TextEditingController();

  List<String> _myGiftCards = [];
  String? _checkedBalanceResult;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadGiftCards();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _emailController.dispose();
    _messageController.dispose();
    _checkCodeController.dispose();
    super.dispose();
  }

  Future<void> _loadGiftCards() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _myGiftCards = prefs.getStringList('noir_user_gift_cards') ?? ['NOIR-GIFT-25K-7749 (₦25,000)'];
    });
  }

  Future<void> _saveGiftCards() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList('noir_user_gift_cards', _myGiftCards);
  }

  void _buyGiftCard() async {
    final email = _emailController.text.trim();
    if (email.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Please enter recipient email.'),
          backgroundColor: Colors.red.shade700,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    final double amount = _selectedAmountValue;

    final formattedSelected = context.read<CurrencyProvider>().format(_selectedAmountValue);

    try {
      final api = context.read<AuthProvider>().api;
      final res = await api.buyGiftCard(amount, email, _messageController.text.trim());
      final issuedCode = res['code'] ?? 'NOIR-GIFT-${Random().nextInt(9000) + 1000}-${Random().nextInt(9000) + 1000}';
      final cardEntry = '$issuedCode ($formattedSelected)';

      setState(() {
        _myGiftCards.insert(0, cardEntry);
      });
      _saveGiftCards();

      if (!mounted) return;
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          backgroundColor: Theme.of(context).colorScheme.surface,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          title: Row(
            children: [
              Icon(Icons.card_giftcard_rounded, color: NoirTheme.primary, size: 28),
              const SizedBox(width: 8),
              Text('Gift Card Issued!', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold)),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Your ${context.read<CurrencyProvider>().format(_selectedAmountValue)} digital gift card is officially active in the Noir database for $email.', style: GoogleFonts.urbanist(fontSize: 14)),
              const SizedBox(height: 16),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: NoirTheme.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: NoirTheme.primary),
                ),
                child: Column(
                  children: [
                    Text('VOUCHER CODE', style: GoogleFonts.urbanist(fontSize: 10, letterSpacing: 1.5, color: NoirTheme.primary, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(issuedCode, style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w900, letterSpacing: 1.5)),
                    const SizedBox(height: 4),
                    Text(context.read<CurrencyProvider>().format(_selectedAmountValue), style: GoogleFonts.urbanist(fontSize: 13, color: Colors.grey)),
                  ],
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                Clipboard.setData(ClipboardData(text: issuedCode));
                Navigator.pop(ctx);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Copied $issuedCode to clipboard!'),
                    backgroundColor: NoirTheme.primary,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              },
              child: Text('Copy Code', style: GoogleFonts.urbanist(color: NoirTheme.primary, fontWeight: FontWeight.bold)),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx),
              style: ElevatedButton.styleFrom(backgroundColor: NoirTheme.primary),
              child: const Text('Done', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      );

      _emailController.clear();
      _messageController.clear();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Purchase error: ${e.toString().replaceAll('Exception: ', '')}'),
          backgroundColor: Colors.red.shade700,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  void _checkBalance() async {
    final code = _checkCodeController.text.trim().toUpperCase();
    if (code.isEmpty) return;

    try {
      final api = context.read<AuthProvider>().api;
      final res = await api.checkGiftCard(code);
      setState(() {
        final bal = (res['balance'] as num?)?.toDouble() ?? 0.0;
        final cp = context.read<CurrencyProvider>();
        final formattedBal = cp.format(bal);
        _checkedBalanceResult = 'Active Balance: $formattedBal (${res['status']?.toString().toUpperCase()})';
      });
    } catch (e) {
      setState(() {
        _checkedBalanceResult = 'Gift Card "$code" not found or invalid.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('Gift Cards & Vouchers', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800)),
        centerTitle: true,
        elevation: 0,
        backgroundColor: Colors.transparent,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: NoirTheme.primary,
          labelColor: Theme.of(context).colorScheme.onSurface,
          labelStyle: GoogleFonts.urbanist(fontWeight: FontWeight.bold),
          tabs: const [
            Tab(text: 'Purchase Card'),
            Tab(text: 'Check Balance & Wallet'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // ── Tab 1: Buy Gift Card ──
          SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Luxury Card Mockup
                AspectRatio(
                  aspectRatio: 1.6,
                  child: Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(24),
                      gradient: LinearGradient(
                        colors: [NoirTheme.primary, const Color(0xFF1B1D18)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: NoirTheme.primary.withValues(alpha: 0.3),
                          blurRadius: 18,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(context.read<SettingsProvider>().brandName.toUpperCase(), style: GoogleFonts.cinzel(color: Colors.white, fontSize: 18, letterSpacing: 2, fontWeight: FontWeight.bold)),
                              const Icon(Icons.card_giftcard_rounded, color: Color(0xFFD4AF37)),
                            ],
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(context.read<CurrencyProvider>().format(_selectedAmountValue), style: GoogleFonts.urbanist(color: Colors.white, fontSize: 34, fontWeight: FontWeight.w900)),
                              const SizedBox(height: 4),
                              Text('VALID FOR ALL ATELIER PIECES & ONLINE', style: GoogleFonts.urbanist(color: Colors.white60, fontSize: 10, letterSpacing: 1.2)),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 32),

                Text('Select Value', style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w800)),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [10000.0, 25000.0, 50000.0, 100000.0].map((amount) {
                    final cp = context.read<CurrencyProvider>();
                    bool isSelected = _selectedAmountValue == amount;
                    return InkWell(
                      onTap: () => setState(() => _selectedAmountValue = amount),
                      borderRadius: BorderRadius.circular(16),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                        decoration: BoxDecoration(
                          color: isSelected ? NoirTheme.primary : Theme.of(context).colorScheme.surface,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: isSelected ? NoirTheme.primary : Theme.of(context).colorScheme.outline.withValues(alpha: 0.4)),
                        ),
                        child: Text(
                          cp.format(amount),
                          style: GoogleFonts.urbanist(
                            fontWeight: FontWeight.w800,
                            fontSize: 14,
                            color: isSelected ? Colors.white : Theme.of(context).colorScheme.onSurface,
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 24),

                Text('Recipient Information', style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w800)),
                const SizedBox(height: 12),
                TextField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    labelText: 'Recipient Email Address',
                    prefixIcon: const Icon(Icons.email_outlined),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _messageController,
                  maxLines: 2,
                  decoration: InputDecoration(
                    labelText: 'Personal Gift Message (Optional)',
                    prefixIcon: const Icon(Icons.edit_note_rounded),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
                const SizedBox(height: 28),

                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _buyGiftCard,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: Text('Purchase Gift Card (${context.read<CurrencyProvider>().format(_selectedAmountValue)})', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 16)),
                  ),
                ),
              ],
            ),
          ),

          // ── Tab 2: Check Balance & Saved Cards ──
          SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Check Card Balance', style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Text('Enter your 16-character Noir gift card or promo code.', style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13)),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _checkCodeController,
                        decoration: InputDecoration(
                          hintText: 'e.g. NOIR-GIFT-25K-7749',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    ElevatedButton(
                      onPressed: _checkBalance,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: NoirTheme.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      child: const Text('Check', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
                if (_checkedBalanceResult != null) ...[
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: _checkedBalanceResult!.contains('Active Balance') ? Colors.green.withValues(alpha: 0.1) : Colors.red.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: _checkedBalanceResult!.contains('Active Balance') ? Colors.green : Colors.red),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          _checkedBalanceResult!.contains('Active Balance') ? Icons.check_circle_outline_rounded : Icons.error_outline_rounded,
                          color: _checkedBalanceResult!.contains('Active Balance') ? Colors.green : Colors.red,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            _checkedBalanceResult!,
                            style: GoogleFonts.urbanist(
                              fontWeight: FontWeight.bold,
                              color: _checkedBalanceResult!.contains('Active Balance') ? Colors.green.shade800 : Colors.red.shade800,
                              fontSize: 14,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 36),

                Text('My Saved Gift Cards (${_myGiftCards.length})', style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 12),
                ..._myGiftCards.map((card) {
                  return Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.card_giftcard_rounded, color: NoirTheme.primary),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(card, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 14)),
                        ),
                        IconButton(
                          icon: const Icon(Icons.copy_rounded, size: 18),
                          onPressed: () {
                            final code = card.split(' ').first;
                            Clipboard.setData(ClipboardData(text: code));
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text('Copied $code!'),
                                backgroundColor: NoirTheme.primary,
                                behavior: SnackBarBehavior.floating,
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  );
                }),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
