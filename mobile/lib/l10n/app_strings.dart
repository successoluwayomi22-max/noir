import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/locale_provider.dart';

/// Lightweight comprehensive localisation helper.
/// Supports 10 language profiles: English (US/UK), Français, Español, Deutsch, Italiano, Arabic, Hausa, Igbo, Yoruba.
class AppStrings {
  final _StringMap _map;
  AppStrings._(this._map);

  static AppStrings of(BuildContext context) {
    final langName = context.watch<LocaleProvider>().selectedLanguageName;
    return AppStrings._(_resolve(langName));
  }

  /// Non-listening variant for use in callbacks, validators, and event handlers
  /// where context.watch would throw a Provider assertion error.
  static AppStrings ofRead(BuildContext context) {
    final langName = context.read<LocaleProvider>().selectedLanguageName;
    return AppStrings._(_resolve(langName));
  }

  /// Use this for store-name-aware strings like login subtitle and about page.
  static AppStrings ofWithStore(BuildContext context) {
    final langName = context.watch<LocaleProvider>().selectedLanguageName;
    return AppStrings._(_resolve(langName));
  }

  /// Dynamic sign-in subtitle — replaces hardcoded store name.
  String signInToStore(String storeName) {
      final full = _map.signInToAccount;
      if (full.contains('Noir & Co.')) return full.replaceAll('Noir & Co.', storeName);
      return full.replaceAll('Noir', storeName);
  }

  /// Dynamic about string — replaces hardcoded store name.
  String aboutStore(String storeName) {
      final full = _map.aboutNoir;
      if (full.contains('Noir & Co.')) return full.replaceAll('Noir & Co.', storeName);
      return full.replaceAll('Noir', storeName);
  }

  String clubVip(String storeName) =>
      _map.clubVip.replaceAll('Noir', storeName);

  String aiStylist(String storeName) =>
      _map.aiStylist.replaceAll('Noir', storeName);

  String pressBackAgainToExit(String storeName) =>
      _map.pressBackAgainToExit.replaceAll('Noir', storeName);

  String joinCommunity(String storeName) {
      final full = _map.joinCommunity;
      if (full.contains('Noir & Co.')) return full.replaceAll('Noir & Co.', storeName);
      return full.replaceAll('Noir', storeName);
  }

  String biometricPrompt(String storeName) {
      final full = _map.biometricPrompt;
      if (full.contains('Noir & Co.')) return full.replaceAll('Noir & Co.', storeName);
      return full.replaceAll('Noir', storeName);
  }

  // ─── Auth ───
  String get welcomeBack => _map.welcomeBack;
  String get signInToAccount => _map.signInToAccount;
  String get signIn => _map.signIn;
  String get signOut => _map.signOut;
  String get createAccount => _map.createAccount;
  String get forgotPassword => _map.forgotPassword;
  String get browseAsGuest => _map.browseAsGuest;
  String get emailAddress => _map.emailAddress;
  String get password => _map.password;
  String get continueWithGoogle => _map.continueWithGoogle;

  // ─── Navigation / Common ───
  String get home => _map.home;
  String get catalogue => _map.catalogue;
  String get discover => _map.discover;
  String get cart => _map.cart;
  String get profile => _map.profile;
  String get search => _map.search;
  String get searchProducts => _map.searchProducts;
  String get all => _map.all;
  String get sortBy => _map.sortBy;
  String get loadMore => _map.loadMore;
  String get addToBag => _map.addToBag;
  String get addedToBag => _map.addedToBag;
  String get viewCart => _map.viewCart;
  String get checkout => _map.checkout;
  String get settings => _map.settings;
  String get language => _map.language;
  String get theme => _map.theme;
  String get notifications => _map.notifications;
  String get newEditorialDrop => _map.newEditorialDrop;
  String get editorialSubtitle => _map.editorialSubtitle;
  String get pieces => _map.pieces;
  String get newest => _map.newest;
  String get priceLowHigh => _map.priceLowHigh;
  String get priceHighLow => _map.priceHighLow;
  String get reels => _map.reels;

  // ─── Profile ───
  String get myOrders => _map.myOrders;
  String get wishlist => _map.wishlist;
  String get shippingAddresses => _map.shippingAddresses;
  String get paymentMethods => _map.paymentMethods;
  String get helpAndSupport => _map.helpAndSupport;
  String get aboutNoir => _map.aboutNoir;
  String get privacySecurity => _map.privacySecurity;
  String get logOut => _map.logOut;
  String get account => _map.account;
  String get signInToViewProfile => _map.signInToViewProfile;

  // ─── Admin & Management ───
  String get admin => _map.admin;
  String get adminDashboard => _map.adminDashboard;
  String get manageOrders => _map.manageOrders;
  String get manageProducts => _map.manageProducts;
  String get manageUsers => _map.manageUsers;
  String get manageCategories => _map.manageCategories;

  // ─── Help & Support ───
  String get faq => _map.faq;
  String get contactUs => _map.contactUs;
  String get sendMessage => _map.sendMessage;
  String get yourName => _map.yourName;
  String get howCanWeHelp => _map.howCanWeHelp;
  String get messageSent => _map.messageSent;

  // ─── Orders ───
  String get orderHistory => _map.orderHistory;
  String get orderDetails => _map.orderDetails;
  String get noOrders => _map.noOrders;
  String get status => _map.status;
  String get total => _map.total;
  String get liveOrderTracking => _map.liveOrderTracking;

  // ─── VIP & Loyalty ───
  String get noirVip => _map.noirVip;
  String get loyaltyPoints => _map.loyaltyPoints;
  String get vipTier => _map.vipTier;
  String get redeem => _map.redeem;
  String get rewards => _map.rewards;
  String get perksAndRewards => _map.perksAndRewards;
  String get loyaltyProgram => _map.loyaltyProgram;
  String get giftCards => _map.giftCards;
  String get referAFriend => _map.referAFriend;

  // ─── Social & Engagement ───
  String get communityFeed => _map.communityFeed;
  String get videoCommerce => _map.videoCommerce;
  String get stylist => _map.stylist;
  String get authenticityScanner => _map.authenticityScanner;

  // ─── Cart & Shopping ───
  String get emptyBag => _map.emptyBag;
  String get continueShopping => _map.continueShopping;

  // ─── Admin ───
  String get adminProducts => _map.adminProducts;
  String get addProduct => _map.addProduct;
  String get editProduct => _map.editProduct;
  String get deleteProduct => _map.deleteProduct;
  String get subscriptions => _map.subscriptions;

  // ─── Checkout & Address ───
  String get shippingDelivery => _map.shippingDelivery;
  String get paymentMethod => _map.paymentMethod;
  String get orderSummary => _map.orderSummary;
  String get fullName => _map.fullName;
  String get phoneNumber => _map.phoneNumber;
  String get streetAddress => _map.streetAddress;
  String get city => _map.city;
  String get state => _map.state;
  String get postalCode => _map.postalCode;
  String get country => _map.country;
  String get confirmAndPay => _map.confirmAndPay;
  String get paymentCancelled => _map.paymentCancelled;
  String get orderPlaced => _map.orderPlaced;
  String get trackOrder => _map.trackOrder;
  String get continueShopping2 => _map.continueShopping2;
  String get items => _map.items;
  String get free => _map.free;
  String get included => _map.included;
  String get validateAddress => _map.validateAddress;
  String get addressVerified => _map.addressVerified;
  String get useCurrentLocation => _map.useCurrentLocation;
  String get saveAddress => _map.saveAddress;

  // ─── Orders extended ───
  String get orderNumber => _map.orderNumber;
  String get deliveryStatus => _map.deliveryStatus;
  String get pending => _map.pending;
  String get processing => _map.processing;
  String get shipped => _map.shipped;
  String get delivered => _map.delivered;
  String get cancelled => _map.cancelled;
  String get viewDetails => _map.viewDetails;
  String get reorder => _map.reorder;
  String get writeReview => _map.writeReview;

  // ─── Search ───
  String get noResults => _map.noResults;
  String get searchHint => _map.searchHint;
  String get filters => _map.filters;
  String get clearFilters => _map.clearFilters;
  String get priceRange => _map.priceRange;

  // ─── Product ───
  String get selectSize => _map.selectSize;
  String get selectColour => _map.selectColour;
  String get description => _map.description;
  String get reviews => _map.reviews;
  String get addToWishlist => _map.addToWishlist;
  String get removeFromWishlist => _map.removeFromWishlist;
  String get share => _map.share;
  String get inStock => _map.inStock;
  String get outOfStock => _map.outOfStock;
  String get quantity => _map.quantity;

  // ─── Wishlist ───
  String get myWishlist => _map.myWishlist;
  String get emptyWishlist => _map.emptyWishlist;

  // ─── Shipping ───
  String get addNewAddress => _map.addNewAddress;
  String get setDefault => _map.setDefault;
  String get editAddress => _map.editAddress;
  String get deleteAddress => _map.deleteAddress;
  String get noAddresses => _map.noAddresses;

  // ─── Payment Methods ───
  String get addCard => _map.addCard;
  String get noCards => _map.noCards;
  String get cardNumber => _map.cardNumber;
  String get expiry => _map.expiry;
  String get cvv => _map.cvv;

