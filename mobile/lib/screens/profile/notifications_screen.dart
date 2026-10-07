import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';
import '../store/order_detail_screen.dart';
import '../store/order_tracking_screen.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<Map<String, dynamic>> _notifications = [];
  List<Map<String, dynamic>> _dropAlerts = [];
  Map<String, dynamic>? _activeShipment;
  bool _loading = true;
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final now = DateTime.now();
    final fmt = DateFormat('MMM dd, yyyy • hh:mm a');
    final brand = mounted ? context.read<SettingsProvider>().brandName : 'Store';

    final systemNotifications = [
      {
        'id': 'sys_1',
        'type': 'vip',
        'department': '${brand.toUpperCase()} VIP CIRCLE',
        'title': '✨ Welcome to $brand Atelier Club',
        'summary': 'Your account has been registered with priority access to seasonal capsule releases.',
        'body': 'Welcome to $brand Atelier.\n\nYour membership grants you private showroom previews, direct bespoke stylist consultations, and early access to our seasonal runway capsules.',
        'timestamp': fmt.format(now.subtract(const Duration(minutes: 10))),
        'icon': Icons.stars_rounded,
        'color': const Color(0xFFD4AF37),
        'unread': true,
        'actionLabel': 'View Loyalty Privileges',
        'actionRoute': '/loyalty_wallet',
      },
      {
        'id': 'sys_2',
        'type': 'security',
        'department': '${brand.toUpperCase()} SECURITY',
        'title': '🛡️ Security Verification Active',
        'summary': 'Your session is encrypted with TLS 1.3 luxury banking standard protection.',
        'body': 'Your account and billing credentials are protected under $brand Biometric and TLS 1.3 protocols. All payment cards must pass real-time banking verification before authorization.',
        'timestamp': fmt.format(now.subtract(const Duration(hours: 1))),
        'icon': Icons.verified_user_outlined,
        'color': NoirTheme.primary,
        'unread': false,
      },
    ];

    try {
      final api = context.read<NoirApiClient>();
      
      // Fetch Drop Alerts & Flash Sales
      try {
        final drops = await api.getDropAlerts();
        _dropAlerts = drops;
      } catch (_) {}

      // Fetch Real Orders
      List<Map<String, dynamic>> orderNotifs = [];
      if (api.isAuthenticated) {
        final data = await api.getOrders(page: 1);
        final realOrders = data.orders;

        if (realOrders.isNotEmpty) {
          orderNotifs = realOrders.map((o) {
            final oDate = o.createdAt;
            final isDelivered = o.status.toLowerCase() == 'delivered';
            final isShipped = o.status.toLowerCase() == 'shipped';
            final isProcessing = o.status.toLowerCase() == 'processing';

            final statusMsg = isDelivered
                ? 'This package was delivered to your address. Thank you for your patronage.'
                : isShipped
                    ? 'Your package is dispatched and en route with NOIR White-Glove VIP Fleet.'
                    : isProcessing
                        ? 'Our artisans are tailoring and preparing your items for dispatch.'
                        : 'Order registered in our system and awaiting confirmation.';

            return {
              'id': 'order_${o.id}',
              'type': 'orders',
              'department': 'NOIR LOGISTICS & DISPATCH',
              'title': 'Order #${o.orderNumber} ${o.statusLabel}',
              'summary': 'Order status: ${o.status.toUpperCase()} • Total: ${o.formattedTotal}',
              'body':
                  'Official Order Update for #${o.orderNumber}:\n\nYour order containing ${o.items.length} item(s) is currently [${o.status.toUpperCase()}].\nTotal Amount: ${o.formattedTotal}\nCurrency: ${o.currency}\nDate: ${o.formattedDate}\n\n$statusMsg',
              'timestamp': fmt.format(oDate),
              'icon': _iconForStatus(o.status),
              'color': _colorForStatus(o.status),
              'unread': isProcessing || o.status.toLowerCase() == 'pending',
              'orderNumber': o.orderNumber,
              'actionLabel': 'View Live GPS Tracking',
            };
          }).toList();

          final inTransit = realOrders.where((o) => o.status.toLowerCase() == 'shipped').toList();
          if (inTransit.isNotEmpty) {
            final active = inTransit.first;
            _activeShipment = {
              'orderNumber': active.orderNumber,
              'tracking': active.trackingNumber ?? 'NR-LUXE-${active.orderNumber}',
              'destination': active.shippingAddress ?? 'Registered Shipping Address',
            };
          } else {
            _activeShipment = null;
          }
        }
      }

      // Convert drops to notification list items if on 'drops' or 'all' filter
      final dropNotifs = _dropAlerts.map((d) {
        final isLive = d['status'] == 'live';
        return {
          'id': 'drop_${d['id']}',
          'type': 'drops',
          'department': isLive ? '🔥 LIVE FLASH SALE' : '💎 VIP RUNWAY DROP',
          'title': d['title'],
          'summary': d['subtitle'] ?? d['description'],
          'body': '${d['title']}\n\n${d['description']}\n\nDiscount: ${d['discount_percentage']}% OFF\nVIP Tier: ${d['min_vip_tier'].toString().toUpperCase()}\nStock Limit: ${d['stock_limit'] ?? 'Unlimited'} units',
          'timestamp': isLive ? 'LIVE NOW' : 'Starts in: ${_formatSeconds(d['starts_in_seconds'] ?? 0)}',
          'icon': isLive ? Icons.bolt_rounded : Icons.diamond_outlined,
          'color': isLive ? Colors.amber : const Color(0xFFD4AF37),
          'unread': isLive || (d['is_subscribed'] == true),
          'actionLabel': isLive ? 'Shop Drop Now' : 'Toggle VIP Reminder',
          'dropId': d['id'],
          'isSubscribed': d['is_subscribed'] == true,
        };
      }).toList();

      setState(() {
        _notifications = [...dropNotifs, ...orderNotifs, ...systemNotifications];
        _loading = false;
      });
      return;
    } catch (_) {}

    setState(() {
      _notifications = systemNotifications;
      _activeShipment = null;
      _loading = false;
    });
  }

  String _formatSeconds(int sec) {
    if (sec <= 0) return '0m';
    final h = sec ~/ 3600;
    final m = (sec % 3600) ~/ 60;
    if (h > 0) return '${h}h ${m}m';
    return '${m}m';
  }

  IconData _iconForStatus(String status) {
    switch (status.toLowerCase()) {
      case 'delivered':
        return Icons.check_circle_outline_rounded;
      case 'shipped':
        return Icons.local_shipping_outlined;
      case 'processing':
        return Icons.hourglass_empty_rounded;
      case 'cancelled':
        return Icons.cancel_outlined;
      default:
        return Icons.shopping_bag_outlined;
    }
  }

  Color _colorForStatus(String status) {
    switch (status.toLowerCase()) {
      case 'delivered':
        return Colors.green;
      case 'shipped':
        return Colors.blue;
      case 'cancelled':
        return Colors.red;
      default:
        return NoirTheme.primary;
    }
  }

  List<Map<String, dynamic>> get _filteredNotifications {
    if (_selectedFilter == 'all') return _notifications;
    return _notifications.where((n) => n['type'] == _selectedFilter).toList();
  }

  Future<void> _toggleDropReminder(int dropId) async {
    final messenger = ScaffoldMessenger.of(context);
    try {
      final api = context.read<NoirApiClient>();
      final res = await api.toggleDropAlertSubscription(dropId);
      final isSub = res['subscribed'] == true;
      if (!mounted) return;
      setState(() {
        for (final d in _dropAlerts) {
          if (d['id'] == dropId) {
            d['is_subscribed'] = isSub;
          }
        }
      });
      messenger.showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? (isSub ? 'VIP Alert confirmed!' : 'Reminder removed.')),
          backgroundColor: isSub ? NoirTheme.primary : const Color(0xFF252820),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(content: Text('Error updating alert: $e'), backgroundColor: Colors.red),
      );
    }
  }

  void _markAllAsRead() {
    setState(() {
      for (final n in _notifications) {
        n['unread'] = false;
      }
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text('All dispatches marked as read.'),
        backgroundColor: NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _deleteNotification(int index) {
    final deleted = _notifications[index];
    setState(() {
      _notifications.removeAt(index);
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text('Dispatch archived.'),
        backgroundColor: const Color(0xFF1E211A),
        behavior: SnackBarBehavior.floating,
        action: SnackBarAction(
          label: 'UNDO',
          textColor: NoirTheme.primary,
          onPressed: () {
            setState(() {
              _notifications.insert(index, deleted);
            });
          },
        ),
      ),
    );
  }

  void _openMessageDetails(Map<String, dynamic> notif) {
    setState(() {
      notif['unread'] = false;
    });

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _NotificationDetailSheet(notif: notif),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _filteredNotifications;

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
          'Notifications & Dispatches',
          style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
        ),
        actions: [
          if (_notifications.isNotEmpty)
            TextButton(
              onPressed: _markAllAsRead,
              child: Text(
                'Mark read',
                style: GoogleFonts.urbanist(
                    color: NoirTheme.primary, fontWeight: FontWeight.bold, fontSize: 13),
              ),
            ),
        ],
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: NoirTheme.primary))
          : RefreshIndicator(
              onRefresh: _load,
              color: NoirTheme.primary,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Logistics Pulse Card
                  if (_activeShipment != null) ...[
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF1E211A), Color(0xFF131510)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.35)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    width: 8,
                                    height: 8,
                                    decoration: const BoxDecoration(
                                      color: Color(0xFF2ECC71),
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    'ACTIVE REAL-TIME SHIPMENT',
                                    style: GoogleFonts.urbanist(
                                      fontSize: 10.5,
                                      fontWeight: FontWeight.w900,
                                      letterSpacing: 1.2,
                                      color: NoirTheme.primary,
                                    ),
                                  ),
                                ],
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: Colors.blue.withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  'IN TRANSIT',
                                  style: GoogleFonts.urbanist(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w800,
                                    color: Colors.blue.shade300,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'Order #${_activeShipment!['orderNumber']}',
                            style: GoogleFonts.urbanist(
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                              color: Colors.white,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Tracking: ${_activeShipment!['tracking']}',
                            style: GoogleFonts.urbanist(fontSize: 12, color: Colors.white70),
                          ),
                          const SizedBox(height: 14),
                          InkWell(
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => OrderTrackingScreen(
                                      initialOrderNumber: _activeShipment!['orderNumber']),
                                ),
                              );
                            },
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.end,
                              children: [
                                Text(
                                  'View live transit journey',
                                  style: GoogleFonts.urbanist(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w800,
                                    color: NoirTheme.primary,
                                  ),
                                ),
                                const SizedBox(width: 4),
                                Icon(Icons.arrow_forward_rounded,
                                    size: 14, color: NoirTheme.primary),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Featured VIP Drops & Flash Sales Carousel
                  if (_dropAlerts.isNotEmpty) ...[
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            const Text('🔥', style: TextStyle(fontSize: 16)),
                            const SizedBox(width: 6),
                            Text(
                              'VIP DROPS & FLASH SALES',
                              style: GoogleFonts.urbanist(
                                fontSize: 11,
                                fontWeight: FontWeight.w900,
                                letterSpacing: 1.2,
                                color: NoirTheme.primary,
                              ),
                            ),
                          ],
                        ),
                        Text(
                          '${_dropAlerts.length} Active Drops',
                          style: GoogleFonts.urbanist(fontSize: 11, color: Colors.grey),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    SizedBox(
                      height: 150,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _dropAlerts.length,
                        separatorBuilder: (_, _) => const SizedBox(width: 12),
                        itemBuilder: (ctx, i) {
                          final drop = _dropAlerts[i];
                          final isLive = drop['status'] == 'live';
                          final isSubscribed = drop['is_subscribed'] == true;
                          final disc = drop['discount_percentage'] ?? 0;

                          return Container(
                            width: 280,
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: isLive
                                    ? [const Color(0xFF1E2818), const Color(0xFF10160D)]
                                    : [const Color(0xFF252219), const Color(0xFF16140E)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(18),
                              border: Border.all(
                                color: isLive
                                    ? const Color(0xFF2ECC71).withValues(alpha: 0.5)
                                    : NoirTheme.primary.withValues(alpha: 0.4),
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: isLive
                                            ? Colors.green.withValues(alpha: 0.2)
                                            : NoirTheme.primary.withValues(alpha: 0.2),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        isLive ? '● LIVE FLASH SALE' : '⏳ UPCOMING DROP',
                                        style: GoogleFonts.urbanist(
                                          fontSize: 9,
                                          fontWeight: FontWeight.w900,
                                          color: isLive ? Colors.greenAccent : NoirTheme.primary,
                                        ),
                                      ),
                                    ),
                                    if (disc > 0)
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: Colors.red.withValues(alpha: 0.2),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          '-$disc% OFF',
                                          style: GoogleFonts.urbanist(
                                            fontSize: 9,
                                            fontWeight: FontWeight.w900,
                                            color: Colors.redAccent,
                                          ),
                                        ),
                                      ),
                                  ],
                                ),
                                Text(
                                  drop['title'] ?? 'Exclusive Drop',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: GoogleFonts.urbanist(
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w800,
                                    color: Colors.white,
                                  ),
                                ),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      isLive ? 'Ends soon' : 'Starts in: ${_formatSeconds(drop['starts_in_seconds'] ?? 0)}',
                                      style: GoogleFonts.urbanist(
                                        fontSize: 11,
                                        color: Colors.white70,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    InkWell(
                                      onTap: () => _toggleDropReminder(drop['id']),
                                      borderRadius: BorderRadius.circular(10),
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                        decoration: BoxDecoration(
                                          color: isSubscribed ? Colors.green.shade900 : NoirTheme.primary,
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Icon(
                                              isSubscribed ? Icons.check : Icons.notifications_active_outlined,
                                              size: 12,
                                              color: isSubscribed ? Colors.white : Colors.black,
                                            ),
                                            const SizedBox(width: 4),
                                            Text(
                                              isSubscribed ? 'Alert Set' : 'Remind Me',
                                              style: GoogleFonts.urbanist(
                                                fontSize: 10,
                                                fontWeight: FontWeight.w800,
                                                color: isSubscribed ? Colors.white : Colors.black,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],

                  // Category Filter Chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildFilterChip('all', 'All (${_notifications.length})'),
                        _buildFilterChip('drops', 'VIP Drops (${_dropAlerts.length})'),
                        _buildFilterChip('orders', 'Orders & Shipping'),
                        _buildFilterChip('vip', 'VIP Club'),
                        _buildFilterChip('security', 'Security'),
                      ],
                    ),
                  ),

                  const SizedBox(height: 12),

                  if (filtered.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 40),
                      child: Center(
                        child: Text(
                          'No dispatches in this category.',
                          style: GoogleFonts.urbanist(fontSize: 14, color: Colors.grey),
                        ),
                      ),
                    )
                  else
                    ...List.generate(filtered.length, (index) {
                      final notif = filtered[index];
                      final isUnread = notif['unread'] == true;
                      final dept = notif['department'] as String? ?? 'NOIR ATELIER';

                      return Dismissible(
                        key: Key(notif['id'].toString() + index.toString()),
                        direction: DismissDirection.endToStart,
                        background: Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.only(right: 20),
                          alignment: Alignment.centerRight,
                          decoration: BoxDecoration(
                            color: Colors.red.shade900.withValues(alpha: 0.8),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: const Icon(Icons.archive_outlined, color: Colors.white),
                        ),
                        onDismissed: (_) => _deleteNotification(index),
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          child: InkWell(
                            onTap: () => _openMessageDetails(notif),
                            borderRadius: BorderRadius.circular(16),
                            child: Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: isUnread
                                    ? NoirTheme.primary.withValues(alpha: 0.08)
                                    : Theme.of(context).colorScheme.surface,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(
                                  color: isUnread
                                      ? NoirTheme.primary.withValues(alpha: 0.45)
                                      : Theme.of(context).colorScheme.outline.withValues(alpha: 0.25),
                                  width: isUnread ? 1.2 : 1.0,
                                ),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(11),
                                    decoration: BoxDecoration(
                                      color: (notif['color'] as Color).withValues(alpha: 0.15),
                                      shape: BoxShape.circle,
                                      border: Border.all(
                                        color: (notif['color'] as Color).withValues(alpha: 0.3),
                                        width: 1,
                                      ),
                                    ),
                                    child: Icon(notif['icon'] as IconData,
                                        color: notif['color'] as Color, size: 22),
                                  ),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: (notif['color'] as Color).withValues(alpha: 0.1),
                                                borderRadius: BorderRadius.circular(6),
                                              ),
                                              child: Text(
                                                dept,
                                                style: GoogleFonts.urbanist(
                                                  fontWeight: FontWeight.w800,
                                                  fontSize: 10,
                                                  letterSpacing: 0.8,
                                                  color: notif['color'] as Color,
                                                ),
                                              ),
                                            ),
                                            if (isUnread)
                                              Container(
                                                width: 8,
                                                height: 8,
                                                decoration: BoxDecoration(
                                                  color: NoirTheme.primary,
                                                  shape: BoxShape.circle,
                                                ),
                                              ),
                                          ],
                                        ),
                                        const SizedBox(height: 6),
                                        Text(
                                          notif['title'] as String,
                                          style: GoogleFonts.urbanist(
                                            fontWeight: isUnread ? FontWeight.w900 : FontWeight.w700,
                                            fontSize: 14,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          notif['summary'] as String? ?? notif['body'] as String,
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                          style: GoogleFonts.urbanist(
                                            color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.75),
                                            fontSize: 13,
                                            height: 1.35,
                                          ),
                                        ),
                                        const SizedBox(height: 10),
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Row(
                                              children: [
                                                const Icon(Icons.access_time_rounded, size: 12, color: Colors.grey),
                                                const SizedBox(width: 4),
                                                Text(
                                                  notif['timestamp'] as String,
                                                  style: GoogleFonts.urbanist(
                                                    color: Colors.grey,
                                                    fontSize: 11,
                                                    fontWeight: FontWeight.w600,
                                                  ),
                                                ),
                                              ],
                                            ),
                                            Row(
                                              children: [
                                                Text(
                                                  'Read',
                                                  style: GoogleFonts.urbanist(
                                                    fontSize: 12,
                                                    fontWeight: FontWeight.w700,
                                                    color: NoirTheme.primary,
                                                  ),
                                                ),
                                                const SizedBox(width: 2),
                                                Icon(Icons.arrow_forward_ios_rounded, size: 10, color: NoirTheme.primary),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      );
                    }),
                ],
              ),
            ),
    );
  }

  Widget _buildFilterChip(String key, String label) {
    final isSelected = _selectedFilter == key;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(
          label,
          style: GoogleFonts.urbanist(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
            color: isSelected ? Colors.black : Theme.of(context).colorScheme.onSurface,
          ),
        ),
        selected: isSelected,
        selectedColor: NoirTheme.primary,
        backgroundColor: Theme.of(context).colorScheme.surface,
        onSelected: (_) => setState(() => _selectedFilter = key),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        side: BorderSide(
          color: isSelected ? NoirTheme.primary : Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
        ),
        showCheckmark: false,
      ),
    );
  }
}

