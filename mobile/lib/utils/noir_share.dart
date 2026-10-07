import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:share_plus/share_plus.dart';
import '../theme.dart';

class NoirShare {
  static Future<void> share(
    BuildContext context, {
    required String text,
    String? subject,
    String? fallbackCopyText,
  }) async {
    try {
      final box = context.findRenderObject() as RenderBox?;
      final origin = box != null ? box.localToGlobal(Offset.zero) & box.size : null;

      await Share.share(
        text,
        subject: subject,
        sharePositionOrigin: origin,
      );
    } catch (e) {
      // Fallback: Copy to clipboard and notify user
      final copyContent = fallbackCopyText ?? text;
      await Clipboard.setData(ClipboardData(text: copyContent));
      if (context.mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.copy_rounded, color: Colors.white, size: 20),
                const SizedBox(width: 10),
                const Expanded(
                  child: Text(
                    'Link copied to clipboard! Ready to paste & share.',
                    style: TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
            backgroundColor: NoirTheme.primary,
            behavior: SnackBarBehavior.floating,
            duration: const Duration(seconds: 3),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        );
      }
    }
  }
}
