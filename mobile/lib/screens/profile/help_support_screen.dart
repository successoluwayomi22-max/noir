import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:provider/provider.dart';
import '../../providers/settings_provider.dart';
import '../ai/ai_chat_screen.dart';
import '../../l10n/app_strings.dart';

class HelpSupportScreen extends StatefulWidget {
  const HelpSupportScreen({super.key});

  @override
  State<HelpSupportScreen> createState() => _HelpSupportScreenState();
}

class _HelpSupportScreenState extends State<HelpSupportScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _messageController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _sending = false;
  bool _sent = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _nameController.dispose();
    _emailController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _sendMessage() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _sending = true);
    // Simulate network delay; replace with real API call if needed
    await Future.delayed(Duration(seconds: 2));
    if (mounted) {
      setState(() {
        _sending = false;
        _sent = true;
      });
    }
  }

  Future<void> _launchEmail() async {
    final settings = Provider.of<SettingsProvider>(context, listen: false).settings;
    final email = settings?.storeEmail ?? 'support@noirandco.com';
    final uri = Uri(
      scheme: 'mailto',
      path: email,
      query: 'subject=Support%20Request&body=Hello%20Noir%20Team,',
    );
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      await Clipboard.setData(ClipboardData(text: email));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Email copied: $email', style: GoogleFonts.urbanist()),
            backgroundColor: Theme.of(context).colorScheme.primary,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        );
      }
    }
  }

  Future<void> _launchPhone() async {
    final settings = Provider.of<SettingsProvider>(context, listen: false).settings;
    final phone = settings?.storePhone ?? '+2349012345678';
    final uri = Uri(scheme: 'tel', path: phone.replaceAll(' ', ''));
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _launchWhatsApp() async {
    final settings = Provider.of<SettingsProvider>(context, listen: false).settings;
    final phone = settings?.storePhone ?? '+2349012345678';
    final whatsappNumber = phone.replaceAll(RegExp(r'[^0-9]'), '');
    final uri = Uri.parse('https://wa.me/$whatsappNumber?text=Hello%20Noir%20%26%20Co.%2C%20I%20need%20help');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

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
        title: Text(AppStrings.of(context).helpAndSupport,
            style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800)),
        bottom: TabBar(
          controller: _tabController,
          labelColor: Theme.of(context).colorScheme.primary,
          unselectedLabelColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
          indicatorColor: Theme.of(context).colorScheme.primary,
          indicatorWeight: 2,
          labelStyle: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 14),
          tabs: [Tab(text: AppStrings.of(context).faq), Tab(text: AppStrings.of(context).contactUs)],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildFaqTab(context),
          _buildContactTab(),
        ],
      ),
    );
  }

  Widget _buildFaqTab(BuildContext context) {
    return ListView(
      padding: EdgeInsets.all(20),
      children: [
        // AI Support card
        _SupportCard(
          icon: Icons.smart_toy_outlined,
          title: 'AI Customer Support',
          subtitle: 'Instant answers, available 24/7',
          iconColor: Color(0xFF687158),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => AiChatScreen(
                title: 'AI Support',
                initialMessage:
                    'Hello! I\'m the ${context.read<SettingsProvider>().brandName} AI Assistant. I can help with orders, returns, sizing, and more. What do you need help with today?',
              ),
            ),
          ),
        ),
        SizedBox(height: 28),
        Text(AppStrings.of(context).faq,
            style: GoogleFonts.urbanist(fontSize: 17, fontWeight: FontWeight.w800, color: Theme.of(context).colorScheme.onSurface)),
        SizedBox(height: 12),
        ..._faqs.map((faq) => _FaqItem(question: faq['q']!, answer: faq['a']!)),
      ],
    );
  }

  Widget _buildContactTab() {
    final settings = Provider.of<SettingsProvider>(context).settings;
    final email = settings?.storeEmail ?? 'support@noirandco.com';
    final phone = settings?.storePhone ?? '+234 901 234 5678';
    
    return SingleChildScrollView(
      padding: EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Quick contact options
          Row(
            children: [
              Expanded(
                child: _QuickContactCard(
                  icon: Icons.email_outlined,
                  label: 'Email',
                  value: email.length > 15 ? '${email.substring(0, 15)}...' : email,
                  onTap: _launchEmail,
                ),
              ),
              SizedBox(width: 12),
              Expanded(
                child: _QuickContactCard(
                  icon: Icons.phone_outlined,
                  label: 'Call',
                  value: phone.replaceAll(' ', '\n'),
                  onTap: _launchPhone,
                ),
              ),
              SizedBox(width: 12),
              Expanded(
                child: _QuickContactCard(
                  icon: Icons.chat_outlined,
                  label: 'WhatsApp',
                  value: 'Message\nus directly',
                  onTap: _launchWhatsApp,
                ),
              ),
            ],
          ),
          SizedBox(height: 28),
          Text(AppStrings.of(context).sendMessage,
              style: GoogleFonts.urbanist(fontSize: 17, fontWeight: FontWeight.w800)),
          SizedBox(height: 4),
          Text('We typically respond within 24 hours.',
              style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5))),
          SizedBox(height: 16),
          if (_sent) ...[
            Container(
              padding: EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Color(0xFFEEF0E7),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Color(0xFF687158).withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  Icon(Icons.check_circle_rounded, color: Color(0xFF687158), size: 28),
                  SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(AppStrings.of(context).messageSent,
                            style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 16)),
                        Text('We\'ll get back to you at ${_emailController.text}',
                            style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7))),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(height: 16),
            TextButton(
              onPressed: () => setState(() {
                _sent = false;
                _nameController.clear();
                _emailController.clear();
                _messageController.clear();
              }),
              child: Text('Send another message',
                  style: GoogleFonts.urbanist(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5))),
            ),
          ] else
            Form(
              key: _formKey,
              child: Column(
                children: [
                  _buildField(
                    controller: _nameController,
                    label: AppStrings.of(context).yourName,
                    icon: Icons.person_outline_rounded,
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Please enter your name' : null,
                  ),
                  SizedBox(height: 14),
                  _buildField(
                    controller: _emailController,
                    label: AppStrings.of(context).emailAddress,
                    icon: Icons.email_outlined,
                    keyboardType: TextInputType.emailAddress,
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return 'Please enter your email';
                      if (!RegExp(r'^[\w.-]+@[\w.-]+\.\w+$').hasMatch(v.trim())) {
                        return 'Please enter a valid email';
                      }
                      return null;
                    },
                  ),
                  SizedBox(height: 14),
                  _buildField(
                    controller: _messageController,
                    label: AppStrings.of(context).howCanWeHelp,
                    icon: Icons.chat_bubble_outline_rounded,
                    maxLines: 5,
                    validator: (v) =>
                        (v == null || v.trim().length < 10) ? 'Please describe your issue (min 10 chars)' : null,
                  ),
                  SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _sending ? null : _sendMessage,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Theme.of(context).colorScheme.primary,
                        foregroundColor: Colors.white,
                        padding: EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      child: _sending
                          ? SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(AppStrings.of(context).sendMessage,
                              style: GoogleFonts.urbanist(
                                  fontWeight: FontWeight.w700, fontSize: 15, letterSpacing: 0.5)),
                    ),
                  ),
                ],
              ),
            ),
          SizedBox(height: 32),
          // Business hours
          Container(
            padding: EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Theme.of(context).colorScheme.outline),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Business Hours',
                    style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 14)),
                SizedBox(height: 8),
                _buildHoursRow('Monday – Friday', '9:00 AM – 6:00 PM'),
                _buildHoursRow('Saturday', '10:00 AM – 4:00 PM'),
                _buildHoursRow('Sunday', 'Closed'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    TextInputType? keyboardType,
    int maxLines = 1,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      style: GoogleFonts.urbanist(fontSize: 15),
      validator: validator,
      decoration: InputDecoration(
        labelText: label,
        labelStyle: GoogleFonts.urbanist(fontSize: 14, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
        prefixIcon: Icon(icon, size: 20, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
        filled: true,
        fillColor: Theme.of(context).colorScheme.surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Theme.of(context).colorScheme.outline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Theme.of(context).colorScheme.outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Theme.of(context).colorScheme.primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.redAccent),
        ),
        contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: maxLines > 1 ? 14 : 0),
      ),
    );
  }

  Widget _buildHoursRow(String day, String hours) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          Expanded(child: Text(day, style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)))),
          Text(hours, style: GoogleFonts.urbanist(fontSize: 13, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

// ─── FAQ Data ────────────────────────────────────────────────
List<Map<String, String>> _faqs = [
  {
    'q': 'How do I track my order?',
    'a': 'Go to Profile → My Orders. Tap on any order to see its status and tracking details. You\'ll also receive an email with a tracking link once your order ships.',
  },
  {
    'q': 'What is your return policy?',
    'a': 'We accept returns within 7 days of delivery. Items must be unworn, unwashed, and in original packaging with tags attached. Sale items are final sale and cannot be returned.',
  },
  {
    'q': 'How long does delivery take?',
    'a': 'Standard delivery takes 2–5 business days within Nigeria. Express delivery (1–2 days) is available at checkout for an additional fee.',
  },
  {
    'q': 'Can I change or cancel my order?',
    'a': 'Orders can be modified or cancelled within 1 hour of placing them. After that, the order is processed and cannot be changed. Contact support immediately if you need to make changes.',
  },
  {
    'q': 'What payment methods do you accept?',
    'a': 'We accept all major debit/credit cards (Visa, Mastercard), bank transfers, and Paystack. All transactions are secured with 256-bit SSL encryption.',
  },
  {
    'q': 'How do I return an item?',
    'a': 'Contact our support team within 7 days of delivery to initiate a return. We\'ll email you a prepaid return label. Refunds are processed within 5–7 business days after we receive the item.',
  },
  {
    'q': 'Are all items authentic?',
    'a': 'Yes. Every item sold on our platform is 100% authentic. We source directly from brands and authorized distributors, and each item goes through our quality check process.',
  },
  {
    'q': 'How do I find my size?',
    'a': 'Check the size guide on each product page. Measurements are listed in both Nigerian/African sizing and international standards. When in doubt, size up for a more relaxed fit.',
  },
];

// ─── Widgets ──────────────────────────────────────────────────
class _SupportCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Color iconColor;
  final VoidCallback onTap;

  const _SupportCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.iconColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          border: Border.all(color: Theme.of(context).colorScheme.outline),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: iconColor, size: 24),
            ),
            SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w700)),
                  Text(subtitle,
                      style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7))),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
          ],
        ),
      ),
    );
  }
}