class _NotificationDetailSheet extends StatelessWidget {
  final Map<String, dynamic> notif;

  const _NotificationDetailSheet({required this.notif});

  @override
  Widget build(BuildContext context) {
    final dept = notif['department'] as String? ?? 'NOIR ATELIER';
    final title = notif['title'] as String;
    final body = notif['body'] as String;
    final timestamp = notif['timestamp'] as String;
    final icon = notif['icon'] as IconData;
    final color = notif['color'] as Color;
    final orderNum = notif['orderNumber'] as String?;
    final actionLabel = notif['actionLabel'] as String?;

    return DraggableScrollableSheet(
      initialChildSize: 0.7,
      minChildSize: 0.45,
      maxChildSize: 0.9,
      builder: (_, scrollController) => Container(
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.5),
              blurRadius: 25,
              spreadRadius: 5,
            ),
          ],
        ),
        child: Column(
          children: [
            Container(
              margin: const EdgeInsets.only(top: 12, bottom: 8),
              width: 44,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.withValues(alpha: 0.35),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Expanded(
              child: ListView(
                controller: scrollController,
                padding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
                children: [
                  Center(
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.12),
                        shape: BoxShape.circle,
                        border: Border.all(color: color.withValues(alpha: 0.3), width: 1.5),
                      ),
                      child: Icon(icon, color: color, size: 36),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: color.withValues(alpha: 0.3)),
                      ),
                      child: Text(
                        dept,
                        style: GoogleFonts.urbanist(
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 1.2,
                          color: color,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Center(
                    child: Text(
                      title,
                      textAlign: TextAlign.center,
                      style: GoogleFonts.urbanist(
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
                        color: Theme.of(context).colorScheme.onSurface,
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Center(
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.access_time_rounded, size: 13, color: Colors.grey),
                        const SizedBox(width: 5),
                        Text(
                          timestamp,
                          style: GoogleFonts.urbanist(
                            color: Colors.grey,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                  const Divider(),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.mark_email_read_outlined, size: 16, color: NoirTheme.primary),
                            const SizedBox(width: 8),
                            Text(
                              'OFFICIAL ATELIER NOTICE',
                              style: GoogleFonts.urbanist(
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 1.0,
                                color: NoirTheme.primary,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        Text(
                          body,
                          style: GoogleFonts.urbanist(
                            fontSize: 14.5,
                            height: 1.6,
                            color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.88),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  if (orderNum != null) ...[
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: NoirTheme.primary,
                          foregroundColor: Colors.black,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        icon: const Icon(Icons.receipt_long_rounded),
                        label: Text(
                          actionLabel ?? 'View Order Details',
                          style: GoogleFonts.urbanist(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        onPressed: () {
                          Navigator.pop(context);
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => OrderDetailScreen(orderNumber: orderNum),
                            ),
                          );
                        },
                      ),
                    ),
                  ] else if (actionLabel != null) ...[
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: NoirTheme.primary,
                          foregroundColor: Colors.black,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        child: Text(
                          actionLabel,
                          style: GoogleFonts.urbanist(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        onPressed: () {
                          Navigator.pop(context);
                          final route = notif['actionRoute'] as String?;
                          if (route != null) {
                            if (route == '/home') {
                              Navigator.pushNamedAndRemoveUntil(context, '/home', (_) => false);
                            } else {
                              Navigator.pushNamed(context, route);
                            }
                          }
                        },
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                      child: Text(
                        'Close',
                        style: GoogleFonts.urbanist(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
