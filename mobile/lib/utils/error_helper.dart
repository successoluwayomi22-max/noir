import 'dart:async';
import 'dart:io';

/// Converts raw technical exceptions into clear, modern, user-friendly messages.
class ErrorHelper {
  static String format(dynamic error, {String fallback = 'Something went wrong. Please try again.'}) {
    if (error == null) return fallback;

    final str = error.toString().trim();

    // Clean up Exception: or Error: prefixes
    var clean = str;
    if (clean.startsWith('Exception: ')) {
      clean = clean.substring(11).trim();
    }
    if (clean.startsWith('Error: ')) {
      clean = clean.substring(7).trim();
    }

    // Network & Connection errors
    if (error is SocketException ||
        clean.contains('SocketException') ||
        clean.contains('Failed host lookup') ||
        clean.contains('Connection refused') ||
        clean.contains('Network is unreachable')) {
      return 'No internet connection. Please check your network and try again.';
    }

    if (error is TimeoutException || clean.contains('TimeoutException') || clean.contains('timed out')) {
      return 'The request took too long to complete. Please try again.';
    }

    if (error is HttpException || clean.contains('HttpException')) {
      return 'Unable to reach the server. Please check your connection and try again.';
    }

    // JSON Format / parsing errors
    if (error is FormatException || clean.contains('FormatException') || clean.contains('Unexpected character')) {
      return 'Unable to process the server response. Please try again in a moment.';
    }

    // Authentication / Session
    if (clean.contains('Unauthorized') || clean.contains('401') || clean.contains('Unauthenticated') || clean.contains('Token expired')) {
      return 'Your session has expired. Please sign in to continue.';
    }

    if (clean.contains('Forbidden') || clean.contains('403')) {
      return 'You do not have permission to perform this action.';
    }

    if (clean.contains('404') || clean.contains('Not Found')) {
      return 'The requested item could not be found.';
    }

    if (clean.contains('500') || clean.contains('Internal Server Error')) {
      return 'The service is temporarily unavailable. Please try again shortly.';
    }

    // Code & Runtime errors
    if (clean.contains('NoSuchMethodError') ||
        clean.contains('Null check operator') ||
        clean.contains('TypeError') ||
        clean.contains('RangeError') ||
        clean.contains('setState() called after dispose()')) {
      return fallback;
    }

    // If it is already a clean readable sentence, return it
    if (clean.isNotEmpty && !clean.contains('Exception') && !clean.contains('Stack trace:') && clean.length < 150) {
      return clean;
    }

    return fallback;
  }
}
