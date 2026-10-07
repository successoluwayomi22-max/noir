import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shimmer/shimmer.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../providers/cart_provider.dart';
import '../../providers/settings_provider.dart';
import '../../providers/currency_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import '../../models/product.dart';
import 'product_detail_screen.dart';
import 'cart_screen.dart';

class CatalogueScreen extends StatefulWidget {
  const CatalogueScreen({super.key});

  @override
  State<CatalogueScreen> createState() => _CatalogueScreenState();
}

class _CatalogueScreenState extends State<CatalogueScreen> {
  List<Product> _products = [];
  List<dynamic> _categories = [];
  bool _loading = true;
  int _selectedCategory = 0; // 0 = All
  String _sort = 'newest';
  int _page = 1;
  int _totalPages = 1;
  final _searchController = TextEditingController();
  bool _showSearch = false;

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadProducts();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    try {
      final api = context.read<NoirApiClient>();
      final cats = await api.getCategories();
      if (mounted) setState(() => _categories = cats);
    } catch (_) {}
  }

  Future<void> _loadProducts({bool append = false}) async {
    if (!append) setState(() => _loading = true);
    try {
      final api = context.read<NoirApiClient>();
      final data = await api.getProducts(
        search: _searchController.text.trim().isEmpty
            ? null
            : _searchController.text.trim(),
        categoryId: _selectedCategory > 0 ? _selectedCategory : null,
        page: _page,
        sort: _sort,
      );
      if (mounted) {
        setState(() {
          if (append) {
            _products.addAll(data.products);
          } else {
            _products = data.products;
          }
          _totalPages = data.totalPages;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _loading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.toString().contains('SocketException') 
                  ? 'Unable to connect to the server. Please check your internet connection.' 
                  : 'An error occurred while loading products. Please try again.',
            ),
            backgroundColor: Theme.of(context).colorScheme.error,
          ),
        );
      }
    }
  }

  void _onCategoryTap(int catId) {
    setState(() {
      _selectedCategory = catId;
      _page = 1;
    });
    _loadProducts();
  }

  void _onSearch() {
    setState(() => _page = 1);
    _loadProducts();
  }

  void _addProductToBag(Product product) {
    final cart = context.read<CartProvider>();
    cart.addItem(
      CartItem(
        productId: product.id,
        name: product.name,
        price: product.price,
        image: product.featuredImage,
      ),
    );
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          AppStrings.of(context).addedToBag,
          style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
        ),
        backgroundColor: Theme.of(context).colorScheme.primary,
        behavior: SnackBarBehavior.floating,
        duration: Duration(seconds: 1),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        action: SnackBarAction(
          label: AppStrings.of(context).viewCart,
          textColor: Colors.white,
          onPressed: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => CartScreen()),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartProvider>();
    final strings = AppStrings.of(context);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        automaticallyImplyLeading: false,
        leading: null,
        titleSpacing: 16,
        centerTitle: false,
        title: _showSearch
            ? TextField(
                controller: _searchController,
                autofocus: true,
                decoration: InputDecoration(
                  hintText: AppStrings.of(context).searchProducts,
                  border: InputBorder.none,
                  hintStyle: GoogleFonts.urbanist(
                    color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                    fontSize: 16,
                  ),
                ),
                style: GoogleFonts.urbanist(fontSize: 16),
                onSubmitted: (_) => _onSearch(),
              )
            : Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.asset(
                      'assets/images/noir_logo.jpg',
                      width: 32,
                      height: 32,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => Image.asset(
                        'assets/app_icon.jpg',
                        width: 32,
                        height: 32,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        context.watch<SettingsProvider>().storeName.toUpperCase(),
                        style: GoogleFonts.urbanist(
                          fontSize: 17,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 1.8,
                        ),
                      ),
                      Text(
                        'Curated Boutique',
                        style: GoogleFonts.urbanist(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
        actions: [
          IconButton(
            icon: Icon(
              _showSearch ? Icons.close : Icons.search_rounded,
              size: 22,
            ),
            onPressed: () {
              setState(() {
                _showSearch = !_showSearch;
                if (!_showSearch) {
                  _searchController.clear();
                  _page = 1;
                  _loadProducts();
                }
              });
            },
          ),
          Stack(
            children: [
              IconButton(
                icon: Icon(Icons.shopping_bag_outlined, size: 22),
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => CartScreen()),
                ),
              ),
              if (cart.itemCount > 0)
                Positioned(
                  right: 6,
                  top: 6,
                  child: Container(
                    padding: EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.primary,
                      shape: BoxShape.circle,
                    ),
                    child: Text(
                      '${cart.itemCount}',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: EdgeInsets.fromLTRB(20, 8, 20, 16),
            child: _buildEditorialHeader(),
          ),

          // Category Chips
          if (_categories.isNotEmpty)
            SizedBox(
              height: 46,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: EdgeInsets.symmetric(horizontal: 16),
                children: [
                  _buildCategoryChip(strings.all, 0),
                  ..._categories.map(
                    (cat) => _buildCategoryChip(
                      strings.translateCategory(cat['name']?.toString() ?? ''),
                      int.tryParse(cat['id'].toString()) ?? 0,
                    ),
                  ),
                ],
              ),
            ),

          // Sort Row
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 20, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${_products.length} ${strings.pieces}',
                  style: GoogleFonts.urbanist(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (v) {
                    setState(() {
                      _sort = v;
                      _page = 1;
                    });
                    _loadProducts();
                  },
                  itemBuilder: (_) => [
                    PopupMenuItem(value: 'newest', child: Text(strings.newest)),
                    PopupMenuItem(
                       value: 'price_asc',
                      child: Text(strings.priceLowHigh),
                    ),
                    PopupMenuItem(
                      value: 'price_desc',
                      child: Text(strings.priceHighLow),
                    ),
                  ],
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        strings.sortBy,
                        style: GoogleFonts.urbanist(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                        ),
                      ),
                      SizedBox(width: 4),
                      Icon(
                        Icons.keyboard_arrow_down_rounded,
                        size: 18,
                        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Products Grid
          Expanded(
            child: _loading
                ? _buildShimmerGrid()
                : _products.isEmpty
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.shopping_bag_outlined,
                          size: 64,
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.3),
                        ),
                        SizedBox(height: 16),
                        Text(
                          'No products found',
                          style: GoogleFonts.urbanist(
                            color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                            fontSize: 16,
                          ),
                        ),
                      ],
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: () async {
                      _page = 1;
                      await _loadProducts();
                    },
                    child: GridView.builder(
                      padding: EdgeInsets.fromLTRB(16, 0, 16, 24),
                      gridDelegate:
                          SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            childAspectRatio: 0.50,
                            mainAxisSpacing: 16,
                            crossAxisSpacing: 14,
                          ),
                      itemCount:
                          _products.length + (_page < _totalPages ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index == _products.length) {
                          // Load more trigger
                          WidgetsBinding.instance.addPostFrameCallback((_) {
                            _page++;
                            _loadProducts(append: true);
                          });
                          return Center(
                            child: Padding(
                              padding: EdgeInsets.all(16),
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                          );
                        }
                        return _buildProductCard(_products[index]);
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryChip(String name, int id) {
    final selected = _selectedCategory == id;
    return Padding(
      padding: EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(name),
        selected: selected,
        onSelected: (_) => _onCategoryTap(id),
        backgroundColor: Theme.of(context).colorScheme.surface,
        selectedColor: Theme.of(context).colorScheme.primary,
        labelStyle: GoogleFonts.urbanist(
          fontSize: 13,
          fontWeight: FontWeight.w800,
          color: selected ? Colors.white : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(22),
          side: BorderSide(
            color: selected ? Theme.of(context).colorScheme.primary : Theme.of(context).colorScheme.outline,
          ),
        ),
        showCheckmark: false,
        padding: EdgeInsets.symmetric(horizontal: 8),
      ),
    );
  }

  Widget _buildEditorialHeader() {
    final strings = AppStrings.of(context);
    return Container(
      height: 126,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(26),
        gradient: LinearGradient(
          colors: [Theme.of(context).colorScheme.primary, NoirTheme.inkSoft, NoirTheme.accent],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.18),
            blurRadius: 28,
            offset: Offset(0, 16),
          ),
        ],
      ),
      child: Stack(
        children: [
          Positioned(
            right: -16,
            top: -24,
            child: Container(
              width: 118,
              height: 118,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.08),
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.all(20),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        strings.newEditorialDrop,
                        style: GoogleFonts.urbanist(
                          color: Colors.amber,
                          fontSize: 12,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.8,
                        ),
                      ),
                      SizedBox(height: 8),
                      Text(
                        strings.editorialSubtitle,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.urbanist(
                          color: Colors.white,
                          fontSize: 22,
                          height: 1.05,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ],
                  ),
                ),
                SizedBox(width: 14),
                Container(
                  width: 76,
                  height: 76,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(22),
                    border: Border.all(color: Colors.white24),
                  ),
                  child: Icon(
                    Icons.auto_awesome_rounded,
                    color: Colors.white,
                    size: 34,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProductCard(Product product) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final image = product.featuredImage ?? '';
    final name = AppStrings.of(context).translateProductName(product.name);
    final price = context.watch<CurrencyProvider>().format(product.price);
    final brand = product.brandName ?? context.read<SettingsProvider>().brandName;

    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => ProductDetailScreen(
            productId: product.id,
          ),
        ),
      ),
      child: Container(
        decoration: BoxDecoration(
          color: isDark ? NoirTheme.charcoalCard : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: isDark ? Border.all(color: const Color(0xFF2D322B), width: 1) : null,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.35 : 0.04),
              blurRadius: 18,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    CachedNetworkImage(
                      imageUrl: image,
                      fit: BoxFit.cover,
                      placeholder: (_, _) => Shimmer.fromColors(
                        baseColor: isDark ? const Color(0xFF1E211C) : Colors.grey[200]!,
                        highlightColor: isDark ? const Color(0xFF2A2E27) : Colors.grey[50]!,
                        child: Container(color: isDark ? NoirTheme.charcoalCard : Colors.white),
                      ),
                      errorWidget: (_, _, _) => Container(
                        color: isDark ? const Color(0xFF1B1E1A) : Theme.of(context).colorScheme.surfaceTint,
                        child: Icon(
                          Icons.image_outlined,
                          color: isDark ? Colors.white38 : Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                          size: 32,
                        ),
                      ),
                    ),
                    Positioned(
                      right: 12,
                      top: 12,
                      child: Consumer<CartProvider>(
                        builder: (context, cart, _) {
                          final isSaved = cart.isWishlisted(product.id);
                          return Material(
                            color: isDark ? Colors.black.withValues(alpha: 0.75) : Colors.white,
                            shape: const CircleBorder(),
                            elevation: 2,
                            shadowColor: Colors.black26,
                            child: IconButton(
                              constraints: const BoxConstraints.tightFor(
                                width: 34,
                                height: 34,
                              ),
                              padding: EdgeInsets.zero,
                              icon: Icon(
                                isSaved ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                                size: 18,
                                color: isSaved ? Colors.red : (isDark ? Colors.white : Colors.black87),
                              ),
                              onPressed: () => cart.toggleWishlist(product.toJson()),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    style: GoogleFonts.urbanist(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: isDark ? Colors.white : Colors.black87,
                      height: 1.2,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    brand,
                    style: GoogleFonts.urbanist(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: isDark ? NoirTheme.mutedWhiteText : Colors.grey.shade600,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerRight,
                    child: Text(
                      price,
                      style: GoogleFonts.urbanist(
                        fontSize: 15,
                        fontWeight: FontWeight.w900,
                        color: isDark ? NoirTheme.softOlive : NoirTheme.primary,
                        letterSpacing: -0.2,
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Stone · S, M, L, XL',
                    style: GoogleFonts.urbanist(
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                      color: isDark ? Colors.white60 : Colors.grey.shade500,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    height: 38,
                    child: ElevatedButton(
                      onPressed: () => _addProductToBag(product),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: isDark ? NoirTheme.softOlive : Theme.of(context).colorScheme.primary,
                        foregroundColor: isDark ? const Color(0xFF121412) : Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 0),
                      ),
                      child: Text(
                        AppStrings.of(context).addToBag,
                        style: GoogleFonts.urbanist(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.4,
                        ),
                      ),
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

  Widget _buildShimmerGrid() {
    return GridView.builder(
      padding: EdgeInsets.fromLTRB(16, 0, 16, 24),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.62,
        mainAxisSpacing: 16,
        crossAxisSpacing: 14,
      ),
      itemCount: 6,
      itemBuilder: (_, _) => Shimmer.fromColors(
        baseColor: Colors.grey[200]!,
        highlightColor: Colors.grey[50]!,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
            ),
            SizedBox(height: 10),
            Container(width: 60, height: 10, color: Colors.white),
            SizedBox(height: 6),
            Container(width: 120, height: 14, color: Colors.white),
            SizedBox(height: 6),
            Container(width: 80, height: 14, color: Colors.white),
          ],
        ),
      ),
    );
  }
}