class _QuickContactCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final VoidCallback onTap;

  const _QuickContactCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 16, horizontal: 10),
        decoration: BoxDecoration(
          border: Border.all(color: Theme.of(context).colorScheme.outline),
          borderRadius: BorderRadius.circular(12),
          color: Theme.of(context).colorScheme.surface,
        ),
        child: Column(
          children: [
            Icon(icon, color: Theme.of(context).colorScheme.primary, size: 24),
            SizedBox(height: 6),
            Text(label,
                style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.w700)),
            SizedBox(height: 2),
            Text(
              value,
              textAlign: TextAlign.center,
              style: GoogleFonts.urbanist(fontSize: 10, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
            ),
          ],
        ),
      ),
    );
  }
}

class _FaqItem extends StatelessWidget {
  final String question;
  final String answer;

  const _FaqItem({required this.question, required this.answer});

  @override
  Widget build(BuildContext context) {
    return Theme(
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: Container(
        margin: EdgeInsets.only(bottom: 8),
        decoration: BoxDecoration(
          border: Border.all(color: Theme.of(context).colorScheme.outline),
          borderRadius: BorderRadius.circular(12),
          color: Theme.of(context).colorScheme.surface,
        ),
        child: ExpansionTile(
          tilePadding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          childrenPadding: EdgeInsets.only(left: 16, right: 16, bottom: 16),
          shape: RoundedRectangleBorder(side: BorderSide.none),
          collapsedShape: RoundedRectangleBorder(side: BorderSide.none),
          title: Text(question,
              style: GoogleFonts.urbanist(
                  fontSize: 14, fontWeight: FontWeight.w600, color: Theme.of(context).colorScheme.onSurface)),
          iconColor: Theme.of(context).colorScheme.primary,
          collapsedIconColor: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
          children: [
            Text(answer,
                style: GoogleFonts.urbanist(
                    fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7), height: 1.6)),
          ],
        ),
      ),
    );
  }
}
