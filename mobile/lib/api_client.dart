import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'models/product.dart';
import 'models/order.dart';

class NoirApiClient {
  String baseUrl;
  String? _token;

  NoirApiClient({required this.baseUrl, this._token});

  void setBaseUrl(String url) => baseUrl = url;
  void setToken(String token) => _token = token;
  void clearToken() => _token = null;
  String? get token => _token;
  bool get isAuthenticated => _token != null && _token!.isNotEmpty;

  Map<String, String> _headers({bool auth = false}) {
    final h = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (auth && _token != null) h['Authorization'] = 'Bearer $_token';
    return h;
  }

  dynamic _parseResponse(http.Response res) {
    if (res.body.trim().isEmpty) {
      return <String, dynamic>{'success': res.statusCode >= 200 && res.statusCode < 300};
    }
    try {
      return jsonDecode(res.body);
    } catch (_) {
      if (res.statusCode >= 200 && res.statusCode < 300) {
        return <String, dynamic>{'success': true, 'message': 'Operation successful'};
      }
      throw Exception('Unable to process the server response. Please try again.');
    }
  }

  // AUTH
  Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/login'),
      headers: _headers(),
      body: jsonEncode({'email': email, 'password': password}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) {
      _token = data['token'];
      return data;
    }
    throw Exception(data['error'] ?? 'Login failed');
  }

  Future<Map<String, dynamic>> loginWithGoogle(String idToken) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/google'),
      headers: _headers(),
      body: jsonEncode({'id_token': idToken}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) {
      _token = data['token'];
      return data;
    }
    throw Exception(data['error'] ?? 'Google Login failed');
  }

  Future<Map<String, dynamic>> register(
    String name,
    String email,
    String password,
  ) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/register'),
      headers: _headers(),
      body: jsonEncode({'name': name, 'email': email, 'password': password}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 201 && data['success'] == true) {
      _token = data['token'];
      return data;
    }
    throw Exception(data['error'] ?? 'Registration failed');
  }

  Future<Map<String, dynamic>> getMe() async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/auth/me'),
      headers: _headers(auth: true),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to get profile');
  }

  Future<Map<String, dynamic>> updateProfile({required String name, String? phone}) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/user/profile'),
      headers: _headers(auth: true),
      body: jsonEncode({'name': name, 'phone': ?phone}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Failed to update profile');
  }

  // ── Password Management & OTP ──
  Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/change-password'),
      headers: _headers(auth: true),
      body: jsonEncode({
        'current_password': currentPassword,
        'new_password': newPassword,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Failed to change password');
  }

  Future<Map<String, dynamic>> requestPasswordOtp() async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/request-password-otp'),
      headers: _headers(auth: true),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Failed to request OTP');
  }

  Future<Map<String, dynamic>> changePasswordWithOtp({
    required String otp,
    required String newPassword,
  }) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/change-password-with-otp'),
      headers: _headers(auth: true),
      body: jsonEncode({
        'otp': otp,
        'new_password': newPassword,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Failed to change password with OTP');
  }

  Future<Map<String, dynamic>> forgotPassword(String email) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/forgot-password'),
      headers: _headers(),
      body: jsonEncode({'email': email}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Failed to send OTP');
  }

  Future<Map<String, dynamic>> verifyOtp(String email, String otp) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/verify-otp'),
      headers: _headers(),
      body: jsonEncode({'email': email, 'otp': otp}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Invalid OTP code');
  }

  Future<Map<String, dynamic>> resetPassword(String email, String otp, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/auth/reset-password'),
      headers: _headers(),
      body: jsonEncode({
        'email': email,
        'otp': otp,
        'password': password,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Failed to reset password');
  }

  Future<String> uploadProfileImage(String base64Image) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/user/profile-image'),
      headers: _headers(auth: true),
      body: jsonEncode({'image': base64Image}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) {
      return data['profile_image']?.toString() ?? '';
    }
    throw Exception(data['error'] ?? 'Failed to upload image');
  }

  Future<void> deleteProfileImage() async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/user/profile-image/delete'),
      headers: _headers(auth: true),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return;
    throw Exception(data['error'] ?? 'Failed to delete photo');
  }

  // STORE
  Future<ProductsResult> getProducts({
    String? search,
    int? categoryId,
    int page = 1,
    String sort = 'newest',
  }) async {
    final params = <String, String>{'page': page.toString(), 'sort': sort};
    if (search != null && search.isNotEmpty) params['search'] = search;
    if (categoryId != null && categoryId > 0) {
      params['category'] = categoryId.toString();
    }
    final uri = Uri.parse('$baseUrl/api/v1/mobile/store/products')
        .replace(queryParameters: params);
    
    try {
      final res = await http.get(uri, headers: _headers());
      final data = jsonDecode(res.body);
      if (res.statusCode == 200) {
        if (page == 1 && (search == null || search.isEmpty) && (categoryId == null || categoryId == 0)) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('cached_products_$sort', res.body);
        }
        return ProductsResult.fromJson(data);
      }
      throw Exception(data['error'] ?? 'Failed to fetch products');
    } catch (e) {
      if (page == 1 && (search == null || search.isEmpty) && (categoryId == null || categoryId == 0)) {
        final prefs = await SharedPreferences.getInstance();
        final cached = prefs.getString('cached_products_$sort');
        if (cached != null) {
          return ProductsResult.fromJson(jsonDecode(cached));
        }
      }
      rethrow;
    }
  }

  Future<List<dynamic>> getCategories() async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/store/categories'),
      headers: _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data['categories'] ?? [];
    throw Exception(data['error'] ?? 'Failed to fetch categories');
  }

  Future<ProductDetailResult> getProductDetail(int id) async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/store/products/$id'),
      headers: _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) {
       return ProductDetailResult.fromJson(data);
    }
    throw Exception(data['error'] ?? 'Product not found');
  }

  Future<Map<String, dynamic>> getSettings() async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/store/settings'),
      headers: _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to load settings');
  }

  Future<List<dynamic>> getVideoCommerce() async {
    final res = await http.get(Uri.parse('$baseUrl/api/v1/mobile/store/video-commerce'));
    if (res.statusCode == 200) {
      final data = jsonDecode(res.body);
      return data['videos'] ?? [];
    }
    return [];
  }

  Future<List<dynamic>> getCommunityFeed() async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/store/community-feed'),
      headers: _headers(auth: true)
    );
    if (res.statusCode == 200) {
      final data = jsonDecode(res.body);
      return data['posts'] ?? [];
    }
    return [];
  }

  Future<Map<String, dynamic>> toggleCommunityPostLike(int postId) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/store/community-feed/$postId/like'),
      headers: _headers(auth: true),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) {
      return data;
    }
    throw Exception(data['error'] ?? 'Failed to toggle like');
  }

  Future<List<dynamic>> getCommunityPostComments(int postId) async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/store/community-feed/$postId/comments'),
      headers: _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) {
      return data['comments'] ?? [];
    }
    throw Exception(data['error'] ?? 'Failed to fetch comments');
  }

  Future<Map<String, dynamic>> addCommunityPostComment(int postId, String content) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/store/community-feed/$postId/comments'),
      headers: _headers(auth: true),
      body: jsonEncode({'content': content}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) {
      return data;
    }
    throw Exception(data['error'] ?? 'Failed to add comment');
  }

  // CHECKOUT
  Future<Map<String, dynamic>> checkout({
    required List<Map<String, dynamic>> items,
    required Map<String, dynamic> billing,
    String? callbackUrl,
  }) async {
    final payload = <String, dynamic>{
      'items': items,
      'name': billing['name'],
      'email': billing['email'],
      'phone': billing['phone'],
      'address': billing['address'],
      'city': billing['city'],
      'state': billing['state'],
      'payment_method': billing['payment_method'] ?? 'paystack',
      'billing': billing,
    };
    if (callbackUrl != null) payload['callback_url'] = callbackUrl;
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/checkout'),
      headers: _headers(auth: true),
      body: jsonEncode(payload),
    );
    try {
      final data = jsonDecode(res.body);
      if (res.statusCode == 200 && data['success'] == true) return data;
      throw Exception(data['error'] ?? data['message'] ?? 'Checkout failed (${res.statusCode})');
    } catch (e) {
      if (e is FormatException) {
        throw Exception('Server error: Could not complete checkout. Please try again.');
      }
      rethrow;
    }
  }
  // ORDERS
  Future<OrdersResult> getOrders({int page = 1}) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/orders')
        .replace(queryParameters: {'page': page.toString()});
    final res = await http.get(uri, headers: _headers(auth: true));
    try {
      final data = jsonDecode(res.body);
      if (res.statusCode == 200) return OrdersResult.fromJson(data);
      throw Exception(data['error'] ?? 'Failed to fetch orders');
    } catch (e) {
      if (e is FormatException) {
         throw Exception('Server error: Could not load orders. Please try again later.');
      }
      rethrow;
    }
  }

  Future<Order> getOrderDetail(String orderNumber) async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/orders/$orderNumber'),
      headers: _headers(auth: true),
    );
    try {
      final data = jsonDecode(res.body);
      if (res.statusCode == 200) {
        final oData = data['order'] as Map<String, dynamic>? ?? {};
        final iData = data['items'] as List<dynamic>? ?? [];
        oData['items'] = iData;
        return Order.fromJson(oData);
      }
      throw Exception(data['error'] ?? 'Order not found');
    } catch (e) {
      if (e is FormatException) {
         throw Exception('Server error: Could not load order details. Please try again later.');
      }
      rethrow;
    }
  }



  /// Generic authenticated POST helper.
  Future<Map<String, dynamic>> post(
    String path,
    Map<String, dynamic> body, {
    String? token,
  }) async {
    final tok = token ?? _token;
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (tok != null && tok.isNotEmpty) headers['Authorization'] = 'Bearer $tok';
    final res = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: headers,
      body: jsonEncode(body),
    );
    if (res.body.isEmpty) return {'success': res.statusCode < 300};
    final data = jsonDecode(res.body);
    if (res.statusCode < 300) return data is Map<String, dynamic> ? data : {'success': true, 'data': data};
    throw Exception((data is Map ? data['error'] ?? data['message'] : null) ?? 'Request failed (${res.statusCode})');
  }

  /// Save a shipping address for the logged-in customer.
  Future<Map<String, dynamic>> addAddress(Map<String, String> address) async {
    return post('/api/v1/mobile/addresses', address);
  }

  // ─── PERKS & REWARDS ──────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getLoyaltyDetails() async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/profile/loyalty'),
      headers: _headers(auth: true),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to fetch loyalty details');
  }

  Future<Map<String, dynamic>> redeemLoyaltyReward(int cost, String reward) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/profile/loyalty/redeem'),
      headers: _headers(auth: true),
      body: jsonEncode({'cost': cost, 'reward': reward}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) return data;
    throw Exception(data['error'] ?? 'Failed to redeem reward');
  }

  // ─── DISCOVER ─────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getAiStyling(String prompt, {List<Map<String, String>>? history, String? sessionId}) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/discover/ai-stylist'),
      headers: _headers(auth: true),
      body: jsonEncode({
        'prompt': prompt,
        'history': history ?? [],
        'session_id': sessionId,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'AI styling failed');
  }

  Future<bool> sendStylistFeedback(String action, {int? productId, Map<String, dynamic>? metadata}) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/api/v1/mobile/discover/stylist/feedback'),
        headers: _headers(auth: true),
        body: jsonEncode({
          'action': action,
          'product_id': productId,
          'metadata': metadata ?? {},
        }),
      );
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  Future<Map<String, dynamic>> saveAiOutfit({
    required String title,
    required String occasion,
    required double totalPrice,
    required List<int> productIds,
    String? notes,
  }) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/discover/stylist/save-outfit'),
      headers: _headers(auth: true),
      body: jsonEncode({
        'title': title,
        'occasion': occasion,
        'total_price': totalPrice,
        'product_ids': productIds,
        'notes': notes ?? '',
      }),
    );
    return jsonDecode(res.body);
  }

  Future<Map<String, dynamic>> verifyAuthenticity(String code) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/discover/scan-authenticity'),
      headers: _headers(),
      body: jsonEncode({'code': code}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    return data;
  }

  Future<Map<String, dynamic>> buyGiftCard(double amount, String email, String message) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/store/gift-cards/buy'),
      headers: _headers(),
      body: jsonEncode({'amount': amount, 'email': email, 'message': message}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to issue gift card');
  }

  Future<Map<String, dynamic>> checkGiftCard(String code) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/store/gift-cards/check'),
      headers: _headers(),
      body: jsonEncode({'code': code}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Gift card not found');
  }

  Future<List<dynamic>> getSubscriptions() async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/store/subscriptions'),
      headers: _headers(auth: true),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data['subscriptions'] ?? [];
    return [];
  }

  Future<Map<String, dynamic>> createSubscription(Map<String, dynamic> payload) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/store/subscriptions'),
      headers: _headers(auth: true),
      body: jsonEncode(payload),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to activate subscription');
  }

  // ─── ADMIN ────────────────────────────────────────────────────────────────

  /// Fetch admin dashboard stats, product summary, and recent orders.
  Future<Map<String, dynamic>> getAdminDashboard() async {
    final res = await http.get(
      Uri.parse('$baseUrl/api/v1/mobile/admin/dashboard'),
      headers: _headers(auth: true),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to fetch admin dashboard');
  }

  /// Fetch paginated orders for admin.
  Future<Map<String, dynamic>> getAdminOrders({int page = 1}) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/orders')
        .replace(queryParameters: {'page': page.toString()});
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to fetch admin orders');
  }

  /// Update an order's status (admin only).
  Future<Map<String, dynamic>> updateOrderStatus(
    String orderNumber,
    String status,
  ) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/mobile/admin/orders/status'),
      headers: _headers(auth: true),
      body: jsonEncode({'order_number': orderNumber, 'status': status}),
    );
    final data = _parseResponse(res);
    if (data is Map<String, dynamic>) {
      if (res.statusCode == 200 && (data['success'] == true || data['status'] != null)) return data;
      throw Exception(data['error'] ?? data['message'] ?? 'Unable to update order status');
    }
    if (res.statusCode == 200) return {'success': true};
    throw Exception('Unable to update order status');
  }

  Future<Map<String, dynamic>> getAdminProducts({int page = 1}) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/products')
        .replace(queryParameters: {'page': page.toString()});
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = _parseResponse(res);
    if (data is Map<String, dynamic> && res.statusCode == 200) return data;
    throw Exception(data is Map ? (data['error'] ?? 'Unable to load products') : 'Unable to load products');
  }

  Future<void> createAdminProduct(Map<String, dynamic> payload) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/products');
    final res = await http.post(uri, headers: _headers(auth: true), body: jsonEncode(payload));
    final data = _parseResponse(res);
    if (res.statusCode != 200) throw Exception(data is Map ? (data['error'] ?? 'Unable to create product') : 'Unable to create product');
  }

  Future<void> updateAdminProduct(int id, Map<String, dynamic> payload) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/products/$id');
    final res = await http.post(uri, headers: _headers(auth: true), body: jsonEncode(payload));
    final data = _parseResponse(res);
    if (res.statusCode != 200) throw Exception(data is Map ? (data['error'] ?? 'Unable to update product') : 'Unable to update product');
  }

  Future<void> deleteAdminProduct(int id) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/products/$id/delete');
    final res = await http.post(uri, headers: _headers(auth: true));
    final data = _parseResponse(res);
    if (res.statusCode != 200) throw Exception(data is Map ? (data['error'] ?? 'Unable to delete product') : 'Unable to delete product');
  }

  Future<Map<String, dynamic>> getAdminCustomers() async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/customers');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = _parseResponse(res);
    if (data is Map<String, dynamic> && res.statusCode == 200) return data;
    throw Exception(data is Map ? (data['error'] ?? 'Unable to load customers') : 'Unable to load customers');
  }

  Future<Map<String, dynamic>> getAdminReports() async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/reports');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = _parseResponse(res);
    if (data is Map<String, dynamic> && res.statusCode == 200) return data;
    throw Exception(data is Map ? (data['error'] ?? 'Unable to load reports') : 'Unable to load reports');
  }

  Future<Map<String, dynamic>> getAdminSettings() async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/settings');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = _parseResponse(res);
    if (data is Map<String, dynamic> && res.statusCode == 200) return data;
    throw Exception(data is Map ? (data['error'] ?? 'Unable to load settings') : 'Unable to load settings');
  }

  Future<void> updateAdminSettings(Map<String, dynamic> payload) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/admin/settings');
    final res = await http.post(uri, headers: _headers(auth: true), body: jsonEncode(payload));
    final data = _parseResponse(res);
    if (res.statusCode != 200) throw Exception(data is Map ? (data['error'] ?? 'Unable to update settings') : 'Unable to update settings');
  }

  Future<Map<String, dynamic>> getProductReviews(int productId) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/products/$productId/reviews');
    final res = await http.get(uri);
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to load reviews');
  }

  Future<void> submitProductReview(int productId, int rating, String body) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/products/$productId/reviews');
    final res = await http.post(uri, headers: _headers(auth: true), body: jsonEncode({'rating': rating, 'body': body}));
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) throw Exception(data['error'] ?? 'Failed to submit review');
  }

  Future<Map<String, dynamic>> getAddresses() async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/addresses');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to load addresses');
  }


  
  Future<void> deleteAddress(int id) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/addresses/$id/delete');
    final res = await http.post(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) throw Exception(data['error'] ?? 'Failed to delete address');
  }


  
  Future<Map<String, dynamic>> applyCoupon(String code) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/cart/coupon');
    final res = await http.post(uri, headers: _headers(auth: true), body: jsonEncode({'code': code}));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to apply coupon');
  }

  Future<Map<String, dynamic>> getOrderTracking(String orderId) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/orders/$orderId/tracking');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to get tracking');
  }

  // ─── VIP DROP ALERTS & FLASH SALES ──────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getDropAlerts() async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/drop-alerts');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['drops'] != null) {
      return List<Map<String, dynamic>>.from(data['drops']);
    }
    return [];
  }

  Future<Map<String, dynamic>> toggleDropAlertSubscription(int dropId) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/drop-alerts/$dropId/notify-me');
    final res = await http.post(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['error'] ?? 'Failed to update reminder');
  }

  Future<Map<String, dynamic>> getNotifications() async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/notifications');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    return {'success': false, 'notifications': [], 'unread_count': 0};
  }

  Future<void> markNotificationAsRead({int? id}) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/notifications/read');
    await http.post(
      uri,
      headers: _headers(auth: true),
      body: jsonEncode(id != null ? {'id': id} : {}),
    );
  }

  Future<void> registerFcmToken(String token) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/notifications/register-device');
    await http.post(
      uri,
      headers: _headers(auth: true),
      body: jsonEncode({'fcm_token': token}),
    );
  }

  // ── AI Visual Search ("Snap & Match") ──────────────────────────────────────
  Future<Map<String, dynamic>> performVisualSearch({String? imageBase64, String? queryHint}) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/ai/visual-search');
    final res = await http.post(
      uri,
      headers: _headers(auth: true),
      body: jsonEncode({
        'image_base64': ?imageBase64,
        'query_hint': ?queryHint,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['message'] ?? 'Visual search failed');
  }

  // ── 1-Tap Biometric & Express Fast Checkout ────────────────────────────────
  Future<Map<String, dynamic>> fastCheckout({
    int? productId,
    int quantity = 1,
    String size = 'Standard',
    String color = 'Default',
    String? shippingAddress,
  }) async {
    final uri = Uri.parse('$baseUrl/api/v1/mobile/fast-checkout');
    final res = await http.post(
      uri,
      headers: _headers(auth: true),
      body: jsonEncode({
        'product_id': ?productId,
        'quantity': quantity,
        'size': size,
        'color': color,
        'shipping_address': ?shippingAddress,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['message'] ?? 'Fast checkout failed');
  }

  // ── Digital VIP Luxury Gift Cards ──────────────────────────────────────────
  Future<Map<String, dynamic>> checkGiftCardBalance(String code) async {
    final uri = Uri.parse('$baseUrl/api/v1/gift-cards/balance?code=${Uri.encodeComponent(code)}');
    final res = await http.get(uri, headers: _headers(auth: true));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['message'] ?? 'Invalid gift card');
  }

  Future<Map<String, dynamic>> purchaseGiftCard({
    required double amount,
    required String recipientEmail,
    String recipientName = 'Distinguished Guest',
    String? message,
    String theme = 'gold_foil',
  }) async {
    final uri = Uri.parse('$baseUrl/api/v1/gift-cards/purchase');
    final res = await http.post(
      uri,
      headers: _headers(auth: true),
      body: jsonEncode({
        'amount': amount,
        'recipient_email': recipientEmail,
        'recipient_name': recipientName,
        'message': message,
        'theme': theme,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['message'] ?? 'Failed to purchase gift card');
  }

  Future<Map<String, dynamic>> applyGiftCard(String code, double orderTotal) async {
    final uri = Uri.parse('$baseUrl/api/v1/gift-cards/apply');
    final res = await http.post(
      uri,
      headers: _headers(auth: true),
      body: jsonEncode({
        'code': code,
        'order_total': orderTotal,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['message'] ?? 'Failed to apply gift card');
  }

  Future<Map<String, dynamic>> predictSize({
    required int productId,
    required double height,
    required double weight,
    required double chest,
    required double waist,
    required double hip,
    required String fitPreference,
  }) async {
    final uri = Uri.parse('$baseUrl/mobile/ai/size-predictor');
    final res = await http.post(
      uri,
      headers: _headers(),
      body: jsonEncode({
        'product_id': productId,
        'height': height,
        'weight': weight,
        'chest_bust': chest,
        'waist': waist,
        'hip': hip,
        'fit_preference': fitPreference,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) return data;
    throw Exception(data['message'] ?? 'Failed to predict size');
  }

  // ── Wishlist Cross-Device Persistence ──────────────────────────────────────
  Future<List<Map<String, dynamic>>> getWishlist() async {
    try {
      final uri = Uri.parse('$baseUrl/api/v1/mobile/wishlist');
      final res = await http.get(uri, headers: _headers(auth: true));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['wishlist'] is List) {
          return List<Map<String, dynamic>>.from(data['wishlist']);
        }
      }
    } catch (_) {}
    return [];
  }

  Future<bool> toggleWishlist(int productId) async {
    try {
      final uri = Uri.parse('$baseUrl/api/v1/mobile/wishlist/toggle');
      final res = await http.post(
        uri,
        headers: _headers(auth: true),
        body: jsonEncode({'product_id': productId}),
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data['is_saved'] == true;
      }
    } catch (_) {}
    return false;
  }

  Future<void> syncWishlist(List<int> productIds) async {
    try {
      final uri = Uri.parse('$baseUrl/api/v1/mobile/wishlist/sync');
      await http.post(
        uri,
        headers: _headers(auth: true),
        body: jsonEncode({'product_ids': productIds}),
      );
    } catch (_) {}
  }

  // ── Cart Cross-Device Persistence ──────────────────────────────────────────
  Future<List<Map<String, dynamic>>> getRemoteCart() async {
    try {
      final uri = Uri.parse('$baseUrl/api/v1/mobile/cart');
      final res = await http.get(uri, headers: _headers(auth: true));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['items'] is List) {
          return List<Map<String, dynamic>>.from(data['items']);
        }
      }
    } catch (_) {}
    return [];
  }

  Future<void> syncCart(List<Map<String, dynamic>> items) async {
    try {
      final uri = Uri.parse('$baseUrl/api/v1/mobile/cart/sync');
      await http.post(
        uri,
        headers: _headers(auth: true),
        body: jsonEncode({'items': items}),
      );
    } catch (_) {}
  }

  Future<void> clearRemoteCart() async {
    try {
      final uri = Uri.parse('$baseUrl/api/v1/mobile/cart/clear');
      await http.post(uri, headers: _headers(auth: true));
    } catch (_) {}
  }
}
