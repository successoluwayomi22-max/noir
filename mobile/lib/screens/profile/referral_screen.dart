import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';

import '../../utils/noir_share.dart';

class ReferralScreen extends StatelessWidget {
  const ReferralScreen({super.key});

  final String referralCode = 'NOIR-VIP-7829';

  void _copyCode(BuildContext context) {
    Clipboard.setData(ClipboardData(text: referralCode));
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text('Referral code copied to clipboard!'),
      backgroundColor: NoirTheme.primary,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
    ));
  }

  void _shareCode(BuildContext context) {
    final brand = context.read<SettingsProvider>().brandName;
    NoirShare.share(
      context,
      text: 'Use my exclusive referral code $referralCode at $brand to get discounts off your first order!',
      subject: 'Get a special discount at $brand',
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('Invite Friends', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800)),
        centerTitle: true,
        elevation: 0,
        backgroundColor: Colors.transparent,
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Container(
              padding: EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.favorite_rounded, size: 80, color: Theme.of(context).colorScheme.primary),
            ),
            SizedBox(height: 32),
            Text('Give ₦5,000, Get ₦5,000', style: GoogleFonts.urbanist(fontSize: 28, fontWeight: FontWeight.w900)),
            SizedBox(height: 16),
            Text(
              'Share your unique code. When a friend makes their first purchase, you both get ₦5,000 in store credit.',
              textAlign: TextAlign.center,
              style: GoogleFonts.urbanist(fontSize: 16, color: Colors.grey.shade600, height: 1.5),
            ),
            SizedBox(height: 48),
            Text('YOUR REFERRAL CODE', style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w800, letterSpacing: 1.5, color: Colors.grey.shade500)),
            SizedBox(height: 12),
            GestureDetector(
              onTap: () => _copyCode(context),
              child: Container(
                padding: EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                decoration: BoxDecoration(
                  color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade900 : Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      referralCode,
                      style: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.w900, letterSpacing: 2, color: Theme.of(context).colorScheme.primary),
                    ),
                    Icon(Icons.copy_rounded, color: Colors.grey.shade600),
                  ],
                ),
              ),
            ),
            SizedBox(height: 48),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _shareCode(context),
                icon: Icon(Icons.share_rounded),
                label: Text('Share Code', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 16)),
                style: ElevatedButton.styleFrom(
                  padding: EdgeInsets.symmetric(vertical: 18),
                  backgroundColor: NoirTheme.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  elevation: 0,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
