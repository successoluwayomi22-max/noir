import 'product.dart';

/// A single line-item within an order.
class OrderItem {
  final int productId;
  final String name;
  final int quantity;
  final double price;
  final String? image;
  final String? size;
  final String? color;

  OrderItem({
    required this.productId,
    required this.name,
    required this.quantity,
    required this.price,
    this.image,
    this.size,
    this.color,
  });

  static String _fixImageUrl(String? path) {
    return Product.fixImageUrl(path);
  }

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      productId: int.tryParse(json['product_id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? json['product_name']?.toString() ?? '',
      quantity: int.tryParse(json['quantity']?.toString() ?? '') ?? 1,
      price: double.tryParse(json['price']?.toString() ?? '') ?? 0,
      image: _fixImageUrl(json['image']?.toString() ?? json['featured_image']?.toString()),
      size: json['size']?.toString(),
      color: json['color']?.toString(),
    );
  }

  String get formattedPrice {
    final val = (price * quantity).toStringAsFixed(0);
    final withCommas = val.replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]},',
    );
    return '₦$withCommas';
  }
}

/// Full order model.
class Order {
  final int id;
  final String orderNumber;
  final String status;
  final double total;
  final String currency;
  final DateTime createdAt;
  final List<OrderItem> items;
  final String? shippingName;
  final String? shippingAddress;
  final String? trackingNumber;
  final String? paymentMethod;

  Order({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.total,
    required this.currency,
    required this.createdAt,
    this.items = const [],
    this.shippingName,
    this.shippingAddress,
    this.trackingNumber,
    this.paymentMethod,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List<dynamic>? ?? [];
    DateTime parsed;
    try {
      parsed = json['created_at'] != null && json['created_at'].toString().isNotEmpty
          ? DateTime.parse(json['created_at'].toString())
          : DateTime.now();
    } catch (_) {
      parsed = DateTime.now();
    }
    return Order(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      orderNumber: json['order_number']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      total: double.tryParse(json['total']?.toString() ?? '') ?? 0,
      currency: json['currency']?.toString() ?? 'NGN',
      createdAt: parsed,
      items: rawItems
          .map((i) => OrderItem.fromJson(Map<String, dynamic>.from(i as Map)))
          .toList(),
      shippingName: json['shipping_name']?.toString() ??
          json['name']?.toString(),
      shippingAddress: () {
        if (json['shipping_address'] != null && json['shipping_address'].toString().trim().isNotEmpty) {
          return json['shipping_address'].toString();
        }
        final parts = [
          json['address']?.toString() ?? '',
          json['city']?.toString() ?? '',
          json['state']?.toString() ?? '',
        ].where((s) => s.trim().isNotEmpty).toList();
        return parts.isNotEmpty ? parts.join(', ') : null;
      }(),
      trackingNumber: json['tracking_number']?.toString(),
      paymentMethod: json['payment_method']?.toString(),
    );
  }

  String get formattedTotal {
    final symbols = {'NGN': '₦', 'USD': '\$', 'GBP': '£', 'EUR': '€'};
    final symbol = symbols[currency] ?? '₦';
    final val = total.toStringAsFixed(0);
    final withCommas = val.replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]},',
    );
    return '$symbol$withCommas';
  }

  String get formattedDate {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    return '${createdAt.day} ${months[createdAt.month - 1]} ${createdAt.year}';
  }

  String get statusLabel => status[0].toUpperCase() + status.substring(1);
}

/// Paginated orders result.
class OrdersResult {
  final List<Order> orders;
  final int page;
  final int totalPages;

  OrdersResult({
    required this.orders,
    required this.page,
    required this.totalPages,
  });

  factory OrdersResult.fromJson(Map<String, dynamic> json) {
    final rawOrders = json['orders'] as List<dynamic>? ?? [];
    final pagination = json['pagination'] as Map<String, dynamic>? ?? {};
    return OrdersResult(
      orders: rawOrders
          .map((o) => Order.fromJson(Map<String, dynamic>.from(o as Map)))
          .toList(),
      page: int.tryParse(pagination['page']?.toString() ?? '') ?? 1,
      totalPages: int.tryParse(pagination['pages']?.toString() ?? '') ?? 1,
    );
  }
}