  // ─── Biometric / Security ───
  String get enableBiometric => _map.enableBiometric;
  String get biometricRegistered => _map.biometricRegistered;
  String get removeBiometric => _map.removeBiometric;
  String get changePassword => _map.changePassword;
  String get currentPassword => _map.currentPassword;
  String get newPassword => _map.newPassword;
  String get confirmPassword => _map.confirmPassword;

  // ─── Onboarding ───
  String get getStarted => _map.getStarted;
  String get skip => _map.skip;
  String get next => _map.next;

  // ─── Themes ───
  String get themeSystem => _map.themeSystem;
  String get themeLight => _map.themeLight;
  String get themeDark => _map.themeDark;

  // ─── Misc ───
  String get retry => _map.retry;
  String get close => _map.close;
  String get save => _map.save;
  String get cancel => _map.cancel;
  String get loading => _map.loading;
  String get error => _map.error;
  String get success => _map.success;
  String get confirm => _map.confirm;
  String get delete => _map.delete;
  String get edit => _map.edit;
  String get submit => _map.submit;
  String get back => _map.back;
  String get done => _map.done;
  String get required => _map.required;
  String get optional => _map.optional;
  String get invalidEmail => _map.invalidEmail;

  String translateProductName(String name) {
    return _map.productNames[name] ?? name;
  }

  String translateCategory(String category) {
    return _map.categories[category] ?? category;
  }

  static _StringMap _resolve(String langName) {
    switch (langName) {
      case 'Français':
        return _fr;
      case 'Español':
        return _es;
      case 'Deutsch':
        return _de;
      case 'Italiano':
        return _it;
      case 'العربية':
        return _ar;
      case 'Hausa':
        return _ha;
      case 'Igbo':
        return _ig;
      case 'Yoruba':
        return _yo;
      default:
        return _en;
    }
  }
}

class _StringMap {
  final String welcomeBack;
  final String signInToAccount;
  final String signIn;
  final String signOut;
  final String createAccount;
  final String forgotPassword;
  final String browseAsGuest;
  final String emailAddress;
  final String password;
  final String continueWithGoogle;

  final String home;
  final String catalogue;
  final String discover;
  final String cart;
  final String profile;
  final String search;
  final String searchProducts;
  final String all;
  final String sortBy;
  final String loadMore;
  final String addToBag;
  final String addedToBag;
  final String viewCart;
  final String checkout;
  final String settings;
  final String language;
  final String theme;
  final String notifications;
  final String newEditorialDrop = 'New editorial drop';
  final String editorialSubtitle = 'Streetwear curated for sharper everyday dressing.';
  final String pieces = 'pieces';
  final String newest = 'Newest';
  final String priceLowHigh = 'Price: Low to High';
  final String priceHighLow = 'Price: High to Low';
  final String reels;

  final String myOrders;
  final String wishlist;
  final String shippingAddresses;
  final String paymentMethods;
  final String helpAndSupport;
  final String aboutNoir;
  final String privacySecurity;
  final String logOut;
  final String account;
  final String signInToViewProfile;

  // Admin
  final String admin;
  final String adminDashboard;
  final String manageOrders;
  final String manageProducts;
  final String manageUsers;
  final String manageCategories;

  final String faq;
  final String contactUs;
  final String sendMessage;
  final String yourName;
  final String howCanWeHelp;
  final String messageSent;

  final String orderHistory;
  final String orderDetails;
  final String noOrders;
  final String status;
  final String total;
  final String liveOrderTracking;

  final String noirVip;
  final String loyaltyPoints;
  final String vipTier;
  final String redeem;
  final String rewards;
  final String perksAndRewards;
  final String clubVip;
  final String loyaltyProgram;
  final String giftCards;
  final String referAFriend;

  final String communityFeed;
  final String videoCommerce;
  final String stylist;
  final String aiStylist;
  final String authenticityScanner;

  final String emptyBag;
  final String continueShopping;

  final String adminProducts;
  final String addProduct;
  final String editProduct;
  final String deleteProduct;
  final String subscriptions;

  // Checkout
  final String shippingDelivery;
  final String paymentMethod;
  final String orderSummary;
  final String fullName;
  final String phoneNumber;
  final String streetAddress;
  final String city;
  final String state;
  final String postalCode;
  final String country;
  final String confirmAndPay;
  final String paymentCancelled;
  final String orderPlaced;
  final String trackOrder;
  final String continueShopping2;
  final String items;
  final String free;
  final String included;
  final String validateAddress;
  final String addressVerified;
  final String useCurrentLocation;
  final String saveAddress;

  // Orders extended
  final String orderNumber;
  final String deliveryStatus;
  final String pending;
  final String processing;
  final String shipped;
  final String delivered;
  final String cancelled;
  final String viewDetails;
  final String reorder;
  final String writeReview;

  // Search
  final String noResults;
  final String searchHint;
  final String filters;
  final String clearFilters;
  final String priceRange;

  // Product
  final String selectSize;
  final String selectColour;
  final String description;
  final String reviews;
  final String addToWishlist;
  final String removeFromWishlist;
  final String share;
  final String inStock;
  final String outOfStock;
  final String quantity;

  // Wishlist
  final String myWishlist;
  final String emptyWishlist;

  // Shipping
  final String addNewAddress;
  final String setDefault;
  final String editAddress;
  final String deleteAddress;
  final String noAddresses;

  // Payment Methods
  final String addCard;
  final String noCards;
  final String cardNumber;
  final String expiry;
  final String cvv;

  // Biometric / Security
  final String enableBiometric;
  final String biometricRegistered;
  final String removeBiometric;
  final String changePassword;
  final String currentPassword;
  final String newPassword;
  final String confirmPassword;

  // Onboarding
  final String getStarted;
  final String skip;
  final String next;

  // Themes
  final String themeSystem;
  final String themeLight;
  final String themeDark;

  // App & Community
  final String pressBackAgainToExit;
  final String joinCommunity;
  final String biometricPrompt;

  // Misc extended
  final String retry;
  final String close;
  final String save;
  final String cancel;
  final String loading;
  final String error;
  final String success;
  final String confirm;
  final String delete;
  final String edit;
  final String submit;
  final String back;
  final String done;
  final String required;
  final String optional;
  final String invalidEmail = 'Enter a valid email address';
  final Map<String, String> productNames = const {};
  final Map<String, String> categories = const {};

  const _StringMap({
    this.discover = 'Discover',
    this.reels = 'Reels',
    this.admin = 'ADMIN',
    this.adminDashboard = 'Admin Dashboard',
    this.manageOrders = 'Manage Orders',
    this.manageProducts = 'Manage Products',
    this.manageUsers = 'Manage Users',
    this.manageCategories = 'Manage Categories',
    this.liveOrderTracking = 'Live Order Tracking & GPS',
    this.perksAndRewards = 'PERKS & REWARDS',
    this.clubVip = 'Noir Club VIP',
    this.loyaltyProgram = 'Loyalty Program',
    this.giftCards = 'Gift Cards',
    this.referAFriend = 'Refer a Friend',
    this.aiStylist = 'AI Stylist',
    this.authenticityScanner = 'Authenticity Scanner',
    this.postalCode = 'Postal / ZIP Code',
    this.country = 'Country',
    this.validateAddress = 'Verify Address with Google Maps',
    this.addressVerified = 'Address verified with Google Maps',
    this.useCurrentLocation = 'Use Current Location',
    this.saveAddress = 'SAVE ADDRESS',
    this.pressBackAgainToExit = 'Press back again to exit Noir',
    this.joinCommunity = 'Join the Noir & Co. community',
    this.biometricPrompt = 'Scan fingerprint or Face ID to unlock your Noir & Co. account',
    this.themeSystem = 'System default',
    this.themeLight = 'Light',
    this.themeDark = 'Dark',
    required this.welcomeBack,
    required this.signInToAccount,
    required this.signIn,
    required this.signOut,
    required this.createAccount,
    required this.forgotPassword,
    required this.browseAsGuest,
    required this.emailAddress,
    required this.password,
    required this.continueWithGoogle,
    required this.home,
    required this.catalogue,
    required this.cart,
    required this.profile,
    required this.search,
    required this.searchProducts,
    required this.all,
    required this.sortBy,
    required this.loadMore,
    required this.addToBag,
    required this.addedToBag,
    required this.viewCart,
    required this.checkout,
    required this.settings,
    required this.language,
    required this.theme,
    required this.notifications,
    required this.myOrders,
    required this.wishlist,
    required this.shippingAddresses,
    required this.paymentMethods,
    required this.helpAndSupport,
    required this.aboutNoir,
    required this.privacySecurity,
    required this.logOut,
    required this.account,
    required this.signInToViewProfile,
    required this.faq,
    required this.contactUs,
    required this.sendMessage,
    required this.yourName,
    required this.howCanWeHelp,
    required this.messageSent,
    required this.orderHistory,
    required this.orderDetails,
    required this.noOrders,
    required this.status,
    required this.total,
    required this.noirVip,
    required this.loyaltyPoints,
    required this.vipTier,
    required this.redeem,
    required this.rewards,
    required this.communityFeed,
    required this.videoCommerce,
    required this.stylist,
    required this.emptyBag,
    required this.continueShopping,
    required this.adminProducts,
    required this.addProduct,
    required this.editProduct,
    required this.deleteProduct,
    required this.subscriptions,
    this.shippingDelivery = 'Shipping & Delivery Address',
    this.paymentMethod = 'Payment Method',
    this.orderSummary = 'Order Summary',
    this.fullName = 'Full Name',
    this.phoneNumber = 'Phone Number',
    this.streetAddress = 'Street Address',
    this.city = 'City',
    this.state = 'State / Province',
    this.confirmAndPay = 'CONFIRM & PAY',
    this.paymentCancelled = 'Payment cancelled',
    this.orderPlaced = 'Order Placed!',
    this.trackOrder = 'Track Order',
    this.continueShopping2 = 'Continue Shopping',
    this.items = 'items',
    this.free = 'FREE',
    this.included = 'INCLUDED',
    this.orderNumber = 'Order Number',
    this.deliveryStatus = 'Delivery Status',
    this.pending = 'Pending',
    this.processing = 'Processing',
    this.shipped = 'Shipped',
    this.delivered = 'Delivered',
    this.cancelled = 'Cancelled',
    this.viewDetails = 'View Details',
    this.reorder = 'Reorder',
    this.writeReview = 'Write a Review',
    this.noResults = 'No results found',
    this.searchHint = 'Search products...',
    this.filters = 'Filters',
    this.clearFilters = 'Clear Filters',
    this.priceRange = 'Price Range',
    this.selectSize = 'Select Size',
    this.selectColour = 'Select Colour',
    this.description = 'Description',
    this.reviews = 'Reviews',
    this.addToWishlist = 'Add to Wishlist',
    this.removeFromWishlist = 'Remove from Wishlist',
    this.share = 'Share',
    this.inStock = 'In Stock',
    this.outOfStock = 'Out of Stock',
    this.quantity = 'Quantity',
    this.myWishlist = 'My Wishlist',
    this.emptyWishlist = 'Your wishlist is empty',
    this.addNewAddress = 'Add New Address',
    this.setDefault = 'Set as Default',
    this.editAddress = 'Edit Address',
    this.deleteAddress = 'Delete Address',
    this.noAddresses = 'No saved addresses',
    this.addCard = 'Add Card',
    this.noCards = 'No saved cards',
    this.cardNumber = 'Card Number',
    this.expiry = 'Expiry',
    this.cvv = 'CVV',
    this.enableBiometric = 'Enable Biometric Login',
    this.biometricRegistered = 'Biometric Registered',
    this.removeBiometric = 'Remove Biometric',
    this.changePassword = 'Change Password',
    this.currentPassword = 'Current Password',
    this.newPassword = 'New Password',
    this.confirmPassword = 'Confirm Password',
    this.getStarted = 'Get Started',
    this.skip = 'Skip',
    this.next = 'Next',
    required this.retry,
    required this.close,
    required this.save,
    required this.cancel,
    required this.loading,
    this.error = 'Error',
    this.success = 'Success',
    this.confirm = 'Confirm',
    this.delete = 'Delete',
    this.edit = 'Edit',
    this.submit = 'Submit',
    this.back = 'Back',
    this.done = 'Done',
    this.required = 'Required',
    this.optional = 'Optional',
  });
}

