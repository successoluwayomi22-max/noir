import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';

class PaymentWebviewScreen extends StatefulWidget {
  final String url;

  const PaymentWebviewScreen({super.key, required this.url});

  @override
  State<PaymentWebviewScreen> createState() => _PaymentWebviewScreenState();
}

class _PaymentWebviewScreenState extends State<PaymentWebviewScreen> {
  WebViewController? _controller;
  bool _isLoading = true;
  bool _webviewAvailable = false;

  @override
  void initState() {
    super.initState();
    _initPaymentView();
  }

  void _initPaymentView() {
    final hasPlatform = !kIsWeb && WebViewPlatform.instance != null;
    if (hasPlatform) {
      try {
        _controller = WebViewController()
          ..setJavaScriptMode(JavaScriptMode.unrestricted)
          ..setNavigationDelegate(
            NavigationDelegate(
              onPageStarted: (String url) {
                if (url.contains('callback') || url.contains('success')) {
                  Navigator.pop(context, true);
                } else if (url.contains('cancel') || url.contains('failed')) {
                  Navigator.pop(context, false);
                }
              },
              onPageFinished: (String url) {
                if (mounted) setState(() => _isLoading = false);
              },
              onWebResourceError: (WebResourceError error) {
                if (mounted) setState(() => _isLoading = false);
              },
            ),
          )
          ..loadRequest(Uri.parse(widget.url));
        _webviewAvailable = true;
      } catch (_) {
        _webviewAvailable = false;
      }
    } else {
      _webviewAvailable = false;
    }

    if (!_webviewAvailable) {
      _launchExternalPayment();
    }
  }

  Future<void> _launchExternalPayment() async {
    final uri = Uri.parse(widget.url);
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}
    if (mounted) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'SECURE CHECKOUT',
          style: GoogleFonts.urbanist(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            letterSpacing: 2,
          ),
        ),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => Navigator.pop(context, false),
        ),
      ),
      body: _webviewAvailable && _controller != null
          ? Stack(
              children: [
                WebViewWidget(controller: _controller!),
                if (_isLoading) const Center(child: CircularProgressIndicator()),
              ],
            )
          : Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.payment_rounded, size: 64, color: Colors.white70),
                  const SizedBox(height: 20),
                  Text(
                    'Complete Payment in Browser',
                    style: GoogleFonts.urbanist(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Your secure payment gateway has been opened in your browser. Once completed, tap below to confirm.',
                    style: GoogleFonts.urbanist(fontSize: 14, color: Colors.grey),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context, true),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF687158),
                        foregroundColor: Colors.black,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text(
                        'I HAVE COMPLETED PAYMENT',
                        style: GoogleFonts.urbanist(fontWeight: FontWeight.w800),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextButton(
                    onPressed: _launchExternalPayment,
                    child: Text(
                      'Re-open Payment Link',
                      style: GoogleFonts.urbanist(color: Colors.white70),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
