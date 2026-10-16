import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:firebase_core/firebase_core.dart';

import 'api_client.dart';
import 'providers/auth_provider.dart';
import 'providers/cart_provider.dart';
import 'providers/wishlist_provider.dart';
import 'providers/locale_provider.dart';
import 'providers/theme_provider.dart';
import 'providers/ratings_provider.dart';
import 'providers/currency_provider.dart';
import 'theme.dart';
import 'screens/onboarding_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/auth/forgot_password_screen.dart';
import 'screens/store/main_layout.dart';
import 'screens/store/cart_screen.dart';
import 'screens/store/checkout_screen.dart';
import 'screens/store/order_success_screen.dart';
import 'screens/store/my_orders_screen.dart';
import 'screens/store/order_detail_screen.dart';
import 'screens/store/wishlist_screen.dart';
import 'screens/profile/shipping_addresses_screen.dart';
import 'screens/profile/notifications_screen.dart';
import 'screens/profile/settings_screen.dart';
import 'screens/profile/help_support_screen.dart';
import 'screens/profile/payment_methods_screen.dart';
import 'screens/profile/settings/about_screen.dart';
import 'screens/profile/settings/language_screen.dart';
import 'screens/admin/admin_dashboard_screen.dart';
import 'screens/admin/admin_orders_screen.dart';
import 'screens/admin/admin_products_screen.dart';
import 'screens/admin/admin_users_screen.dart';
import 'screens/admin/admin_categories_screen.dart';
import 'screens/profile/referral_screen.dart';
import 'screens/store/product_reviews_screen.dart';
import 'screens/profile/loyalty_program_screen.dart';
import 'screens/profile/noir_club_screen.dart';
import 'screens/profile/subscriptions_screen.dart';
import 'screens/store/order_tracking_screen.dart';
import 'screens/store/ai_stylist_screen.dart';
import 'screens/store/authenticity_scanner_screen.dart';
import 'screens/store/gift_card_screen.dart';
import 'screens/store/video_commerce_screen.dart';
import 'screens/social/community_feed_screen.dart';
import 'screens/app_lock_wrapper.dart';
import 'screens/error/offline_screen.dart';
import 'screens/error/server_error_screen.dart';
import 'screens/error/not_found_screen.dart';

import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'providers/settings_provider.dart';

import 'package:flutter/foundation.dart' show kIsWeb, defaultTargetPlatform, TargetPlatform;

String getBaseUrl([SharedPreferences? prefs]) {
  final customUrl = prefs?.getString('custom_api_base_url');
  if (customUrl != null && customUrl.trim().isNotEmpty) {
    return customUrl.trim();
  }

  final envUrl = dotenv.env['API_BASE_URL'];
  if (envUrl != null && envUrl.isNotEmpty) {
    return envUrl;
  }

  if (kIsWeb || defaultTargetPlatform == TargetPlatform.windows) {
    return 'http://127.0.0.1/noir/public';
  }
  
  return 'http://192.168.164.108/noir/public';
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  try {
    await dotenv.load(fileName: ".env");
  } catch (e) {
    debugPrint("Could not load .env file: $e");
  }

  try {
    await Firebase.initializeApp();
    debugPrint("Firebase initialized successfully");
  } catch (e) {
    debugPrint("Firebase initialization notice: $e");
  }

  final prefs = await SharedPreferences.getInstance();
  final showOnboarding = !(prefs.getBool('onboarding_complete') ?? false);

  final api = NoirApiClient(baseUrl: getBaseUrl(prefs));

  final authProvider = AuthProvider(api: api);
  final cartProvider = CartProvider();
  final wishlistProvider = WishlistProvider(api);
  final localeProvider = LocaleProvider();

  cartProvider.updateApi(api);
  wishlistProvider.updateApi(api);

  await authProvider.init();
  await cartProvider.init(authProvider.user?.id.toString());
  if (authProvider.isAuthenticated) {
    wishlistProvider.syncWithCloud();
  }
  
  authProvider.addListener(() {
    final uId = authProvider.user?.id.toString();
    cartProvider.switchUser(uId);
    if (authProvider.isAuthenticated) {
      wishlistProvider.syncWithCloud();
    }
  });

  await localeProvider.init();

  final themeProvider = ThemeProvider();
  final ratingsProvider = RatingsProvider();
  final settingsProvider = SettingsProvider(api: api);
  final currencyProvider = CurrencyProvider();



  runApp(
    NoirApp(
      authProvider: authProvider,
      cartProvider: cartProvider,
      wishlistProvider: wishlistProvider,
      localeProvider: localeProvider,
      themeProvider: themeProvider,
      ratingsProvider: ratingsProvider,
      settingsProvider: settingsProvider,
      currencyProvider: currencyProvider,
      showOnboarding: showOnboarding,
    ),
  );
}

class NoirApp extends StatelessWidget {
  final AuthProvider authProvider;
  final CartProvider cartProvider;
  final WishlistProvider wishlistProvider;
  final LocaleProvider localeProvider;
  final ThemeProvider themeProvider;
  final RatingsProvider ratingsProvider;
  final SettingsProvider settingsProvider;
  final CurrencyProvider currencyProvider;
  final bool showOnboarding;

