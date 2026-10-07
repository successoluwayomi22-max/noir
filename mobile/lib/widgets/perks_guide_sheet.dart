import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../theme.dart';
import '../providers/settings_provider.dart';

class PerksGuideSheet extends StatelessWidget {
  const PerksGuideSheet({super.key});

  static void show(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.82,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (_, scrollController) => Container(
          decoration: BoxDecoration(
            color: Theme.of(context).scaffoldBackgroundColor,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
          ),
          child: PerksGuideSheetContent(scrollController: scrollController),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return const PerksGuideSheetContent();
  }
}

class PerksGuideSheetContent extends StatelessWidget {
  final ScrollController? scrollController;

  const PerksGuideSheetContent({super.key, this.scrollController});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Handle bar
        Container(
          margin: const EdgeInsets.only(top: 14, bottom: 8),
          width: 44,
          height: 5,
          decoration: BoxDecoration(
            color: Colors.grey.withValues(alpha: 0.3),
            borderRadius: BorderRadius.circular(3),
          ),
        ),

        // Header
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: const Color(0xFFD4AF37).withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(Icons.stars_rounded, color: Color(0xFFD4AF37), size: 26),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Perks & Rewards Guide',
                      style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w900),
                    ),
                    Text(
                      'How to earn, redeem & utilize ${context.read<SettingsProvider>().brandName} VIP privileges',
                      style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey),
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close_rounded),
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
        ),
        const Divider(height: 1),

        // Scrollable Guide Steps
        Expanded(
          child: ListView(
            controller: scrollController,
            padding: const EdgeInsets.fromLTRB(24, 16, 24, 32),
            children: [
              _buildStepCard(
                context,
                stepNumber: '1',
                title: 'How You Earn Points',
                icon: Icons.monetization_on_outlined,
                accentColor: const Color(0xFF4CAF50),
                description:
                    '• **Shopping**: Earn 1 Loyalty Point for every ₦100 spent on any garment, collection, or accessory.\n'
                    '• **Daily Check-In**: Open the app daily and tap "Claim +50 Pts" on the Loyalty screen for instant free points.\n'
                    '• **Reviews & Community**: Sharing verified looks gives up to +100 bonus points.',
              ),
              const SizedBox(height: 16),

              _buildStepCard(
                context,
                stepNumber: '2',
                title: 'VIP Tiers & Automatic Upgrades',
                icon: Icons.workspace_premium_rounded,
                accentColor: const Color(0xFFD4AF37),
                description:
                    '• **Bronze VIP** (0 – 499 pts): Standard boutique access & lookbooks.\n'
                    '• **Silver VIP** (500 – 1,999 pts): 5% discount vouchers & early drop alerts.\n'
                    '• **Gold VIP** (2,000 – 4,999 pts): 10% discount codes & priority atelier dispatch.\n'
                    '• **Obsidian VIP** (5,000+ pts): 15% in-store privileges, champagne fitting appointments, and free express delivery.',
              ),
              const SizedBox(height: 16),

              _buildStepCard(
                context,
                stepNumber: '3',
                title: 'How to Redeem & Use Vouchers',
                icon: Icons.confirmation_number_outlined,
                accentColor: const Color(0xFF2196F3),
                description:
                    '1. Choose a voucher tier on the Loyalty screen (₦5,000, ₦10,000, ₦30,000, or ₦75,000).\n'
                    '2. Tap **Redeem** — points are deducted and a real dynamic discount coupon is created.\n'
                    '3. The code is copied to your clipboard and listed in your "Claimed Vouchers" history.\n'
                    '4. Paste the coupon in your Shopping Bag or Checkout to deduct cash value instantly.',
              ),
              const SizedBox(height: 16),

              _buildStepCard(
                context,
                stepNumber: '4',
                title: 'In-Store Digital Atelier Pass',
                icon: Icons.qr_code_2_rounded,
                accentColor: const Color(0xFF9C27B0),
                description:
                    '• Tap **"Open Digital Pass"** on the ${context.read<SettingsProvider>().brandName} VIP screen.\n'
                    '• Present your personalized encrypted QR code at any physical ${context.read<SettingsProvider>().brandName} Flagship Atelier.\n'
                    '• Unlock in-person bespoke tailoring consultations, private styling suites, and VIP refreshments.',
              ),
              const SizedBox(height: 24),

              // Got It Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: NoirTheme.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  child: Text(
                    'I Understand & Want to Earn Points',
                    style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, color: Colors.white, fontSize: 14),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStepCard(
    BuildContext context, {
    required String stepNumber,
    required String title,
    required IconData icon,
    required Color accentColor,
    required String description,
  }) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: accentColor.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Icon(icon, color: accentColor, size: 18),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  title,
                  style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 15),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Step $stepNumber',
                  style: GoogleFonts.urbanist(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: NoirTheme.primary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            description,
            style: GoogleFonts.urbanist(
              fontSize: 13,
              height: 1.45,
              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.85),
            ),
          ),
        ],
      ),
    );
  }
}
