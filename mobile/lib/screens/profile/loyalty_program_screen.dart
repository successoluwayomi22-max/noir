import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../providers/auth_provider.dart';
import '../../providers/currency_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import '../../widgets/perks_guide_sheet.dart';

class LoyaltyProgramScreen extends StatefulWidget {
  const LoyaltyProgramScreen({super.key});

  @override
  State<LoyaltyProgramScreen> createState() => _LoyaltyProgramScreenState();
}

class _LoyaltyProgramScreenState extends State<LoyaltyProgramScreen> {
  int _points = 0;
  String _tier = 'BRONZE VIP';
  bool _hasCheckedInToday = false;
  List<String> _claimedVouchers = [];

  @override
  void initState() {
    super.initState();
    _loadState();
  }

  Future<void> _loadState() async {
    final auth = context.read<AuthProvider>();
    final prefs = await SharedPreferences.getInstance();
    if (!mounted) return;
    if (auth.user != null) {
      _points = auth.user!.loyaltyPoints > 0 ? auth.user!.loyaltyPoints : (prefs.getInt('noir_loyalty_points') ?? 0);
      final rawTier = auth.user!.vipTier.isNotEmpty ? auth.user!.vipTier : (_points >= 5000 ? 'Obsidian' : (_points >= 2000 ? 'Gold' : (_points >= 500 ? 'Silver' : 'Bronze')));
      _tier = '${rawTier.toUpperCase()} VIP';
    } else {
      _points = prefs.getInt('noir_loyalty_points') ?? 0;
      final rawTier = _points >= 5000 ? 'Obsidian' : (_points >= 2000 ? 'Gold' : (_points >= 500 ? 'Silver' : 'Bronze'));
      _tier = '${rawTier.toUpperCase()} VIP';
    }
    _hasCheckedInToday = prefs.getBool('noir_daily_checkin_${DateTime.now().day}') ?? false;
    _claimedVouchers = prefs.getStringList('noir_claimed_vouchers') ?? [];
    if (mounted) setState(() {});
  }