  const NoirApp({
    super.key,
    required this.authProvider,
    required this.cartProvider,
    required this.wishlistProvider,
    required this.localeProvider,
    required this.themeProvider,
    required this.ratingsProvider,
    required this.settingsProvider,
    required this.currencyProvider,
    required this.showOnboarding,
  });

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: authProvider),
        ChangeNotifierProvider.value(value: cartProvider),
        ChangeNotifierProvider.value(value: wishlistProvider),
        ChangeNotifierProvider.value(value: localeProvider),
        ChangeNotifierProvider.value(value: themeProvider),
        ChangeNotifierProvider.value(value: ratingsProvider),
        ChangeNotifierProvider.value(value: settingsProvider),
        ChangeNotifierProvider.value(value: currencyProvider),
        Provider.value(value: authProvider.api),
      ],
      child: Consumer3<LocaleProvider, ThemeProvider, SettingsProvider>(
        builder: (context, locale, themeProv, settingsProv, _) => MaterialApp(
        title: settingsProv.storeName,
        theme: NoirTheme.lightTheme,
        darkTheme: NoirTheme.darkTheme,
        themeMode: themeProv.currentMode,
        debugShowCheckedModeBanner: false,
        locale: ['yo', 'ha', 'ig'].contains(locale.locale.languageCode) ? Locale('en', 'US') : locale.locale,
        localizationsDelegates: [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        supportedLocales: [
          Locale('en', 'US'),
          Locale('en', 'GB'),
          Locale('fr'),
          Locale('es'),
          Locale('de'),
          Locale('it'),
          Locale('ar'),
          Locale('yo'),
          Locale('ha'),
          Locale('ig'),
        ],
        localeResolutionCallback: (locale, supportedLocales) {
          for (var supportedLocale in supportedLocales) {
            if (supportedLocale.languageCode == locale?.languageCode) {
              return supportedLocale;
            }
          }
          return supportedLocales.first; // Fallback to en_US
        },
        initialRoute: showOnboarding ? '/onboarding' : '/home',
        routes: {
          '/onboarding':       (context) => OnboardingScreen(),
          '/login':            (context) => LoginScreen(),
          '/register':         (context) => RegisterScreen(),
          '/forgot-password':  (context) => ForgotPasswordScreen(),
          '/home':             (context) => AppLockWrapper(child: MainLayout()),
          '/cart':             (context) => CartScreen(),
          '/checkout':         (context) => CheckoutScreen(),
          '/my-orders':        (context) => MyOrdersScreen(),
          '/wishlist':         (context) => WishlistScreen(),
          '/shipping-addresses':(context) => ShippingAddressesScreen(),
          '/notifications':    (context) => NotificationsScreen(),
          '/settings':         (context) => SettingsScreen(),
          '/help-support':     (context) => HelpSupportScreen(),
          '/payment-methods':  (context) => PaymentMethodsScreen(),
          '/about':            (context) => AboutScreen(),
          '/language':         (context) => LanguageScreen(),
          '/admin-dashboard':  (context) => AdminDashboardScreen(),
          '/admin-orders':     (context) => AdminOrdersScreen(),
          '/admin-products':   (context) => AdminProductsScreen(),
          '/admin-users':      (context) => AdminUsersScreen(),
          '/admin-categories': (context) => AdminCategoriesScreen(),
          '/admin-product-form': (context) => DummyScreen(),
          '/order-tracking': (context) => OrderTrackingScreen(),
          // '/product-reviews': (context) => ProductReviewsScreen(),
          '/referral': (context) => ReferralScreen(),
          '/loyalty-program': (context) => LoyaltyProgramScreen(),
          '/noir-club': (context) => NoirClubScreen(),
          '/subscriptions': (context) => SubscriptionsScreen(),
          '/ai-stylist': (context) => AiStylistScreen(),
          '/authenticity-scanner': (context) => AuthenticityScannerScreen(),
          '/gift-cards': (context) => GiftCardScreen(),
          '/video-commerce': (context) => VideoCommerceScreen(),
          '/community-feed': (context) => CommunityFeedScreen(),
          '/offline': (context) => const OfflineScreen(),
          '/server-error': (context) => const ServerErrorScreen(),
          '/not-found': (context) => const NotFoundScreen(),
        },
        onUnknownRoute: (settings) => MaterialPageRoute(
          builder: (_) => NotFoundScreen(routeName: settings.name),
        ),
        onGenerateRoute: (settings) {

          // Handle /order-success with arguments
          if (settings.name == '/order-success') {
            final args = settings.arguments as Map<String, dynamic>? ?? {};
            return MaterialPageRoute(
              builder: (_) => OrderSuccessScreen(
                orderNumber: args['orderNumber'] ?? '',
                total: (args['total'] as num?)?.toDouble() ?? 0,
                currency: args['currency'] ?? 'NGN',
              ),
            );
          }
          // Handle /order-detail/:number with arguments
          if (settings.name == '/order-detail') {
            final args = settings.arguments as Map<String, dynamic>? ?? {};
            return MaterialPageRoute(
              builder: (_) => OrderDetailScreen(orderNumber: args['orderNumber'] ?? ''),
            );
          }
          // Handle /product-reviews with arguments
          if (settings.name == '/product-reviews') {
            final args = settings.arguments as Map<String, dynamic>? ?? {};
            return MaterialPageRoute(
              builder: (_) => ProductReviewsScreen(productId: args['productId'] ?? 0),
            );
          }
          return null;
        },
      ),
      ),
    );
  }
}


class DummyScreen extends StatelessWidget { const DummyScreen({super.key}); @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('Coming Soon'))); }
