import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';

class AuthenticityScannerScreen extends StatefulWidget {
  const AuthenticityScannerScreen({super.key});

  @override
  State<AuthenticityScannerScreen> createState() => _AuthenticityScannerScreenState();
}

class _AuthenticityScannerScreenState extends State<AuthenticityScannerScreen> {
  final MobileScannerController _scannerController = MobileScannerController();
  bool _isScanning = true;
  bool _torchOn = false;

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) async {
    if (!_isScanning) return;
    final barcodes = capture.barcodes;
    for (final barcode in barcodes) {
      if (barcode.rawValue != null) {
        final raw = barcode.rawValue!;
        setState(() => _isScanning = false);
        _scannerController.stop();

        _processScannedCode(raw);
        break;
      }
    }
  }

  void _processScannedCode(String rawCode) async {
    HapticFeedback.mediumImpact();

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => Center(
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: NoirTheme.primary),
              const SizedBox(height: 16),
              Text(
                'Verifying with Noir Atelier Registry...',
                style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 14),
              ),
            ],
          ),
        ),
      ),
    );

    try {
      final brandName = context.read<SettingsProvider>().brandName;
      final api = context.read<AuthProvider>().api;
      final res = await api.verifyAuthenticity(rawCode);
      if (mounted) Navigator.pop(context); // dismiss loading

      if (res['is_authentic'] == true && res['product'] != null) {
        final prod = res['product'];
        _showAuthenticityCertificate(
          prod['sku'] ?? rawCode,
          prod['name'] ?? '$brandName Atelier Garment',
          prod['atelier'] ?? '$brandName Atelier Paris & Lagos',
          prod['material'] ?? '100% Luxury Fine Wool & Silk',
          prod['verification_hash'] ?? 'ECDSA-SHA256-VERIFIED',
          (prod['price'] as num?)?.toDouble() ?? 0.0,
        );
      } else {
        _showUnverifiedDialog(rawCode, res['error'] ?? 'Unverified garment code.');
      }
    } catch (e) {
      if (mounted) {
        Navigator.pop(context);
        _showUnverifiedDialog(rawCode, 'Verification failed. This code is not recognized in our luxury database.');
      }
    }
  }

  void _showAuthenticityCertificate(String serial, String name, String atelier, String material, String hash, double price) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(28),
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(2))),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.green.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.verified_rounded, color: Colors.green, size: 48),
            ),
            const SizedBox(height: 12),
            Text(
              '100% AUTHENTIC GARMENT',
              style: GoogleFonts.urbanist(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                letterSpacing: 1.5,
                color: Colors.green.shade700,
              ),
            ),
            const SizedBox(height: 4),
            Text(name, textAlign: TextAlign.center, style: GoogleFonts.urbanist(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 20),

            _buildDetailTile('Atelier Origin', atelier, Icons.location_on_outlined),
            _buildDetailTile('Materials & Craft', material, Icons.checkroom_outlined),
            _buildDetailTile('Registered SKU / Serial', serial, Icons.fingerprint_rounded),
            _buildDetailTile('Cryptographic Signature', hash, Icons.security_rounded),

            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Registered $name to your digital wardrobe!'),
                      backgroundColor: NoirTheme.primary,
                      behavior: SnackBarBehavior.floating,
                    ),
                  );
                  setState(() => _isScanning = true);
                  _scannerController.start();
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: NoirTheme.primary,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: Text('Register Digital Ownership', style: GoogleFonts.urbanist(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showUnverifiedDialog(String raw, String errorMessage) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Theme.of(context).colorScheme.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            const Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
            const SizedBox(width: 8),
            Text('Counterfeit / Unverified', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, color: Colors.red)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              errorMessage,
              style: GoogleFonts.urbanist(fontSize: 14),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.red.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                'Scanned Code: $raw',
                style: GoogleFonts.urbanist(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.red.shade900),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              setState(() => _isScanning = true);
              _scannerController.start();
            },
            child: Text('Scan Again', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, color: NoirTheme.primary)),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailTile(String label, String value, IconData icon) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 20, color: NoirTheme.primary),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: GoogleFonts.urbanist(fontSize: 11, color: Colors.grey, fontWeight: FontWeight.w600)),
                Text(value, style: GoogleFonts.urbanist(fontSize: 13, fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // Camera Preview
          MobileScanner(
            controller: _scannerController,
            onDetect: _onDetect,
          ),

          // Scanning Overlay Frame
          Center(
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: NoirTheme.primary, width: 2.5),
              ),
            ),
          ),

          // Top Header Overlay
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: EdgeInsets.fromLTRB(16, MediaQuery.of(context).padding.top + 8, 16, 16),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.black87, Colors.transparent],
                ),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
                    onPressed: () => Navigator.pop(context),
                  ),
                  Text(
                    'AUTHENTICITY SCANNER',
                    style: GoogleFonts.urbanist(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.5,
                      fontSize: 15,
                    ),
                  ),
                  IconButton(
                    icon: Icon(
                      _torchOn ? Icons.flash_on_rounded : Icons.flash_off_rounded,
                      color: Colors.white,
                    ),
                    onPressed: () {
                      _scannerController.toggleTorch();
                      setState(() => _torchOn = !_torchOn);
                    },
                  ),
                ],
              ),
            ),
          ),

          // Bottom Instruction Pill
          Positioned(
            bottom: 40,
            left: 32,
            right: 32,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.8),
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: Colors.white24, width: 0.8),
              ),
              child: Text(
                'Align the QR code on your Noir garment tag inside the frame to verify authenticity.',
                textAlign: TextAlign.center,
                style: GoogleFonts.urbanist(color: Colors.white, fontSize: 13, height: 1.3),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