// ── English ──────────────────────────────────────────────────────────
const _en = _StringMap(
  welcomeBack: 'Welcome Back',
  signInToAccount: 'Sign in to continue to Noir & Co.',
  signIn: 'Sign In',
  signOut: 'Sign Out',
  createAccount: 'Create Account',
  forgotPassword: 'Forgot Password?',
  browseAsGuest: 'Browse as Guest',
  emailAddress: 'Email address',
  password: 'Password',
  continueWithGoogle: 'Continue with Google',
  home: 'Home',
  catalogue: 'Catalogue',
  cart: 'Bag',
  profile: 'Profile',
  search: 'Search',
  searchProducts: 'Search products, collections...',
  all: 'All',
  sortBy: 'Sort by',
  loadMore: 'Load more pieces',
  addToBag: 'Add to Bag',
  addedToBag: 'Added to Bag',
  viewCart: 'View Bag',
  checkout: 'Checkout',
  settings: 'Settings',
  language: 'Language',
  theme: 'Theme',
  notifications: 'Notifications',
  myOrders: 'My Orders',
  wishlist: 'Wishlist',
  shippingAddresses: 'Shipping Addresses',
  paymentMethods: 'Payment Methods',
  helpAndSupport: 'Help & Support',
  aboutNoir: 'About Noir & Co.',
  privacySecurity: 'Privacy & Security',
  logOut: 'Log Out',
  account: 'Account',
  signInToViewProfile: 'Sign in to view your profile & orders',
  faq: 'Frequently Asked Questions',
  contactUs: 'Contact Us',
  sendMessage: 'Send Message',
  yourName: 'Your Name',
  howCanWeHelp: 'How can we help you?',
  messageSent: 'Message sent! We\'ll get back to you shortly.',
  orderHistory: 'Order History',
  orderDetails: 'Order Details',
  noOrders: 'No orders placed yet',
  status: 'Status',
  total: 'Total',
  noirVip: 'Noir VIP Club',
  loyaltyPoints: 'Loyalty Points',
  vipTier: 'VIP Tier',
  redeem: 'Redeem Reward',
  rewards: 'Rewards & Benefits',
  communityFeed: 'Community Feed',
  videoCommerce: 'Video Commerce',
  stylist: 'AI Stylist',
  emptyBag: 'Your bag is empty',
  continueShopping: 'Continue Shopping',
  adminProducts: 'Admin Products',
  addProduct: 'Add Product',
  editProduct: 'Edit Product',
  deleteProduct: 'Delete Product',
  subscriptions: 'Subscriptions',
  retry: 'Retry',
  close: 'Close',
  save: 'Save',
  cancel: 'Cancel',
  loading: 'Loading...',
);