  Future<void> _saveState() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('noir_loyalty_points', _points);
    await prefs.setStringList('noir_claimed_vouchers', _claimedVouchers);
  }

  void _claimDailyBonus() async {
    if (_hasCheckedInToday) return;

    setState(() {
      _points += 50;
      _hasCheckedInToday = true;
    });

    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('noir_daily_checkin_${DateTime.now().day}', true);
    await _saveState();

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('✨ Claimed +50 VIP Loyalty Points! Balance: $_points pts'),
        backgroundColor: NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  void _redeemReward(String title, int cost, String voucherCode) async {
    if (_points < cost) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('You need $cost points to redeem $title.'),
          backgroundColor: Colors.red.shade700,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    setState(() {
      _points -= cost;
      _claimedVouchers.insert(0, '$voucherCode ($title)');
    });
    await _saveState();

    // Copy voucher code
    await Clipboard.setData(ClipboardData(text: voucherCode));

    if (!mounted) return;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Theme.of(context).colorScheme.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Icon(Icons.stars_rounded, color: Colors.amber.shade700, size: 28),
            const SizedBox(width: 8),
            Text('Reward Unlocked!', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('You have successfully redeemed "$title".', style: GoogleFonts.urbanist(fontSize: 14)),
            const SizedBox(height: 16),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: NoirTheme.primary.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: NoirTheme.primary),
              ),
              child: Column(
                children: [
                  Text('YOUR VOUCHER CODE', style: GoogleFonts.urbanist(fontSize: 11, letterSpacing: 1.5, color: NoirTheme.primary, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text(voucherCode, style: GoogleFonts.urbanist(fontSize: 22, fontWeight: FontWeight.w900, letterSpacing: 2)),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Text('Code has been copied to your clipboard. Apply it at checkout for instant perk activation.', style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 12)),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Done', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, color: NoirTheme.primary)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings.of(context);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(strings.noirVip, style: GoogleFonts.urbanist(fontWeight: FontWeight.w800)),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.help_outline_rounded, color: Color(0xFFD4AF37)),
            tooltip: 'Rewards Guide',
            onPressed: () => PerksGuideSheet.show(context),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // ── Tier Progress Card ──
            Container(
              padding: const EdgeInsets.all(24.0),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [NoirTheme.primary, const Color(0xFF1E201B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: NoirTheme.primary.withValues(alpha: 0.3),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(_tier, style: GoogleFonts.urbanist(color: const Color(0xFFD4AF37), fontSize: 16, fontWeight: FontWeight.w900, letterSpacing: 1.5)),
                      const Icon(Icons.workspace_premium_rounded, color: Color(0xFFD4AF37), size: 28),
                    ],
                  ),
                  const SizedBox(height: 20),
                  Text('$_points', style: GoogleFonts.urbanist(color: Colors.white, fontSize: 44, fontWeight: FontWeight.w900)),
                  Text(strings.loyaltyPoints, style: GoogleFonts.urbanist(color: Colors.white70, fontSize: 13)),
                  const SizedBox(height: 20),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: LinearProgressIndicator(
                      value: (_points % 2500) / 2500.0,
                      backgroundColor: Colors.white24,
                      color: const Color(0xFFD4AF37),
                      minHeight: 8,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Progress to Platinum VIP', style: GoogleFonts.urbanist(color: Colors.white70, fontSize: 12)),
                      Text('${2500 - (_points % 2500)} pts to next tier', style: GoogleFonts.urbanist(color: const Color(0xFFD4AF37), fontSize: 12, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ],
              ),
            ),
            // ── How It Works Interactive Guide Banner ──
            InkWell(
              onTap: () => PerksGuideSheet.show(context),
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: BoxDecoration(
                  color: const Color(0xFFD4AF37).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFD4AF37).withValues(alpha: 0.4)),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: const BoxDecoration(
                        color: Color(0xFFD4AF37),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.help_outline_rounded, color: Colors.black, size: 18),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('New to Perks & Rewards?', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 13)),
                          Text('Tap here to view the step-by-step points guide', style: GoogleFonts.urbanist(fontSize: 11, color: Colors.grey)),
                        ],
                      ),
                    ),
                    const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Color(0xFFD4AF37)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // ── Daily Check-in Card ──
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  Icon(Icons.calendar_today_rounded, color: NoirTheme.primary),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Daily Atelier Check-in', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 14)),
                        Text('+50 points every 24 hours', style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                  ),
                  ElevatedButton(
                    onPressed: _hasCheckedInToday ? null : _claimDailyBonus,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: Text(_hasCheckedInToday ? 'Claimed' : 'Claim', style: const TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 28),

            // ── Active Claimed Vouchers ──
            if (_claimedVouchers.isNotEmpty) ...[
              Text('My Active Vouchers', style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w900)),
              const SizedBox(height: 12),
              ..._claimedVouchers.map((v) {
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: NoirTheme.primary.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.4)),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.confirmation_number_outlined, color: NoirTheme.primary, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(v, style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 13)),
                      ),
                      IconButton(
                        icon: const Icon(Icons.copy_rounded, size: 16),
                        onPressed: () {
                          final code = v.split(' ').first;
                          Clipboard.setData(ClipboardData(text: code));
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text('Copied $code to clipboard!'),
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
              const SizedBox(height: 20),
            ],

            // ── Rewards List ──
            Text(strings.rewards, style: GoogleFonts.urbanist(fontSize: 22, fontWeight: FontWeight.w900)),
            const SizedBox(height: 4),
            Text('Redeem your points for considered luxury perks and vouchers.', style: GoogleFonts.urbanist(fontSize: 13, color: Colors.grey.shade600)),
            const SizedBox(height: 16),
            _buildRewardCard('Free Express Delivery', '100% waived shipping on your next order.', 500, 'EXPRESS-NOIR-500', Icons.local_shipping_outlined, strings.redeem),
            _buildRewardCard('${context.watch<CurrencyProvider>().format(10000)} Atelier Credit', 'Instant discount voucher applied at checkout.', 1000, 'CREDIT-10K-NOIR', Icons.discount_outlined, strings.redeem),
            _buildRewardCard('Exclusive VIP Hat & Cap', 'Limited edition Noir signature embroidered cap.', 2000, 'VIP-CAP-2000', Icons.checkroom_rounded, strings.redeem),
            _buildRewardCard('Private 1-on-1 Styling', '60-minute virtual styling with lead artisan.', 4000, 'STYLIST-VIP-4000', Icons.auto_awesome_outlined, strings.redeem),
          ],
        ),
      ),
    );
  }

  Widget _buildRewardCard(String title, String subtitle, int cost, String voucherCode, IconData icon, String redeemLabel) {
    bool canAfford = _points >= cost;
    return Container(
      margin: const EdgeInsets.only(bottom: 14.0),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: NoirTheme.primary.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: NoirTheme.primary, size: 24),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 15)),
                  const SizedBox(height: 2),
                  Text(subtitle, style: GoogleFonts.urbanist(color: Colors.grey.shade600, fontSize: 12)),
                  const SizedBox(height: 6),
                  Text('$cost Points', style: GoogleFonts.urbanist(color: NoirTheme.primary, fontWeight: FontWeight.w800, fontSize: 13)),
                ],
              ),
            ),
            const SizedBox(width: 8),
            ElevatedButton(
              onPressed: canAfford ? () => _redeemReward(title, cost, voucherCode) : null,
              style: ElevatedButton.styleFrom(
                backgroundColor: NoirTheme.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 0),
                elevation: 0,
              ),
              child: Text(redeemLabel, style: GoogleFonts.urbanist(fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      ),
    );
  }
}
