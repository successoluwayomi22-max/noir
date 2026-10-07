import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import '../../providers/currency_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';

class OrderSuccessScreen extends StatelessWidget {
  final String orderNumber;
  final double total;
  final String currency;

  const OrderSuccessScreen({
    super.key,
    required this.orderNumber,
    required this.total,
    required this.currency,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: 28, vertical: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Spacer(),

              // Success animation circle
              TweenAnimationBuilder<double>(
                tween: Tween(begin: 0.0, end: 1.0),
                duration: Duration(milliseconds: 700),
                curve: Curves.elasticOut,
                builder: (context, value, child) => Transform.scale(
                  scale: value,
                  child: child,
                ),
                child: Container(
                  width: 100,
                  height: 100,
                  decoration: BoxDecoration(
                    color: Color(0xFF27ae60),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.check_rounded, color: Colors.white, size: 52),
                ),
              ),

              SizedBox(height: 36),
              Text(
                AppStrings.of(context).orderPlaced,
                style: GoogleFonts.urbanist(
                  fontSize: 36,
                  fontWeight: FontWeight.w900,
                  color: Theme.of(context).colorScheme.onSurface,
                  letterSpacing: -0.5,
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 12),
              Text(
                '${AppStrings.of(context).orderNumber}: $orderNumber — ${AppStrings.of(context).processing}',
                style: GoogleFonts.urbanist(
                  fontSize: 15,
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                  height: 1.6,
                ),
                textAlign: TextAlign.center,
              ),

              SizedBox(height: 40),

              // Order summary card
              Container(
                width: double.infinity,
                padding: EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Theme.of(context).colorScheme.outline),
                ),
                child: Column(
                  children: [
                    _infoRow(context, AppStrings.of(context).orderNumber, orderNumber),
                    SizedBox(height: 16),
                    _infoRow(context, AppStrings.of(context).total, context.read<CurrencyProvider>().format(total)),
                    SizedBox(height: 16),
                    _infoRow(context, AppStrings.of(context).deliveryStatus, AppStrings.of(context).processing),
                  ],
                ),
              ),

              SizedBox(height: 24),

              // Info steps
              Row(
                children: [
                  _stepChip(context, Icons.verified_outlined, AppStrings.of(context).paymentMethod),
                  SizedBox(width: 10),
                  _stepChip(context, Icons.inventory_2_outlined, AppStrings.of(context).processing),
                  SizedBox(width: 10),
                  _stepChip(context, Icons.local_shipping_outlined, AppStrings.of(context).shipped),
                ],
              ),

              Spacer(),

              // Actions
              SizedBox(
                width: double.infinity,
                height: 54,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: NoirTheme.primary,
                    foregroundColor: Colors.black,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  icon: const Icon(Icons.radar_rounded, size: 20),
                  onPressed: () {
                    Navigator.of(context).pushReplacementNamed('/order-tracking',
                        arguments: {'initialOrderNumber': orderNumber});
                  },
                  label: Text(AppStrings.of(context).trackOrder,
                      style: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w900, letterSpacing: 0.5)),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: OutlinedButton(
                  onPressed: () => Navigator.of(context).pushNamedAndRemoveUntil('/home', (_) => false),
                  child: Text(AppStrings.of(context).continueShopping,
                      style: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w700)),
                ),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }

  Widget _infoRow(BuildContext context, String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha:0.7))),
        Text(value, style: GoogleFonts.urbanist(fontSize: 14, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.onSurface)),
      ],
    );
  }

  Widget _stepChip(BuildContext context, IconData icon, String label) {
    return Expanded(
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 14, horizontal: 10),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Theme.of(context).colorScheme.outline),
        ),
        child: Column(
          children: [
            Icon(icon, size: 20, color: Theme.of(context).colorScheme.onSurface),
            SizedBox(height: 6),
            Text(label,
                style: GoogleFonts.urbanist(fontSize: 10, fontWeight: FontWeight.w600, height: 1.4),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