// ── Yoruba ──────────────────────────────────────────────────────────
const _yo = _StringMap(
  welcomeBack: 'Kaabọ Pada',
  signInToAccount: 'Wọle lati tẹsiwaju si Noir & Co.',
  signIn: 'Wọle',
  signOut: 'Jade',
  createAccount: 'Ṣẹda Akọọlẹ',
  forgotPassword: 'Gbagbe Ọrọigbaniwọle?',
  browseAsGuest: 'Ṣawakiri bi Alejo',
  emailAddress: 'Adirẹsi Imeeli',
  password: 'Ọrọigbaniwọle',
  continueWithGoogle: 'Tẹsiwaju pẹlu Google',
  home: 'Ile',
  catalogue: 'Katalọọgi',
  discover: 'Ṣawari',
  cart: 'Apo',
  profile: 'Profaili',
  search: 'Wa',
  searchProducts: 'Wa awọn aṣọ, akojọpọ...',
  all: 'Gbogbo',
  sortBy: 'Tọ lẹsẹsẹ',
  loadMore: 'Gba awọn aṣọ diẹ sii',
  addToBag: 'Fi si Apo',
  addedToBag: 'Ti fi si apo',
  viewCart: 'Wo Apo',
  checkout: 'Sanwo',
  settings: 'Eto',
  language: 'Ede',
  theme: 'Àwọ̀',
  notifications: 'Awọn ifitonileti',
  myOrders: 'Awọn aṣẹ mi',
  wishlist: 'Akojọ Ifẹ',
  shippingAddresses: 'Awọn adirẹsi ifijiṣẹ',
  paymentMethods: 'Awọn ọna isanwo',
  helpAndSupport: 'Iranlọwọ & Atilẹyin',
  aboutNoir: 'Nipa Noir & Co.',
  privacySecurity: 'Aṣiri & Aabo',
  logOut: 'Jade',
  account: 'Akọọlẹ',
  signInToViewProfile: 'Wọle lati wo profaili ati awọn aṣẹ rẹ',
  faq: 'Awọn Ibeere Wọpọ',
  contactUs: 'Kan si Wa',
  sendMessage: 'Fi Ifiranṣẹ Ranṣẹ',
  yourName: 'Orukọ Rẹ',
  howCanWeHelp: 'Bawo ni a ṣe le ran ọ lọwọ?',
  messageSent: 'Ifiranṣẹ ti lọ! A o dahun laipẹ.',
  orderHistory: 'Itan Aṣẹ',
  orderDetails: 'Awọn alaye Aṣẹ',
  noOrders: 'Ko si aṣẹ kankan sibẹsibẹ',
  status: 'Ipo',
  total: 'Lapapọ',
  noirVip: 'Ologba VIP Noir',
  loyaltyPoints: 'Awọn aaye Iduroṣinṣin',
  vipTier: 'Ipele VIP',
  redeem: 'Gba Ere',
  rewards: 'Awọn ere & Awọn anfani',
  communityFeed: 'Agbegbe Aṣọ',
  videoCommerce: 'Fidio & Afihan Aṣọ',
  stylist: 'Oluranlowo Aṣọ AI',
  emptyBag: 'Apo rẹ ṣofo',
  continueShopping: 'Tẹsiwaju rira ọja',
  adminProducts: 'Awọn ọja Alakoso',
  addProduct: 'Fi Ọja Kun',
  editProduct: 'Ṣatunkọ Ọja',
  deleteProduct: 'Pa Ọja Rẹ',
  subscriptions: 'Awọn iforukọsilẹ',
  retry: 'Gbiyanju lẹẹkansi',
  close: 'Paade',
  save: 'Fipamọ',
  cancel: 'Fagilee',
  loading: 'N gbe wọle...',
  shippingDelivery: 'Adirẹsi Ifijiṣẹ',
  paymentMethod: 'Ọna Isanwo',
  orderSummary: 'Akopọ Aṣẹ',
  fullName: 'Orukọ Kikun',
  phoneNumber: 'Nọmba Foonu',
  streetAddress: 'Adirẹsi Ita Ọnà',
  city: 'Ilu',
  state: 'Ipinlẹ',
  postalCode: 'Koodu Ifiweranṣẹ',
  country: 'Orilẹ-ede',
  confirmAndPay: 'JẸRISI & SANWO',
  paymentCancelled: 'A fagile isanwo',
  orderPlaced: 'A gba Aṣẹ!',
  trackOrder: 'Tọpasẹ Aṣẹ',
  continueShopping2: 'Tẹsiwaju rira ọja',
  items: 'ohun',
  free: 'OFE',
  included: 'TI WA NĨ',
  orderNumber: 'Nọmba Aṣẹ',
  deliveryStatus: 'Ipo Ifijiṣẹ',
  pending: 'N duro',
  processing: 'N ṣiṣẹ',
  shipped: 'Ti firanṣẹ',
  delivered: 'Ti jiṣẹ',
  cancelled: 'Ti fagile',
  viewDetails: 'Wo Alaye',
  reorder: 'Tún Paṣẹ',
  writeReview: 'Kọ Atunyẹwo',
  noResults: 'Ko si abajade',
  searchHint: 'Wa aṣọ tabi ohun...',
  filters: 'Awọn àlẹmọ',
  clearFilters: 'Xaa Àlẹmọ',
  priceRange: 'Ibiti Iye',
  selectSize: 'Yan Iwọn',
  selectColour: 'Yan Awọ',
  description: 'Apejuwe',
  reviews: 'Awọn Atunyẹwo',
  addToWishlist: 'Fi si Akojọ Ifẹ',
  removeFromWishlist: 'Yọ kuro ninu Akojọ Ifẹ',
  share: 'Pín',
  inStock: 'Wà ni ipamọ',
  outOfStock: 'Pari',
  quantity: 'Iye',
  myWishlist: 'Akojọ Ifẹ Mi',
  emptyWishlist: 'Akojọ Ifẹ rẹ ṣofo',
  addNewAddress: 'Fi Adirẹsi Tuntun Kun',
  setDefault: 'Seto bi Aiyipada',
  editAddress: 'Ṣatunkọ Adirẹsi',
  deleteAddress: 'Pa Adirẹsi Rẹ',
  noAddresses: 'Ko si adirẹsi ti a fipamọ',
  addCard: 'Fi Kaadi Kun',
  noCards: 'Ko si kaadi ti a fipamọ',
  cardNumber: 'Nọmba Kaadi',
  expiry: 'Ọjọ Ipari',
  cvv: 'CVV',
  enableBiometric: 'Mu Wiwọle Ara Ṣiṣẹ',
  biometricRegistered: 'Ti Forukọsilẹ Ara',
  removeBiometric: 'Yọ Wiwọle Ara',
  changePassword: 'Yipada Ọrọigbaniwọle',
  currentPassword: 'Ọrọigbaniwọle Lọwọlọwọ',
  newPassword: 'Ọrọigbaniwọle Tuntun',
  confirmPassword: 'Jẹrisi Ọrọigbaniwọle',
  getStarted: 'Bẹrẹ',
  skip: 'Fo',
  next: 'Tẹle',
  error: 'Aṣiṣe',
  success: 'Aṣeyọri',
  confirm: 'Jẹrisi',
  delete: 'Pa Rẹ',
  edit: 'Ṣatunkọ',
  submit: 'Firanṣẹ',
  back: 'Pada',
  done: 'Pari',
  required: 'Dandan',
  optional: 'Atọka',
  admin: 'ISAKOSO',
  adminDashboard: 'Dasibodu Isakoso',
  manageOrders: 'Ṣakoso Awọn Aṣẹ',
  manageProducts: 'Ṣakoso Awọn Ọja',
  manageUsers: 'Ṣakoso Awọn Olumulo',
  manageCategories: 'Ṣakoso Awọn Ẹka',
  liveOrderTracking: 'Titele Aṣẹ Taara & GPS',
  perksAndRewards: 'AWỌN ANFANI & ERE',
  clubVip: 'Ologba VIP Noir',
  loyaltyProgram: 'Eto Iduroṣinṣin',
  giftCards: 'Awọn Kaadi Ẹbun',
  referAFriend: 'Ṣeduro Ọrẹ Kan',
  aiStylist: 'Oluranlọwọ Aṣọ AI',
  authenticityScanner: 'Ayẹwo Otitọ Ọja',
  reels: 'Awọn Fidio',
  validateAddress: 'Ṣayẹwo Adirẹsi pẹlu Google Maps',
  addressVerified: 'Adirẹsi ti a fọwọsi pẹlu Google Maps',
  useCurrentLocation: 'Lo Ipo Lọwọlọwọ Mi',
  saveAddress: 'FIPAMỌ ADIRẸSI',
  pressBackAgainToExit: 'Tẹ pada lẹẹkansi lati jade Noir',
  joinCommunity: 'Darapọ mọ agbegbe Noir & Co.',
  biometricPrompt: 'Ṣayẹwo itẹka tabi oju lati wọle si Noir & Co.',
  themeSystem: 'Aiyipada Eto',
  themeLight: 'Ina / Imọlẹ',
  themeDark: 'Okunkun / Dudu',
);

