import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../api_client.dart';
import '../../models/product.dart';
import '../../providers/settings_provider.dart';
import '../../providers/currency_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import 'product_detail_screen.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final TextEditingController _controller = TextEditingController();
  final ImagePicker _picker = ImagePicker();
  final List<String> _quickTerms = [
    'Hoodies',
    'Sneakers',
    'Tees',
    'Bags',
    'Accessories',
  ];
  List<Product> _results = [];
  List<Map<String, dynamic>> _visualMatches = [];
  bool _loading = false;
  bool _isVisualSearch = false;
  String? _lastQuery;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _startVisualSearch(ImageSource source) async {
    try {
      final XFile? file = await _picker.pickImage(
        source: source,
        maxWidth: 1024,
        maxHeight: 1024,
        imageQuality: 80,
      );
      if (file == null) return;

      setState(() {
        _loading = true;
        _isVisualSearch = true;
        _visualMatches = [];
        _results = [];
      });

      if (!mounted) return;
      final api = context.read<NoirApiClient>();
      final bytes = await file.readAsBytes();
      final b64 = base64Encode(bytes);
      final res = await api.performVisualSearch(imageBase64: b64);

      if (mounted) {
        setState(() {
          _loading = false;
          if (res['matches'] != null) {
            _visualMatches = List<Map<String, dynamic>>.from(res['matches']);
          }
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _loading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Visual match search failed: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _search(String query) async {
    final term = query.trim();
    if (term.isEmpty) return;
    setState(() {
      _loading = true;
      _isVisualSearch = false;
      _visualMatches = [];
      _lastQuery = term;
    });

    try {
      final api = context.read<NoirApiClient>();
      final data = await api.getProducts(search: term, page: 1, sort: 'newest');
      if (!mounted) return;
      setState(() {
        _results = data.products;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Search failed: $e'),
          backgroundColor: Theme.of(context).colorScheme.error,
        ),
      );
    }
  }

  String _formatPrice(dynamic price) {
    try {
      return context.watch<CurrencyProvider>().format(price);
    } catch (_) {
      final p = double.tryParse(price.toString()) ?? 0;
      return '₦${p.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}';
    }
  }

  void _startVoiceSearch() {
    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF161814),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            bool isListening = true;
            String text = "Listening...";
            
            // Simulate voice recognition
            Future.delayed(const Duration(seconds: 2), () {
              if (mounted) {
                setModalState(() {
                  isListening = false;
                  text = "Black leather jacket";
                });
                Future.delayed(const Duration(milliseconds: 800), () {
                  if (ctx.mounted && Navigator.canPop(ctx)) Navigator.pop(ctx);
                  _controller.text = "Black leather jacket";
                  _search("Black leather jacket");
                });
              }
            });

            return SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(32.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'AI Voice Concierge',
                      style: GoogleFonts.urbanist(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                        letterSpacing: 1.5,
                        color: NoirTheme.primary,
                      ),
                    ),
                    const SizedBox(height: 32),
                    // Pulsing Mic Indicator
                    TweenAnimationBuilder<double>(
                      tween: Tween(begin: 1.0, end: isListening ? 1.4 : 1.0),
                      duration: const Duration(milliseconds: 1000),
                      curve: Curves.easeInOutBack,
                      builder: (context, val, child) {
                        return Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: NoirTheme.primary.withValues(alpha: isListening ? 0.3 : 0.1),
                            boxShadow: [
                              if (isListening)
                                BoxShadow(
                                  color: NoirTheme.primary.withValues(alpha: 0.5),
                                  blurRadius: 30 * val,
                                  spreadRadius: 10 * val,
                                ),
                            ],
                          ),
                          child: child,
                        );
                      },
                      onEnd: () {
                        // Loop animation if still listening
                        if (isListening && mounted) {
                          setModalState(() {});
                        }
                      },
                      child: Center(
                        child: Icon(
                          Icons.mic,
                          color: Colors.white,
                          size: 36,
                        ),
                      ),
                    ),
                    const SizedBox(height: 40),
                    Text(
                      text,
                      style: GoogleFonts.urbanist(
                        fontSize: 22,
                        fontWeight: FontWeight.w500,
                        color: Colors.white,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            );
          }
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: CustomScrollView(
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          slivers: [
            SliverPadding(
              padding: EdgeInsets.fromLTRB(22, 18, 22, 12),
              sliver: SliverToBoxAdapter(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          AppStrings.of(context).searchHint,
                          style: GoogleFonts.urbanist(
                            fontSize: 30,
                            fontWeight: FontWeight.w800,
                            color: Theme.of(context).colorScheme.onSurface,
                            height: 1.05,
                          ),
                        ),
                        // Visual Search Camera Button
                        InkWell(
                          onTap: () {
                            showModalBottomSheet(
                              context: context,
                              backgroundColor: const Color(0xFF161814),
                              shape: const RoundedRectangleBorder(
                                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                              ),
                              builder: (ctx) => SafeArea(
                                child: Padding(
                                  padding: const EdgeInsets.all(20),
                                  child: Column(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Text(
                                        '📸 AI Visual Search ("Snap & Match")',
                                        style: GoogleFonts.urbanist(
                                          fontWeight: FontWeight.w800,
                                          fontSize: 18,
                                          color: NoirTheme.primary,
                                        ),
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        'Upload or photograph any luxury look to match against the NOIR collection.',
                                        textAlign: TextAlign.center,
                                        style: GoogleFonts.urbanist(fontSize: 13, color: Colors.white70),
                                      ),
                                      const SizedBox(height: 18),
                                      ListTile(
                                        leading: const Icon(Icons.camera_alt_rounded, color: Colors.white),
                                        title: Text('Take a Photo', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700)),
                                        onTap: () {
                                          Navigator.pop(ctx);
                                          _startVisualSearch(ImageSource.camera);
                                        },
                                      ),
                                      ListTile(
                                        leading: const Icon(Icons.photo_library_rounded, color: Colors.white),
                                        title: Text('Choose from Gallery', style: GoogleFonts.urbanist(fontWeight: FontWeight.w700)),
                                        onTap: () {
                                          Navigator.pop(ctx);
                                          _startVisualSearch(ImageSource.gallery);
                                        },
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            );
                          },
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: NoirTheme.primary.withValues(alpha: 0.15),
                              border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.4)),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              children: [
                                Icon(Icons.camera_alt_rounded, color: NoirTheme.primary, size: 18),
                                const SizedBox(width: 6),
                                Text(
                                  'Snap & Match',
                                  style: GoogleFonts.urbanist(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 12,
                                    color: NoirTheme.primary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 16),
                    TextField(
                      controller: _controller,
                      autofocus: false,
                      textInputAction: TextInputAction.search,
                      onSubmitted: _search,
                      decoration: InputDecoration(
                        hintText: AppStrings.of(context).searchHint,
                        prefixIcon: Icon(Icons.search_rounded),
                        suffixIcon: _controller.text.isEmpty
                            ? IconButton(
                                icon: Icon(Icons.mic, color: NoirTheme.primary),
                                onPressed: _startVoiceSearch,
                              )
                            : IconButton(
                                icon: Icon(Icons.close_rounded),
                                onPressed: () {
                                  _controller.clear();
                                  setState(() {
                                    _results = [];
                                    _visualMatches = [];
                                    _isVisualSearch = false;
                                  });
                                },
                              ),
                      ),
                      onChanged: (_) => setState(() {}),
                    ),
                    SizedBox(height: 18),
                    Wrap(
                      spacing: 10,
                      runSpacing: 10,
                      children: _quickTerms.map((term) {
                        return ActionChip(
                          label: Text(term),
                          avatar: Icon(
                            Icons.north_east_rounded,
                            size: 16,
                          ),
                          backgroundColor: Theme.of(context).colorScheme.surface,
                          side: BorderSide(color: Theme.of(context).colorScheme.outline),
                          labelStyle: GoogleFonts.urbanist(
                            color: NoirTheme.inkSoft,
                            fontWeight: FontWeight.w700,
                          ),
                          onPressed: () {
                            _controller.text = term;
                            _search(term);
                          },
                        );
                      }).toList(),
                    ),
                  ],
                ),
              ),
            ),
            if (_loading)
              SliverFillRemaining(
                child: Center(child: CircularProgressIndicator(strokeWidth: 2, color: NoirTheme.primary)),
              )
            else if (_isVisualSearch && _visualMatches.isNotEmpty)
              SliverPadding(
                padding: EdgeInsets.fromLTRB(22, 10, 22, 110),
                sliver: SliverList.separated(
                  itemCount: _visualMatches.length,
                  separatorBuilder: (_, _) => SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final item = _visualMatches[index];
                    final pId = item['id'] as int? ?? 0;
                    final name = item['name'] as String? ?? 'Luxury Atelier Piece';
                    final price = (item['price'] as num?)?.toDouble() ?? 0.0;
                    final matchPct = item['match_percentage'] ?? 92;
                    final reason = item['ai_styling_reason'] as String? ?? 'Harmonious silhouette and tone.';
                    final image = item['image'] as String? ?? '';

                    return InkWell(
                      borderRadius: BorderRadius.circular(18),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => ProductDetailScreen(productId: pId),
                        ),
                      ),
                      child: Container(
                        padding: EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFF161814),
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.3)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(12),
                                  child: CachedNetworkImage(
                                    imageUrl: image,
                                    width: 76,
                                    height: 92,
                                    fit: BoxFit.cover,
                                    errorWidget: (_, _, _) => Container(
                                      width: 76,
                                      height: 92,
                                      color: const Color(0xFF252820),
                                      child: const Icon(Icons.image_outlined, color: Colors.white30),
                                    ),
                                  ),
                                ),
                                SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                        decoration: BoxDecoration(
                                          color: NoirTheme.primary.withValues(alpha: 0.2),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          '★ $matchPct% VISUAL MATCH',
                                          style: GoogleFonts.urbanist(
                                            color: NoirTheme.primary,
                                            fontWeight: FontWeight.w900,
                                            fontSize: 10,
                                            letterSpacing: 1,
                                          ),
                                        ),
                                      ),
                                      SizedBox(height: 6),
                                      Text(
                                        name,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                        style: GoogleFonts.urbanist(
                                          color: Colors.white,
                                          fontSize: 15,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                      SizedBox(height: 6),
                                      Text(
                                        _formatPrice(price),
                                        style: GoogleFonts.urbanist(
                                          color: NoirTheme.primary,
                                          fontSize: 15,
                                          fontWeight: FontWeight.w900,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            SizedBox(height: 10),
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: Colors.black45,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Icon(Icons.auto_awesome, color: NoirTheme.primary, size: 14),
                                  SizedBox(width: 6),
                                  Expanded(
                                    child: Text(
                                      reason,
                                      style: GoogleFonts.urbanist(fontSize: 11.5, color: Colors.white70, height: 1.3),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              )
            else if (_results.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: Center(
                  child: Padding(
                    padding: EdgeInsets.all(32),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.travel_explore_rounded,
                          size: 56,
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                        ),
                        SizedBox(height: 18),
                        Text(
                          _lastQuery == null
                              ? AppStrings.of(context).searchHint
                              : AppStrings.of(context).noResults,
                          style: GoogleFonts.urbanist(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          _lastQuery == null
                              ? AppStrings.of(context).searchProducts
                              : AppStrings.of(context).clearFilters,
                          textAlign: TextAlign.center,
                          style: GoogleFonts.urbanist(
                            color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
                            height: 1.4,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              )
            else
              SliverPadding(
                padding: EdgeInsets.fromLTRB(22, 10, 22, 110),
                sliver: SliverList.separated(
                  itemCount: _results.length,
                  separatorBuilder: (_, _) => SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final product = _results[index];
                    final image = product.featuredImage ?? '';
                    return InkWell(
                      borderRadius: BorderRadius.circular(18),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => ProductDetailScreen(
                            productId: product.id,
                          ),
                        ),
                      ),
                      child: Container(
                        padding: EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surface,
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(color: Theme.of(context).colorScheme.outline),
                        ),
                        child: Row(
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(14),
                              child: CachedNetworkImage(
                                imageUrl: image,
                                width: 76,
                                height: 92,
                                fit: BoxFit.cover,
                                errorWidget: (_, _, _) => Container(
                                  width: 76,
                                  height: 92,
                                  color: Theme.of(context).colorScheme.surfaceTint,
                                  child: Icon(Icons.image_outlined),
                                ),
                              ),
                            ),
                            SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    (product.brandName ?? context.read<SettingsProvider>().brandName)
                                        .toUpperCase(),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: GoogleFonts.urbanist(
                                      color: Theme.of(context).colorScheme.secondary,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w800,
                                      letterSpacing: 0.7,
                                    ),
                                  ),
                                  SizedBox(height: 5),
                                  Text(
                                    product.name.isNotEmpty ? product.name : 'Untitled',
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: GoogleFonts.urbanist(
                                      color: Theme.of(context).colorScheme.onSurface,
                                      fontSize: 16,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                  SizedBox(height: 8),
                                  Text(
                                    product.formattedPrice,
                                    style: GoogleFonts.urbanist(
                                      color: Theme.of(context).colorScheme.primary,
                                      fontSize: 15,
                                      fontWeight: FontWeight.w900,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Icon(
                              Icons.chevron_right_rounded,
                              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }
}
