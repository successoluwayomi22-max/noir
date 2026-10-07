import 'package:firebase_analytics/firebase_analytics.dart';

class AnalyticsService {
  final FirebaseAnalytics _analytics = FirebaseAnalytics.instance;

  // Log when a user views a specific product
  Future<void> logViewItem({
    required String itemId,
    required String itemName,
    required double price,
  }) async {
    await _analytics.logViewItem(
      items: [
        AnalyticsEventItem(
          itemId: itemId,
          itemName: itemName,
          price: price,
        )
      ],
      value: price,
      currency: 'USD',
    );
  }

  // Log when a user adds something to their cart (helps find cart abandoners)
  Future<void> logAddToCart({
    required String itemId,
    required String itemName,
    required double price,
  }) async {
    await _analytics.logAddToCart(
      items: [
        AnalyticsEventItem(
          itemId: itemId,
          itemName: itemName,
          price: price,
        )
      ],
      value: price,
      currency: 'USD',
    );
  }

  // Log successful purchases to calculate revenue and track conversion rates
  Future<void> logPurchase({
    required String transactionId,
    required double value,
  }) async {
    await _analytics.logPurchase(
      transactionId: transactionId,
      value: value,
      currency: 'USD',
    );
  }
}