// ── Hausa ──────────────────────────────────────────────────────────
const _ha = _StringMap(
  welcomeBack: 'Barka da Dawowa',
  signInToAccount: 'Shiga don ci gaba zuwa Noir & Co.',
  signIn: 'Shiga',
  signOut: 'Fita',
  createAccount: 'Ƙirƙiri Asusun',
  forgotPassword: 'Ka Manta Kalmar Sirri?',
  browseAsGuest: 'Bincika a Matsayin Baƙo',
  emailAddress: 'Adireshin Imel',
  password: 'Kalmar Sirri',
  continueWithGoogle: 'Ci gaba da Google',
  home: 'Gida',
  catalogue: 'Kundin Kaya',
  discover: 'Binciko',
  cart: 'Jaka',
  profile: 'Bayanin Martaba',
  search: 'Bincika',
  searchProducts: 'Bincika kaya, tarin tufafi...',
  all: 'Duka',
  sortBy: 'Tsara ta hanyar',
  loadMore: 'Nuna ƙarin kaya',
  addToBag: 'Saka a Jaka',
  addedToBag: 'An saka a jaka',
  viewCart: 'Duba Jaka',
  checkout: 'Biyan Kuɗi',
  settings: 'Saituna',
  language: 'Harshe',
  theme: 'Kalar Fuskantar',
  notifications: 'Sanarwa',
  myOrders: 'Odojina',
  wishlist: 'Jerin Abubuwan So',
  shippingAddresses: 'Adiresoshin Isarwa',
  paymentMethods: 'Hanyoyin Biya',
  helpAndSupport: 'Taimako & Tallafi',
  aboutNoir: 'Game da Noir & Co.',
  privacySecurity: 'Sirri & Tsaro',
  logOut: 'Fita',
  account: 'Asusu',
  signInToViewProfile: 'Shiga don ganin bayanan ka da odoji',
  faq: 'Tambayoyi Masu Yawa',
  contactUs: 'Tuntube Mu',
  sendMessage: 'Aika Saƙo',
  yourName: 'Sunanka',
  howCanWeHelp: 'Ta yaya za mu taimake ka?',
  messageSent: 'An aika saƙo! Za mu ba da amsa nan ba da jimawa ba.',
  orderHistory: 'Tarihin Odoji',
  orderDetails: 'Cikakken Bayanin Odo',
  noOrders: 'Babu oda a halin yanzu',
  status: 'Hali',
  total: 'Jimilla',
  noirVip: 'Ƙungiyar VIP ta Noir',
  loyaltyPoints: 'Maki na Aminci',
  vipTier: 'Matsayin VIP',
  redeem: 'Karɓi Lada',
  rewards: 'Kyaututtuka & Lada',
  communityFeed: 'Dandalin Jama\'a',
  videoCommerce: 'Bidiyon Talla',
  stylist: 'Mai Tsara Kayan AI',
  emptyBag: 'Jakarka ba komai',
  continueShopping: 'Ci gaba da Siyayya',
  adminProducts: 'Kayayyakin Manaja',
  addProduct: 'Ƙara Kaya',
  editProduct: 'Gyara Kaya',
  deleteProduct: 'Goge Kaya',
  subscriptions: 'Biyan Kuɗi na Lokaci',
  retry: 'Sake Gwada',
  close: 'Rufe',
  save: 'Ajiye',
  cancel: 'Soke',
  loading: 'Ana lodawa...',
  shippingDelivery: 'Adireshin Isarwa',
  paymentMethod: 'Hanyar Biya',
  orderSummary: 'Takaitaccen Bayanin Oda',
  fullName: 'Cikakken Suna',
  phoneNumber: 'Lambar Waya',
  streetAddress: 'Adireshin Titi',
  city: 'Birni',
  state: 'Jiha',
  postalCode: 'Lambar Wasiƙa',
  country: 'Ƙasa',
  confirmAndPay: 'TABBATAR & BIYA',
  paymentCancelled: 'An soke biyan kuɗi',
  orderPlaced: 'An Karɓi Oda!',
  trackOrder: 'Bibiyi Oda',
  continueShopping2: 'Ci gaba da Siyayya',
  items: 'abubuwa',
  free: 'KYAUTA',
  included: 'YA HAƊA DA',
  orderNumber: 'Lambar Oda',
  deliveryStatus: 'Halin Isarwa',
  pending: 'Ana jira',
  processing: 'Ana aiki akai',
  shipped: 'An tura',
  delivered: 'An isar',
  cancelled: 'An soke',
  viewDetails: 'Duba Cikakken Bayani',
  reorder: 'Sake Oda',
  writeReview: 'Rubuta Sharhi',
  noResults: 'Ba a sami sakamako ba',
  searchHint: 'Bincika kaya ko tufafi...',
  filters: 'Matatun Bincike',
  clearFilters: 'Share Matatu',
  priceRange: 'Kewayon Farashi',
  selectSize: 'Zaɓi Girma',
  selectColour: 'Zaɓi Launi',
  description: 'Bayanin Kaya',
  reviews: 'Sharhohi',
  addToWishlist: 'Saka a Jerin So',
  removeFromWishlist: 'Cire daga Jerin So',
  share: 'Raba',
  inStock: 'Akwai a Ma\'ajiya',
  outOfStock: 'Ya ƙare',
  quantity: 'Yawa',
  myWishlist: 'Jerin Abubuwan da nake So',
  emptyWishlist: 'Jerin abubuwan da kake so babu komai',
  addNewAddress: 'Ƙara Sabon Adireshi',
  setDefault: 'Saita a Matsayin Asali',
  editAddress: 'Gyara Adireshi',
  deleteAddress: 'Goge Adireshi',
  noAddresses: 'Babu adiresoshin da aka ajiye',
  addCard: 'Ƙara Katin Banki',
  noCards: 'Babu katunan da aka ajiye',
  cardNumber: 'Lambar Kati',
  expiry: 'Ranar Ƙarewa',
  cvv: 'CVV',
  enableBiometric: 'Kunna Shiga ta Hanyar Yatsa',
  biometricRegistered: 'An Yi Rajistar Yatsa',
  removeBiometric: 'Cire Shiga ta Yatsa',
  changePassword: 'Canza Kalmar Sirri',
  currentPassword: 'Kalmar Sirri ta Yanzu',
  newPassword: 'Sabuwar Kalmar Sirri',
  confirmPassword: 'Tabbatar da Sabuwar Kalmar Sirri',
  getStarted: 'Fara Yanzu',
  skip: 'Tsallake',
  next: 'Na Gaba',
  error: 'Kuskure',
  success: 'Nasarar',
  confirm: 'Tabbatar',
  delete: 'Goge',
  edit: 'Gyara',
  submit: 'Aika',
  back: 'Koma Baya',
  done: 'An Gama',
  required: 'Wajibi',
  optional: 'Na Zaɓi',
  admin: 'GUDANARWA',
  adminDashboard: 'Fannin Gudanarwa',
  manageOrders: 'Sarrafa Odoji',
  manageProducts: 'Sarrafa Kayayyaki',
  manageUsers: 'Sarrafa Masu Amfani',
  manageCategories: 'Sarrafa Rukunai',
  liveOrderTracking: 'Bibiyar Oda Kai Tsaye & GPS',
  perksAndRewards: 'LADA & ALFANU',
  clubVip: 'Ƙungiyar VIP ta Noir',
  loyaltyProgram: 'Tsarin Aminci',
  giftCards: 'Katunan Kyauta',
  referAFriend: 'Gayyaci Aboki',
  aiStylist: 'Mai Tsara Kayan AI',
  authenticityScanner: 'Na\'urar Binciken Asalin Kaya',
  reels: 'Gajerun Bidiyoyi',
  validateAddress: 'Tabbatar da Adireshi ta Google Maps',
  addressVerified: 'An tabbatar da adireshi ta Google Maps',
  useCurrentLocation: 'Yi Amfani da Wuri na na Yanzu',
  saveAddress: 'AJIYE ADIRESHI',
  pressBackAgainToExit: 'Sake danna baya don fita daga Noir',
  joinCommunity: 'Kasance cikin jama\'ar Noir & Co.',
  biometricPrompt: 'Duba yatsa ko fuska don shiga Noir & Co.',
  themeSystem: 'Na\'urar Asali',
  themeLight: 'Haske',
  themeDark: 'Duhu',
);

// ── Igbo ──────────────────────────────────────────────────────────
const _ig = _StringMap(
  welcomeBack: 'Nnọọ Ọzọ',
  signInToAccount: 'Banye iji gaa n\'ihu na Noir & Co.',
  signIn: 'Banye',
  signOut: 'Pụọ',
  createAccount: 'Mepụta Akaụntụ',
  forgotPassword: 'Chefuru Okwuntughe?',
  browseAsGuest: 'Chọgharịa Dịka Ọbịa',
  emailAddress: 'Adreesị Ozi-e',
  password: 'Okwuntughe',
  continueWithGoogle: 'Gaa n\'ihu na Google',
  home: 'Ụlọ',
  catalogue: 'Katalọgụ',
  discover: 'Chọpụta',
  cart: 'Akpa',
  profile: 'Profaịlụ',
  search: 'Chọọ',
  searchProducts: 'Chọọ uwe, nchịkọta...',
  all: 'Niile',
  sortBy: 'Hazie site na',
  loadMore: 'Bubata uwe ndị ọzọ',
  addToBag: 'Tinye n\'Akpa',
  addedToBag: 'Etinyere n\'akpa',
  viewCart: 'Lelee Akpa',
  checkout: 'Kwụọ Ụgwọ',
  settings: 'Ntọala',
  language: 'Asụsụ',
  theme: 'Ụcha',
  notifications: 'Ọkwa',
  myOrders: 'Iwu m niile',
  wishlist: 'Ndepụta Ihe Ọchịchọ',
  shippingAddresses: 'Adreesị Mbupụ',
  paymentMethods: 'Ụzọ Ịkwụ Ụgwọ',
  helpAndSupport: 'Enyemaka & Nkwado',
  aboutNoir: 'Maka Noir & Co.',
  privacySecurity: 'Nzuzo & Nchedo',
  logOut: 'Pụọ',
  account: 'Akaụntụ',
  signInToViewProfile: 'Banye iji hụ profaịlụ na iwu gị',
  faq: 'Ajụjụ Ndị A Na-ajụkarị',
  contactUs: 'Kpọtụrụ Anyị',
  sendMessage: 'Zipu Ozi',
  yourName: 'Aha Gị',
  howCanWeHelp: 'Kedu ka anyị ga-esi nyere gị aka?',
  messageSent: 'Ezitere ozi! Anyị ga-aza n\'oge na-adịghị anya.',
  orderHistory: 'Akụkọ Iwu',
  orderDetails: 'Nkọwa Iwu',
  noOrders: 'Ọ dịghị iwu etinyere ugbu a',
  status: 'Ọnọdụ',
  total: 'Ngụkọta',
  noirVip: 'Klọb VIP Noir',
  loyaltyPoints: 'Isi Ihe Iguzosi Ike n\'Ihe',
  vipTier: 'Ọkwa VIP',
  redeem: 'Nweta Ụgwọ Ọrụ',
  rewards: 'Ụgwọ Ọrụ & Uru',
  communityFeed: 'Ogbe Uwe',
  videoCommerce: 'Vidio Azụmahịa',
  stylist: 'Onye Ndụmọdụ Uwe AI',
  emptyBag: 'Akpa gị tọgbọrọ chakoo',
  continueShopping: 'Gaa n\'ihu n\'Ịzụ Ahịa',
  adminProducts: 'Ngwaahịa Onye Nlekọta',
  addProduct: 'Tinye Ngwaahịa',
  editProduct: 'Dezie Ngwaahịa',
  deleteProduct: 'Hichapụ Ngwaahịa',
  subscriptions: 'Ndebanye Aha',
  retry: 'Nwalee Ọzọ',
  close: 'Mechie',
  save: 'Chekwaa',
  cancel: 'Kagbuo',
  loading: 'Na-ebudata...',
  shippingDelivery: 'Adreesị Mbupu',
  paymentMethod: 'Ụzọ Ịkwụ Ụgwọ',
  orderSummary: 'Nchịkọta Iwu',
  fullName: 'Aha zuru ezu',
  phoneNumber: 'Nọmba Ekwentị',
  streetAddress: 'Adreesị Okporo Ụzọ',
  city: 'Obodo',
  state: 'Steeti',
  postalCode: 'Koodu Nzipu Ozi',
  country: 'Mba',
  confirmAndPay: 'KWADO & KWỤỌ ỤGWỌ',
  paymentCancelled: 'A kagburu ịkwụ ụgwọ',
  orderPlaced: 'Etinyere Iwu nke ọma!',
  trackOrder: 'Sochie Iwu',
  continueShopping2: 'Gaa n\'ihu n\'Ịzụ Ahịa',
  items: 'ihe',
  free: 'N\'EFEGHỊ ỤGWỌ',
  included: 'AGUNYERE YA',
  orderNumber: 'Nọmba Iwu',
  deliveryStatus: 'Ọnọdụ Mbupu',
  pending: 'Na-eche',
  processing: 'Na-arụ ọrụ',
  shipped: 'Ebufere',
  delivered: 'Ebunyere',
  cancelled: 'Akagburu',
  viewDetails: 'Lelee Nkọwa',
  reorder: 'Nye Iwu Ọzọ',
  writeReview: 'Dee Nlebanya',
  noResults: 'Enweghị nsonaazụ ọ bụla',
  searchHint: 'Chọọ uwe ma ọ bụ ngwaahịa...',
  filters: 'Ihe Nzacha',
  clearFilters: 'Hichapụ Ihe Nzacha',
  priceRange: 'Ọnụ Ahịa',
  selectSize: 'Họrọ Nha',
  selectColour: 'Họrọ Ụcha',
  description: 'Nkọwa',
  reviews: 'Nlebanya',
  addToWishlist: 'Tinye na Ndepụta Ihe Ọchịchọ',
  removeFromWishlist: 'Wepụ na Ndepụta Ihe Ọchịchọ',
  share: 'Kekọrịta',
  inStock: 'Dị na Ụlọ Ahịa',
  outOfStock: 'Agwụla',
  quantity: 'Ọnụọgụgụ',
  myWishlist: 'Ndepụta Ihe Ọchịchọ M',
  emptyWishlist: 'Ndepụta ihe ọchịchọ gị tọgbọrọ chakoo',
  addNewAddress: 'Tinye Adreesị Ọhụrụ',
  setDefault: 'Tọọ Dịka Nke Mbụ',
  editAddress: 'Dezie Adreesị',
  deleteAddress: 'Hichapụ Adreesị',
  noAddresses: 'Enweghị adreesị echekwara',
  addCard: 'Tinye Kaadị Banki',
  noCards: 'Enweghị kaadị echekwara',
  cardNumber: 'Nọmba Kaadị',
  expiry: 'Ụbọchị Ọ Ga-agwụ',
  cvv: 'CVV',
  enableBiometric: 'Mee Ka Nbanye Mkpịsị Aka Rụọ Ọrụ',
  biometricRegistered: 'Edebanyere Mkpịsị Aka',
  removeBiometric: 'Wepụ Nbanye Mkpịsị Aka',
  changePassword: 'Gbanwee Okwuntughe',
  currentPassword: 'Okwuntughe Ugbu A',
  newPassword: 'Okwuntughe Ọhụrụ',
  confirmPassword: 'Kwado Okwuntughe Ọhụrụ',
  getStarted: 'Bido Ugbu A',
  skip: 'Mafere',
  next: 'Ọzọ',
  error: 'Mperi',
  success: 'Ihe Ịga Nke Ọma',
  confirm: 'Kwado',
  delete: 'Hichapụ',
  edit: 'Dezie',
  submit: 'Nyefee',
  back: 'Azụ',
  done: 'Emela',
  required: 'A chọrọ ya',
  optional: 'Nhọrọ',
  admin: 'NLEKỌTA',
  adminDashboard: 'Dasibọdu Nlekọta',
  manageOrders: 'Jikwaa Iwu Niile',
  manageProducts: 'Jikwaa Ngwaahịa',
  manageUsers: 'Jikwaa Ndị Ọrụ',
  manageCategories: 'Jikwaa Otu Dị Iche Iche',
  liveOrderTracking: 'Nsochi Iwu Na-adị Ndụ & GPS',
  perksAndRewards: 'URU & ỤGWỌ ỌRỤ',
  clubVip: 'Klọb VIP Noir',
  loyaltyProgram: 'Mmemme Iguzosi Ike n\'Ihe',
  giftCards: 'Kaadị Onyinye',
  referAFriend: 'Kpọọ Enyi Gị',
  aiStylist: 'Onye Ndụmọdụ Uwe AI',
  authenticityScanner: 'Ihe Nyocha Ezigbo Ngwaahịa',
  reels: 'Vidio Ndị Dị Mkpirikpi',
  validateAddress: 'Nyochaa Adreesị na Google Maps',
  addressVerified: 'Enyochara adreesị na Google Maps nke ọma',
  useCurrentLocation: 'Jiri Ebe M Nọ Ugbu A',
  saveAddress: 'CHEKWAA ADREESỊ',
  pressBackAgainToExit: 'Pịa azụ ọzọ iji pụọ na Noir',
  joinCommunity: 'Soro na ndị ogbe Noir & Co.',
  biometricPrompt: 'Nyochaa mkpịsị aka ma ọ bụ ihu iji banye Noir & Co.',
  themeSystem: 'Ntọala Sistemụ',
  themeLight: 'Ìhè',
  themeDark: 'Ọchịchịrị',
);

