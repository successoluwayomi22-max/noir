import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class CurrencyProvider with ChangeNotifier {
  String _currencyCode = 'NGN';
  String _currencySymbol = '₦';
  double _exchangeRate = 1.0;

  CurrencyProvider() {
    _loadFromPrefs();
  }

  String get currencyCode => _currencyCode;
  String get currencySymbol => _currencySymbol;
  double get exchangeRate => _exchangeRate;

  Future<void> _loadFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString('active_currency') ?? 'NGN';
    _applyRates(saved);
    notifyListeners();
  }

  void _applyRates(String code) {
    _currencyCode = code.toUpperCase();
    switch (_currencyCode) {
      case 'USD':
        _currencySymbol = '\$';
        _exchangeRate = 0.00075;
        break;
      case 'EUR':
        _currencySymbol = '€';
        _exchangeRate = 0.00063;
        break;
      case 'GBP':
        _currencySymbol = '£';
        _exchangeRate = 0.00054;
        break;
      case 'CAD':
        _currencySymbol = 'CA\$';
        _exchangeRate = 0.00100;
        break;
      case 'AUD':
        _currencySymbol = 'AU\$';
        _exchangeRate = 0.00102;
        break;
      case 'GHS':
        _currencySymbol = 'GH₵';
        _exchangeRate = 0.00827;
        break;
      case 'KES':
        _currencySymbol = 'KSh ';
        _exchangeRate = 0.0966;
        break;
      case 'ZAR':
        _currencySymbol = 'R ';
        _exchangeRate = 0.0118;
        break;
      default:
        _currencyCode = 'NGN';
        _currencySymbol = '₦';
        _exchangeRate = 1.0;
    }
  }

  double convert(double amount) {
    return amount * _exchangeRate;
  }

  String format(dynamic amount) {
    final val = double.tryParse(amount.toString()) ?? 0.0;
    final converted = val * _exchangeRate;
    if (_currencyCode == 'NGN') {
      return '₦${converted.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}';
    }
    return '$_currencySymbol${converted.toStringAsFixed(2)}';
  }

  Future<void> setCurrency(String code) async {
    _applyRates(code);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('active_currency', _currencyCode);
    notifyListeners();
  }
}
