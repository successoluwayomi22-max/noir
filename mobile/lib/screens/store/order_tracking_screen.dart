import 'dart:async';
import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../models/order.dart';
import '../../providers/auth_provider.dart';
import '../../providers/currency_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';
import '../../utils/noir_share.dart';
import '../profile/shipping_addresses_screen.dart';

class OrderTrackingScreen extends StatefulWidget {
  final String? initialOrderNumber;
  const OrderTrackingScreen({super.key, this.initialOrderNumber});

  @override
  State<OrderTrackingScreen> createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen>
    with SingleTickerProviderStateMixin {
  late TextEditingController _searchCtrl;

  List<Order> _userOrders = [];
  Order? _selectedOrder;
  bool _loading = true;
  bool _noOrdersFound = false;

  String _orderNumber = '';
  String _carrier = 'DHL Express Luxe';
  String _recipientAddress = '';
  String _status = 'In Transit';
  double _orderTotal = 0;
  String _currency = 'NGN';

  // Live GPS Simulation & Animation
  late AnimationController _animController;
  late Timer _realtimeTimer;
  Duration _etaCountdown = const Duration(minutes: 22, seconds: 40);
  double _distanceKm = 2.1;
  int _speedKmh = 38;
  String _currentStreet = 'Admiralty Way, Lekki Phase 1';

  final String _securityPin = '8492';
  Timer? _pollingTimer;
  String _lastKnownStatus = '';

  @override
  void initState() {
    super.initState();
    _searchCtrl = TextEditingController(text: widget.initialOrderNumber ?? '');

    _animController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 12),
    )..repeat();