// ── Français ────────────────────────────────────────────────────────
const _fr = _StringMap(
  welcomeBack: 'Bon Retour',
  signInToAccount: 'Connectez-vous pour continuer vers Noir & Co.',
  signIn: 'Se connecter',
  signOut: 'Se déconnecter',
  createAccount: 'Créer un compte',
  forgotPassword: 'Mot de passe oublié ?',
  browseAsGuest: 'Continuer en tant qu\'invité',
  emailAddress: 'Adresse e-mail',
  password: 'Mot de passe',
  continueWithGoogle: 'Continuer avec Google',
  home: 'Accueil',
  catalogue: 'Catalogue',
  discover: 'Découvrir',
  cart: 'Panier',
  profile: 'Profil',
  search: 'Rechercher',
  searchProducts: 'Rechercher des pièces, collections...',
  all: 'Tout',
  sortBy: 'Trier par',
  loadMore: 'Charger plus de pièces',
  addToBag: 'Ajouter au panier',
  addedToBag: 'Ajouté au panier',
  viewCart: 'Voir le panier',
  checkout: 'Commander',
  settings: 'Paramètres',
  language: 'Langue',
  theme: 'Thème',
  notifications: 'Notifications',
  myOrders: 'Mes commandes',
  wishlist: 'Favoris',
  shippingAddresses: 'Adresses de livraison',
  paymentMethods: 'Moyens de paiement',
  helpAndSupport: 'Aide & Support',
  aboutNoir: 'À propos de Noir & Co.',
  privacySecurity: 'Confidentialité & Sécurité',
  logOut: 'Déconnexion',
  account: 'Compte',
  signInToViewProfile: 'Connectez-vous pour voir vos commandes',
  faq: 'Foire Aux Questions',
  contactUs: 'Nous contacter',
  sendMessage: 'Envoyer le message',
  yourName: 'Votre nom',
  howCanWeHelp: 'Comment pouvons-nous vous aider ?',
  messageSent: 'Message envoyé ! Nous vous répondrons sous peu.',
  orderHistory: 'Historique des commandes',
  orderDetails: 'Détails de la commande',
  noOrders: 'Aucune commande pour le moment',
  status: 'Statut',
  total: 'Total',
  noirVip: 'Club VIP Noir',
  loyaltyPoints: 'Points de fidélité',
  vipTier: 'Statut VIP',
  redeem: 'Échanger',
  rewards: 'Récompenses & Avantages',
  communityFeed: 'Fil communautaire',
  videoCommerce: 'Commerce vidéo',
  stylist: 'Styliste IA',
  emptyBag: 'Votre panier est vide',
  continueShopping: 'Continuer les achats',
  adminProducts: 'Produits admin',
  addProduct: 'Ajouter un produit',
  editProduct: 'Modifier le produit',
  deleteProduct: 'Supprimer le produit',
  subscriptions: 'Abonnements',
  retry: 'Réessayer',
  close: 'Fermer',
  save: 'Enregistrer',
  cancel: 'Annuler',
  loading: 'Chargement...',
  shippingDelivery: 'Adresse de livraison',
  paymentMethod: 'Moyen de paiement',
  orderSummary: 'Récapitulatif de la commande',
  fullName: 'Nom complet',
  phoneNumber: 'Numéro de téléphone',
  streetAddress: 'Adresse postale',
  city: 'Ville',
  state: 'Région / Département',
  postalCode: 'Code postal',
  country: 'Pays',
  confirmAndPay: 'CONFIRMER ET PAYER',
  paymentCancelled: 'Paiement annulé',
  orderPlaced: 'Commande validée !',
  trackOrder: 'Suivre la commande',
  continueShopping2: 'Continuer les achats',
  items: 'articles',
  free: 'GRATUIT',
  included: 'INCLUS',
  admin: 'ADMINISTRATION',
  adminDashboard: 'Tableau de bord Admin',
  manageOrders: 'Gérer les commandes',
  manageProducts: 'Gérer les produits',
  manageUsers: 'Gérer les utilisateurs',
  manageCategories: 'Gérer les catégories',
  liveOrderTracking: 'Suivi de commande en direct & GPS',
  perksAndRewards: 'AVANTAGES & RÉCOMPENSES',
  clubVip: 'Club VIP Noir',
  loyaltyProgram: 'Programme de fidélité',
  giftCards: 'Cartes cadeaux',
  referAFriend: 'Parrainer un ami',
  aiStylist: 'Styliste IA',
  authenticityScanner: 'Scanner d\'authenticité',
  reels: 'Vidéos',
  validateAddress: 'Valider l\'adresse avec Google Maps',
  addressVerified: 'Adresse vérifiée par Google Maps',
  useCurrentLocation: 'Utiliser ma position actuelle',
  saveAddress: 'ENREGISTRER L\'ADRESSE',
  pressBackAgainToExit: 'Appuyez à nouveau pour quitter Noir',
  joinCommunity: 'Rejoignez la communauté Noir & Co.',
  biometricPrompt: 'Scannez votre empreinte ou visage pour déverrouiller Noir & Co.',
  themeSystem: 'Système par défaut',
  themeLight: 'Clair',
  themeDark: 'Sombre',
);

