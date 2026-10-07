import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';

import '../../providers/cart_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/currency_provider.dart';
import '../../models/product.dart';
import '../../l10n/app_strings.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  String _formatPrice(BuildContext context, double price) {
    try {
      return context.watch<CurrencyProvider>().format(price);
    } catch (_) {
      return '₦${price.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}';
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartProvider>();
    final strings = AppStrings.of(context);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        centerTitle: false,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              strings.cart,
              style: GoogleFonts.urbanist(
                fontSize: 22,
                fontWeight: FontWeight.w900,
              ),
            ),
            if (!cart.isEmpty)
              Text(
                '${cart.itemCount} ${cart.itemCount == 1 ? "item" : "items"} in your bag',
                style: GoogleFonts.urbanist(
                  fontSize: 12,
                  color: Colors.grey,
                  fontWeight: FontWeight.w600,
                ),
              ),
          ],
        ),
      ),
      body: cart.isEmpty
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 104,
                    height: 104,
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      shape: BoxShape.circle,
                      border: Border.all(color: Theme.of(context).colorScheme.outline),
                    ),
                    child: Icon(
                      Icons.shopping_bag_outlined,
                      size: 48,
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                    ),
                  ),
                  SizedBox(height: 24),
                  Text(
                    strings.emptyBag,
                    style: GoogleFonts.urbanist(
                      fontSize: 20,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Items added to your bag will appear here.',
                    style: GoogleFonts.urbanist(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
                  ),
                  SizedBox(height: 32),
                  ElevatedButton(
                    onPressed: () => Navigator.pushNamedAndRemoveUntil(context, '/home', (_) => false),
                    child: Text(strings.continueShopping),
                  ),
                ],
              ),
            )
          : Column(
              children: [
                Expanded(
                  child: ListView.separated(
                    padding: EdgeInsets.all(20),
                    itemCount: cart.items.length,
                    separatorBuilder: (_, _) => Divider(height: 32),
                    itemBuilder: (context, index) {
                      final item = cart.items[index];
                      return Container(
                        padding: EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surface,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Theme.of(context).colorScheme.outline),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Image
                            ClipRRect(
                              borderRadius: BorderRadius.circular(16),
                              child: item.image != null && item.image!.isNotEmpty
                                  ? CachedNetworkImage(
                                      imageUrl: Product.fixImageUrl(item.image!),
                                      width: 86,
                                      height: 112,
                                      fit: BoxFit.cover,
                                      errorWidget: (_, _, _) => Container(
                                        width: 86,
                                        height: 112,
                                        color: Theme.of(context).colorScheme.outline,
                                      ),
                                    )
                                  : Container(
                                      width: 86,
                                      height: 112,
                                      color: Theme.of(context).colorScheme.outline,
                                    ),
                            ),
                            SizedBox(width: 16),

                            // Details
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Expanded(
                                        child: Text(
                                          item.name,
                                          style: GoogleFonts.urbanist(
                                            fontSize: 16,
                                            fontWeight: FontWeight.w700,
                                            height: 1.2,
                                          ),
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                      IconButton(
                                        padding: EdgeInsets.zero,
                                        constraints: BoxConstraints(),
                                        icon: Icon(
                                          Icons.close,
                                          size: 20,
                                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                                        ),
                                        onPressed: () =>
                                            cart.removeItem(item.productId),
                                      ),
                                    ],
                                  ),
                                  SizedBox(height: 8),
                                  Text(
                                    _formatPrice(context, item.price),
                                    style: GoogleFonts.urbanist(
                                      fontSize: 15,
                                      fontWeight: FontWeight.w900,
                                      color: Theme.of(context).colorScheme.primary,
                                    ),
                                  ),
                                  SizedBox(height: 12),

                                  // Quantity Control
                                  Container(
                                    height: 36,
                                    decoration: BoxDecoration(
                                      color: Theme.of(context).colorScheme.surfaceTint,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        IconButton(
                                          padding: EdgeInsets.zero,
                                          icon: Icon(
                                            Icons.remove,
                                            size: 16,
                                          ),
                                          onPressed: () => cart.updateQuantity(
                                            item.productId,
                                            item.quantity - 1,
                                          ),
                                        ),
                                        SizedBox(
                                          width: 30,
                                          child: Center(
                                            child: Text(
                                              '${item.quantity}',
                                              style: GoogleFonts.urbanist(
                                                fontSize: 14,
                                                fontWeight: FontWeight.w600,
                                              ),
                                            ),
                                          ),
                                        ),
                                        IconButton(
                                          padding: EdgeInsets.zero,
                                          icon: Icon(Icons.add, size: 16),
                                          onPressed: () => cart.updateQuantity(
                                            item.productId,
                                            item.quantity + 1,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),

                // Checkout Section
                Container(
                  padding: EdgeInsets.fromLTRB(24, 20, 24, 24),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.vertical(
                      top: Radius.circular(28),
                    ),
                    border: Border(
                      top: BorderSide(
                        color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.5),
                      ),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 24,
                        offset: Offset(0, -8),
                      ),
                    ],
                  ),
                  child: SafeArea(
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              strings.total,
                              style: GoogleFonts.urbanist(
                                fontSize: 16,
                                color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                              ),
                            ),
                            Text(
                              _formatPrice(context, cart.total),
                              style: GoogleFonts.urbanist(
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 20),
                        SizedBox(
                          width: double.infinity,
                          height: 56,
                          child: ElevatedButton(
                            onPressed: () {
                              if (context.read<AuthProvider>().isLoggedIn) {
                                Navigator.pushNamed(context, '/checkout');
                              } else {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(strings.signInToAccount)),
                                );
                                Navigator.pushNamed(context, '/login');
                              }
                            },
                            child: Text(
                              strings.checkout.toUpperCase(),
                              style: GoogleFonts.urbanist(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 1.0,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}
