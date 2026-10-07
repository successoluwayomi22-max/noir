/// Typed model for the authenticated user profile.
class AppUser {
  final int id;
  final String name;
  final String email;
  final String? username;
  final String? phone;
  final String? avatar;
  final String? publicId;
  final String accountType;
  final List<String> roles;
  final int loyaltyPoints;
  final String vipTier;

  AppUser({
    required this.id,
    required this.name,
    required this.email,
    this.username,
    this.phone,
    this.avatar,
    this.publicId,
    this.accountType = 'customer',
    this.roles = const [],
    this.loyaltyPoints = 0,
    this.vipTier = 'Member',
  });

  factory AppUser.fromJson(Map<String, dynamic> json) {
    final rawRoles = json['roles'];
    List<String> rolesList = [];
    if (rawRoles is List) {
      rolesList = rawRoles.map((r) => r.toString()).toList();
    }
    return AppUser(
      id: int.tryParse(json['id']?.toString() ?? '') ??
          int.tryParse(json['user_id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      username: json['username']?.toString(),
      phone: json['phone']?.toString(),
      avatar: json['avatar']?.toString() ?? json['profile_image']?.toString(),
      publicId: json['public_id']?.toString(),
      accountType: json['account_type']?.toString() ?? 'customer',
      roles: rolesList,
      loyaltyPoints: json['loyalty_points'] != null ? int.tryParse(json['loyalty_points'].toString()) ?? 0 : 0,
      vipTier: json['vip_tier']?.toString() ?? 'Member',
    );
  }

  AppUser copyWith({
    int? id,
    String? name,
    String? email,
    String? username,
    String? phone,
    String? avatar,
    String? publicId,
    String? accountType,
    List<String>? roles,
    int? loyaltyPoints,
    String? vipTier,
  }) {
    return AppUser(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      username: username ?? this.username,
      phone: phone ?? this.phone,
      avatar: avatar ?? this.avatar,
      publicId: publicId ?? this.publicId,
      accountType: accountType ?? this.accountType,
      roles: roles ?? this.roles,
      loyaltyPoints: loyaltyPoints ?? this.loyaltyPoints,
      vipTier: vipTier ?? this.vipTier,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'username': username,
        'phone': phone,
        'avatar': avatar,
        'public_id': publicId,
        'account_type': accountType,
        'roles': roles,
        'loyalty_points': loyaltyPoints,
        'vip_tier': vipTier,
      };

  /// The display initial for the avatar circle.
  String get initial => name.isNotEmpty ? name[0].toUpperCase() : 'U';

  /// Whether this user has admin-level access.
  bool get isAdmin =>
      accountType == 'admin' ||
      accountType == 'staff' ||
      roles.any((r) => ['super-admin', 'admin', 'store-manager'].contains(r));

  /// Whether this user has a specific role slug.
  bool hasRole(String role) => roles.contains(role);
}