    _startLiveSimulation();
    _startOrderPolling();
    _loadRealCustomerOrders();
  }

  void _startLiveSimulation() {
    _realtimeTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        if (_etaCountdown.inSeconds > 0) {
          _etaCountdown = _etaCountdown - const Duration(seconds: 1);
          _distanceKm = math.max(0.1, _distanceKm - 0.0015);
          _speedKmh = 32 + (timer.tick % 10);
        } else {
          _etaCountdown = const Duration(minutes: 25);
          _distanceKm = 2.4;
        }
      });
    });
  }

  // ── Real-time order status polling every 30 seconds ──────────────────────
  void _startOrderPolling() {
    _pollingTimer = Timer.periodic(const Duration(seconds: 30), (_) async {
      if (!mounted) return;
      final orderNum = _selectedOrder?.orderNumber ?? _orderNumber;
      if (orderNum.isEmpty) return;
      try {
        final api = context.read<AuthProvider>().api;
        final order = await api.getOrderDetail(orderNum);
        if (!mounted) return;
        final newStatus = order.status;
        if (_lastKnownStatus.isNotEmpty && newStatus != _lastKnownStatus) {
          // Status changed — notify user
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Row(
                children: [
                  const Icon(Icons.local_shipping_outlined, color: Colors.white, size: 18),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      '📦 Order #$orderNum status updated: ${newStatus.toUpperCase()}',
                      style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
              backgroundColor: NoirTheme.primary,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              duration: const Duration(seconds: 4),
            ),
          );
        }
        setState(() {
          _selectedOrder = order;
          _applyOrderDetails(order);
          _lastKnownStatus = newStatus;
        });
      } catch (_) {
        // Silently fail — network may be down
      }
    });
  }

  Future<void> _loadRealCustomerOrders() async {
    setState(() => _loading = true);
    final auth = context.read<AuthProvider>();
    final api = auth.api;

    try {
      if (api.isAuthenticated) {
        final ordersResult = await api.getOrders();
        final orders = ordersResult.orders;
        if (mounted) {
          setState(() {
            _userOrders = orders;
            if (orders.isNotEmpty) {
              if (widget.initialOrderNumber != null &&
                  widget.initialOrderNumber!.isNotEmpty) {
                _selectedOrder = orders.firstWhere(
                  (o) => o.orderNumber == widget.initialOrderNumber,
                  orElse: () => orders.first,
                );
              } else {
                _selectedOrder = orders.first;
              }
              _applyOrderDetails(_selectedOrder!);
              _noOrdersFound = false;
            } else if (widget.initialOrderNumber != null &&
                widget.initialOrderNumber!.isNotEmpty) {
              _fetchSpecificOrder(widget.initialOrderNumber!);
            } else {
              _noOrdersFound = true;
            }
            _loading = false;
          });
          return;
        }
      }
    } catch (_) {}

    if (mounted) {
      if (widget.initialOrderNumber != null &&
          widget.initialOrderNumber!.isNotEmpty) {
        _fetchSpecificOrder(widget.initialOrderNumber!);
      } else {
        setState(() {
          _noOrdersFound = true;
          _loading = false;
        });
      }
    }
  }

  Map<String, dynamic>? _telemetryData;

  void _applyOrderDetails(Order order) {
    _orderNumber = order.orderNumber;
    _searchCtrl.text = order.orderNumber;
    _status = order.status;
    _orderTotal = order.total;
    _currency = order.currency;

    final rawAddress = order.shippingAddress ?? '';
    if (rawAddress.trim().isNotEmpty) {
      _recipientAddress = rawAddress.trim();
      _updateLiveRouteFromAddress(_recipientAddress);
    } else {
      _loadFallbackAddressAndRoute();
    }

    _fetchTrackingTelemetry(order.orderNumber);
  }

  Future<void> _fetchTrackingTelemetry(String orderNum) async {
    try {
      final api = context.read<AuthProvider>().api;
      final data = await api.getOrderTracking(orderNum);
      if (data['success'] == true && data['tracking'] != null && mounted) {
        final t = Map<String, dynamic>.from(data['tracking']);
        setState(() {
          _telemetryData = t;
          if (t['carrier'] != null) _carrier = t['carrier'];
          if (t['estimated_minutes_remaining'] != null) {
            _etaCountdown = Duration(minutes: (t['estimated_minutes_remaining'] as num).toInt());
          }
          if (t['distance_remaining_km'] != null) {
            _distanceKm = (t['distance_remaining_km'] as num).toDouble();
          }
          if (t['destination'] != null && t['destination']['name'] != null) {
            _recipientAddress = t['destination']['name'];
          }
        });
      }
    } catch (_) {}
  }

  void _updateLiveRouteFromAddress(String address) {
    if (address.isEmpty) return;
    final parts = address.split(RegExp(r'[,|\n]')).map((s) => s.trim()).where((s) => s.isNotEmpty).toList();
    if (parts.isNotEmpty) {
      final street = parts.first;
      final city = parts.length > 1 ? parts[1] : '';
      if (city.isNotEmpty) {
        _currentStreet = 'En route to $street ($city)';
      } else {
        _currentStreet = 'Approaching $street';
      }
    } else {
      _currentStreet = 'Approaching destination address';
    }
  }

  Future<void> _loadFallbackAddressAndRoute() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final data = prefs.getString('noir_saved_shipping_addresses');
      if (data != null && data.isNotEmpty) {
        final List<dynamic> decoded = jsonDecode(data);
        if (decoded.isNotEmpty) {
          final defaultAddr = decoded.firstWhere(
            (a) => a['is_default'] == true,
            orElse: () => decoded.first,
          );
          final line1 = defaultAddr['address_line1'] ?? '';
          final city = defaultAddr['city'] ?? '';
          final state = defaultAddr['state'] ?? '';
          final formatted = [line1, city, state].where((s) => (s as String).isNotEmpty).join(', ');
          if (formatted.isNotEmpty && mounted) {
            setState(() {
              _recipientAddress = formatted;
              _updateLiveRouteFromAddress(formatted);
            });
            return;
          }
        }
      }
    } catch (_) {}

    if (mounted && _recipientAddress.isEmpty) {
      setState(() {
        _recipientAddress = 'Lagos Delivery Hub, Nigeria';
        _currentStreet = 'Approaching delivery destination';
      });
    }
  }

  Future<void> _fetchSpecificOrder(String orderNum) async {
    setState(() => _loading = true);
    try {
      final api = context.read<AuthProvider>().api;
      final order = await api.getOrderDetail(orderNum);
      if (mounted) {
        setState(() {
          _selectedOrder = order;
          _applyOrderDetails(order);
          _noOrdersFound = false;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _orderNumber = orderNum;
          _noOrdersFound = false;
          _loading = false;
        });
      }
    }
  }

  @override
  void dispose() {
    _animController.dispose();
    _realtimeTimer.cancel();
    _pollingTimer?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  String _formatEta(Duration d) {
    final m = d.inMinutes;
    final s = d.inSeconds % 60;
    return '${m}m ${s.toString().padLeft(2, '0')}s';
  }

  String _formatPrice(double price, String curr) {
    return context.read<CurrencyProvider>().format(price);
  }

  void _shareTracking() {
    if (_orderNumber.isEmpty) return;
    final brand = context.read<SettingsProvider>().brandName;
    NoirShare.share(
      context,
      text:
          'Track my $brand luxury dispatch #$_orderNumber in real-time:\nhttps://noir-73fy.onrender.com/order-tracking?ref=$_orderNumber',
      subject: '$brand Luxury Dispatch',
      fallbackCopyText:
          'https://noir-73fy.onrender.com/order-tracking?ref=$_orderNumber',
    );
  }

  void _callCourier() {
    final courier = _telemetryData?['courier'] as Map<String, dynamic>?;
    final driverName = courier?['name'] ?? 'Emeka Adeleke';
    final vehicle = courier?['vehicle_model'] ?? 'Mercedes-Benz Sprinter Executive';
    final plate = courier?['vehicle_plate'] ?? 'LND-849-NR';
    final phone = courier?['phone'] ?? '+234 (0) 800-NOIR-LUXE';
    final pin = _telemetryData?['security_otp_pin'] ?? _securityPin;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E211A),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Icon(Icons.phone_in_talk_rounded, color: NoirTheme.primary, size: 22),
            const SizedBox(width: 10),
            Text(
              'Call Courier Escort',
              style: GoogleFonts.urbanist(fontWeight: FontWeight.w900, fontSize: 18),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Driver: $driverName\nCarrier: $_carrier\nVehicle: $vehicle ($plate)\nHotline: $phone',
              style: GoogleFonts.urbanist(
                  fontSize: 13.5, height: 1.5, color: Colors.white70),
            ),
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.black45,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  Icon(Icons.verified_user_rounded,
                      color: NoirTheme.primary, size: 20),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Security Handshake PIN: $pin\n(Quote this PIN upon recipient handover)',
                      style: GoogleFonts.urbanist(
                        color: NoirTheme.primary,
                        fontWeight: FontWeight.w800,
                        fontSize: 11.5,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Dismiss', style: GoogleFonts.urbanist(color: Colors.grey)),
          ),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: NoirTheme.primary,
              foregroundColor: Colors.black,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            icon: const Icon(Icons.phone, size: 16),
            label: const Text('Dial Escort'),
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: const Text(
                      'Connecting to DHL Express Luxe courier dispatch hotline...'),
                  backgroundColor: NoirTheme.primary,
                  behavior: SnackBarBehavior.floating,
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

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
          'Live GPS Order Tracking',
          style: GoogleFonts.urbanist(fontSize: 17, fontWeight: FontWeight.w800),
        ),
        actions: [
          if (!_noOrdersFound)
            IconButton(
              icon: const Icon(Icons.share_outlined),
              onPressed: _shareTracking,
            ),
        ],
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: NoirTheme.primary))
          : _noOrdersFound
              ? _buildNoActiveOrdersView(user)
              : _buildLiveTrackingView(user),
    );
  }

  Widget _buildNoActiveOrdersView(dynamic user) {
    final customerName = user != null ? user.name : 'Valued Patron';
    final customerEmail = user != null ? user.email : '';
    final brand = context.read<SettingsProvider>().brandName;
    final vipTier = user != null ? user.vipTier.toUpperCase() : '${brand.toUpperCase()} MEMBER';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          const SizedBox(height: 10),
          Container(
            width: 90,
            height: 90,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              shape: BoxShape.circle,
              border: Border.all(
                  color: NoirTheme.primary.withValues(alpha: 0.4), width: 2),
            ),
            child: Icon(Icons.radar_rounded, color: NoirTheme.primary, size: 44),
          ),
          const SizedBox(height: 20),
          Text(
            'No Active Live Shipments',
            style: GoogleFonts.urbanist(fontSize: 22, fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 8),
          Text(
            'You do not have an order currently in transit. When your bespoke pieces are confirmed and dispatched, live real-time GPS tracking and courier escort will be activated here.',
            style: GoogleFonts.urbanist(fontSize: 14, color: Colors.grey, height: 1.5),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 28),

          // Confirmed Patron Identity Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                  color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'PATRON PROFILE',
                      style: GoogleFonts.urbanist(
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                        color: NoirTheme.primary,
                        letterSpacing: 1,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: NoirTheme.primary.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        vipTier,
                        style: TextStyle(
                            color: NoirTheme.primary,
                            fontSize: 10,
                            fontWeight: FontWeight.w900),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(customerName,
                    style: GoogleFonts.urbanist(
                        fontSize: 17, fontWeight: FontWeight.w900)),
                if (customerEmail.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(customerEmail,
                      style: GoogleFonts.urbanist(fontSize: 13, color: Colors.grey)),
                ],
                const SizedBox(height: 14),
                const Divider(),
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.check_circle_rounded,
                        color: Color(0xFF2ECC71), size: 16),
                    const SizedBox(width: 6),
                    Text(
                      'Account Verified • Secure GPS Handshake Ready',
                      style: GoogleFonts.urbanist(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: const Color(0xFF2ECC71)),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          // Manual Order Search Input
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                  color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
            ),
            child: Row(
              children: [
                const Icon(Icons.search_rounded, color: Colors.grey, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _searchCtrl,
                    style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
                    decoration: const InputDecoration(
                      hintText: 'Track by Order # (e.g. NOIR-1002)',
                      border: InputBorder.none,
                    ),
                    onSubmitted: (val) {
                      if (val.trim().isNotEmpty) {
                        _fetchSpecificOrder(val.trim());
                      }
                    },
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.arrow_forward_rounded, color: NoirTheme.primary),
                  onPressed: () {
                    if (_searchCtrl.text.trim().isNotEmpty) {
                      _fetchSpecificOrder(_searchCtrl.text.trim());
                    }
                  },
                ),
              ],
            ),
          ),

          const SizedBox(height: 28),

          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: NoirTheme.primary,
                foregroundColor: Colors.black,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              icon: const Icon(Icons.shopping_bag_outlined, size: 20),
              label: Text(
                'START SHOPPING',
                style: GoogleFonts.urbanist(
                    fontSize: 14, fontWeight: FontWeight.w900, letterSpacing: 0.5),
              ),
              onPressed: () => Navigator.pushNamed(context, '/home'),
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                side: BorderSide(color: Theme.of(context).colorScheme.outline),
                padding: const EdgeInsets.symmetric(vertical: 15),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              icon: const Icon(Icons.location_on_outlined, size: 18),
              label: Text(
                'Manage Delivery Destinations',
                style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
              ),
              onPressed: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const ShippingAddressesScreen()),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLiveTrackingView(dynamic user) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Order Selector / Search Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 16),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                    color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  Icon(Icons.radar_rounded, color: NoirTheme.primary, size: 22),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _searchCtrl,
                      style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
                      decoration: const InputDecoration(
                        hintText: 'Enter Order # (e.g. NOIR-1002)',
                        border: InputBorder.none,
                      ),
                      onSubmitted: (val) {
                        if (val.trim().isNotEmpty) {
                          _fetchSpecificOrder(val.trim());
                        }
                      },
                    ),
                  ),
                  IconButton(
                    icon: Icon(Icons.arrow_forward_rounded, color: NoirTheme.primary),
                    onPressed: () {
                      if (_searchCtrl.text.trim().isNotEmpty) {
                        _fetchSpecificOrder(_searchCtrl.text.trim());
                      }
                    },
                  ),
                ],
              ),
            ),
          ),

          // If multiple user orders exist, show order quick selector
          if (_userOrders.length > 1)
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
              child: SizedBox(
                height: 38,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: _userOrders.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 8),
                  itemBuilder: (ctx, idx) {
                    final o = _userOrders[idx];
                    final isSel = o.orderNumber == _orderNumber;
                    return ChoiceChip(
                      selected: isSel,
                      label: Text('#${o.orderNumber} • ${o.status.toUpperCase()}'),
                      labelStyle: GoogleFonts.urbanist(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: isSel ? Colors.black : Colors.white70,
                      ),
                      selectedColor: NoirTheme.primary,
                      backgroundColor: Theme.of(context).colorScheme.surface,
                      onSelected: (_) {
                        setState(() {
                          _selectedOrder = o;
                          _applyOrderDetails(o);
                        });
                      },
                    );
                  },
                ),
              ),
            ),

          // Interactive Simulated Live GPS Map
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 20),
            height: 260,
            decoration: BoxDecoration(
              color: const Color(0xFF0F110D),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                color: NoirTheme.primary.withValues(alpha: 0.4),
                width: 1.5,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.5),
                  blurRadius: 20,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(24),
              child: Stack(
                children: [
                  AnimatedBuilder(
                    animation: _animController,
                    builder: (context, _) {
                      return CustomPaint(
                        size: const Size(double.infinity, 260),
                        painter: _LuxuryMapPainter(
                          progress: _animController.value,
                          primaryColor: NoirTheme.primary,
                        ),
                      );
                    },
                  ),
                  Positioned(
                    top: 14,
                    left: 14,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.8),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: Colors.white24, width: 0.5),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 8,
                            height: 8,
                            decoration: const BoxDecoration(
                              color: Color(0xFF2ECC71),
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'LIVE GPS SIGNAL',
                            style: GoogleFonts.urbanist(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 1,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 14,
                    left: 14,
                    right: 14,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1A1C16).withValues(alpha: 0.95),
                        borderRadius: BorderRadius.circular(14),
                        border:
                            Border.all(color: NoirTheme.primary.withValues(alpha: 0.3)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  _currentStreet,
                                  style: GoogleFonts.urbanist(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 12.5,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                Text(
                                  '${_distanceKm.toStringAsFixed(1)} km away • $_speedKmh km/h',
                                  style: GoogleFonts.urbanist(
                                    color: Colors.white60,
                                    fontSize: 11,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: NoirTheme.primary,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              _formatEta(_etaCountdown),
                              style: GoogleFonts.urbanist(
                                color: Colors.black,
                                fontWeight: FontWeight.w900,
                                fontSize: 12.5,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 20),

          // Real Order Details Card
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                    color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'ORDER #$_orderNumber',
                        style: GoogleFonts.urbanist(
                          fontSize: 14,
                          fontWeight: FontWeight.w900,
                          color: NoirTheme.primary,
                          letterSpacing: 1,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: _status.toLowerCase() == 'delivered'
                              ? const Color(0xFF2ECC71)
                              : NoirTheme.primary,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          _status.toUpperCase(),
                          style: const TextStyle(
                              color: Colors.black,
                              fontSize: 10.5,
                              fontWeight: FontWeight.w900),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      const Icon(Icons.location_on_outlined,
                          size: 16, color: Colors.grey),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'Destination: $_recipientAddress',
                          style: GoogleFonts.urbanist(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: Colors.white),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  if (_orderTotal > 0) ...[
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        const Icon(Icons.payments_outlined,
                            size: 16, color: Colors.grey),
                        const SizedBox(width: 6),
                        Text(
                          'Confirmed Total: ${_formatPrice(_orderTotal, _currency)}',
                          style: GoogleFonts.urbanist(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w700,
                              color: Colors.white70),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Live Metrics Strip
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              children: [
                Expanded(
                  child: _buildMetricCard('ESTIMATED ARRIVAL',
                      _formatEta(_etaCountdown), Icons.access_time_rounded,
                      NoirTheme.primary),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildMetricCard('REMAINING DISTANCE',
                      '${_distanceKm.toStringAsFixed(1)} KM',
                      Icons.route_rounded, Colors.blue),
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          // Escort & Vehicle Profile Card
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                    color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 24,
                        backgroundColor: NoirTheme.primary.withValues(alpha: 0.2),
                        child: Icon(Icons.person_rounded,
                            color: NoirTheme.primary, size: 28),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  'Adeyemi Ogunlesi',
                                  style: GoogleFonts.urbanist(
                                    fontWeight: FontWeight.w900,
                                    fontSize: 15.5,
                                  ),
                                ),
                                const SizedBox(width: 4),
                                const Icon(Icons.verified_rounded,
                                    size: 16, color: Colors.blue),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'DHL Express Luxe Verified Escort • 4.98 ★',
                              style: GoogleFonts.urbanist(
                                  fontSize: 12, color: Colors.grey),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  const Divider(),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'SECURITY HANDSHAKE PIN',
                            style: GoogleFonts.urbanist(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              color: NoirTheme.primary,
                              letterSpacing: 1,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            _securityPin,
                            style: GoogleFonts.sourceCodePro(
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 3,
                            ),
                          ),
                        ],
                      ),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: NoirTheme.primary,
                          foregroundColor: Colors.black,
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12)),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 14, vertical: 10),
                        ),
                        icon: const Icon(Icons.phone_in_talk_rounded, size: 16),
                        label: const Text('Call Escort'),
                        onPressed: _callCourier,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 24),

          // Live Shipment Milestones
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Text(
              'Live Dispatch Milestones',
              style: GoogleFonts.urbanist(fontSize: 17, fontWeight: FontWeight.w900),
            ),
          ),
          const SizedBox(height: 14),

          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                    color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
              ),
              child: Column(
                children: [
                  _buildMilestone('Order Registered & Payment Confirmed',
                      'Noir Vault Ledger', 'Completed', true),
                  _buildMilestone('Bespoke Handcrafted Tailoring & Quality Audit',
                      'Noir Master Tailors', 'Completed', true),
                  _buildMilestone('Silk Packaging & Tamper-Proof Hologram Seal',
                      'Noir Central Logistics Hub', 'Completed', true),
                  _buildMilestone('Out for White Glove Delivery',
                      'Escort in transit via Mercedes-Benz Sprinter', 'Live Pulse', true,
                      isCurrent: true),
                  _buildMilestone('Destination Handover & Security PIN Signature',
                      _recipientAddress, 'Pending Arrival', false,
                      isLast: true),
                ],
              ),
            ),
          ),

          const SizedBox(height: 36),
        ],
      ),
    );
  }

  Widget _buildMetricCard(
      String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: GoogleFonts.urbanist(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: Colors.grey,
                    letterSpacing: 0.8,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: GoogleFonts.urbanist(
              fontSize: 18,
              fontWeight: FontWeight.w900,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMilestone(
      String title, String subtitle, String state, bool isDone,
      {bool isCurrent = false, bool isLast = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 22,
              height: 22,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isCurrent
                    ? NoirTheme.primary
                    : isDone
                        ? const Color(0xFF2ECC71)
                        : Colors.grey.shade800,
                border: Border.all(
                  color: isCurrent ? Colors.white : Colors.transparent,
                  width: 2,
                ),
              ),
              child: Center(
                child: isDone
                    ? const Icon(Icons.check, size: 12, color: Colors.black)
                    : null,
              ),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 38,
                color: isDone
                    ? NoirTheme.primary.withValues(alpha: 0.6)
                    : Colors.grey.shade800,
              ),
          ],
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: GoogleFonts.urbanist(
                  fontSize: 13.5,
                  fontWeight: isDone ? FontWeight.w800 : FontWeight.w600,
                  color: isDone
                      ? Theme.of(context).colorScheme.onSurface
                      : Colors.grey,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: GoogleFonts.urbanist(fontSize: 11.5, color: Colors.grey),
              ),
              const SizedBox(height: 10),
            ],
          ),
        ),
      ],
    );
  }
}

