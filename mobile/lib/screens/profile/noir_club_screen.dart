import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import '../../utils/noir_share.dart';
import '../../widgets/perks_guide_sheet.dart';
import 'loyalty_program_screen.dart';
import '../store/ai_stylist_screen.dart';

class NoirClubScreen extends StatefulWidget {
  const NoirClubScreen({super.key});

  @override
  State<NoirClubScreen> createState() => _NoirClubScreenState();
}

class _NoirClubScreenState extends State<NoirClubScreen> {
  int _points = 0;
  String _tier = 'BRONZE VIP';
  String _memberName = 'Noir Member';

  @override
  void initState() {
    super.initState();
    final auth = context.read<AuthProvider>();
    if (auth.user != null) {
      _memberName = auth.user!.name.isNotEmpty ? auth.user!.name : 'Noir Member';
      _points = auth.user!.loyaltyPoints;
      final rawTier = auth.user!.vipTier.isNotEmpty ? auth.user!.vipTier : (_points >= 5000 ? 'Obsidian' : (_points >= 2000 ? 'Gold' : (_points >= 500 ? 'Silver' : 'Bronze')));
      _tier = '${rawTier.toUpperCase()} VIP';
    }
  }

  void _openDigitalPass() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(28),
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(2))),
            const SizedBox(height: 24),
            Text('ATELIER DIGITAL PASS', style: GoogleFonts.urbanist(letterSpacing: 2, fontWeight: FontWeight.w900, fontSize: 16)),
            const SizedBox(height: 8),
            Text('Present this pass at physical Noir flagship ateliers for concierge priority access.', textAlign: TextAlign.center, style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13)),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 16),
                ],
              ),
              child: Column(
                children: [
                  Icon(Icons.qr_code_2_rounded, size: 180, color: Colors.black),
                  const SizedBox(height: 12),
                  Text('NOIR-$_tier-0837', style: GoogleFonts.urbanist(color: Colors.black, fontWeight: FontWeight.bold, fontSize: 14, letterSpacing: 1.5)),
                  Text(_memberName, style: GoogleFonts.urbanist(color: Colors.grey.shade700, fontSize: 12)),
                ],
              ),
            ),
            const SizedBox(height: 28),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(ctx),
                style: ElevatedButton.styleFrom(
                  backgroundColor: NoirTheme.primary,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: Text('Close Pass', style: GoogleFonts.urbanist(color: Colors.white, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _shareVipInvite() {
    final brand = context.read<SettingsProvider>().brandName;
    NoirShare.share(
      context,
      text: 'Join me at the exclusive $brand Atelier Club. Unlock private runway previews, dedicated styling, and bespoke benefits!',
      subject: '$brand VIP Club Invitation',
    );
  }

  void _connectConcierge() async {
    final brand = context.read<SettingsProvider>().brandName;
    final uri = Uri.parse('https://wa.me/2349012345678?text=Hello%20$brand%20VIP%20Concierge,%20I%20would%20like%20to%20book%20a%20private%20styling%20appointment.');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings.of(context);
    final brand = context.watch<SettingsProvider>().brandName;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        centerTitle: true,
        title: Text(
          strings.clubVip(brand),
          style: GoogleFonts.urbanist(fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.help_outline_rounded, color: Color(0xFFD4AF37)),
            tooltip: 'Perks Guide',
            onPressed: () => PerksGuideSheet.show(context),
          ),
          IconButton(
            icon: const Icon(Icons.share_outlined),
            onPressed: _shareVipInvite,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Luxury Membership Card ──
            Container(
              height: 220,
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(24),
                gradient: LinearGradient(
                  colors: [
                    NoirTheme.primary,
                    const Color(0xFF181A16),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                boxShadow: [
                  BoxShadow(
                    color: NoirTheme.primary.withValues(alpha: 0.35),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        brand.toUpperCase(),
                        style: GoogleFonts.cinzel(
                          color: Colors.white,
                          fontSize: 18,
                          letterSpacing: 2,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const Icon(Icons.workspace_premium_rounded, color: Color(0xFFD4AF37)),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _memberName,
                        style: GoogleFonts.urbanist(
                          color: Colors.white,
                          fontSize: 22,
                          letterSpacing: 1,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _tier,
                        style: GoogleFonts.urbanist(
                          color: const Color(0xFFD4AF37),
                          fontSize: 13,
                          letterSpacing: 3,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ],
                  ),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'MEMBER SINCE ${DateTime.now().year}',
                        style: GoogleFonts.urbanist(
                          color: Colors.white54,
                          fontSize: 11,
                          letterSpacing: 1,
                        ),
                      ),
                      Text(
                        '$_points PTS',
                        style: GoogleFonts.urbanist(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // ── How Perks Work Guide Banner ──
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
                          Text('VIP Benefits & Rewards Guide', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 13)),
                          Text('Tap to see how tiers, points & vouchers work', style: GoogleFonts.urbanist(fontSize: 11, color: Colors.grey)),
                        ],
                      ),
                    ),
                    const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Color(0xFFD4AF37)),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 28),

            // ── VIP Exclusive Perks ──
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.qr_code_2_rounded, size: 18),
                    label: const Text('Digital Pass'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: NoirTheme.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: _openDigitalPass,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.stars_rounded, size: 18),
                    label: const Text('Redeem Perks'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: NoirTheme.primary,
                      side: BorderSide(color: NoirTheme.primary),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const LoyaltyProgramScreen()),
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 32),

            Text(
              'VIP Member Privileges',
              style: GoogleFonts.urbanist(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 16),

            _buildBenefitItem(context, Icons.local_shipping_outlined, 'Complimentary Express Delivery', 'Zero delivery fees on all bespoke and ready-to-wear orders.'),
            _buildBenefitItem(context, Icons.lock_open_rounded, 'Private Drop Access (24h Early)', 'Purchase limited capsule runs 24 hours before public release.'),
            _buildBenefitItem(context, Icons.support_agent_rounded, 'Dedicated 24/7 VIP Concierge', 'Direct access to your personal sartorial consultant on WhatsApp.', onTap: _connectConcierge),
            _buildBenefitItem(context, Icons.auto_awesome_rounded, 'Priority AI Wardrobe Curation', 'Unlimited instant outfit capsule generation tailored to your fit.', onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const AiStylistScreen()));
            }),
          ],
        ),
      ),
    );
  }

  Widget _buildBenefitItem(BuildContext context, IconData icon, String title, String desc, {VoidCallback? onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: NoirTheme.primary.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: NoirTheme.primary, size: 22),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 14)),
                  const SizedBox(height: 2),
                  Text(desc, style: GoogleFonts.urbanist(color: Colors.grey.shade600, fontSize: 12)),
                ],
              ),
            ),
            if (onTap != null)
              Icon(Icons.arrow_forward_ios_rounded, size: 14, color: NoirTheme.primary),
          ],
        ),
      ),
    );
  }
}
