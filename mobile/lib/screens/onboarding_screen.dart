import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';

import '../providers/settings_provider.dart';
import '../l10n/app_strings.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _controller = PageController();
  int _currentPage = 0;

  Future<void> _completeOnboarding() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('onboarding_complete', true);
    if (mounted) {
      Navigator.of(context).pushReplacementNamed('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    final brand = context.watch<SettingsProvider>().brandName;
    final List<_OnboardingPage> pages = [
      _OnboardingPage(
        image: 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?auto=format&fit=crop&w=800&q=80',
        title: 'Welcome to\n$brand',
        subtitle: 'Curated fashion for the modern individual.\nMinimal design, maximum statement.',
      ),
      _OnboardingPage(
        image: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=800&q=80',
        title: 'Discover\nThe Collection',
        subtitle: 'Browse our carefully curated catalogue\nof premium essentials and accessories.',
      ),
      _OnboardingPage(
        image: 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?auto=format&fit=crop&w=800&q=80',
        title: 'Shop With\nConfidence',
        subtitle: 'Secure checkout, fast delivery,\nand effortless returns.',
      ),
    ];
    return Scaffold(
      backgroundColor: Theme.of(context).colorScheme.primary,
      body: Stack(
        children: [
          // Page View
          PageView.builder(
            controller: _controller,
            itemCount: pages.length,
            onPageChanged: (i) => setState(() => _currentPage = i),
            itemBuilder: (context, index) {
              final page = pages[index];
              return Stack(
                fit: StackFit.expand,
                children: [
                  // Background Image with gradient overlay
                  CachedNetworkImage(
                    imageUrl: page.image,
                    fit: BoxFit.cover,
                    errorWidget: (_, _, _) =>
                        Container(color: Theme.of(context).colorScheme.primary),
                  ),
                  // Gradient overlay
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.black.withValues(alpha: 0.1),
                          Colors.black.withValues(alpha: 0.3),
                          Colors.black.withValues(alpha: 0.85),
                        ],
                        stops: [0.0, 0.5, 1.0],
                      ),
                    ),
                  ),
                  // Content
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 32),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.end,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          page.title,
                          style: GoogleFonts.urbanist(
                            fontSize: 38,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            height: 1.15,
                            letterSpacing: -0.5,
                          ),
                        ),
                        SizedBox(height: 16),
                        Text(
                          page.subtitle,
                          style: GoogleFonts.urbanist(
                            fontSize: 16,
                            fontWeight: FontWeight.w400,
                            color: Colors.white70,
                            height: 1.5,
                          ),
                        ),
                        SizedBox(height: 120),
                      ],
                    ),
                  ),
                ],
              );
            },
          ),

          // Bottom Controls
          Positioned(
            left: 32,
            right: 32,
            bottom: 48,
            child: Column(
              children: [
                // Page Indicator
                SmoothPageIndicator(
                  controller: _controller,
                  count: pages.length,
                  effect: ExpandingDotsEffect(
                    dotColor: Colors.white30,
                    activeDotColor: Colors.white,
                    dotHeight: 6,
                    dotWidth: 6,
                    expansionFactor: 4,
                    spacing: 6,
                  ),
                ),
                SizedBox(height: 32),

                // Button
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: _currentPage == pages.length - 1
                      ? ElevatedButton(
                          onPressed: _completeOnboarding,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: Theme.of(context).colorScheme.primary,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            elevation: 0,
                          ),
                          child: Text(
                            AppStrings.of(context).getStarted.toUpperCase(),
                            style: GoogleFonts.urbanist(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 2.0,
                            ),
                          ),
                        )
                      : ElevatedButton(
                          onPressed: () => _controller.nextPage(
                            duration: Duration(milliseconds: 400),
                            curve: Curves.easeInOut,
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: Theme.of(context).colorScheme.primary,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            elevation: 0,
                          ),
                          child: Text(
                            AppStrings.of(context).next.toUpperCase(),
                            style: GoogleFonts.urbanist(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 2.0,
                            ),
                          ),
                        ),
                ),
                SizedBox(height: 12),

                // Skip
                if (_currentPage < pages.length - 1)
                  TextButton(
                    onPressed: _completeOnboarding,
                    child: Text(
                      AppStrings.of(context).skip,
                      style: GoogleFonts.urbanist(
                        color: Colors.white54,
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _OnboardingPage {
  final String image;
  final String title;
  final String subtitle;

  _OnboardingPage({
    required this.image,
    required this.title,
    required this.subtitle,
  });
}