class _LuxuryMapPainter extends CustomPainter {
  final double progress;
  final Color primaryColor;

  _LuxuryMapPainter({required this.progress, required this.primaryColor});

  @override
  void paint(Canvas canvas, Size size) {
    final bgPaint = Paint()..color = const Color(0xFF0F110D);
    canvas.drawRect(Rect.fromLTWH(0, 0, size.width, size.height), bgPaint);

    final gridPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.04)
      ..strokeWidth = 1;
    for (double x = 0; x < size.width; x += 30) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), gridPaint);
    }
    for (double y = 0; y < size.height; y += 30) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), gridPaint);
    }

    final roadPaint = Paint()
      ..color = const Color(0xFF1E211A)
      ..strokeWidth = 14
      ..strokeCap = StrokeCap.round;

    final roadPath = Path();
    roadPath.moveTo(size.width * 0.15, size.height * 0.25);
    roadPath.cubicTo(
      size.width * 0.45,
      size.height * 0.1,
      size.width * 0.35,
      size.height * 0.8,
      size.width * 0.85,
      size.height * 0.65,
    );
    canvas.drawPath(roadPath, roadPaint);

    final activeRoutePaint = Paint()
      ..color = primaryColor
      ..strokeWidth = 4
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;
    canvas.drawPath(roadPath, activeRoutePaint);

    final originPaint = Paint()..color = const Color(0xFFD4AF37);
    canvas.drawCircle(
        Offset(size.width * 0.15, size.height * 0.25), 7, originPaint);

    final destPaint = Paint()..color = const Color(0xFF2ECC71);
    canvas.drawCircle(
        Offset(size.width * 0.85, size.height * 0.65), 8, destPaint);

    final t = (progress % 1.0);
    final p0 = Offset(size.width * 0.15, size.height * 0.25);
    final p1 = Offset(size.width * 0.45, size.height * 0.1);
    final p2 = Offset(size.width * 0.35, size.height * 0.8);
    final p3 = Offset(size.width * 0.85, size.height * 0.65);

    final courierPos = _cubicBezier(p0, p1, p2, p3, t);

    final pulsePaint = Paint()
      ..color = primaryColor.withValues(alpha: 0.35)
      ..style = PaintingStyle.fill;
    canvas.drawCircle(courierPos, 18, pulsePaint);

    final vehiclePaint = Paint()..color = Colors.white;
    canvas.drawCircle(courierPos, 7, vehiclePaint);
    final innerPaint = Paint()..color = Colors.black;
    canvas.drawCircle(courierPos, 4, innerPaint);
  }

  Offset _cubicBezier(Offset p0, Offset p1, Offset p2, Offset p3, double t) {
    final u = 1 - t;
    final tt = t * t;
    final uu = u * u;
    final uuu = uu * u;
    final ttt = tt * t;

    final x =
        uuu * p0.dx + 3 * uu * t * p1.dx + 3 * u * tt * p2.dx + ttt * p3.dx;
    final y =
        uuu * p0.dy + 3 * uu * t * p1.dy + 3 * u * tt * p2.dy + ttt * p3.dy;
    return Offset(x, y);
  }

  @override
  bool shouldRepaint(covariant _LuxuryMapPainter oldDelegate) =>
      oldDelegate.progress != progress;
}