// ── Español ──────────────────────────────────────────────────────────
const _es = _StringMap(
  welcomeBack: 'Bienvenido de nuevo',
  signInToAccount: 'Inicia sesión para continuar en Noir & Co.',
  signIn: 'Iniciar sesión',
  signOut: 'Cerrar sesión',
  createAccount: 'Crear cuenta',
  forgotPassword: '¿Olvidaste tu contraseña?',
  browseAsGuest: 'Continuar como invitado',
  emailAddress: 'Correo electrónico',
  password: 'Contraseña',
  continueWithGoogle: 'Continuar con Google',
  home: 'Inicio',
  catalogue: 'Catálogo',
  discover: 'Descubrir',
  cart: 'Bolsa',
  profile: 'Perfil',
  search: 'Buscar',
  searchProducts: 'Buscar prendas, colecciones...',
  all: 'Todo',
  sortBy: 'Ordenar por',
  loadMore: 'Cargar más prendas',
  addToBag: 'Añadir a la bolsa',
  addedToBag: 'Añadido a la bolsa',
  viewCart: 'Ver bolsa',
  checkout: 'Pagar',
  settings: 'Ajustes',
  language: 'Idioma',
  theme: 'Tema',
  notifications: 'Notificaciones',
  myOrders: 'Mis pedidos',
  wishlist: 'Favoritos',
  shippingAddresses: 'Direcciones de envío',
  paymentMethods: 'Métodos de pago',
  helpAndSupport: 'Ayuda y soporte',
  aboutNoir: 'Acerca de Noir & Co.',
  privacySecurity: 'Privacidad y seguridad',
  logOut: 'Cerrar sesión',
  account: 'Cuenta',
  signInToViewProfile: 'Inicia sesión para ver tus pedidos',
  faq: 'Preguntas frecuentes',
  contactUs: 'Contáctanos',
  sendMessage: 'Enviar mensaje',
  yourName: 'Tu nombre',
  howCanWeHelp: '¿Cómo podemos ayudarte?',
  messageSent: '¡Mensaje enviado! Te responderemos pronto.',
  orderHistory: 'Historial de pedidos',
  orderDetails: 'Detalles del pedido',
  noOrders: 'Aún no hay pedidos',
  status: 'Estado',
  total: 'Total',
  noirVip: 'Club VIP Noir',
  loyaltyPoints: 'Puntos de fidelidad',
  vipTier: 'Nivel VIP',
  redeem: 'Canjear recompensa',
  rewards: 'Recompensas y beneficios',
  communityFeed: 'Comunidad de estilo',
  videoCommerce: 'Comercio en video',
  stylist: 'Estilista IA',
  emptyBag: 'Tu bolsa está vacía',
  continueShopping: 'Continuar comprando',
  adminProducts: 'Productos de administrador',
  addProduct: 'Añadir producto',
  editProduct: 'Editar producto',
  deleteProduct: 'Eliminar producto',
  subscriptions: 'Suscripciones',
  retry: 'Reintentar',
  close: 'Cerrar',
  save: 'Guardar',
  cancel: 'Cancelar',
  loading: 'Cargando...',
  admin: 'ADMINISTRACIÓN',
  adminDashboard: 'Panel de Administrador',
  manageOrders: 'Gestionar Pedidos',
  manageProducts: 'Gestionar Productos',
  manageUsers: 'Gestionar Usuarios',
  manageCategories: 'Gestionar Categorías',
  liveOrderTracking: 'Seguimiento de Pedido en Vivo & GPS',
  perksAndRewards: 'BENEFICIOS Y RECOMPENSAS',
  clubVip: 'Club VIP Noir',
  loyaltyProgram: 'Programa de Fidelidad',
  giftCards: 'Tarjetas de Regalo',
  referAFriend: 'Recomendar a un Amigo',
  aiStylist: 'Estilista IA',
  authenticityScanner: 'Escáner de Autenticidad',
  reels: 'Reels',
  validateAddress: 'Validar dirección con Google Maps',
  addressVerified: 'Dirección verificada con Google Maps',
  useCurrentLocation: 'Usar mi ubicación actual',
  saveAddress: 'GUARDAR DIRECCIÓN',
  pressBackAgainToExit: 'Presiona atrás de nuevo para salir de Noir',
  joinCommunity: 'Únete a la comunidad de Noir & Co.',
  biometricPrompt: 'Escanea tu huella o rostro para desbloquear Noir & Co.',
  themeSystem: 'Predeterminado del sistema',
  themeLight: 'Claro',
  themeDark: 'Oscuro',
);

// ── Deutsch ──────────────────────────────────────────────────────────
const _de = _StringMap(
  welcomeBack: 'Willkommen zurück',
  signInToAccount: 'Melden Sie sich an, um fortzufahren.',
  signIn: 'Anmelden',
  signOut: 'Abmelden',
  createAccount: 'Konto erstellen',
  forgotPassword: 'Passwort vergessen?',
  browseAsGuest: 'Als Gast fortfahren',
  emailAddress: 'E-Mail-Adresse',
  password: 'Passwort',
  continueWithGoogle: 'Mit Google fortfahren',
  home: 'Startseite',
  catalogue: 'Katalog',
  discover: 'Entdecken',
  cart: 'Warenkorb',
  profile: 'Profil',
  search: 'Suchen',
  searchProducts: 'Produkte, Kollektionen suchen...',
  all: 'Alle',
  sortBy: 'Sortieren nach',
  loadMore: 'Mehr Stücke laden',
  addToBag: 'In den Warenkorb',
  addedToBag: 'Hinzugefügt',
  viewCart: 'Warenkorb ansehen',
  checkout: 'Zur Kasse',
  settings: 'Einstellungen',
  language: 'Sprache',
  theme: 'Design',
  notifications: 'Benachrichtigungen',
  myOrders: 'Meine Bestellungen',
  wishlist: 'Wunschliste',
  shippingAddresses: 'Lieferadressen',
  paymentMethods: 'Zahlungsmethoden',
  helpAndSupport: 'Hilfe & Support',
  aboutNoir: 'Über Noir & Co.',
  privacySecurity: 'Datenschutz & Sicherheit',
  logOut: 'Abmelden',
  account: 'Konto',
  signInToViewProfile: 'Melden Sie sich an, um Ihr Profil zu sehen',
  faq: 'Häufig gestellte Fragen',
  contactUs: 'Kontaktieren Sie uns',
  sendMessage: 'Nachricht senden',
  yourName: 'Ihr Name',
  howCanWeHelp: 'Wie können wir Ihnen helfen?',
  messageSent: 'Nachricht gesendet!',
  orderHistory: 'Bestellverlauf',
  orderDetails: 'Bestelldetails',
  noOrders: 'Noch keine Bestellungen',
  status: 'Status',
  total: 'Gesamt',
  noirVip: 'Noir VIP Club',
  loyaltyPoints: 'Treuepunkte',
  vipTier: 'VIP-Stufe',
  redeem: 'Punkte einlösen',
  rewards: 'Prämien & Vorteile',
  communityFeed: 'Community Feed',
  videoCommerce: 'Video-Commerce',
  stylist: 'KI-Stylist',
  emptyBag: 'Ihr Warenkorb ist leer',
  continueShopping: 'Weiter einkaufen',
  adminProducts: 'Admin-Produkte',
  addProduct: 'Produkt hinzufügen',
  editProduct: 'Produkt bearbeiten',
  deleteProduct: 'Produkt löschen',
  subscriptions: 'Abonnements',
  retry: 'Wiederholen',
  close: 'Schließen',
  save: 'Speichern',
  cancel: 'Abbrechen',
  loading: 'Laden...',
  admin: 'ADMINISTRATION',
  adminDashboard: 'Admin-Dashboard',
  manageOrders: 'Bestellungen verwalten',
  manageProducts: 'Produkte verwalten',
  manageUsers: 'Benutzer verwalten',
  manageCategories: 'Kategorien verwalten',
  liveOrderTracking: 'Live-Bestellverfolgung & GPS',
  perksAndRewards: 'VORTEILE & PRÄMIEN',
  clubVip: 'Noir VIP Club',
  loyaltyProgram: 'Treueprogramm',
  giftCards: 'Geschenkkarten',
  referAFriend: 'Freunde werben',
  aiStylist: 'KI-Stylist',
  authenticityScanner: 'Echtheitsprüfer',
  reels: 'Reels',
  validateAddress: 'Adresse mit Google Maps prüfen',
  addressVerified: 'Adresse über Google Maps bestätigt',
  useCurrentLocation: 'Aktuellen Standort verwenden',
  saveAddress: 'ADRESSE SPEICHERN',
  pressBackAgainToExit: 'Erneut zurück drücken, um Noir zu beenden',
  joinCommunity: 'Werden Sie Teil der Noir & Co. Community',
  biometricPrompt: 'Fingerabdruck scannen, um Noir & Co. zu entsperren',
  themeSystem: 'Systemstandard',
  themeLight: 'Hell',
  themeDark: 'Dunkel',
);

