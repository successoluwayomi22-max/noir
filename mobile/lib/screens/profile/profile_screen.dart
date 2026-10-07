import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../providers/currency_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import '../auth/login_screen.dart';
import 'edit_profile_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  @override
  void initState() {
    super.initState();
    // Auto-refresh profile data if logged in but user data is missing
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final auth = context.read<AuthProvider>();
      if (auth.isLoggedIn && auth.user == null) {
        auth.refreshProfile();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    final user = auth.user;
    final isGuest = auth.isGuest || user == null;

    final avatarUrl = user?.avatar;
    String fullAvatarUrl = '';
    if (avatarUrl != null && avatarUrl.isNotEmpty) {
      if (avatarUrl.startsWith('http')) {
        fullAvatarUrl = avatarUrl;
      } else {
        final base = context.read<NoirApiClient>().baseUrl;
        fullAvatarUrl = '$base$avatarUrl';
      }
    }

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(
          AppStrings.of(context).profile.toUpperCase(),
          style: GoogleFonts.urbanist(
            fontSize: 16,
            fontWeight: FontWeight.w700,
            letterSpacing: 2,
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => auth.refreshProfile(),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(24),
          children: [
          // Header
          if (isGuest)
            Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.account_circle_outlined, size: 64, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
                  const SizedBox(height: 16),
                  Text(
                    AppStrings.of(context).signInToViewProfile,
                    style: GoogleFonts.urbanist(fontSize: 16),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => LoginScreen()),
                    ),
                    child: Text(AppStrings.of(context).signIn.toUpperCase()),
                  ),
                ],
              ),
            )
          else
            GestureDetector(
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const EditProfileScreen()),
              ),
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: Theme.of(context).dividerColor.withValues(alpha: 0.1),
                  ),
                ),
                child: Row(
                  children: [
                    Stack(
                      children: [
                        Container(
                          width: 68,
                          height: 68,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.3), width: 2),
                          ),
                          child: ClipOval(
                            child: fullAvatarUrl.isNotEmpty
                                ? Image.network(
                                    fullAvatarUrl,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, _, _) => _buildFallbackAvatar(user.name),
                                  )
                                : _buildFallbackAvatar(user.name),
                          ),
                        ),
                        Positioned(
                          bottom: 0,
                          right: 0,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              color: NoirTheme.primary,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.edit, size: 12, color: Colors.white),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  user.name,
                                  style: GoogleFonts.urbanist(
                                    fontSize: 20,
                                    fontWeight: FontWeight.w700,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              Icon(Icons.chevron_right, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.4)),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            user.email,
                            style: GoogleFonts.urbanist(
                              fontSize: 13,
                              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  user.vipTier.toUpperCase(),
                                  style: GoogleFonts.urbanist(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: Theme.of(context).colorScheme.primary,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                '${user.loyaltyPoints} PTS',
                                style: GoogleFonts.urbanist(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.8),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 32),

          // Admin Dashboard (only visible to admins)
          if (!isGuest && user.isAdmin) ...[
            _buildMenuSection(AppStrings.of(context).admin, [
              _buildMenuItem(Icons.dashboard_outlined, AppStrings.of(context).adminDashboard, () {
                Navigator.pushNamed(context, '/admin-dashboard');
              }),
              _buildMenuItem(Icons.receipt_long_outlined, AppStrings.of(context).manageOrders, () {
                Navigator.pushNamed(context, '/admin-orders');
              }),
              _buildMenuItem(Icons.inventory_2_outlined, AppStrings.of(context).manageProducts, () {
                Navigator.pushNamed(context, '/admin-products');
              }),
            ]),
          ],

          // Menu
          if (!isGuest)
            _buildMenuSection(AppStrings.of(context).account.toUpperCase(), [
              _buildMenuItem(Icons.person_outline_rounded, 'Edit Profile & Photo', () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const EditProfileScreen()),
                );
              }),
              _buildMenuItem(Icons.shopping_bag_outlined, AppStrings.of(context).myOrders, () {
                Navigator.pushNamed(context, '/my-orders');
              }),
              _buildMenuItem(Icons.favorite_border_rounded, AppStrings.of(context).wishlist, () {
                Navigator.pushNamed(context, '/wishlist');
              }),
              _buildMenuItem(Icons.location_on_outlined, AppStrings.of(context).shippingAddresses, () {
                Navigator.pushNamed(context, '/shipping-addresses');
              }),
              _buildMenuItem(Icons.radar_rounded, AppStrings.of(context).liveOrderTracking, () {
                Navigator.pushNamed(context, '/order-tracking');
              }),
              _buildMenuItem(Icons.payment_outlined, AppStrings.of(context).paymentMethods, () {
                Navigator.pushNamed(context, '/payment-methods');
              }),
            ]),

          _buildMenuSection(AppStrings.of(context).perksAndRewards, [
            _buildMenuItem(Icons.workspace_premium_rounded, AppStrings.of(context).clubVip(context.read<SettingsProvider>().brandName), () {
              Navigator.pushNamed(context, '/noir-club');
            }),
            _buildMenuItem(Icons.star_rounded, AppStrings.of(context).loyaltyProgram, () {
              Navigator.pushNamed(context, '/loyalty-program');
            }),
            _buildMenuItem(Icons.card_giftcard_rounded, AppStrings.of(context).giftCards, () {
              Navigator.pushNamed(context, '/gift-cards');
            }),
            _buildMenuItem(Icons.auto_awesome_rounded, AppStrings.of(context).referAFriend, () {
              Navigator.pushNamed(context, '/referral');
            }),
            _buildMenuItem(Icons.all_inclusive_rounded, AppStrings.of(context).subscriptions, () {
              Navigator.pushNamed(context, '/subscriptions');
            }),
          ]),

          _buildMenuSection(AppStrings.of(context).discover.toUpperCase(), [
            _buildMenuItem(Icons.face_retouching_natural_rounded, AppStrings.of(context).aiStylist(context.read<SettingsProvider>().brandName), () {
              Navigator.pushNamed(context, '/ai-stylist');
            }),
            _buildMenuItem(Icons.verified_user_outlined, AppStrings.of(context).authenticityScanner, () {
              Navigator.pushNamed(context, '/authenticity-scanner');
            }),
            _buildMenuItem(Icons.video_library_rounded, AppStrings.of(context).videoCommerce, () {
              Navigator.pushNamed(context, '/video-commerce');
            }),
            _buildMenuItem(Icons.groups_rounded, AppStrings.of(context).communityFeed, () {
              Navigator.pushNamed(context, '/community-feed');
            }),
          ]),

          _buildMenuSection(AppStrings.of(context).settings.toUpperCase(), [
            Consumer<CurrencyProvider>(
              builder: (context, currencyProv, _) {
                return _buildMenuItem(
                  Icons.currency_exchange_outlined,
                  'Currency (${currencyProv.currencyCode} ${currencyProv.currencySymbol})',
                  () {
                    showModalBottomSheet(
                      context: context,
                      backgroundColor: Theme.of(context).colorScheme.surface,
                      shape: const RoundedRectangleBorder(
                        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                      ),
                      builder: (modalCtx) {
                        return SafeArea(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Padding(
                                padding: const EdgeInsets.all(16.0),
                                child: Text(
                                  'Select Currency',
                                  style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.bold),
                                ),
                              ),
                              ListTile(
                                title: const Text('NGN - Nigerian Naira (₦)'),
                                trailing: currencyProv.currencyCode == 'NGN' ? const Icon(Icons.check, color: Colors.green) : null,
                                onTap: () {
                                  currencyProv.setCurrency('NGN');
                                  Navigator.pop(modalCtx);
                                },
                              ),
                              ListTile(
                                title: const Text('USD - US Dollar (\$)'),
                                trailing: currencyProv.currencyCode == 'USD' ? const Icon(Icons.check, color: Colors.green) : null,
                                onTap: () {
                                  currencyProv.setCurrency('USD');
                                  Navigator.pop(modalCtx);
                                },
                              ),
                              ListTile(
                                title: const Text('EUR - Euro (€)'),
                                trailing: currencyProv.currencyCode == 'EUR' ? const Icon(Icons.check, color: Colors.green) : null,
                                onTap: () {
                                  currencyProv.setCurrency('EUR');
                                  Navigator.pop(modalCtx);
                                },
                              ),
                              ListTile(
                                title: const Text('GBP - British Pound (£)'),
                                trailing: currencyProv.currencyCode == 'GBP' ? const Icon(Icons.check, color: Colors.green) : null,
                                onTap: () {
                                  currencyProv.setCurrency('GBP');
                                  Navigator.pop(modalCtx);
                                },
                              ),
                              const SizedBox(height: 16),
                            ],
                          ),
                        );
                      },
                    );
                  },
                );
              },
            ),
            _buildMenuItem(Icons.notifications_none_rounded, AppStrings.of(context).notifications, () {
              Navigator.pushNamed(context, '/notifications');
            }),
            _buildMenuItem(Icons.settings_outlined, AppStrings.of(context).settings, () {
              Navigator.pushNamed(context, '/settings');
            }),
            _buildMenuItem(Icons.help_outline_rounded, AppStrings.of(context).helpAndSupport, () {
              Navigator.pushNamed(context, '/help-support');
            }),
            _buildMenuItem(Icons.info_outline_rounded, AppStrings.of(context).aboutStore(context.read<SettingsProvider>().brandName), () {
              Navigator.pushNamed(context, '/about');
            }),
          ]),

          SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            height: 56,
            child: OutlinedButton(
              style: OutlinedButton.styleFrom(
                foregroundColor: Theme.of(context).colorScheme.error,
                side: BorderSide(color: Theme.of(context).colorScheme.error.withValues(alpha: 0.5)),
              ),
              onPressed: () {
                auth.logout();
                Navigator.of(context).pushReplacementNamed('/login');
              },
              child: Text(
                AppStrings.of(context).logOut.toUpperCase(),
                style: GoogleFonts.urbanist(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 1.0,
                ),
              ),
            ),
          ),
          SizedBox(height: 40),
        ],
      ),
    ),
  );
}

  Widget _buildMenuSection(String title, List<Widget> items) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: GoogleFonts.urbanist(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
            letterSpacing: 1.5,
          ),
        ),
        SizedBox(height: 12),
        Container(
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Theme.of(context).colorScheme.outline),
          ),
          child: Column(children: items),
        ),
        SizedBox(height: 32),
      ],
    );
  }

  Widget _buildMenuItem(IconData icon, String title, VoidCallback onTap) {
    return Material(
      color: Colors.transparent,
      child: ListTile(
        leading: Icon(icon, color: Theme.of(context).colorScheme.onSurface, size: 22),
        title: Text(
          title,
          style: GoogleFonts.urbanist(fontSize: 15, fontWeight: FontWeight.w500),
        ),
        onTap: onTap,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
    );
  }

  Widget _buildFallbackAvatar(String name) {
    return Container(
      color: Theme.of(context).colorScheme.primary,
      child: Center(
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : 'U',
          style: GoogleFonts.urbanist(
            fontSize: 26,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}
