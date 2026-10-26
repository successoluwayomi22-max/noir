import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../providers/cart_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import 'catalogue_screen.dart';
import 'cart_screen.dart';
import 'ai_stylist_screen.dart';
import 'video_commerce_screen.dart';
import '../social/community_feed_screen.dart';
import '../profile/profile_screen.dart';
import 'package:google_fonts/google_fonts.dart';

class MainLayout extends StatefulWidget {
  const MainLayout({super.key});

  @override
  State<MainLayout> createState() => _MainLayoutState();
}

class _MainLayoutState extends State<MainLayout> {
  int _currentIndex = 0;
  DateTime? _lastBackPressTime;

  @override
  Widget build(BuildContext context) {
    final cartCount = context.watch<CartProvider>().itemCount;
    final strings = AppStrings.of(context);
    final brandName = context.watch<SettingsProvider>().brandName;

    final screens = [
      CatalogueScreen(),
      VideoCommerceScreen(
        isActive: _currentIndex == 1,
        onBackPressed: () => setState(() => _currentIndex = 0),
      ),
      const CommunityFeedScreen(),
      CartScreen(),
      ProfileScreen(),
    ];

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) return;
        if (_currentIndex != 0) {
          setState(() => _currentIndex = 0);
          return;
        }

        final now = DateTime.now();
        if (_lastBackPressTime == null || now.difference(_lastBackPressTime!) > const Duration(seconds: 2)) {
          _lastBackPressTime = now;
          ScaffoldMessenger.of(context).removeCurrentSnackBar();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.shield_outlined, color: Color(0xFFC49A45), size: 18),
                  const SizedBox(width: 10),
                  Text(
                    strings.pressBackAgainToExit(brandName),
                    style: GoogleFonts.urbanist(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.3,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
              backgroundColor: const Color(0xFF1B1D19),
              duration: const Duration(seconds: 2),
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
                side: const BorderSide(color: Color(0xFF2C3026)),
              ),
              margin: const EdgeInsets.symmetric(horizontal: 48, vertical: 16),
            ),
          );
        } else {
          SystemNavigator.pop();
        }
      },
      child: Scaffold(
        extendBody: true,
        body: IndexedStack(index: _currentIndex, children: screens),
        bottomNavigationBar: _currentIndex == 1
            ? null
            : SafeArea(
                minimum: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: Theme.of(context).brightness == Brightness.light ? Theme.of(context).colorScheme.primary : Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.circular(26),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.18),
                        blurRadius: 28,
                        offset: const Offset(0, 14),
                      ),
                    ],
                  ),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
                    child: Row(
                      children: [
                        _buildNavItem(0, Icons.grid_view_rounded, strings.catalogue),
                        _buildNavItem(1, Icons.play_circle_fill_rounded, strings.reels),
                        _buildNavItem(2, Icons.groups_rounded, strings.communityFeed),
                        _buildNavItem(
                          3,
                          Icons.shopping_bag_rounded,
                          strings.cart,
                          badge: cartCount,
                        ),
                        _buildNavItem(4, Icons.person_rounded, strings.profile),
                      ],
                    ),
                  ),
                ),
              ),
        floatingActionButton: _currentIndex == 0 ? Padding(
          padding: const EdgeInsets.only(bottom: 90.0), // Above bottom nav
          child: FloatingActionButton.extended(
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const AiStylistScreen()),
            ),
            backgroundColor: NoirTheme.primary,
            icon: const Icon(Icons.auto_awesome_rounded, color: Colors.white),
            label: Text(strings.aiStylist(brandName), style: GoogleFonts.urbanist(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ) : null,
      ),
    );
  }

  Widget _buildNavItem(
    int index,
    IconData icon,
    String label, {
    int badge = 0,
  }) {
    final selected = _currentIndex == index;

    return Expanded(
      flex: selected ? 2 : 1,
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: () {
          if (index == 4 && !context.read<AuthProvider>().isLoggedIn) {
            Navigator.pushNamed(context, '/login');
            return;
          }
          setState(() => _currentIndex = index);
        },
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 240),
          curve: Curves.easeOutCubic,
          height: 48,
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
          decoration: BoxDecoration(
            color: selected
                ? (Theme.of(context).brightness == Brightness.light ? Colors.white : Theme.of(context).colorScheme.primary)
                : Colors.transparent,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Stack(
            alignment: Alignment.center,
            clipBehavior: Clip.none,
            children: [
              Center(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Icon(
                      icon,
                      size: 19,
                      color: selected
                          ? (Theme.of(context).brightness == Brightness.light ? Theme.of(context).colorScheme.primary : Theme.of(context).colorScheme.onPrimary)
                          : Colors.white70,
                    ),
                    if (selected) ...[
                      const SizedBox(width: 5),
                      Flexible(
                        child: Text(
                          label,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: Theme.of(context).brightness == Brightness.light ? Theme.of(context).colorScheme.primary : Theme.of(context).colorScheme.onPrimary,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.2,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              if (badge > 0)
                Positioned(
                  top: -2,
                  right: selected ? 6 : 14,
                  child: Container(
                    constraints: const BoxConstraints(
                      minWidth: 20,
                      minHeight: 20,
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD4AF37),
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.35),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      badge > 99 ? '99+' : '$badge',
                      style: const TextStyle(
                        color: Colors.black,
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                        height: 1.0,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