// ── Italiano ──────────────────────────────────────────────────────────
const _it = _StringMap(
  welcomeBack: 'Bentornato',
  signInToAccount: 'Accedi per continuare su Noir & Co.',
  signIn: 'Accedi',
  signOut: 'Esci',
  createAccount: 'Crea account',
  forgotPassword: 'Password dimenticata?',
  browseAsGuest: 'Continua come ospite',
  emailAddress: 'Indirizzo email',
  password: 'Password',
  continueWithGoogle: 'Continua con Google',
  home: 'Home',
  catalogue: 'Catalogo',
  discover: 'Scopri',
  cart: 'Borsa',
  profile: 'Profilo',
  search: 'Cerca',
  searchProducts: 'Cerca capi, collezioni...',
  all: 'Tutti',
  sortBy: 'Ordina per',
  loadMore: 'Carica altri capi',
  addToBag: 'Aggiungi alla borsa',
  addedToBag: 'Aggiunto alla borsa',
  viewCart: 'Vedi borsa',
  checkout: 'Cassa',
  settings: 'Impostazioni',
  language: 'Lingua',
  theme: 'Tema',
  notifications: 'Notifiche',
  myOrders: 'I miei ordini',
  wishlist: 'Preferiti',
  shippingAddresses: 'Indirizzi di spedizione',
  paymentMethods: 'Metodi di pagamento',
  helpAndSupport: 'Aiuto e supporto',
  aboutNoir: 'Informazioni su Noir & Co.',
  privacySecurity: 'Privacy e sicurezza',
  logOut: 'Esci',
  account: 'Account',
  signInToViewProfile: 'Accedi per visualizzare i tuoi ordini',
  faq: 'Domande frequenti',
  contactUs: 'Contattaci',
  sendMessage: 'Invia messaggio',
  yourName: 'Il tuo nome',
  howCanWeHelp: 'Come possiamo aiutarti?',
  messageSent: 'Messaggio inviato con successo!',
  orderHistory: 'Cronologia ordini',
  orderDetails: 'Dettagli ordine',
  noOrders: 'Nessun ordine presente',
  status: 'Stato',
  total: 'Totale',
  noirVip: 'Club VIP Noir',
  loyaltyPoints: 'Punti fedeltà',
  vipTier: 'Livello VIP',
  redeem: 'Riscatta premio',
  rewards: 'Premi e vantaggi',
  communityFeed: 'Feed della community',
  videoCommerce: 'Video Commerce',
  stylist: 'Stilista IA',
  emptyBag: 'La tua borsa è vuota',
  continueShopping: 'Continua lo shopping',
  adminProducts: 'Prodotti amministratore',
  addProduct: 'Aggiungi prodotto',
  editProduct: 'Modifica prodotto',
  deleteProduct: 'Elimina prodotto',
  subscriptions: 'Abbonamenti',
  retry: 'Riprova',
  close: 'Chiudi',
  save: 'Salva',
  cancel: 'Annulla',
  loading: 'Caricamento...',
  admin: 'AMMINISTRAZIONE',
  adminDashboard: 'Dashboard Amministratore',
  manageOrders: 'Gestisci Ordini',
  manageProducts: 'Gestisci Prodotti',
  manageUsers: 'Gestisci Utenti',
  manageCategories: 'Gestisci Categorie',
  liveOrderTracking: 'Tracciamento Ordine Live & GPS',
  perksAndRewards: 'VANTAGGI & PREMI',
  clubVip: 'Club VIP Noir',
  loyaltyProgram: 'Programma Fedeltà',
  giftCards: 'Carte Regalo',
  referAFriend: 'Invita un Amico',
  aiStylist: 'Stilista IA',
  authenticityScanner: 'Scanner di Autenticità',
  reels: 'Reels',
  validateAddress: 'Verifica indirizzo con Google Maps',
  addressVerified: 'Indirizzo verificato con Google Maps',
  useCurrentLocation: 'Usa la mia posizione attuale',
  saveAddress: 'SALVA INDIRIZZO',
  pressBackAgainToExit: 'Premi di nuovo indietro per uscire da Noir',
  joinCommunity: 'Unisciti alla community di Noir & Co.',
  biometricPrompt: 'Scansiona impronta o viso per accedere a Noir & Co.',
  themeSystem: 'Predefinito di sistema',
  themeLight: 'Chiaro',
  themeDark: 'Scuro',
);

// ── Arabic ──────────────────────────────────────────────────────────
const _ar = _StringMap(
  welcomeBack: 'مرحباً بعودتك',
  signInToAccount: 'تسجيل الدخول للمتابعة إلى Noir & Co.',
  signIn: 'تسجيل الدخول',
  signOut: 'تسجيل الخروج',
  createAccount: 'إنشاء حساب',
  forgotPassword: 'هل نسيت كلمة المرور؟',
  browseAsGuest: 'المتابعة كضيف',
  emailAddress: 'البريد الإلكتروني',
  password: 'كلمة المرور',
  continueWithGoogle: 'المتابعة باستخدام Google',
  home: 'الرئيسية',
  catalogue: 'الكتالوج',
  discover: 'استكشاف',
  cart: 'الحقيبة',
  profile: 'الملف الشخصي',
  search: 'بحث',
  searchProducts: 'ابحث عن الملابس، المجموعات...',
  all: 'الكل',
  sortBy: 'ترتيب حسب',
  loadMore: 'عرض المزيد',
  addToBag: 'أضف إلى الحقيبة',
  addedToBag: 'تمت الإضافة إلى الحقيبة',
  viewCart: 'عرض الحقيبة',
  checkout: 'الدفع',
  settings: 'الإعدادات',
  language: 'اللغة',
  theme: 'المظهر',
  notifications: 'الإشعارات',
  myOrders: 'طلباتي',
  wishlist: 'قائمة الرغبات',
  shippingAddresses: 'عناوين الشحن',
  paymentMethods: 'طرق الدفع',
  helpAndSupport: 'المساعدة والدعم',
  aboutNoir: 'حول Noir & Co.',
  privacySecurity: 'الخصوصية والأمان',
  logOut: 'تسجيل الخروج',
  account: 'الحساب',
  signInToViewProfile: 'سجل الدخول لعرض ملفك الشخصي وطلباتك',
  faq: 'الأسئلة الشائعة',
  contactUs: 'اتصل بنا',
  sendMessage: 'إرسال الرسالة',
  yourName: 'اسمك',
  howCanWeHelp: 'كيف يمكننا مساعدتك؟',
  messageSent: 'تم إرسال الرسالة! سنرد عليك قريباً.',
  orderHistory: 'سجل الطلبات',
  orderDetails: 'تفاصيل الطلب',
  noOrders: 'لا توجد طلبات بعد',
  status: 'الحالة',
  total: 'الإجمالي',
  noirVip: 'نادي Noir VIP',
  loyaltyPoints: 'نقاط الولاء',
  vipTier: 'مستوى VIP',
  redeem: 'استبدال المكافأة',
  rewards: 'المكافآت والمزايا',
  communityFeed: 'مجتمع الموضة',
  videoCommerce: 'التسوق عبر الفيديو',
  stylist: 'منسق الأزياء الذكي',
  emptyBag: 'حقيبتك فارغة',
  continueShopping: 'متابعة التسوق',
  adminProducts: 'منتجات الإدارة',
  addProduct: 'إضافة منتج',
  editProduct: 'تعديل المنتج',
  deleteProduct: 'حذف المنتج',
  subscriptions: 'الاشتراكات',
  retry: 'إعادة المحاولة',
  close: 'إغلاق',
  save: 'حفظ',
  cancel: 'إلغاء',
  loading: 'جار التحميل...',
  admin: 'الإدارة',
  adminDashboard: 'لوحة تحكم الإدارة',
  manageOrders: 'إدارة الطلبات',
  manageProducts: 'إدارة المنتجات',
  manageUsers: 'إدارة المستخدمين',
  manageCategories: 'إدارة الفئات',
  liveOrderTracking: 'تتبع الطلب المباشر و GPS',
  perksAndRewards: 'المزايا والمكافآت',
  clubVip: 'نادي Noir VIP',
  loyaltyProgram: 'برنامج الولاء',
  giftCards: 'بطاقات الهدايا',
  referAFriend: 'دعوة صديق',
  aiStylist: 'منسق الأزياء الذكي',
  authenticityScanner: 'ماسح أصالة المنتج',
  reels: 'مقاطع الفيديو',
  validateAddress: 'التحقق من العنوان عبر Google Maps',
  addressVerified: 'تم التحقق من العنوان عبر Google Maps',
  useCurrentLocation: 'استخدام موقعي الحالي',
  saveAddress: 'حفظ العنوان',
  pressBackAgainToExit: 'اضغط رجوع مرة أخرى للخروج من Noir',
  joinCommunity: 'انضم إلى مجتمع Noir & Co.',
  biometricPrompt: 'امسح بصمة الإصبع أو الوجه لفتح Noir & Co.',
  themeSystem: 'افتراضي النظام',
  themeLight: 'فاتح',
  themeDark: 'داكن',
);
