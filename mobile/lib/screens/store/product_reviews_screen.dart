import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../providers/ratings_provider.dart';
import '../../l10n/app_strings.dart';

class ProductReviewsScreen extends StatelessWidget {
  final int productId;
  const ProductReviewsScreen({super.key, required this.productId});

  void _showAddReviewDialog(BuildContext context) {
    int selectedRating = 5;
    final TextEditingController textController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: Text(AppStrings.of(context).writeReview, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(5, (index) {
                  return IconButton(
                    icon: Icon(Icons.star, color: index < selectedRating ? Colors.amber : Colors.grey, size: 32),
                    onPressed: () => setState(() => selectedRating = index + 1),
                  );
                }),
              ),
              SizedBox(height: 16),
              TextField(
                controller: textController,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: AppStrings.of(context).description,
                  border: OutlineInputBorder(),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: Text(AppStrings.of(context).cancel)),
            ElevatedButton(
              onPressed: () {
                if (textController.text.isNotEmpty) {
                  Provider.of<RatingsProvider>(context, listen: false).addReview(productId, 'Verified Buyer', selectedRating, textController.text);
                  Navigator.pop(context);
                }
              },
              child: Text(AppStrings.of(context).submit),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(AppStrings.of(context).reviews, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold))),
      body: Consumer<RatingsProvider>(
        builder: (context, provider, child) {
          final avg = provider.getAverageRating(productId);
          final reviews = provider.getReviewsForProduct(productId);
          return Column(
            children: [
              Container(
                padding: EdgeInsets.all(24),
                color: Colors.grey.shade100,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(avg.toStringAsFixed(1), style: GoogleFonts.urbanist(fontSize: 48, fontWeight: FontWeight.bold)),
                    SizedBox(width: 16),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: List.generate(5, (index) => Icon(Icons.star, size: 20, color: index < avg.round() ? Colors.amber : Colors.grey))),
                        Text('${reviews.length} ${AppStrings.of(context).reviews}', style: GoogleFonts.urbanist(color: Colors.grey.shade700)),
                      ],
                    ),
                  ],
                ),
              ),
              Expanded(
                child: ListView.builder(
                  padding: EdgeInsets.all(16),
                  itemCount: reviews.length,
                  itemBuilder: (context, index) {
                    final review = reviews[index];
                    return _buildReviewCard(review);
                  },
                ),
              ),
            ],
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddReviewDialog(context),
        icon: Icon(Icons.edit),
        label: Text(AppStrings.of(context).writeReview),
      ),
    );
  }

  Widget _buildReviewCard(Review review) {
    return Card(
      margin: EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(review.name, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold)),
                SizedBox(width: 8),
                if (review.isVerifiedPurchase)
                  Container(
                    padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(4)),
                    child: Text('Verified', style: TextStyle(color: Colors.green, fontSize: 10, fontWeight: FontWeight.bold)),
                  ),
                Spacer(),
                Row(children: List.generate(5, (index) => Icon(Icons.star, size: 16, color: index < review.rating ? Colors.amber : Colors.grey))),
              ],
            ),
            SizedBox(height: 8),
            Text(review.text, style: GoogleFonts.urbanist()),
          ],
        ),
      ),
    );
  }
}
