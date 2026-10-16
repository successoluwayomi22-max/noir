import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../theme.dart';

enum ErrorStateType {
  offline,
  serverError,
  notFound,
  empty,
}

class ErrorStateView extends StatelessWidget {
  final ErrorStateType type;
  final String? title;
  final String? message;
  final String? actionLabel;
  final VoidCallback? onRetry;
  final VoidCallback? onSecondaryAction;
  final String? secondaryActionLabel;
  final bool isCompact;

  const ErrorStateView({
    super.key,
    required this.type,
    this.title,
    this.message,
    this.actionLabel,
    this.onRetry,
    this.onSecondaryAction,
    this.secondaryActionLabel,
    this.isCompact = false,
  });

  factory ErrorStateView.offline({
    VoidCallback? onRetry,
    VoidCallback? onSecondaryAction,
    String? secondaryActionLabel,
    String? message,
    bool isCompact = false,
  }) {
    return ErrorStateView(
      type: ErrorStateType.offline,
      title: 'Connection Lost',
      message: message ?? 'Unable to connect to Noir & Co. Please check your internet connection or server availability.',
      actionLabel: 'Retry',
      onRetry: onRetry,
      onSecondaryAction: onSecondaryAction,
      secondaryActionLabel: secondaryActionLabel,
      isCompact: isCompact,
    );
  }

  factory ErrorStateView.serverError({
    VoidCallback? onRetry,
    VoidCallback? onSecondaryAction,
    String? secondaryActionLabel,
    String? message,
    bool isCompact = false,
  }) {
    return ErrorStateView(
      type: ErrorStateType.serverError,
      title: 'Atelier Unavailable',
      message: message ?? 'Our services are experiencing a temporary disruption. We are working to restore access shortly.',
      actionLabel: 'Try Again',
      onRetry: onRetry,
      onSecondaryAction: onSecondaryAction,
      secondaryActionLabel: secondaryActionLabel,
      isCompact: isCompact,
    );
  }

  factory ErrorStateView.empty({
    String? title,
    String? message,
    String? actionLabel,
    VoidCallback? onAction,
    bool isCompact = false,
  }) {
    return ErrorStateView(
      type: ErrorStateType.empty,
      title: title ?? 'No Pieces Found',
      message: message ?? 'There are currently no items matching your criteria in this collection.',
      actionLabel: actionLabel,
      onRetry: onAction,
      isCompact: isCompact,
    );
  }

  IconData get _icon {
    switch (type) {
      case ErrorStateType.offline:
        return Icons.wifi_off_rounded;
      case ErrorStateType.serverError:
        return Icons.cloud_off_rounded;
      case ErrorStateType.notFound:
        return Icons.search_off_rounded;
      case ErrorStateType.empty:
        return Icons.shopping_bag_outlined;
    }
  }

  Color _accentColor(BuildContext context) {
    switch (type) {
      case ErrorStateType.offline:
        return const Color(0xFFD4A373); // Muted warm amber
      case ErrorStateType.serverError:
        return Theme.of(context).colorScheme.error;
      case ErrorStateType.notFound:
      case ErrorStateType.empty:
        return NoirTheme.primary;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final accent = _accentColor(context);
    final isDark = theme.brightness == Brightness.dark;

    if (isCompact) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: accent.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(_icon, color: accent, size: 28),
            ),
            const SizedBox(height: 12),
            Text(
              title ?? 'Something went wrong',
              style: GoogleFonts.urbanist(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: theme.colorScheme.onSurface,
              ),
              textAlign: TextAlign.center,
            ),
            if (message != null) ...[
              const SizedBox(height: 6),
              Text(
                message!,
                style: GoogleFonts.urbanist(
                  fontSize: 13,
                  color: theme.colorScheme.onSurface.withValues(alpha: 0.65),
                ),
                textAlign: TextAlign.center,
              ),
            ],
            if (onRetry != null) ...[
              const SizedBox(height: 14),
              ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded, size: 16),
                label: Text(
                  actionLabel ?? 'Retry',
                  style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
                ),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
              ),
            ],
          ],
        ),
      );
    }

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Icon Badge with layered glows
            Container(
              width: 96,
              height: 96,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: accent.withValues(alpha: 0.08),
                border: Border.all(
                  color: accent.withValues(alpha: 0.25),
                  width: 1.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: accent.withValues(alpha: 0.12),
                    blurRadius: 32,
                    spreadRadius: 4,
                  ),
                ],
              ),
              child: Icon(
                _icon,
                size: 42,
                color: accent,
              ),
            ),
            const SizedBox(height: 28),

            // Title
            Text(
              title ?? 'Something went wrong',
              style: GoogleFonts.urbanist(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: theme.colorScheme.onSurface,
                letterSpacing: 0.3,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 10),

            // Message
            if (message != null)
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 320),
                child: Text(
                  message!,
                  style: GoogleFonts.urbanist(
                    fontSize: 14,
                    height: 1.45,
                    color: theme.colorScheme.onSurface.withValues(alpha: 0.65),
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            const SizedBox(height: 32),

            // Primary Action
            if (onRetry != null)
              SizedBox(
                width: 200,
                height: 50,
                child: ElevatedButton.icon(
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: Text(
                    actionLabel ?? 'Retry',
                    style: GoogleFonts.urbanist(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 1.2,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: isDark ? const Color(0xFF232523) : NoirTheme.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                      side: BorderSide(color: accent.withValues(alpha: 0.4)),
                    ),
                  ),
                ),
              ),

            // Secondary Action
            if (onSecondaryAction != null) ...[
              const SizedBox(height: 12),
              TextButton(
                onPressed: onSecondaryAction,
                child: Text(
                  secondaryActionLabel ?? 'Go Back',
                  style: GoogleFonts.urbanist(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: theme.colorScheme.onSurface.withValues(alpha: 0.7),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
