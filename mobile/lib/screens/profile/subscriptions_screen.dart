import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../providers/currency_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';

class SubscriptionsScreen extends StatefulWidget {
  const SubscriptionsScreen({super.key});

  @override
  State<SubscriptionsScreen> createState() => _SubscriptionsScreenState();
}

class _SubscriptionsScreenState extends State<SubscriptionsScreen> {
  static const String _storageKey = 'noir_user_active_subscriptions_v2';

  List<Map<String, dynamic>> _subscriptions = [
    {
      'id': 'daily_essentials',
      'title': 'The Daily Essentials Box',
      'items': '3 x Luxury Blank Tees, 2 x Merino Socks',
      'amount': 45000.0,
      'interval': 'month',
      'plan': 'Monthly',
      'active': true,
      'next_delivery': 'Nov 15, 2026',
      'image': 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?q=80&w=500&auto=format&fit=crop'
    },
    {
      'id': 'sneaker_care',
      'title': 'Atelier Footwear & Leather Care Kit',
      'items': 'Monthly refill of organic balm, horsehair brush & cleaner',
      'amount': 18000.0,
      'interval': 'month',
      'plan': 'Monthly',
      'active': false,
      'next_delivery': 'Paused',
      'image': 'https://images.unsplash.com/photo-1600269452121-4f2416e55c28?q=80&w=500&auto=format&fit=crop'
    },
    {
      'id': 'atelier_capsule',
      'title': 'Noir Quarterly Haute Capsule',
      'items': 'Curated seasonal runway piece + 2 styling accessories',
      'amount': 120000.0,
      'interval': 'quarter',
      'plan': 'Quarterly',
      'active': true,
      'next_delivery': 'Dec 1, 2026',
      'image': 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=500&auto=format&fit=crop'
    },
  ];

  String _formatSubscriptionPrice(Map<String, dynamic> sub) {
    try {
      final cp = context.watch<CurrencyProvider>();
      if (sub['amount'] != null) {
        final amt = (sub['amount'] as num).toDouble();
        final interval = sub['interval'] ?? 'month';
        return '${cp.format(amt)} / $interval';
      }
      final rawPrice = sub['price']?.toString() ?? '';
      final match = RegExp(r'\d[\d,]*').firstMatch(rawPrice);
      if (match != null) {
        final numVal = double.tryParse(match.group(0)!.replaceAll(',', '')) ?? 0.0;
        final interval = rawPrice.contains('quarter') ? 'quarter' : 'month';
        return '${cp.format(numVal)} / $interval';
      }
    } catch (_) {}
    return sub['price']?.toString() ?? '₦45,000 / month';
  }

  @override
  void initState() {
    super.initState();
    _loadSubscriptions();
  }

  Future<void> _loadSubscriptions() async {
    final prefs = await SharedPreferences.getInstance();
    final data = prefs.getString(_storageKey);
    if (data != null && data.isNotEmpty) {
      try {
        final List<dynamic> decoded = jsonDecode(data);
        if (mounted && decoded.isNotEmpty) {
          setState(() {
            _subscriptions = decoded.map((e) => Map<String, dynamic>.from(e)).toList();
          });
        }
      } catch (_) {}
    }
  }

