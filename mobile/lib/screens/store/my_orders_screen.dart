import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../theme.dart';
import '../../models/order.dart';
import '../../l10n/app_strings.dart';
import 'order_detail_screen.dart';
import '../../widgets/error_state_view.dart';

class MyOrdersScreen extends StatefulWidget {
  const MyOrdersScreen({super.key});

  @override
  State<MyOrdersScreen> createState() => _MyOrdersScreenState();
}

class _MyOrdersScreenState extends State<MyOrdersScreen> {
  List<Order> _orders = [];
  bool _loading = true;
  bool _hasError = false;
  bool _isOffline = false;
  int _page = 1;
  int _totalPages = 1;
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders({int page = 1}) async {
    setState(() {
      _loading = true;
      _hasError = false;
    });
    try {
      final api = context.read<NoirApiClient>();
      final data = await api.getOrders(page: page);
      if (mounted) {
        setState(() {
          _orders = data.orders;
          _page = data.page;
          _totalPages = data.totalPages;
          _loading = false;
          _hasError = false;
        });
      }
    } catch (e) {
      if (mounted) {
        final errStr = e.toString();
        final isNet = errStr.contains('SocketException') ||
            errStr.contains('ClientException') ||
            errStr.contains('Failed host lookup') ||
            errStr.contains('Connection refused') ||
            errStr.contains('Connection timed out');
        setState(() {
          _orders = [];
          _loading = false;
          _hasError = true;
          _isOffline = isNet;
        });
      }
    }
  }

