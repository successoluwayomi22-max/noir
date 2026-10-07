import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';

import '../../api_client.dart';
import '../../theme.dart';
import '../../utils/error_helper.dart';

class AdminOrdersScreen extends StatefulWidget {
  const AdminOrdersScreen({super.key});

  @override
  State<AdminOrdersScreen> createState() => _AdminOrdersScreenState();
}

class _AdminOrdersScreenState extends State<AdminOrdersScreen> {
  bool _loading = true;
  List<dynamic> _orders = [];
  int _page = 1;
  int _totalPages = 1;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders({int page = 1}) async {
    setState(() => _loading = true);
    try {
      final api = context.read<NoirApiClient>();
      final data = await api.getAdminOrders(page: page);
      if (mounted) {
        setState(() {
          _orders = data['orders'] ?? [];
          _page = data['pagination']?['page'] ?? 1;
          _totalPages = data['pagination']?['pages'] ?? 1;
          _loading = false;
          _error = null;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString().replaceAll('Exception: ', '');
          _loading = false;
        });
      }
    }
  }

  Future<void> _updateStatus(String orderNumber, String newStatus) async {
    final messenger = ScaffoldMessenger.of(context);
    final theme = Theme.of(context);
    try {
      final api = context.read<NoirApiClient>();
      await api.updateOrderStatus(orderNumber, newStatus);
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(
          content: Text('Order $orderNumber status updated to ${newStatus[0].toUpperCase()}${newStatus.substring(1)}'),
          backgroundColor: theme.colorScheme.secondary,
        ),
      );
      _loadOrders(page: _page);
    } catch (e) {
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(
          content: Text(ErrorHelper.format(e, fallback: 'Unable to update order status. Please try again.')),
          backgroundColor: theme.colorScheme.error,
        ),
      );
    }
  }

  void _showStatusSheet(Map<String, dynamic> order) {
    final current = (order['status'] ?? '').toString();
    final statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    showModalBottomSheet(
      context: context,
      backgroundColor: Theme.of(context).cardColor,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: EdgeInsets.fromLTRB(24, 20, 24, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Update Status',
                  style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                Text(
                  'Order ${order['order_number']}',
                  style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
                ),
                SizedBox(height: 16),
                ...statuses.map((s) {
                  final isActive = s == current;
                  return ListTile(
                    dense: true,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    leading: Icon(
                      isActive ? Icons.radio_button_checked : Icons.radio_button_off,
                      color: isActive ? Theme.of(context).colorScheme.secondary : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                      size: 20,
                    ),
                    title: Text(
                      s[0].toUpperCase() + s.substring(1),
                      style: GoogleFonts.urbanist(
                        fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                        color: isActive ? Theme.of(context).colorScheme.secondary : Theme.of(context).colorScheme.onSurface,
                      ),
                    ),
                    onTap: isActive
                        ? null
                        : () {
                            Navigator.pop(ctx);
                            _updateStatus(order['order_number'], s);
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        foregroundColor: Theme.of(context).colorScheme.onSurface,
        elevation: 0,
        title: Text(
          'Manage Orders',
          style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 18),
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded),
            onPressed: () => _loadOrders(page: _page),
          ),
        ],
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.secondary))
          : _error != null
              ? Center(
                  child: Padding(
                    padding: EdgeInsets.all(32),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.error_outline, size: 48, color: Theme.of(context).colorScheme.error),
                        SizedBox(height: 16),
                        Text(_error!, style: GoogleFonts.urbanist(fontSize: 16), textAlign: TextAlign.center),
                        SizedBox(height: 24),
                        ElevatedButton(onPressed: () => _loadOrders(), child: Text('Retry')),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  color: Theme.of(context).colorScheme.secondary,
                  onRefresh: () => _loadOrders(page: _page),
                  child: _orders.isEmpty
                      ? ListView(
                          children: [
                            SizedBox(height: 120),
                            Center(child: Icon(Icons.receipt_long_outlined, size: 64, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5))),
                            SizedBox(height: 16),
                            Center(child: Text('No orders found.', style: GoogleFonts.urbanist(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5), fontSize: 16))),
                          ],
                        )
                      : Column(
                          children: [
                            Expanded(
                              child: ListView.separated(
                                padding: EdgeInsets.all(16),
                                itemCount: _orders.length,
                                separatorBuilder: (_, _) => SizedBox(height: 10),
                                itemBuilder: (context, i) => _buildOrderCard(_orders[i]),
                              ),
                            ),
                            if (_totalPages > 1) _buildPagination(),
                          ],
                        ),
                ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order) {
    final status = (order['status'] ?? 'pending').toString();
    final total = double.tryParse(order['total']?.toString() ?? '0') ?? 0;
    final currency = order['currency'] ?? '₦';
    final date = DateTime.tryParse(order['created_at']?.toString() ?? '');

    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: 0.1),
        ),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 10, offset: Offset(0, 4)),
        ],
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () => _showStatusSheet(order),
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    order['order_number'] ?? '',
                    style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 16),
                  ),
                  _buildStatusChip(status),
                ],
              ),
              SizedBox(height: 8),
              if (order['customer_name'] != null)
                Text(
                  '${order['customer_name']} • ${order['customer_email'] ?? ''}',
                  style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
                  overflow: TextOverflow.ellipsis,
                ),
              SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    '$currency ${NumberFormat('#,##0.00').format(total)}',
                    style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 15, color: Theme.of(context).colorScheme.secondary),
                  ),
                  if (date != null)
                    Text(
                      DateFormat('d MMM yyyy, h:mm a').format(date),
                      style: GoogleFonts.urbanist(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
                    ),
                ],
              ),
              SizedBox(height: 8),
              Align(
                alignment: Alignment.centerRight,
                child: Text(
                  'Tap to update status →',
                  style: GoogleFonts.urbanist(fontSize: 11, color: Theme.of(context).colorScheme.secondary, fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    final colors = {
      'delivered': NoirTheme.success,
      'processing': Theme.of(context).colorScheme.secondary,
      'shipped': Colors.blue,
      'pending': NoirTheme.accent,
      'cancelled': Theme.of(context).colorScheme.error,
      'refunded': Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
    };
    final color = colors[status] ?? Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5);

    return Container(
      padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        status[0].toUpperCase() + status.substring(1),
        style: GoogleFonts.urbanist(fontSize: 11, fontWeight: FontWeight.w700, color: color),
      ),
    );
  }

  Widget _buildPagination() {
    return Container(
      padding: EdgeInsets.symmetric(vertical: 12, horizontal: 24),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        border: Border(top: BorderSide(color: Theme.of(context).dividerColor.withValues(alpha: 0.1))),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          TextButton.icon(
            onPressed: _page > 1 ? () => _loadOrders(page: _page - 1) : null,
            icon: Icon(Icons.chevron_left, size: 18),
            label: Text('Previous', style: GoogleFonts.urbanist(fontWeight: FontWeight.w600)),
          ),
          Text(
            'Page $_page of $_totalPages',
            style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
          ),
          TextButton.icon(
            onPressed: _page < _totalPages ? () => _loadOrders(page: _page + 1) : null,
            icon: Text('Next', style: GoogleFonts.urbanist(fontWeight: FontWeight.w600)),
            label: Icon(Icons.chevron_right, size: 18),
          ),
        ],
      ),
    );
  }
}
