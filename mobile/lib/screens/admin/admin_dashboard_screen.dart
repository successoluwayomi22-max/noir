import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';

import '../../api_client.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../providers/currency_provider.dart';
import '../../utils/error_helper.dart';
import '../../theme.dart';

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  bool _loading = true;
  Map<String, dynamic> _stats = {};
  Map<String, dynamic> _productSummary = {};
  List<dynamic> _orders = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    try {
      final api = context.read<NoirApiClient>();
      final data = await api.getAdminDashboard();
      if (mounted) {
        setState(() {
          _stats = data['stats'] ?? {};
          _productSummary = data['productSummary'] ?? {};
          _orders = data['orders'] ?? [];
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = ErrorHelper.format(e, fallback: 'Unable to load dashboard. Please try again.');
          _loading = false;
        });
      }
    }
  }

  String _formatCurrency(dynamic value) {
    try {
      return context.watch<CurrencyProvider>().format(value);
    } catch (_) {
      final num amount = (value is num) ? value : (double.tryParse(value.toString()) ?? 0);
      if (amount >= 1000000) {
        return '₦${(amount / 1000000).toStringAsFixed(1)}M';
      } else if (amount >= 1000) {
        return '₦${(amount / 1000).toStringAsFixed(0)}K';
      }
      return '₦${NumberFormat('#,##0').format(amount)}';
    }
  }

  String _formatNumber(dynamic value) {
    final num n = (value is num) ? value : (int.tryParse(value.toString()) ?? 0);
    return NumberFormat('#,##0').format(n);
  }

  @override
  Widget build(BuildContext context) {
    final user = context.read<AuthProvider>().user;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Admin Dashboard',
          style: GoogleFonts.urbanist(
            fontWeight: FontWeight.w700,
            fontSize: 20,
          ),
        ),
        actions: [
          IconButton(
            icon: Hero(tag: 'refresh_icon', child: Icon(Icons.refresh_rounded)),
            onPressed: () {
              setState(() => _loading = true);
              _loadDashboard();
            },
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
                        Hero(tag: 'error_icon', child: Icon(Icons.error_outline, size: 48, color: Theme.of(context).colorScheme.error)),
                        SizedBox(height: 16),
                        Text(_error!, style: GoogleFonts.urbanist(fontSize: 16), textAlign: TextAlign.center),
                        SizedBox(height: 24),
                        ElevatedButton(
                          onPressed: () {
                            setState(() { _loading = true; _error = null; });
                            _loadDashboard();
                          },
                          child: Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  color: Theme.of(context).colorScheme.secondary,
                  onRefresh: _loadDashboard,
                  child: ListView(
                    padding: EdgeInsets.all(20),
                    children: [
                      // Greeting
                      Text(
                        'Good ${_greeting()}, ${user?.name.split(' ').first ?? 'Admin'}.',
                        style: GoogleFonts.urbanist(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: Theme.of(context).colorScheme.onSurface,
                        ),
                      ),
                      SizedBox(height: 4),
                      Text(
                        'Here\'s what\'s happening across ${context.read<SettingsProvider>().brandName} today.',
                        style: GoogleFonts.urbanist(
                          fontSize: 14,
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                        ),
                      ),
                      SizedBox(height: 24),

                      // Stats Grid
                      _buildSectionLabel('OVERVIEW'),
                      SizedBox(height: 12),
                      _buildStatsGrid(),
                      SizedBox(height: 28),

                      // Product Summary
                      _buildSectionLabel('PRODUCTS'),
                      SizedBox(height: 12),
                      _buildProductSummary(),
                      SizedBox(height: 28),

                      // Recent Orders
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          _buildSectionLabel('RECENT ORDERS'),
                          TextButton(
                            onPressed: () => Navigator.pushNamed(context, '/admin-orders'),
                            child: Text(
                              'View all →',
                              style: GoogleFonts.urbanist(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: Theme.of(context).colorScheme.secondary,
                              ),
                            ),
                          ),
                        ],
                      ),
                      SizedBox(height: 8),
                      _buildRecentOrders(),
                      SizedBox(height: 28),

                      // Quick Actions
                      _buildSectionLabel('QUICK ACTIONS'),
                      SizedBox(height: 12),
                      _buildQuickActions(),
                      SizedBox(height: 40),
                    ],
                  ),
                ),
    );
  }

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'morning';
    if (hour < 17) return 'afternoon';
    return 'evening';
  }

  Widget _buildSectionLabel(String label) {
    return Text(
      label,
      style: GoogleFonts.urbanist(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        letterSpacing: 1.5,
        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
      ),
    );
  }

  Widget _buildStatsGrid() {
    final items = [
      _StatItem('Customers', _formatNumber(_stats['customers']), Icons.people_outline),
      _StatItem('Staff', _formatNumber(_stats['staff']), Icons.badge_outlined),
      _StatItem('Products', _formatNumber(_stats['products']), Icons.inventory_2_outlined),
      _StatItem('Orders', _formatNumber(_stats['orders']), Icons.shopping_bag_outlined),
      _StatItem('Revenue', _formatCurrency(_stats['revenue']), Icons.trending_up_rounded),
    ];

    return GridView.builder(
      shrinkWrap: true,
      physics: NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 1.65,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
      ),
      itemCount: items.length,
      itemBuilder: (context, i) {
        final item = items[i];
        return Container(
          padding: EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: Theme.of(context).dividerColor.withValues(alpha: 0.1),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.15),
                blurRadius: 12,
                offset: Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(item.icon, size: 22, color: Theme.of(context).colorScheme.secondary),
              Spacer(),
              FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(
                  item.value,
                  style: GoogleFonts.urbanist(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
              SizedBox(height: 2),
              Text(
                item.label,
                style: GoogleFonts.urbanist(
                  fontSize: 12,
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildProductSummary() {
    final items = [
      ('Total', _productSummary['total'] ?? 0, Theme.of(context).colorScheme.primary),
      ('Active', _productSummary['active'] ?? 0, NoirTheme.success),
      ('Draft', _productSummary['draft'] ?? 0, NoirTheme.accent),
      ('Out of stock', _productSummary['out'] ?? 0, Theme.of(context).colorScheme.error),
    ];

    return Container(
      padding: EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: 0.1),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 12,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: items.map((item) {
          return Column(
            children: [
              Text(
                _formatNumber(item.$2),
                style: GoogleFonts.urbanist(
                  fontSize: 24,
                  fontWeight: FontWeight.w800,
                  color: item.$3,
                ),
              ),
              SizedBox(height: 4),
              Text(
                item.$1,
                style: GoogleFonts.urbanist(
                  fontSize: 12,
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                ),
              ),
            ],
          );
        }).toList(),
      ),
    );
  }

  Widget _buildRecentOrders() {
    if (_orders.isEmpty) {
      return Container(
        padding: EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: Theme.of(context).dividerColor.withValues(alpha: 0.1),
          ),
        ),
        child: Center(
          child: Text(
            'No orders yet.',
            style: GoogleFonts.urbanist(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
          ),
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: 0.1),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 12,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: _orders.take(6).map((order) {
          final status = (order['status'] ?? 'pending').toString();
          return ListTile(
            dense: true,
            contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            title: Text(
              order['order_number'] ?? '',
              style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 14),
            ),
            subtitle: Text(
              order['created_at'] != null
                  ? DateFormat('d MMM yyyy').format(DateTime.tryParse(order['created_at'].toString()) ?? DateTime.now())
                  : '',
              style: GoogleFonts.urbanist(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
            ),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  _formatCurrency(order['total']),
                  style: GoogleFonts.urbanist(fontWeight: FontWeight.w600, fontSize: 13),
                ),
                SizedBox(width: 8),
                _buildStatusChip(status),
              ],
            ),
          );
        }).toList(),
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
        style: GoogleFonts.urbanist(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }

  Widget _buildQuickActions() {
    return Column(
      children: [
        _quickActionTile(
          icon: Icons.receipt_long_outlined,
          label: 'Manage Orders',
          subtitle: 'View and update order statuses',
          onTap: () => Navigator.pushNamed(context, '/admin-orders'),
        ),
        SizedBox(height: 10),
        _quickActionTile(
          icon: Icons.inventory_2_outlined,
          label: 'View Products',
          subtitle: 'Browse full product catalogue',
          onTap: () => Navigator.pushNamed(context, '/admin-products'),
        ),
        SizedBox(height: 10),
        _quickActionTile(
          icon: Icons.people_outline,
          label: 'Manage Users',
          subtitle: 'View and manage registered users',
          onTap: () => Navigator.pushNamed(context, '/admin-users'),
        ),
        SizedBox(height: 10),
        _quickActionTile(
          icon: Icons.category_outlined,
          label: 'Manage Categories',
          subtitle: 'Organize your product categories',
          onTap: () => Navigator.pushNamed(context, '/admin-categories'),
        ),
      ],
    );
  }

  Widget _quickActionTile({
    required IconData icon,
    required String label,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Theme.of(context).cardColor,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: Theme.of(context).dividerColor.withValues(alpha: 0.1),
            ),
          ),
          padding: EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.secondary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: Theme.of(context).colorScheme.secondary, size: 22),
              ),
              SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(label, style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 15)),
                    Text(subtitle, style: GoogleFonts.urbanist(fontSize: 12, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7))),
                  ],
                ),
              ),
              Hero(tag: 'chevron_$label', child: Icon(Icons.chevron_right, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5), size: 20)),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatItem {
  final String label;
  final String value;
  final IconData icon;
  _StatItem(this.label, this.value, this.icon);
}