  List<Order> get _filteredOrders {
    if (_selectedFilter == 'all') return _orders;
    if (_selectedFilter == 'active') {
      return _orders
          .where((o) =>
              o.status.toLowerCase() == 'shipped' ||
              o.status.toLowerCase() == 'confirmed')
          .toList();
    }
    if (_selectedFilter == 'processing') {
      return _orders
          .where((o) =>
              o.status.toLowerCase() == 'processing' ||
              o.status.toLowerCase() == 'pending')
          .toList();
    }
    if (_selectedFilter == 'delivered') {
      return _orders
          .where((o) => o.status.toLowerCase() == 'delivered')
          .toList();
    }
    return _orders;
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'delivered':
        return const Color(0xFF27AE60);
      case 'shipped':
        return const Color(0xFF2980B9);
      case 'processing':
        return const Color(0xFF8E44AD);
      case 'confirmed':
        return const Color(0xFF16A085);
      case 'cancelled':
        return const Color(0xFFC0392B);
      default:
        return const Color(0xFFE67E22);
    }
  }

  String _formatPrice(dynamic amount, String currency) {
    final symbols = {'NGN': '₦', 'USD': '\$', 'GBP': '£', 'EUR': '€', 'CAD': 'CA\$', 'AUD': 'AU\$', 'GHS': 'GH₵', 'KES': 'KSh ', 'ZAR': 'R '};
    final symbol = symbols[currency] ?? (currency.isNotEmpty ? '$currency ' : '₦');
    final val = double.tryParse(amount.toString()) ?? 0;
    if (currency == 'NGN') {
      return '$symbol${val.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}';
    }
    return '$symbol${val.toStringAsFixed(2)}';
  }

  String _formatDate(dynamic raw) {
    if (raw == null) return '';
    try {
      final dt = DateTime.parse(raw.toString());
      final months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
      ];
      return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
    } catch (_) {
      return raw.toString();
    }
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _filteredOrders;

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
          AppStrings.of(context).myOrders,
          style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
        ),
      ),
      body: Column(
        children: [
          if (_orders.isNotEmpty) ...[
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  _buildFilterChip('all', '${AppStrings.of(context).all} (${_orders.length})'),
                  _buildFilterChip('active', AppStrings.of(context).shipped),
                  _buildFilterChip('processing', AppStrings.of(context).processing),
                  _buildFilterChip('delivered', AppStrings.of(context).delivered),
                ],
              ),
            ),
            const Divider(height: 1),
          ],
          Expanded(
            child: _loading
                ? Center(child: CircularProgressIndicator(color: NoirTheme.primary))
                : _hasError
                    ? _isOffline
                        ? ErrorStateView.offline(
                            message: 'Unable to synchronize your order history while offline.',
                            onRetry: () => _loadOrders(),
                          )
                        : ErrorStateView.serverError(
                            message: 'Could not load your orders. Please check your connection and retry.',
                            onRetry: () => _loadOrders(),
                          )
                    : _orders.isEmpty
                        ? _buildEmpty()
                        : _buildList(filtered),
          ),
        ],
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
            fontSize: 12.5,
            fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
            color: isSelected ? Colors.black : Theme.of(context).colorScheme.onSurface,
          ),
        ),
        selected: isSelected,
        selectedColor: NoirTheme.primary,
        backgroundColor: Theme.of(context).colorScheme.surface,
        onSelected: (_) => setState(() => _selectedFilter = key),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        side: BorderSide(
          color: isSelected
              ? NoirTheme.primary
              : Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
        ),
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 88,
              height: 88,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                shape: BoxShape.circle,
                border: Border.all(
                    color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.4)),
              ),
              child: Icon(Icons.receipt_long_outlined,
                  size: 40,
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 24),
            Text(
              AppStrings.of(context).noOrders,
              style: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 8),
            Text(
              AppStrings.of(context).orderHistory,
              style: GoogleFonts.urbanist(
                fontSize: 14,
                color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.65),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 28),
            SizedBox(
              height: 48,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: NoirTheme.primary,
                  foregroundColor: Colors.black,
                  padding: const EdgeInsets.symmetric(horizontal: 28),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: () =>
                    Navigator.pushNamedAndRemoveUntil(context, '/home', (_) => false),
                child: Text(
                  'START SHOPPING',
                  style: GoogleFonts.urbanist(fontWeight: FontWeight.w900, letterSpacing: 0.8),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildList(List<Order> orders) {
    if (orders.isEmpty) {
      return Center(
        child: Text(
          'No orders in this category.',
          style: GoogleFonts.urbanist(fontSize: 14, color: Colors.grey),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => _loadOrders(page: _page),
      color: NoirTheme.primary,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: orders.length + (_totalPages > 1 ? 1 : 0),
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          if (index == orders.length) return _buildPagination();
          final order = orders[index];
          final status = order.status;
          final color = _statusColor(status);
          final itemsCount = order.items.length;

          return InkWell(
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => OrderDetailScreen(orderNumber: order.orderNumber),
              ),
            ),
            borderRadius: BorderRadius.circular(18),
            child: Container(
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
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: color.withValues(alpha: 0.12),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              status.toLowerCase() == 'delivered'
                                  ? Icons.check_circle_outline_rounded
                                  : status.toLowerCase() == 'shipped'
                                      ? Icons.local_shipping_outlined
                                      : Icons.hourglass_empty_rounded,
                              color: color,
                              size: 18,
                            ),
                          ),
                          const SizedBox(width: 10),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                order.orderNumber,
                                style: GoogleFonts.urbanist(
                                    fontSize: 15, fontWeight: FontWeight.w900),
                              ),
                              Text(
                                _formatDate(order.createdAt),
                                style: GoogleFonts.urbanist(
                                    fontSize: 11.5, color: Colors.grey),
                              ),
                            ],
                          ),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: color.withValues(alpha: 0.4)),
                        ),
                        child: Text(
                          status[0].toUpperCase() + status.substring(1),
                          style: GoogleFonts.urbanist(
                              fontSize: 11, fontWeight: FontWeight.w800, color: color),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  const Divider(height: 1),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        itemsCount > 0
                            ? '$itemsCount item(s)'
                            : 'Garment',
                        style: GoogleFonts.urbanist(
                          fontSize: 13,
                          color: Theme.of(context)
                              .colorScheme
                              .onSurface
                              .withValues(alpha: 0.75),
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      Row(
                        children: [
                          Text(
                            _formatPrice(order.total, order.currency),
                            style: GoogleFonts.urbanist(
                                fontSize: 16, fontWeight: FontWeight.w900),
                          ),
                          const SizedBox(width: 4),
                          Icon(Icons.arrow_forward_ios_rounded,
                              size: 12, color: NoirTheme.primary),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildPagination() {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          if (_page > 1)
            TextButton(
              onPressed: () => _loadOrders(page: _page - 1),
              child: Text('← Previous', style: GoogleFonts.urbanist(fontSize: 13)),
            ),
          if (_page < _totalPages)
            TextButton(
              onPressed: () => _loadOrders(page: _page + 1),
              child: Text('Next →', style: GoogleFonts.urbanist(fontSize: 13)),
            ),
        ],
      ),
    );
  }
}
