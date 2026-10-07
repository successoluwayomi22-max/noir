import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class PrivacyScreen extends StatelessWidget {
  const PrivacyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text('Privacy Policy', style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800)),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Privacy Policy',
              style: GoogleFonts.urbanist(
                fontSize: 24,
                fontWeight: FontWeight.w900,
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Last updated: August 2026',
              style: GoogleFonts.urbanist(
                fontSize: 14,
                color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
              ),
            ),
            SizedBox(height: 24),
            _buildSection(context, '1. Information We Collect', 
              'We collect information that you provide directly to us, including your name, email address, shipping address, and payment information when you make a purchase or create an account.'),
            _buildSection(context, '2. How We Use Information', 
              'We use the information we collect to process your orders, communicate with you about products and promotions, and improve our services and application experience.'),
            _buildSection(context, '3. Data Security', 
              'We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.'),
            _buildSection(context, '4. Third-Party Services', 
              'We may use third-party service providers to facilitate our services, process payments, or perform application-related services. These third parties have access to your personal data only to perform these tasks on our behalf.'),
          ],
        ),
      ),
    );
  }

  Widget _buildSection(BuildContext context, String title, String content) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: GoogleFonts.urbanist(
              fontSize: 18,
              fontWeight: FontWeight.w700,
            ),
          ),
          SizedBox(height: 8),
          Text(
            content,
            style: GoogleFonts.urbanist(
              fontSize: 15,
              height: 1.6,
              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.8),
            ),
          ),
        ],
      ),
    );
  }
}
