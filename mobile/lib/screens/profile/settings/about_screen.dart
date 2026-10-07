import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:provider/provider.dart';
import '../../../../providers/settings_provider.dart';
import '../../../../l10n/app_strings.dart';
import 'terms_screen.dart';
import 'privacy_screen.dart';

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  Future<void> _launchUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final settingsProv = Provider.of<SettingsProvider>(context);
    final storeName = settingsProv.storeName;
    final storeEmail = settingsProv.settings?.storeEmail ?? 'support@noirandco.com';
    final storePhone = settingsProv.settings?.storePhone ?? '+2349012345678';

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          AppStrings.of(context).aboutStore(storeName),
          style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            const SizedBox(height: 32),
            // Brand logo block
            Center(
              child: Column(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(22),
                    child: Image.asset(
                      'assets/images/noir_logo.jpg',
                      width: 80,
                      height: 80,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => Image.asset(
                        'assets/app_icon.jpg',
                        width: 80,
                        height: 80,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    storeName,
                    style: GoogleFonts.urbanist(fontSize: 26, fontWeight: FontWeight.w900),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Version 1.2.0 (Build 54)',
                    style: GoogleFonts.urbanist(
                      fontSize: 13,
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 36),
                    child: Text(
                      '$storeName is an artisanal luxury fashion destination delivering curated contemporary tailoring and timeless aesthetics directly to you.',
                      style: GoogleFonts.urbanist(
                        fontSize: 14,
                        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                        height: 1.6,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 36),

            // Info tiles
            _buildSection(context, [
              _buildTile(context, Icons.language_rounded, 'Website', 'noir.atelier.com', () {
                _launchUrl('http://192.168.61.108/noir/public');
              }),
              _buildTile(context, Icons.email_outlined, 'Support Email', storeEmail, () => _launchUrl('mailto:$storeEmail')),
              _buildTile(context, Icons.phone_outlined, 'Contact Phone', storePhone, () => _launchUrl('tel:${storePhone.replaceAll(' ', '')}')),
            ]),

            const SizedBox(height: 8),

            _buildSection(context, [
              _buildTile(context, Icons.description_outlined, 'Terms of Service', null, () {
                Navigator.push(context, MaterialPageRoute(builder: (_) => TermsScreen()));
              }),
              _buildTile(context, Icons.lock_outline_rounded, 'Privacy Policy', null, () {
                Navigator.push(context, MaterialPageRoute(builder: (_) => PrivacyScreen()));
              }),
              _buildTile(context, Icons.code_rounded, 'Open Source Licenses', null, () {
                showLicensePage(
                  context: context,
                  applicationName: storeName,
                  applicationVersion: '1.0.0',
                );
              }),
            ]),

            SizedBox(height: 40),

            Text(
              '© ${DateTime.now().year} $storeName. All rights reserved.',
              style: GoogleFonts.urbanist(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
            ),
            SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildSection(BuildContext context, List<Widget> children) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: 20),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).colorScheme.outline),
      ),
      child: Column(children: children),
    );
  }

  Widget _buildTile(BuildContext context, IconData icon, String title, String? subtitle, VoidCallback onTap) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Row(
            children: [
              Icon(icon, size: 20, color: Theme.of(context).colorScheme.primary),
              SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w600)),
                    if (subtitle != null) ...[
                      SizedBox(height: 2),
                      Text(subtitle, style: GoogleFonts.urbanist(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7))),
                    ],
                  ],
                ),
              ),
              Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
            ],
          ),
        ),
      ),
    );
  }
}
