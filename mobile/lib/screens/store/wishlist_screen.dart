import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../providers/cart_provider.dart';
import '../../providers/currency_provider.dart';
import '../../l10n/app_strings.dart';

import '../../models/product.dart';

class WishlistScreen extends StatelessWidget {
  const WishlistScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(AppStrings.of(context).wishlist, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold)),
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, child) {
          if (cart.wishlist.isEmpty) {
            return Center(
              child: Text(AppStrings.of(context).emptyWishlist, style: GoogleFonts.urbanist(fontSize: 18)),
            );
          }
          return ListView.builder(
            itemCount: cart.wishlist.length,
            itemBuilder: (context, index) {
              final productMap = cart.wishlist[index];
              final product = Product.fromJson(productMap);
              final priceFormatted = context.watch<CurrencyProvider>().format(product.price);
              
              return ListTile(
                leading: CachedNetworkImage(
                  imageUrl: product.featuredImage ?? "",
                  width: 50, 
                  height: 50, 
                  fit: BoxFit.cover,
                  errorWidget: (context, url, error) => Icon(Icons.error),
                ),
                title: Text(product.name, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold)),
                subtitle: Text(priceFormatted),
                trailing: IconButton(
                  icon: Icon(Icons.favorite, color: Colors.red),
                  onPressed: () {
                    cart.toggleWishlist(product.toJson());
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}
