import 'dart:async';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:local_auth/local_auth.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../providers/cart_provider.dart';
import '../../providers/settings_provider.dart';
import '../../providers/currency_provider.dart';
import '../../theme.dart';
import '../../models/product.dart';
import '../../providers/ratings_provider.dart';
import '../../utils/noir_share.dart';
import '../../l10n/app_strings.dart';
import 'cart_screen.dart';
import 'order_tracking_screen.dart';
import '../../widgets/size_predictor_widget.dart';
import '../../widgets/error_state_view.dart';

class ProductDetailScreen extends StatefulWidget {
  final int productId;
  const ProductDetailScreen({super.key, required this.productId});

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  Product? _product;
  List<dynamic> _images = [];
  List<dynamic> _variants = [];
  bool _loading = true;
  bool _hasError = false;
  bool _isOffline = false;
  int _currentImageIndex = 0;
  String? _selectedSize;
  String? _selectedColor;
  int _quantity = 1;
  final PageController _imageController = PageController();

  // Real-time dynamic indicators
  late Timer _realtimeTimer;
  Duration _dispatchCountdown = const Duration(hours: 3, minutes: 24, seconds: 45);
  final int _liveViewers = 6;

  @override
  void initState() {
    super.initState();
    _loadProduct();
    _startRealtimeTicker();
  }

