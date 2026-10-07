import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';

import '../../api_client.dart';
import '../../theme.dart';
import '../../providers/currency_provider.dart';
import '../../utils/error_helper.dart';

class AdminProductsScreen extends StatefulWidget {
  const AdminProductsScreen({super.key});

  @override
  State<AdminProductsScreen> createState() => _AdminProductsScreenState();
}

class _AdminProductsScreenState extends State<AdminProductsScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _products = [];
  Map<String, dynamic>? _summary;
  String _searchQuery = '';
  String _stockFilter = 'all'; // 'all', 'low_stock', 'out_of_stock', 'published', 'draft'
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchProducts();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchProducts() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = context.read<NoirApiClient>();
      final res = await api.getAdminProducts();
      if (mounted) {
        setState(() {
          _products = res['products'] ?? res['data'] ?? [];
          _summary = res['summary'] is Map<String, dynamic> ? res['summary'] : null;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = ErrorHelper.format(e, fallback: 'Unable to load products. Please try again.');
          _isLoading = false;
        });
      }
    }
  }

  List<dynamic> get _filteredProducts {
    var list = _products;
    if (_stockFilter == 'low_stock') {
      list = list.where((p) {
        final q = int.tryParse(p['quantity']?.toString() ?? '0') ?? 0;
        return q > 0 && q < 5;
      }).toList();
    } else if (_stockFilter == 'out_of_stock') {
      list = list.where((p) {
        final q = int.tryParse(p['quantity']?.toString() ?? '0') ?? 0;
        return q <= 0;
      }).toList();
    } else if (_stockFilter == 'published') {
      list = list.where((p) => p['status'] == 'published').toList();
    } else if (_stockFilter == 'draft') {
      list = list.where((p) => p['status'] == 'draft').toList();
    }

    if (_searchQuery.isEmpty) return list;
    return list.where((p) {
      final name = (p['name'] ?? '').toString().toLowerCase();
      final slug = (p['slug'] ?? '').toString().toLowerCase();
      return name.contains(_searchQuery.toLowerCase()) || slug.contains(_searchQuery.toLowerCase());
    }).toList();
  }

  void _showProductDialog({Map<String, dynamic>? product}) {
    final isEdit = product != null;
    final nameCtrl = TextEditingController(text: isEdit ? product['name'] : '');
    final descCtrl = TextEditingController(text: isEdit ? (product['description'] ?? '') : '');
    final priceCtrl = TextEditingController(text: isEdit ? product['price']?.toString() : '');
    final qtyCtrl = TextEditingController(text: isEdit ? product['quantity']?.toString() : '10');
    final imgCtrl = TextEditingController(text: isEdit ? (product['featured_image'] ?? '') : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800');
    final videoCtrl = TextEditingController();
    String status = isEdit ? (product['status'] ?? 'published') : 'published';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) => Container(
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(context).viewInsets.bottom + 20),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isEdit ? 'Edit Product' : 'Add New Product',
                      style: GoogleFonts.urbanist(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: nameCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Product Name *',
                    hintText: 'e.g. Noir Silk Trench Coat',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: descCtrl,
                  maxLines: 2,
                  decoration: const InputDecoration(
                    labelText: 'Description',
                    hintText: 'Luxury details, materials...',
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: priceCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Price (₦) *',
                          hintText: '45000',
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextField(
                        controller: qtyCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Stock Quantity',
                          hintText: '10',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: imgCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Image URL',
                    hintText: 'https://...',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: videoCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Video URL (Optional for Reels)',
                    hintText: 'https://...',
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Text('Status: ', style: GoogleFonts.urbanist(fontWeight: FontWeight.w600)),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('Published'),
                      selected: status == 'published',
                      selectedColor: NoirTheme.primary.withValues(alpha: 0.2),
                      onSelected: (val) {
                        if (val) setModalState(() => status = 'published');
                      },
                    ),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('Draft'),
                      selected: status == 'draft',
                      onSelected: (val) {
                        if (val) setModalState(() => status = 'draft');
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () async {
                    final name = nameCtrl.text.trim();
                    final price = double.tryParse(priceCtrl.text.trim());
                    if (name.isEmpty || price == null) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Please provide a valid product name and price')),
                      );
                      return;
                    }
                    Navigator.pop(ctx);

                    final payload = {
                      'name': name,
                      'description': descCtrl.text.trim(),
                      'price': price,
                      'quantity': int.tryParse(qtyCtrl.text.trim()) ?? 10,
                      'status': status,
                      'featured_image': imgCtrl.text.trim(),
                      'video_url': videoCtrl.text.trim(),
                    };

                    final api = context.read<NoirApiClient>();
                    final messenger = ScaffoldMessenger.of(context);

                    try {
                      if (isEdit) {
                        await api.updateAdminProduct(product['id'], payload);
                      } else {
                        await api.createAdminProduct(payload);
                      }
                      _fetchProducts();
                      if (!mounted) return;
                      messenger.showSnackBar(
                        SnackBar(
                          content: Text(isEdit ? 'Product updated successfully' : 'Product created & published!'),
                          backgroundColor: NoirTheme.primary,
                        ),
                      );
                    } catch (e) {
                      if (!mounted) return;
                      messenger.showSnackBar(
                        SnackBar(content: Text('Failed: $e'), backgroundColor: Colors.red),
                      );
                    }
                  },
                  child: Text(isEdit ? 'SAVE CHANGES' : 'CREATE PRODUCT'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _deleteProduct(int id, String name) async {
    final messenger = ScaffoldMessenger.of(context);
    final api = context.read<NoirApiClient>();
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Product?'),
        content: Text('Are you sure you want to delete "$name"? This action cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await api.deleteAdminProduct(id);
        _fetchProducts();
        if (!mounted) return;
        messenger.showSnackBar(
          SnackBar(content: const Text('Product removed'), backgroundColor: Colors.orange.shade800),
        );
      } catch (e) {
        if (!mounted) return;
        messenger.showSnackBar(
          SnackBar(content: Text('Delete failed: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  String _formatPrice(dynamic price) {
    try {
      return context.watch<CurrencyProvider>().format(price);
    } catch (_) {
      final p = double.tryParse(price.toString()) ?? 0;
      return '₦${p.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}';
    }
  }

  void _showProductOptionsBottomSheet(Map<String, dynamic> p) {
    final id = int.tryParse(p['id']?.toString() ?? '0') ?? 0;
    final name = p['name']?.toString() ?? 'Product';
    final price = double.tryParse(p['price']?.toString() ?? '0') ?? 0.0;
    final status = p['status']?.toString() ?? 'draft';

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.of(ctx).padding.bottom + 20),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.2)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${_formatPrice(price)} · ${status.toUpperCase()}',
                        style: GoogleFonts.urbanist(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: NoirTheme.softOlive,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded),
                  onPressed: () => Navigator.pop(ctx),
                ),
              ],
            ),
            const Divider(height: 24),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: NoirTheme.softOlive.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.edit_outlined, color: NoirTheme.softOlive, size: 20),
              ),
              title: Text('Edit Product Details', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700)),
              subtitle: Text('Modify title, pricing, stock & category', style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey)),
              onTap: () {
                Navigator.pop(ctx);
                _showProductDialog(product: p);
              },
            ),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.red.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.delete_outline_rounded, color: Colors.red, size: 20),
              ),
              title: Text('Delete Product', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, color: Colors.red)),
              subtitle: Text('Permanently remove piece from catalogue', style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey)),
              onTap: () {
                Navigator.pop(ctx);
                _deleteProduct(id, name);
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _filteredProducts;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('Manage Catalogue', style: GoogleFonts.urbanist(fontWeight: FontWeight.w800)),
        centerTitle: false,
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _fetchProducts,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showProductDialog(),
        backgroundColor: Theme.of(context).colorScheme.primary,
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: Text('Add Product', style: GoogleFonts.urbanist(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          // ── Product Inventory Observatory Banner ──
          if (_summary != null)
            Container(
              margin: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Theme.of(context).cardColor,
                    NoirTheme.primary.withValues(alpha: 0.12),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: NoirTheme.softOlive.withValues(alpha: 0.25)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.query_stats_rounded, size: 16, color: NoirTheme.softOlive),
                          const SizedBox(width: 6),
                          Text(
                            'INVENTORY OBSERVATORY',
                            style: GoogleFonts.urbanist(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 1.1,
                              color: NoirTheme.softOlive,
                            ),
                          ),
                        ],
                      ),
                      Text(
                        'Valuation: ${_formatPrice(_summary!['total_inventory_value'] ?? 0)}',
                        style: GoogleFonts.urbanist(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: _buildStatChip(
                          'Total Pieces',
                          '${_summary!['total_products'] ?? _products.length}',
                          Colors.blueGrey,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _buildStatChip(
                          'Low Stock',
                          '${_summary!['low_stock_count'] ?? 0}',
                          Colors.amber.shade700,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _buildStatChip(
                          'Out of Stock',
                          '${_summary!['out_of_stock_count'] ?? 0}',
                          Colors.redAccent,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

          // ── Search & Quick Filter Chips ──
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 4.0),
            child: TextField(
              controller: _searchController,
              onChanged: (val) => setState(() => _searchQuery = val),
              decoration: InputDecoration(
                hintText: 'Search products by name or SKU...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 18),
                        onPressed: () {
                          _searchController.clear();
                          setState(() => _searchQuery = '');
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              ),
            ),
          ),

          // ── Stock Health Filter Tabs ──
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Row(
              children: [
                _buildFilterChip('All', 'all'),
                const SizedBox(width: 8),
                _buildFilterChip('⚠️ Low Stock (<5)', 'low_stock'),
                const SizedBox(width: 8),
                _buildFilterChip('🚨 Out of Stock', 'out_of_stock'),
                const SizedBox(width: 8),
                _buildFilterChip('Published', 'published'),
                const SizedBox(width: 8),
                _buildFilterChip('Draft', 'draft'),
              ],
            ),
          ),

          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 32),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: 64,
                                height: 64,
                                decoration: BoxDecoration(
                                  color: NoirTheme.softOlive.withValues(alpha: 0.15),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.inventory_2_outlined, size: 30, color: NoirTheme.softOlive),
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'Unable to Load Inventory',
                                style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                _error!,
                                textAlign: TextAlign.center,
                                style: GoogleFonts.urbanist(
                                  fontSize: 14,
                                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                                ),
                              ),
                              const SizedBox(height: 20),
                              ElevatedButton.icon(
                                onPressed: _fetchProducts,
                                icon: const Icon(Icons.refresh_rounded, size: 18),
                                label: const Text('Reload Catalogue'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: NoirTheme.primary,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                    : filtered.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey),
                                const SizedBox(height: 12),
                                Text(
                                  _searchQuery.isEmpty ? 'No products matching filter' : 'No matching products',
                                  style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          )
                        : ListView.separated(
                            padding: const EdgeInsets.fromLTRB(16, 8, 16, 88),
                            itemCount: filtered.length,
                            separatorBuilder: (_, _) => const SizedBox(height: 10),
                            itemBuilder: (ctx, i) {
                              final p = filtered[i];
                              final name = p['name']?.toString() ?? 'Unnamed';
                              final price = double.tryParse(p['price']?.toString() ?? '0') ?? 0.0;
                              final qty = int.tryParse(p['quantity']?.toString() ?? '0') ?? 0;
                              String img = (p['featured_image'] ?? p['image'] ?? '').toString();
                              if (img.isNotEmpty && !img.startsWith('http')) {
                                final base = context.read<NoirApiClient>().baseUrl;
                                img = '$base${img.startsWith('/') ? '' : '/'}$img'.replaceAll('\\', '/');
                              }
                              final status = p['status']?.toString() ?? 'draft';

                              // Stock health color & text
                              Color stockColor = NoirTheme.softOlive;
                              String stockLabel = '$qty in stock';
                              if (qty <= 0) {
                                stockColor = Colors.redAccent;
                                stockLabel = 'OUT OF STOCK';
                              } else if (qty < 5) {
                                stockColor = Colors.amber.shade700;
                                stockLabel = 'LOW STOCK ($qty left)';
                              }

                              return Card(
                                elevation: 0,
                                color: Theme.of(context).cardColor,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  side: BorderSide(
                                    color: qty <= 0
                                        ? Colors.redAccent.withValues(alpha: 0.4)
                                        : qty < 5
                                            ? Colors.amber.withValues(alpha: 0.35)
                                            : Theme.of(context).dividerColor.withValues(alpha: 0.1),
                                  ),
                                ),
                                child: ListTile(
                                  leading: ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: img.isNotEmpty
                                        ? CachedNetworkImage(
                                            imageUrl: img,
                                            width: 48,
                                            height: 48,
                                            fit: BoxFit.cover,
                                            errorWidget: (_, _, _) => Container(
                                              width: 48,
                                              height: 48,
                                              color: Colors.grey.shade800,
                                              child: const Icon(Icons.broken_image, size: 20, color: Colors.grey),
                                            ),
                                          )
                                        : Container(
                                            width: 48,
                                            height: 48,
                                            color: Colors.grey.shade800,
                                            child: const Icon(Icons.inventory_2, size: 20, color: Colors.grey),
                                          ),
                                  ),
                                  title: Text(name, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold)),
                                  subtitle: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const SizedBox(height: 4),
                                      Text(
                                        _formatPrice(price),
                                        style: TextStyle(
                                          fontWeight: FontWeight.w700,
                                          color: Theme.of(context).colorScheme.onSurface,
                                        ),
                                      ),
                                      const SizedBox(height: 5),
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                            decoration: BoxDecoration(
                                              color: stockColor.withValues(alpha: 0.15),
                                              borderRadius: BorderRadius.circular(6),
                                              border: Border.all(color: stockColor.withValues(alpha: 0.3)),
                                            ),
                                            child: Text(
                                              stockLabel,
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: stockColor,
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 6),
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                            decoration: BoxDecoration(
                                              color: status == 'published'
                                                  ? NoirTheme.softOlive.withValues(alpha: 0.2)
                                                  : Colors.grey.withValues(alpha: 0.2),
                                              borderRadius: BorderRadius.circular(6),
                                            ),
                                            child: Text(
                                              status.toUpperCase(),
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: status == 'published' ? NoirTheme.softOlive : Colors.grey.shade400,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                  trailing: IconButton(
                                    icon: const Icon(Icons.more_vert_rounded),
                                    onPressed: () => _showProductOptionsBottomSheet(p),
                                  ),
                                ),
                              );
                            },
                          ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatChip(String title, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: GoogleFonts.urbanist(fontSize: 10, color: color, fontWeight: FontWeight.w600)),
          const SizedBox(height: 2),
          Text(value, style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.w800, color: color)),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String filterKey) {
    final selected = _stockFilter == filterKey;
    return ChoiceChip(
      label: Text(label, style: GoogleFonts.urbanist(fontSize: 12, fontWeight: selected ? FontWeight.bold : FontWeight.w500)),
      selected: selected,
      selectedColor: NoirTheme.primary.withValues(alpha: 0.25),
      onSelected: (val) {
        if (val) setState(() => _stockFilter = filterKey);
      },
    );
  }
}