  Future<void> _saveSubscriptions() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_storageKey, jsonEncode(_subscriptions));
  }

  void _toggleSubscription(int index) {
    setState(() {
      final wasActive = _subscriptions[index]['active'] == true;
      _subscriptions[index]['active'] = !wasActive;
      _subscriptions[index]['next_delivery'] = !wasActive ? 'Dec 1, 2026' : 'Paused';
    });
    _saveSubscriptions();

    final isNowActive = _subscriptions[index]['active'] == true;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          isNowActive
              ? 'Reactivated ${_subscriptions[index]['title']}'
              : 'Paused subscription for ${_subscriptions[index]['title']}',
          style: GoogleFonts.urbanist(fontWeight: FontWeight.w600),
        ),
        backgroundColor: isNowActive ? NoirTheme.primary : Colors.amber.shade800,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  void _changeDeliverySchedule(int index) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Theme.of(context).colorScheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        final schedules = ['Every 2 Weeks', 'Monthly (Recommended)', 'Every 2 Months', 'Quarterly'];
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Change Delivery Frequency',
                  style: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Text(
                  'Select how often you want your ${_subscriptions[index]['title']} dispatched.',
                  style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13),
                ),
                const SizedBox(height: 16),
                ...schedules.map((schedule) {
                  return ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(schedule, style: GoogleFonts.urbanist(fontWeight: FontWeight.w600)),
                    trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
                    onTap: () {
                      Navigator.pop(ctx);
                      setState(() {
                        _subscriptions[index]['plan'] = schedule;
                      });
                      _saveSubscriptions();
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('Delivery schedule updated to $schedule!'),
                          backgroundColor: NoirTheme.primary,
                          behavior: SnackBarBehavior.floating,
                        ),
                      );
                    },
                  );
                }),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showAddSubscriptionModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return Padding(
          padding: EdgeInsets.fromLTRB(24, 24, 24, MediaQuery.of(ctx).viewInsets.bottom + 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Explore New Subscription Boxes',
                style: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 6),
              Text(
                'Unlock automated concierge wardrobe care delivered straight to your doorstep.',
                style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13),
              ),
              const SizedBox(height: 20),
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: NoirTheme.primary.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.card_giftcard_rounded, color: NoirTheme.primary),
                ),
                title: Text('VIP Fragrance & Scent Discovery', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700)),
                subtitle: Text('${context.read<CurrencyProvider>().format(28000)} / month • 2 Luxury sample vials', style: GoogleFonts.urbanist(fontSize: 12)),
                trailing: ElevatedButton(
                  style: ElevatedButton.styleFrom(backgroundColor: NoirTheme.primary),
                  onPressed: () {
                    Navigator.pop(ctx);
                    setState(() {
                      _subscriptions.add({
                        'id': 'scent_box_${DateTime.now().millisecondsSinceEpoch}',
                        'title': 'VIP Fragrance & Scent Discovery',
                        'items': '2 x Niche Eau de Parfum 15ml vials + travel atomiser',
                        'amount': 28000.0,
                        'interval': 'month',
                        'plan': 'Monthly',
                        'active': true,
                        'next_delivery': 'Nov 1, 2026',
                        'image': 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?q=80&w=500&auto=format&fit=crop'
                      });
                    });
                    _saveSubscriptions();
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: const Text('Subscribed to VIP Fragrance & Scent Discovery!'),
                        backgroundColor: NoirTheme.primary,
                        behavior: SnackBarBehavior.floating,
                      ),
                    );
                  },
                  child: const Text('Add', style: TextStyle(color: Colors.white)),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings.of(context);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(strings.subscriptions, style: GoogleFonts.urbanist(fontWeight: FontWeight.w800)),
        centerTitle: true,
        elevation: 0,
        backgroundColor: Colors.transparent,
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outline_rounded),
            onPressed: _showAddSubscriptionModal,
          ),
        ],
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(20.0),
        itemCount: _subscriptions.length,
        itemBuilder: (context, index) {
          final sub = _subscriptions[index];
          return _buildSubscriptionCard(
            context,
            index,
            sub['title'] ?? '',
            sub['items'] ?? '',
            _formatSubscriptionPrice(sub),
            sub['plan'] ?? 'Monthly',
            sub['active'] == true,
            sub['next_delivery'] ?? 'Pending',
            sub['image'] ?? '',
          );
        },
      ),
    );
  }

  Widget _buildSubscriptionCard(
    BuildContext context,
    int index,
    String title,
    String items,
    String price,
    String plan,
    bool active,
    String nextDelivery,
    String image,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20.0),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
            child: Stack(
              children: [
                Image.network(
                  image,
                  height: 140,
                  width: double.infinity,
                  fit: BoxFit.cover,
                  errorBuilder: (_, _, _) => Container(height: 140, color: Colors.grey.shade900),
                ),
                Positioned(
                  top: 12,
                  right: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: active ? Colors.green.shade700 : Colors.orange.shade800,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      active ? 'ACTIVE' : 'PAUSED',
                      style: GoogleFonts.urbanist(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 1,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(18.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(
                  items,
                  style: GoogleFonts.urbanist(color: Colors.grey.shade600, fontSize: 13),
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      price,
                      style: GoogleFonts.urbanist(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: NoirTheme.primary,
                      ),
                    ),
                    Text(
                      'Next: $nextDelivery',
                      style: GoogleFonts.urbanist(
                        fontSize: 12,
                        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => _changeDeliverySchedule(index),
                        style: OutlinedButton.styleFrom(
                          side: BorderSide(color: NoirTheme.primary.withValues(alpha: 0.5)),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: Text(
                          plan,
                          style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 12),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: () => _toggleSubscription(index),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: active ? Colors.grey.shade800 : NoirTheme.primary,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: Text(
                          active ? 'Pause' : 'Resume',
                          style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 12),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
