import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class CartItem {
  final int productId;
  final String name;
  final double price;
  final String? image;
  int quantity;

  CartItem({
    required this.productId,
    required this.name,
    required this.price,
    this.image,
    this.quantity = 1,
  });

  Map<String, dynamic> toJson() => {
    'id': productId,
    'name': name,
    'price': price,
    'image': image,
    'quantity': quantity,
  };

  factory CartItem.fromJson(Map<String, dynamic> json) => CartItem(
    productId: json['id'] ?? 0,
    name: json['name'] ?? '',
    price: (json['price'] ?? 0).toDouble(),
    image: json['image'],
    quantity: json['quantity'] ?? 1,
  );
}

class CartProvider extends ChangeNotifier {
  List<CartItem> _items = [];
  String _userContext = 'guest';
  dynamic _api;

  void updateApi(dynamic api) {
    _api = api;
    if (api != null && api.isAuthenticated) {
      syncWithCloud();
    }
  }

  List<CartItem> get items => _items;
  int get itemCount => _items.fold(0, (sum, item) => sum + item.quantity);
  double get total =>
      _items.fold(0.0, (sum, item) => sum + (item.price * item.quantity));
  bool get isEmpty => _items.isEmpty;

  Future<void> init([String? userId]) async {
    _userContext = (userId != null && userId.isNotEmpty) ? userId : 'guest';
    final prefs = await SharedPreferences.getInstance();
    final cartJson = prefs.getString('cart_items_$_userContext');
    if (cartJson != null) {
      try {
        final List<dynamic> decoded = jsonDecode(cartJson);
        _items = decoded.map((e) => CartItem.fromJson(e)).toList();
      } catch (_) {
        _items = [];
      }
    } else {
      _items = [];
    }
    await _loadWishlist();
    notifyListeners();

    if (_api != null && _api.isAuthenticated) {
      syncWithCloud();
    }
  }

  Future<void> switchUser(String? userId) async {
    final newContext = (userId != null && userId.isNotEmpty) ? userId : 'guest';
    if (_userContext != newContext) {
      final prefs = await SharedPreferences.getInstance();
      
      // If transitioning from guest to logged-in user, merge guest items
      if (_userContext == 'guest' && newContext != 'guest' && _items.isNotEmpty) {
        final guestItems = List<CartItem>.from(_items);
        final userCartJson = prefs.getString('cart_items_$newContext');
        List<CartItem> userItems = [];
        if (userCartJson != null) {
          try {
            final List<dynamic> decoded = jsonDecode(userCartJson);
            userItems = decoded.map((e) => CartItem.fromJson(e)).toList();
          } catch (_) {}
        }
        
        // Merge guest items into user items
        for (final guestItem in guestItems) {
          final existingIdx = userItems.indexWhere((i) => i.productId == guestItem.productId);
          if (existingIdx >= 0) {
            userItems[existingIdx].quantity += guestItem.quantity;
          } else {
            userItems.add(guestItem);
          }
        }
        
        _userContext = newContext;
        _items = userItems;
        await _persist();
        await prefs.remove('cart_items_guest');
        notifyListeners();

        if (_api != null && _api.isAuthenticated) {
          syncWithCloud();
        }
        return;
      }

      await init(newContext);
    }
  }

  Future<void> syncWithCloud() async {
    if (_api == null || !_api.isAuthenticated) return;
    try {
      final remoteList = await _api.getRemoteCart();
      if (remoteList.isNotEmpty) {
        final List<CartItem> cloudItems = remoteList.map<CartItem>((e) => CartItem.fromJson(e)).toList();
        final Map<int, CartItem> map = {};
        for (final item in cloudItems) {
          map[item.productId] = item;
        }
        for (final item in _items) {
          if (map.containsKey(item.productId)) {
            map[item.productId]!.quantity = map[item.productId]!.quantity > item.quantity ? map[item.productId]!.quantity : item.quantity;
          } else {
            map[item.productId] = item;
          }
        }
        _items = map.values.toList();
        notifyListeners();
        await _persist();
      } else if (_items.isNotEmpty) {
        await _api.syncCart(_items.map((e) => e.toJson()).toList());
      }
    } catch (_) {}
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      'cart_items_$_userContext',
      jsonEncode(_items.map((e) => e.toJson()).toList()),
    );
    if (_api != null && _api.isAuthenticated) {
      _api.syncCart(_items.map((e) => e.toJson()).toList()).catchError((_) {});
    }
  }

  void addItem(CartItem item) {
    final existing = _items.indexWhere((i) => i.productId == item.productId);
    if (existing >= 0) {
      _items[existing].quantity += item.quantity;
    } else {
      _items.add(item);
    }
    notifyListeners();
    _persist();
  }

  void removeItem(int productId) {
    _items.removeWhere((i) => i.productId == productId);
    notifyListeners();
    _persist();
  }

  void updateQuantity(int productId, int quantity) {
    final idx = _items.indexWhere((i) => i.productId == productId);
    if (idx >= 0) {
      if (quantity <= 0) {
        _items.removeAt(idx);
      } else {
        _items[idx].quantity = quantity;
      }
      notifyListeners();
      _persist();
    }
  }

  void clear() {
    _items.clear();
    notifyListeners();
    _persist();
    if (_api != null && _api.isAuthenticated) {
      _api.clearRemoteCart().catchError((_) {});
    }
  }

  List<Map<String, dynamic>> toApiFormat() =>
      _items.map((i) => {'id': i.productId, 'quantity': i.quantity}).toList();

  // ─── WISHLIST ─────────────────────────────────────────────────────────────
  List<Map<String, dynamic>> _wishlist = [];
  List<Map<String, dynamic>> get wishlist => _wishlist;
  bool isWishlisted(int productId) => _wishlist.any((p) => p['id'] == productId);

  Future<void> _loadWishlist() async {
    final prefs = await SharedPreferences.getInstance();
    final listJson = prefs.getString('wishlist_items_$_userContext');
    if (listJson != null) {
      try {
        final List<dynamic> decoded = jsonDecode(listJson);
        _wishlist = decoded.cast<Map<String, dynamic>>();
      } catch (_) {
        _wishlist = [];
      }
    } else {
      _wishlist = [];
    }
  }

  Future<void> _saveWishlist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('wishlist_items_$_userContext', jsonEncode(_wishlist));
  }

  void toggleWishlist(Map<String, dynamic> product) {
    final id = product['id'];
    final idx = _wishlist.indexWhere((p) => p['id'] == id);
    if (idx >= 0) {
      _wishlist.removeAt(idx);
    } else {
      _wishlist.add(product);
    }
    notifyListeners();
    _saveWishlist();
  }

  void removeFromWishlist(int productId) {
    _wishlist.removeWhere((p) => p['id'] == productId);
    notifyListeners();
    _saveWishlist();
  }

  void clearWishlist() {
    _wishlist.clear();
    notifyListeners();
    _saveWishlist();
  }
}
