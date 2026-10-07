import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api_client.dart';
import '../models/product.dart';

class WishlistProvider with ChangeNotifier {
  List<Product> _items = [];
  NoirApiClient? _api;

  WishlistProvider([this._api]) {
    _loadFromPrefs();
  }

  void updateApi(NoirApiClient api) {
    _api = api;
    if (api.isAuthenticated) {
      syncWithCloud();
    }
  }

  List<Product> get items => _items;

  bool isInWishlist(int productId) {
    return _items.any((item) => item.id == productId);
  }

  void toggleWishlist(Product product) {
    final willSave = !isInWishlist(product.id);
    if (willSave) {
      _items.add(product);
    } else {
      _items.removeWhere((item) => item.id == product.id);
    }
    notifyListeners();
    _saveToPrefs();

    if (_api != null && _api!.isAuthenticated) {
      _api!.toggleWishlist(product.id).catchError((_) => false);
    }
  }

  Future<void> syncWithCloud() async {
    if (_api == null || !_api!.isAuthenticated) return;
    try {
      final remoteList = await _api!.getWishlist();
      if (remoteList.isNotEmpty) {
        final List<Product> cloudProducts = remoteList.map((e) => Product.fromJson(e)).toList();
        final Map<int, Product> map = {};
        for (final p in cloudProducts) {
          map[p.id] = p;
        }
        for (final p in _items) {
          map[p.id] = p;
        }
        _items = map.values.toList();
        notifyListeners();
        await _saveToPrefs();
      } else if (_items.isNotEmpty) {
        await _api!.syncWishlist(_items.map((e) => e.id).toList());
      }
    } catch (_) {}
  }

  Future<void> _loadFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonStr = prefs.getString('user_wishlist_items');
      if (jsonStr != null) {
        final List<dynamic> list = jsonDecode(jsonStr);
        _items = list.map((e) => Product.fromJson(e)).toList();
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> _saveToPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final list = _items.map((e) => e.toJson()).toList();
      await prefs.setString('user_wishlist_items', jsonEncode(list));
    } catch (_) {}
  }
}
