class AppSettings {
  final String storeName;
  final String storeEmail;
  final String storePhone;
  final String currency;
  final String? storeLogo;

  AppSettings({
    required this.storeName,
    required this.storeEmail,
    required this.storePhone,
    required this.currency,
    this.storeLogo,
  });

  factory AppSettings.fromJson(Map<String, dynamic> json) {
    return AppSettings(
      storeName: json['store_name'] ?? 'Noir & Co.',
      storeEmail: json['store_email'] ?? 'support@noirandco.com',
      storePhone: json['store_phone'] ?? '+234 901 234 5678',
      currency: json['currency'] ?? 'NGN',
      storeLogo: json['store_logo'],
    );
  }
}
