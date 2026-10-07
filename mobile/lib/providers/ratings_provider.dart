import 'package:flutter/foundation.dart';

class Review {
  final String name;
  final int rating;
  final String text;
  final bool isVerifiedPurchase;

  Review({required this.name, required this.rating, required this.text, this.isVerifiedPurchase = true});
}

class RatingsProvider with ChangeNotifier {
  final Map<int, List<Review>> _reviewsByProduct = {};

  List<Review> getReviewsForProduct(int productId) {
    if (!_reviewsByProduct.containsKey(productId)) {
      // Default mock reviews for any product that doesn't have reviews yet
      _reviewsByProduct[productId] = [
        Review(name: 'Sarah M.', rating: 5, text: 'Absolutely love this product! The quality is amazing.'),
        Review(name: 'David K.', rating: 4, text: 'Great value for the price, but shipping took a while.'),
      ];
    }
    return _reviewsByProduct[productId]!;
  }

  double getAverageRating(int productId) {
    final reviews = getReviewsForProduct(productId);
    if (reviews.isEmpty) return 0.0;
    double sum = reviews.fold(0, (prev, element) => prev + element.rating);
    return sum / reviews.length;
  }

  void addReview(int productId, String name, int rating, String text) {
    final reviews = getReviewsForProduct(productId);
    reviews.insert(0, Review(name: name, rating: rating, text: text, isVerifiedPurchase: true));
    notifyListeners();
  }
}
