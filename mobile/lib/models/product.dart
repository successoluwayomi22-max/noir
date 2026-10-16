import 'package:flutter_dotenv/flutter_dotenv.dart';

/// Typed model for a product returned from the API.
class Product {
  final int id;
  final String name;
  final double price;
  final double? comparePrice;
  final String? featuredImage;
  final String? category;
  final String? description;
  final int stock;
  final String? slug;
  final bool inStock;
  final String? brandName;

  static String fixImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http')) return path;
    
    final baseUrl = dotenv.env['API_BASE_URL'] ?? 'https://noir-73fy.onrender.com';
    final uri = Uri.tryParse(baseUrl);
    final origin = uri != null ? '${uri.scheme}://${uri.host}${uri.hasPort ? ':${uri.port}' : ''}' : 'https://noir-73fy.onrender.com';

    if (path.startsWith('/noir/public/')) {
      return '$origin$path';
    }
    if (path.startsWith('noir/public/')) {
      return '$origin/$path';
    }
    if (path.startsWith('/noir/')) {
      return '$origin$path';
    }
    if (path.startsWith('/')) path = path.substring(1);
    return '$baseUrl/$path';
  }

  Product({
    required this.id,
    required this.name,
    required this.price,
    this.comparePrice,
    this.featuredImage,
    this.category,
    this.description,
    this.stock = 0,
    this.slug,
    this.inStock = true,
    this.brandName,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? '',
      price: double.tryParse(json['price']?.toString() ?? '') ?? 0,
      comparePrice: json['compare_price'] != null
          ? double.tryParse(json['compare_price'].toString())
          : null,
      featuredImage: fixImageUrl(json['featured_image']?.toString()),
      category: json['category']?.toString(),
      description: json['description']?.toString(),
      stock: int.tryParse(json['stock']?.toString() ?? '') ?? 0,
      slug: json['slug']?.toString(),
      inStock: (json['in_stock'] ?? true) == true ||
          (json['in_stock']?.toString() == '1'),
      brandName: json['brand_name']?.toString() ?? json['brand']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'price': price,
        'compare_price': comparePrice,
        'featured_image': featuredImage,
        'category': category,
        'description': description,
        'stock': stock,
        'slug': slug,
        'in_stock': inStock,
        'brand_name': brandName,
      };

  /// Formatted price in Naira (or other symbol if needed).
  String get formattedPrice {
    final val = price.toStringAsFixed(0);
    final withCommas = val.replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]},',
    );
    return '₦$withCommas';
  }

  String get formattedComparePrice {
    if (comparePrice == null) return '';
    final val = comparePrice!.toStringAsFixed(0);
    final withCommas = val.replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]},',
    );
    return '₦$withCommas';
  }

  bool get hasDiscount =>
      comparePrice != null && comparePrice! > price;
}

/// Pagination metadata returned alongside product lists.
class ProductsResult {
  final List<Product> products;
  final int page;
  final int totalPages;
  final int total;

  ProductsResult({
    required this.products,
    required this.page,
    required this.totalPages,
    required this.total,
  });

  factory ProductsResult.fromJson(Map<String, dynamic> json) {
    final rawProducts = json['products'] as List<dynamic>? ?? [];
    final pagination = json['pagination'] as Map<String, dynamic>? ?? {};
    return ProductsResult(
      products: rawProducts
          .map((p) => Product.fromJson(Map<String, dynamic>.from(p as Map)))
          .toList(),
      page: int.tryParse(pagination['page']?.toString() ?? '') ?? 1,
      totalPages: int.tryParse(pagination['pages']?.toString() ?? '') ?? 1,
      total: int.tryParse(pagination['total']?.toString() ?? '') ?? 0,
    );
  }
}

class ProductDetailResult {
  final Product product;
  final List<dynamic> images;
  final List<dynamic> variants;

  ProductDetailResult({
    required this.product,
    required this.images,
    required this.variants,
  });

  factory ProductDetailResult.fromJson(Map<String, dynamic> json) {
    return ProductDetailResult(
      product: Product.fromJson(json['product'] ?? {}),
      images: json['images'] as List<dynamic>? ?? [],
      variants: json['variants'] as List<dynamic>? ?? [],
    );
  }
}