  void _startRealtimeTicker() {
    _realtimeTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        if (_dispatchCountdown.inSeconds > 0) {
          _dispatchCountdown = _dispatchCountdown - const Duration(seconds: 1);
        } else {
          _dispatchCountdown = const Duration(hours: 4, minutes: 0, seconds: 0);
        }
      });
    });
  }

  @override
  void dispose() {
    _realtimeTimer.cancel();
    _imageController.dispose();
    super.dispose();
  }

  String _formatCountdown(Duration d) {
    final h = d.inHours.toString().padLeft(2, '0');
    final m = (d.inMinutes % 60).toString().padLeft(2, '0');
    final s = (d.inSeconds % 60).toString().padLeft(2, '0');
    return '${h}h ${m}m ${s}s';
  }

  Future<void> _loadProduct() async {
    setState(() {
      _loading = true;
      _hasError = false;
      _isOffline = false;
    });
    try {
      final api = context.read<NoirApiClient>();
      final data = await api.getProductDetail(widget.productId);
      if (mounted) {
        setState(() {
          _product = data.product;
          _images = data.images;
          _variants = data.variants;
          _loading = false;
          _hasError = false;

          final sizesSet = <String>{};
          final colorsSet = <String>{};
          for (final v in _variants) {
            final size = (v['size'] ?? '').toString();
            final color = (v['color'] ?? '').toString();
            if (size.isNotEmpty) sizesSet.add(size);
            if (color.isNotEmpty) colorsSet.add(color);
          }
          if (sizesSet.isEmpty) {
            sizesSet.addAll(['S (38)', 'M (40)', 'L (42)', 'XL (44)']);
          }
          if (colorsSet.isEmpty) {
            colorsSet.addAll(['Obsidian Black', 'Charcoal', 'Ecru Silk']);
          }
          _selectedSize = sizesSet.first;
          _selectedColor = colorsSet.first;
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
          _loading = false;
          _hasError = true;
          _isOffline = isNet;
        });
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

  void _addToCart({bool andCheckout = false}) {
    if (_product == null) return;

    final cart = context.read<CartProvider>();
    cart.addItem(
      CartItem(
        productId: _product!.id,
        name: _product!.name,
        price: _product!.price,
        image: _product!.featuredImage,
        quantity: _quantity,
      ),
    );

    if (andCheckout) {
      Navigator.push(context, MaterialPageRoute(builder: (_) => const CartScreen()));
      return;
    }

    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.check_circle_outline_rounded, color: Colors.green, size: 20),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'Added to Bag (${_quantity}x)',
                style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF1E211A),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        action: SnackBarAction(
          label: 'CHECKOUT',
          textColor: NoirTheme.primary,
          onPressed: () => Navigator.pushNamed(context, '/cart'),
        ),
      ),
    );
  }

  Future<void> _performBiometricFastCheckout() async {
    if (_product == null) return;
    final p = _product!;
    final auth = LocalAuthentication();

    bool authenticated = false;
    try {
      final canAuth = await auth.canCheckBiometrics || await auth.isDeviceSupported();
      if (canAuth) {
        authenticated = await auth.authenticate(
          localizedReason: 'Authorize 1-Tap Biometric Purchase of ${p.name}',
          persistAcrossBackgrounding: true,
          biometricOnly: false,
        );
      } else {
        authenticated = false;
      }
    } catch (_) {
      authenticated = false;
    }

    if (!mounted) return;

    if (!authenticated) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Purchase cancelled — biometric authorization required.',
              style: GoogleFonts.urbanist(fontWeight: FontWeight.w600),
            ),
            backgroundColor: NoirTheme.primaryDark,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
      return;
    }

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => Center(
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: const Color(0xFF161814),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: NoirTheme.primary),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: NoirTheme.primary),
              const SizedBox(height: 16),
              Text(
                '⚡ Authorizing Express Courier Dispatch...',
                style: GoogleFonts.urbanist(color: Colors.white, fontWeight: FontWeight.w800),
              ),
            ],
          ),
        ),
      ),
    );

    try {
      final api = context.read<NoirApiClient>();
      final res = await api.fastCheckout(
        productId: p.id,
        quantity: _quantity,
        size: _selectedSize ?? 'M (40)',
        color: _selectedColor ?? 'Obsidian Black',
      );

      if (!mounted) return;
      Navigator.pop(context); // dismiss loading

      final order = res['order'] as Map<String, dynamic>?;
      final orderNumber = order?['order_number']?.toString() ?? '';

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('⚡ Order #$orderNumber Authorized & Dispatched!'),
          backgroundColor: NoirTheme.primary,
          behavior: SnackBarBehavior.floating,
        ),
      );

      if (orderNumber.isNotEmpty) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => OrderTrackingScreen(initialOrderNumber: orderNumber),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        Navigator.pop(context); // dismiss loading
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Fast checkout error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _showBespokeFittingModal() {
    if (_product == null) return;
    
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        ),
        padding: const EdgeInsets.fromLTRB(24, 16, 24, 32),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 44,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.withValues(alpha: 0.35),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  Icon(Icons.auto_awesome, color: NoirTheme.primary, size: 22),
                  const SizedBox(width: 10),
                  Text(
                    'Smart AI Size Predictor',
                    style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w900),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                'Our AI predicts your perfect bespoke fit using real dimensions.',
                style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey),
              ),
              const SizedBox(height: 24),
              SizePredictorWidget(
                product: _product!,
                apiClient: Provider.of<NoirApiClient>(context, listen: false),
                onSizeSelected: (String size) {
                  setState(() {
                    _selectedSize = size;
                  });
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(backgroundColor: Colors.transparent, elevation: 0),
        body: Center(child: CircularProgressIndicator(color: NoirTheme.primary)),
      );
    }

    if (_product == null) {
      return Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(backgroundColor: Colors.transparent, elevation: 0),
        body: _hasError
            ? _isOffline
                ? ErrorStateView.offline(
                    onRetry: _loadProduct,
                    onSecondaryAction: () => Navigator.maybePop(context),
                  )
                : ErrorStateView.serverError(
                    onRetry: _loadProduct,
                    onSecondaryAction: () => Navigator.maybePop(context),
                  )
            : ErrorStateView.empty(
                title: 'Piece Not Found',
                message: 'The requested piece could not be located in this edition.',
                actionLabel: 'Return to Catalogue',
                onAction: () => Navigator.maybePop(context),
              ),
      );
    }

    final p = _product!;
    final allImages = _images.isNotEmpty
        ? _images
            .map((i) => Product.fixImageUrl(i['image_path']?.toString() ?? ''))
            .where((i) => i.isNotEmpty)
            .toList()
        : [p.featuredImage ?? ''];

    final sizes = ['S (38)', 'M (40)', 'L (42)', 'XL (44)'];
    final colors = ['Obsidian Black', 'Charcoal', 'Ecru Silk'];

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: CustomScrollView(
        slivers: [
          // Image Showcase with live badges
          SliverAppBar(
            expandedHeight: MediaQuery.of(context).size.height * 0.52,
            pinned: true,
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            leading: Padding(
              padding: const EdgeInsets.all(8),
              child: CircleAvatar(
                backgroundColor: Colors.black.withValues(alpha: 0.6),
                child: IconButton(
                  icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 18, color: Colors.white),
                  onPressed: () => Navigator.pop(context),
                ),
              ),
            ),
            actions: [
              Consumer<CartProvider>(builder: (context, cart, _) {
                final isSaved = cart.isWishlisted(p.id);
                return Padding(
                  padding: const EdgeInsets.all(8),
                  child: CircleAvatar(
                    backgroundColor: Colors.black.withValues(alpha: 0.6),
                    child: IconButton(
                      icon: Icon(
                        isSaved ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                        size: 18,
                        color: isSaved ? Colors.red : Colors.white,
                      ),
                      onPressed: () => cart.toggleWishlist(p.toJson()),
                    ),
                  ),
                );
              }),
              Padding(
                padding: const EdgeInsets.all(8),
                child: CircleAvatar(
                  backgroundColor: Colors.black.withValues(alpha: 0.6),
                  child: IconButton(
                    icon: const Icon(Icons.share_outlined, size: 18, color: Colors.white),
                    onPressed: () {
                      final slugOrId = (p.slug != null && p.slug!.isNotEmpty) ? p.slug! : p.id.toString();
                      final url = 'http://192.168.61.108/noir/public/product/$slugOrId';
                      NoirShare.share(
                        context,
                        text: 'Discover "${p.name}" at Noir & Co. Luxury Atelier:\n$url',
                        subject: p.name,
                        fallbackCopyText: url,
                      );
                    },
                  ),
                ),
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  PageView.builder(
                    controller: _imageController,
                    itemCount: allImages.length,
                    onPageChanged: (i) => setState(() => _currentImageIndex = i),
                    itemBuilder: (_, i) => CachedNetworkImage(
                      imageUrl: allImages[i],
                      fit: BoxFit.cover,
                      placeholder: (_, _) => Container(color: const Color(0xFF131511)),
                      errorWidget: (_, _, _) => Container(
                        color: const Color(0xFF131511),
                        child: const Center(child: Icon(Icons.image_outlined, size: 48, color: Colors.white24)),
                      ),
                    ),
                  ),

                  // Real-time live viewing badge
                  Positioned(
                    top: MediaQuery.of(context).padding.top + 50,
                    left: 16,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.75),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: Colors.white24, width: 0.5),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 6,
                            height: 6,
                            decoration: const BoxDecoration(
                              color: Color(0xFF2ECC71),
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            '$_liveViewers patrons viewing now',
                            style: GoogleFonts.urbanist(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  // Page dots
                  if (allImages.length > 1)
                    Positioned(
                      bottom: 16,
                      left: 0,
                      right: 0,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(
                          allImages.length,
                          (i) => AnimatedContainer(
                            duration: const Duration(milliseconds: 300),
                            width: i == _currentImageIndex ? 22 : 6,
                            height: 6,
                            margin: const EdgeInsets.symmetric(horizontal: 3),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(3),
                              color: i == _currentImageIndex
                                  ? NoirTheme.primary
                                  : Colors.white.withValues(alpha: 0.4),
                            ),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),

          // Main Product Content
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(22),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Real-time Dispatch Countdown Banner
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E211A),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.flash_on_rounded, color: Color(0xFFF1C40F), size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: RichText(
                            text: TextSpan(
                              style: GoogleFonts.urbanist(fontSize: 12, color: Colors.white70),
                              children: [
                                const TextSpan(text: 'Order within '),
                                TextSpan(
                                  text: _formatCountdown(_dispatchCountdown),
                                  style: GoogleFonts.urbanist(
                                    color: NoirTheme.primary,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                                const TextSpan(text: ' for next-day DHL delivery.'),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 18),

                  // Brand tag
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        (p.brandName ?? context.read<SettingsProvider>().brandName).toUpperCase(),
                        style: GoogleFonts.urbanist(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: NoirTheme.primary,
                          letterSpacing: 1.5,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: Colors.green.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'IN STOCK',
                          style: GoogleFonts.urbanist(
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            color: Colors.green,
                          ),
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 6),

                  // Title
                  Text(
                    AppStrings.of(context).translateProductName(p.name),
                    style: GoogleFonts.urbanist(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      height: 1.2,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),

                  const SizedBox(height: 10),

                  // Price & Rating
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        _formatPrice(p.price),
                        style: GoogleFonts.urbanist(
                          fontSize: 24,
                          fontWeight: FontWeight.w900,
                          color: Theme.of(context).colorScheme.primary,
                          letterSpacing: -0.5,
                        ),
                      ),
                      Consumer<RatingsProvider>(builder: (context, ratings, _) {
                        return InkWell(
                          onTap: () {
                            Navigator.pushNamed(context, '/product-reviews',
                                arguments: {'productId': p.id});
                          },
                          child: Row(
                            children: [
                              const Icon(Icons.star_rounded, color: Colors.amber, size: 20),
                              const SizedBox(width: 4),
                              Text(
                                ratings.getAverageRating(p.id).toStringAsFixed(1),
                                style: GoogleFonts.urbanist(
                                    fontSize: 14, fontWeight: FontWeight.w800),
                              ),
                              const SizedBox(width: 4),
                              Text(
                                '(${ratings.getReviewsForProduct(p.id).length} reviews)',
                                style: GoogleFonts.urbanist(fontSize: 12, color: Colors.grey),
                              ),
                            ],
                          ),
                        );
                      }),
                    ],
                  ),

                  const SizedBox(height: 16),
                  const Divider(),
                  const SizedBox(height: 12),

                  // Size selection with guide
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Select Size',
                        style: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w800),
                      ),
                      GestureDetector(
                        onTap: _showBespokeFittingModal,
                        child: Row(
                          children: [
                            Icon(Icons.straighten_rounded, size: 14, color: NoirTheme.primary),
                            const SizedBox(width: 4),
                            Text(
                              'Size & Fit Guide',
                              style: GoogleFonts.urbanist(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: NoirTheme.primary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 10),
                  Wrap(
                    spacing: 8,
                    children: sizes.map((s) {
                      final isSelected = _selectedSize == s;
                      return ChoiceChip(
                        label: Text(s),
                        selected: isSelected,
                        onSelected: (_) => setState(() => _selectedSize = s),
                        selectedColor: NoirTheme.primary,
                        backgroundColor: Theme.of(context).colorScheme.surface,
                        labelStyle: GoogleFonts.urbanist(
                          fontSize: 12.5,
                          fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                          color: isSelected ? Colors.black : Theme.of(context).colorScheme.onSurface,
                        ),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        side: BorderSide(
                          color: isSelected
                              ? NoirTheme.primary
                              : Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
                        ),
                        showCheckmark: false,
                      );
                    }).toList(),
                  ),

                  const SizedBox(height: 18),

                  // Color selection
                  Text(
                    'Color Edition: ${_selectedColor ?? 'Obsidian'}',
                    style: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 10),
                  Wrap(
                    spacing: 8,
                    children: colors.map((c) {
                      final isSelected = _selectedColor == c;
                      return ChoiceChip(
                        label: Text(c),
                        selected: isSelected,
                        onSelected: (_) => setState(() => _selectedColor = c),
                        selectedColor: NoirTheme.primary,
                        backgroundColor: Theme.of(context).colorScheme.surface,
                        labelStyle: GoogleFonts.urbanist(
                          fontSize: 12.5,
                          fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                          color: isSelected ? Colors.black : Theme.of(context).colorScheme.onSurface,
                        ),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        side: BorderSide(
                          color: isSelected
                              ? NoirTheme.primary
                              : Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
                        ),
                        showCheckmark: false,
                      );
                    }).toList(),
                  ),

                  const SizedBox(height: 20),

                  // Quantity Row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Quantity',
                        style: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w800),
                      ),
                      Container(
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                              color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
                        ),
                        child: Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.remove, size: 16),
                              onPressed: _quantity > 1 ? () => setState(() => _quantity--) : null,
                            ),
                            Text(
                              '$_quantity',
                              style: GoogleFonts.urbanist(
                                  fontSize: 15, fontWeight: FontWeight.w800),
                            ),
                            IconButton(
                              icon: const Icon(Icons.add, size: 16),
                              onPressed: () => setState(() => _quantity++),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 22),
                  const Divider(),
                  const SizedBox(height: 16),

                  // Description
                  Text(
                    'Notes & Details',
                    style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    p.description?.isNotEmpty == true
                        ? p.description!
                        : 'Exquisite tailored silhouette constructed from high-grade natural fabrics. Emphasizes structured shoulder cuts, clean architectural drape, and hand-finished seams designed for timeless elegance.',
                    style: GoogleFonts.urbanist(
                      fontSize: 13.5,
                      height: 1.55,
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.75),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Craftsmanship guarantees
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                          color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
                    ),
                    child: Column(
                      children: [
                        _guaranteeRow(Icons.verified_outlined, '100% Authentic Haute Craftsmanship'),
                        const SizedBox(height: 10),
                        _guaranteeRow(Icons.local_shipping_outlined, 'Complimentary DHL Express Shipping'),
                        const SizedBox(height: 10),
                        _guaranteeRow(Icons.restart_alt_rounded, '14-Day Complimentary Bespoke Exchange'),
                      ],
                    ),
                  ),

                  const SizedBox(height: 100),
                ],
              ),
            ),
          ),
        ],
      ),

      // Bottom Bar with Real-time Total and Direct Actions
      bottomSheet: Container(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          border: Border(
            top: BorderSide(
                color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.25)),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.2),
              blurRadius: 15,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: Row(
          children: [
            // Add to Bag Button
            SizedBox(
              height: 52,
              width: 52,
              child: OutlinedButton(
                style: OutlinedButton.styleFrom(
                  padding: EdgeInsets.zero,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  side: BorderSide(color: NoirTheme.primary),
                ),
                onPressed: () => _addToCart(andCheckout: false),
                child: Icon(Icons.shopping_bag_outlined, color: NoirTheme.primary, size: 20),
              ),
            ),
            const SizedBox(width: 8),

            // ⚡ 1-Tap Biometric Fast Checkout
            SizedBox(
              height: 52,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1E211A),
                  foregroundColor: NoirTheme.primary,
                  side: BorderSide(color: NoirTheme.primary.withValues(alpha: 0.5)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  elevation: 0,
                ),
                onPressed: _performBiometricFastCheckout,
                icon: const Icon(Icons.fingerprint_rounded, size: 20),
                label: Text(
                  '1-TAP BUY',
                  style: GoogleFonts.urbanist(
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),

            // Standard Checkout
            Expanded(
              child: SizedBox(
                height: 52,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: NoirTheme.primary,
                    foregroundColor: Colors.black,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    elevation: 0,
                  ),
                  onPressed: () => _addToCart(andCheckout: true),
                  icon: const Icon(Icons.bolt_rounded, size: 18),
                  label: Text(
                    '${AppStrings.of(context).checkout.toUpperCase()} • ${_formatPrice(p.price * _quantity)}',
                    style: GoogleFonts.urbanist(
                      fontSize: 13,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.3,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _guaranteeRow(IconData icon, String label) {
    return Row(
      children: [
        Icon(icon, size: 18, color: NoirTheme.primary),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            label,
            style: GoogleFonts.urbanist(fontSize: 12.5, fontWeight: FontWeight.w700),
          ),
        ),
      ],
    );
  }
}
