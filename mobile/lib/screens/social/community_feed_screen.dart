import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';
import 'package:timeago/timeago.dart' as timeago;
import 'package:flutter_dotenv/flutter_dotenv.dart';

import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../providers/currency_provider.dart';
import '../../providers/cart_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import '../../utils/noir_share.dart';
import '../store/product_detail_screen.dart';
import '../../models/product.dart';

class CommunityFeedScreen extends StatefulWidget {
  const CommunityFeedScreen({super.key});

  @override
  State<CommunityFeedScreen> createState() => _CommunityFeedScreenState();
}

class _CommunityFeedScreenState extends State<CommunityFeedScreen> {
  late Future<List<dynamic>> _feedFuture;

  @override
  void initState() {
    super.initState();
    _loadFeed();
  }

  void _loadFeed() {
    final api = context.read<AuthProvider>().api;
    _feedFuture = api.getCommunityFeed().catchError((e) {
      debugPrint('Community feed fetch error: $e');
      return <dynamic>[];
    });
  }

  List<Map<String, dynamic>> _getFallbackPosts() {
    final images = [
      'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1521223890158-f9f7c3d5d504?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1492707892479-7bc8d5a4ee93?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&q=80&w=800',
    ];

    final handles = [
      '@noir.atelier',
      '@amara.couture',
      '@tomiwa.style',
      '@zainab.looks',
      '@kofi.mensah',
      '@chidi.noir',
      '@favour.atelier',
      '@temi.luxe',
      '@noir.paris',
      '@victoria.style'
    ];

    final captions = [
      'Introducing the Autumn/Winter Atelier Capsule. Sculptural silhouettes in earthy tones. #NoirStyle #MinimalElegance',
      'Draped in Noir Monochrome. The feel of this tailored linen blazer is pure luxury. ✨',
      'Friday fittings in Lagos. Noir accessories always complete the outfit.',
      'Understated luxury in every thread. The new bespoke wool coat collection is here.',
      'Styling the Noir signature trench for evening cocktails. Effortlessly refined.',
      'Atelier craftsmanship meeting modern streetwear aesthetics. #NoirHomme',
      'Weekend brunch look featuring the Silk Pleated Set. Unbelievably comfortable.',
      'Tailoring perfection. Noir pieces are investments that never go out of style.',
      'Sartorial harmony in olive and obsidian. #NoirCapsule',
      'Minimalist cuts, maximum presence. The Noir luxury identity.'
    ];

    return List.generate(images.length, (i) {
      return {
        'id': i + 1,
        'user_handle': handles[i % handles.length],
        'caption': captions[i % captions.length],
        'image_url': images[i],
        'likes_count': 120 + (i * 47) % 800,
        'comments_count': 15 + (i * 11) % 120,
        'products_count': (i % 3) + 1,
        'is_liked': i % 3 == 0,
        'created_at': DateTime.now().subtract(Duration(hours: i * 3 + 1)).toIso8601String(),
        'tagged_products': [
          {
            'id': i + 101,
            'name': 'Noir Atelier Capsule Piece ${i + 1}',
            'price': 35000 + (i * 5000),
            'image': images[i],
          }
        ]
      };
    });
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings.of(context);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        centerTitle: false,
        title: Text(
          strings.communityFeed,
          style: GoogleFonts.urbanist(
            fontWeight: FontWeight.w800,
            fontSize: 22,
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded, color: Theme.of(context).colorScheme.onSurface),
            onPressed: () {
              setState(() {
                _loadFeed();
              });
            },
          ),
        ],
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _feedFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return Center(child: CircularProgressIndicator(color: NoirTheme.primary));
          }

          List<dynamic> posts = snapshot.data ?? [];
          if (posts.isEmpty) {
            posts = _getFallbackPosts();
          }

          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _loadFeed();
              });
            },
            color: NoirTheme.primary,
            child: ListView.builder(
              itemCount: posts.length,
              itemBuilder: (context, index) {
                return SocialPostCard(post: posts[index]);
              },
            ),
          );
        },
      ),
    );
  }
}

class SocialPostCard extends StatefulWidget {
  final Map<String, dynamic> post;

  const SocialPostCard({super.key, required this.post});

  @override
  State<SocialPostCard> createState() => _SocialPostCardState();
}

class _SocialPostCardState extends State<SocialPostCard> {
  late bool _isLiked;
  late int _likesCount;
  bool _isBookmarked = false;

  @override
  void initState() {
    super.initState();
    _isLiked = widget.post['is_liked'] == true;
    _likesCount = (widget.post['likes_count'] as num?)?.toInt() ?? 0;
  }

  Future<void> _toggleLike() async {
    final client = context.read<AuthProvider>().api;
    if (!client.isAuthenticated) {
      setState(() {
        _isLiked = !_isLiked;
        _likesCount += _isLiked ? 1 : -1;
      });
      return;
    }

    setState(() {
      _isLiked = !_isLiked;
      _likesCount += _isLiked ? 1 : -1;
    });

    try {
      final postId = (widget.post['id'] as num?)?.toInt() ?? 1;
      await client.toggleCommunityPostLike(postId);
    } catch (_) {}
  }

  void _showCommentsBottomSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.65,
        minChildSize: 0.4,
        maxChildSize: 0.95,
        builder: (_, scrollController) => CommentsSheet(
          postId: (widget.post['id'] as num?)?.toInt() ?? 1,
          scrollController: scrollController,
        ),
      ),
    );
  }

  void _sharePost() {
    final brand = context.read<SettingsProvider>().brandName;
    final handle = widget.post['user_handle'] ?? '@$brand';
    final caption = widget.post['caption'] ?? brand;
    NoirShare.share(
      context,
      text: '$caption\n\nShared from $brand Atelier by $handle\nhttps://noir-73fy.onrender.com',
      subject: '$brand Atelier Style',
    );
  }

  void _showPostOptions() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(width: 36, height: 4, decoration: BoxDecoration(color: Colors.grey.shade600, borderRadius: BorderRadius.circular(2))),
            const SizedBox(height: 16),
            ListTile(
              leading: const Icon(Icons.share_outlined),
              title: const Text('Share Post'),
              onTap: () {
                Navigator.pop(ctx);
                _sharePost();
              },
            ),
            ListTile(
              leading: const Icon(Icons.link_rounded),
              title: const Text('Copy Post Link'),
              onTap: () {
                Navigator.pop(ctx);
                Clipboard.setData(const ClipboardData(text: 'https://noir-73fy.onrender.com/community'));
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: const Text('Post link copied to clipboard!'),
                    backgroundColor: NoirTheme.primary,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              },
            ),
            ListTile(
              leading: Icon(_isBookmarked ? Icons.bookmark_remove_rounded : Icons.bookmark_add_outlined),
              title: Text(_isBookmarked ? 'Remove from Saved' : 'Save to ${context.read<SettingsProvider>().brandName} Collections'),
              onTap: () {
                Navigator.pop(ctx);
                setState(() => _isBookmarked = !_isBookmarked);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(_isBookmarked ? 'Saved to collections!' : 'Removed from collections.'),
                    backgroundColor: NoirTheme.primary,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.flag_outlined, color: Colors.red),
              title: const Text('Report Post / Flag Content', style: TextStyle(color: Colors.red)),
              onTap: () {
                Navigator.pop(ctx);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Post flagged for Atelier moderator review.'),
                    backgroundColor: Colors.red,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showTaggedProductsModal() {
    final tagged = widget.post['tagged_products'] as List<dynamic>? ?? [];
    if (tagged.isEmpty) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        final cp = ctx.read<CurrencyProvider>();
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          minChildSize: 0.35,
          maxChildSize: 0.88,
          expand: false,
          builder: (_, scrollController) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
              child: ListView(
                controller: scrollController,
                children: [
                  Center(
                    child: Container(
                      width: 36,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade600,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      const Icon(Icons.shopping_bag_outlined, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        'Tagged Pieces in this Look',
                        style: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 17),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  ...tagged.map((itemRaw) {
                    final item = itemRaw is Map<String, dynamic> ? itemRaw : <String, dynamic>{};
                    final pId = (item['id'] as num?)?.toInt() ?? 1;
                    final name = item['name']?.toString() ?? 'Noir Piece';
                    final price = (item['price'] as num?)?.toDouble() ?? 0.0;
                    final shortDesc = item['short_description']?.toString() ?? '';
                    final desc = item['description']?.toString() ?? '';
                    final displayDesc = shortDesc.isNotEmpty ? shortDesc : (desc.isNotEmpty ? desc : 'Exquisite luxury craftsmanship tailored for modern timeless elegance.');
                    final img = Product.fixImageUrl(item['image']?.toString());

                    return Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Theme.of(ctx).colorScheme.surface,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: NoirTheme.primary.withValues(alpha: 0.25),
                          width: 0.8,
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          InkWell(
                            onTap: () {
                              Navigator.pop(ctx);
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => ProductDetailScreen(productId: pId),
                                ),
                              );
                            },
                            borderRadius: BorderRadius.circular(10),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(10),
                                  child: CachedNetworkImage(
                                    imageUrl: img,
                                    width: 64,
                                    height: 64,
                                    fit: BoxFit.cover,
                                    errorWidget: (_, _, _) => Container(
                                      width: 64,
                                      height: 64,
                                      color: Colors.grey.shade900,
                                      child: const Icon(Icons.checkroom_rounded, color: Colors.white24),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        name,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 15),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        cp.format(price),
                                        style: GoogleFonts.urbanist(
                                          color: NoirTheme.primary,
                                          fontWeight: FontWeight.w800,
                                          fontSize: 14,
                                        ),
                                      ),
                                      if (displayDesc.isNotEmpty) ...[
                                        const SizedBox(height: 2),
                                        Text(
                                          displayDesc,
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                          style: GoogleFonts.urbanist(
                                            fontSize: 12,
                                            height: 1.25,
                                            color: Theme.of(ctx).colorScheme.onSurface.withValues(alpha: 0.72),
                                          ),
                                        ),
                                      ],
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 10),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: () {
                                ctx.read<CartProvider>().addItem(
                                  CartItem(
                                    productId: pId,
                                    name: name,
                                    price: price,
                                    image: img,
                                    quantity: 1,
                                  ),
                                );
                                HapticFeedback.lightImpact();
                                Navigator.pop(ctx);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Row(
                                      children: [
                                        const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
                                        const SizedBox(width: 8),
                                        Expanded(child: Text('Added $name to your bag!')),
                                      ],
                                    ),
                                    backgroundColor: NoirTheme.primary,
                                    behavior: SnackBarBehavior.floating,
                                    duration: const Duration(seconds: 2),
                                  ),
                                );
                              },
                              icon: const Icon(Icons.shopping_bag_outlined, color: Colors.white, size: 15),
                              label: const Text('Add to Bag', style: TextStyle(color: Colors.white)),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: NoirTheme.primary,
                                elevation: 0,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                padding: const EdgeInsets.symmetric(vertical: 9),
                                textStyle: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 13),
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final rawDate = widget.post['created_at']?.toString() ?? '';
    final parsedDate = DateTime.tryParse(rawDate) ?? DateTime.now();

    final userHandle = widget.post['user_handle']?.toString() ?? '@noir.atelier';
    final caption = widget.post['caption']?.toString() ?? '';
    String imageUrl = widget.post['image_url']?.toString() ?? '';
    if (imageUrl.startsWith('/')) {
      final baseUrl = dotenv.env['API_BASE_URL'] ?? 'https://noir-73fy.onrender.com';
      imageUrl = '$baseUrl$imageUrl';
    }
    final productsCount = (widget.post['products_count'] as num?)?.toInt() ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        border: Border(
          bottom: BorderSide(
            color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.2),
          ),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header: Avatar, Handle, More button
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              children: [
                CircleAvatar(
                  backgroundColor: NoirTheme.primary.withValues(alpha: 0.15),
                  child: Text(
                    userHandle.replaceAll('@', '').substring(0, 1).toUpperCase(),
                    style: TextStyle(color: NoirTheme.primary, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        userHandle,
                        style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 15),
                      ),
                      Text(
                        timeago.format(parsedDate),
                        style: GoogleFonts.urbanist(
                          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.more_horiz),
                  color: Theme.of(context).colorScheme.onSurface,
                  onPressed: _showPostOptions,
                ),
              ],
            ),
          ),

          // Image with tap to view look
          GestureDetector(
            onTap: _showTaggedProductsModal,
            child: Container(
              height: 400,
              width: double.infinity,
              color: const Color(0xFF131511),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  CachedNetworkImage(
                    imageUrl: imageUrl,
                    fit: BoxFit.cover,
                    placeholder: (context, url) => Container(color: const Color(0xFF131511)),
                    errorWidget: (context, url, error) => Container(
                      color: const Color(0xFF131511),
                      child: const Center(
                        child: Icon(
                          Icons.image_outlined,
                          size: 64,
                          color: Colors.white24,
                        ),
                      ),
                    ),
                  ),
                  if (productsCount > 0)
                    Positioned(
                      bottom: 16,
                      left: 16,
                      child: GestureDetector(
                        onTap: _showTaggedProductsModal,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.75),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: Colors.white24, width: 0.5),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.shopping_bag_outlined, color: Colors.white, size: 14),
                              const SizedBox(width: 6),
                              Text(
                                '$productsCount tagged items',
                                style: GoogleFonts.urbanist(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),

          // Actions: Like, Comment, Share, Bookmark
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Row(
              children: [
                IconButton(
                  icon: Icon(
                    _isLiked ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                    color: _isLiked ? Colors.red : Theme.of(context).colorScheme.onSurface,
                    size: 26,
                  ),
                  onPressed: _toggleLike,
                ),
                IconButton(
                  icon: Icon(
                    Icons.chat_bubble_outline_rounded,
                    color: Theme.of(context).colorScheme.onSurface,
                    size: 24,
                  ),
                  onPressed: _showCommentsBottomSheet,
                ),
                IconButton(
                  icon: Icon(
                    Icons.share_outlined,
                    color: Theme.of(context).colorScheme.onSurface,
                    size: 24,
                  ),
                  onPressed: _sharePost,
                ),
                const Spacer(),
                IconButton(
                  icon: Icon(
                    _isBookmarked ? Icons.bookmark_rounded : Icons.bookmark_border_rounded,
                    color: _isBookmarked ? NoirTheme.primary : Theme.of(context).colorScheme.onSurface,
                    size: 26,
                  ),
                  onPressed: () {
                    setState(() {
                      _isBookmarked = !_isBookmarked;
                    });
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(_isBookmarked ? 'Saved to collections' : 'Removed from saved'),
                        duration: const Duration(seconds: 1),
                        behavior: SnackBarBehavior.floating,
                      ),
                    );
                  },
                ),
              ],
            ),
          ),

          // Likes count
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              '$_likesCount likes',
              style: GoogleFonts.urbanist(
                fontWeight: FontWeight.bold,
                fontSize: 14,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),

          // Caption
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                RichText(
                  text: TextSpan(
                    style: GoogleFonts.urbanist(color: Theme.of(context).colorScheme.onSurface, fontSize: 14),
                    children: [
                      TextSpan(
                        text: '$userHandle ',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      TextSpan(text: caption),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                GestureDetector(
                  onTap: _showCommentsBottomSheet,
                  child: Text(
                    'View all comments',
                    style: GoogleFonts.urbanist(
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                      fontSize: 13,
                    ),
                  ),
                ),
                if ((widget.post['tagged_products'] as List<dynamic>? ?? []).isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Builder(
                    builder: (context) {
                      final taggedList = widget.post['tagged_products'] as List<dynamic>;
                      final item = taggedList.first is Map<String, dynamic> ? taggedList.first : <String, dynamic>{};
                      final pId = (item['id'] as num?)?.toInt() ?? 1;
                      final pName = item['name']?.toString() ?? 'Noir Atelier Piece';
                      final pPrice = (item['price'] as num?)?.toDouble() ?? 0.0;
                      final pShortDesc = item['short_description']?.toString() ?? widget.post['product_short_description']?.toString() ?? '';
                      final pDesc = item['description']?.toString() ?? widget.post['product_description']?.toString() ?? '';
                      final displayDesc = pShortDesc.isNotEmpty ? pShortDesc : (pDesc.isNotEmpty ? pDesc : 'Exquisite luxury craftsmanship tailored for modern timeless elegance.');
                      final pImg = Product.fixImageUrl(item['image']?.toString() ?? widget.post['image_url']?.toString());
                      final isDark = Theme.of(context).brightness == Brightness.dark;

                      return Container(
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFF191C16) : const Color(0xFFF7F7F0),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: NoirTheme.primary.withValues(alpha: 0.28),
                            width: 0.8,
                          ),
                        ),
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            InkWell(
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => ProductDetailScreen(productId: pId),
                                  ),
                                );
                              },
                              borderRadius: BorderRadius.circular(10),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.center,
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(10),
                                    child: CachedNetworkImage(
                                      imageUrl: pImg,
                                      width: 64,
                                      height: 64,
                                      fit: BoxFit.cover,
                                      errorWidget: (_, _, _) => Container(
                                        width: 64,
                                        height: 64,
                                        color: Colors.grey.shade900,
                                        child: const Icon(Icons.checkroom_rounded, color: Colors.white24),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          pName,
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: GoogleFonts.urbanist(
                                            fontWeight: FontWeight.w800,
                                            fontSize: 15,
                                            color: Theme.of(context).colorScheme.onSurface,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          context.read<CurrencyProvider>().format(pPrice),
                                          style: GoogleFonts.urbanist(
                                            color: NoirTheme.primary,
                                            fontWeight: FontWeight.w900,
                                            fontSize: 14,
                                          ),
                                        ),
                                        if (displayDesc.isNotEmpty) ...[
                                          const SizedBox(height: 2),
                                          Text(
                                            displayDesc,
                                            maxLines: 2,
                                            overflow: TextOverflow.ellipsis,
                                            style: GoogleFonts.urbanist(
                                              fontSize: 12,
                                              height: 1.25,
                                              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.72),
                                            ),
                                          ),
                                        ],
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 10),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton.icon(
                                onPressed: () {
                                  context.read<CartProvider>().addItem(
                                    CartItem(
                                      productId: pId,
                                      name: pName,
                                      price: pPrice,
                                      image: pImg,
                                      quantity: 1,
                                    ),
                                  );
                                  HapticFeedback.lightImpact();
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Row(
                                        children: [
                                          const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
                                          const SizedBox(width: 8),
                                          Expanded(child: Text('Added $pName to your bag!')),
                                        ],
                                      ),
                                      backgroundColor: NoirTheme.primary,
                                      behavior: SnackBarBehavior.floating,
                                      duration: const Duration(seconds: 2),
                                    ),
                                  );
                                },
                                icon: const Icon(Icons.shopping_bag_outlined, color: Colors.white, size: 15),
                                label: const Text('Add to Bag', style: TextStyle(color: Colors.white)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: NoirTheme.primary,
                                  elevation: 0,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  padding: const EdgeInsets.symmetric(vertical: 9),
                                  textStyle: GoogleFonts.urbanist(fontWeight: FontWeight.w800, fontSize: 13),
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 12),
        ],
      ),
    );
  }
}

class CommentsSheet extends StatefulWidget {
  final int postId;
  final ScrollController scrollController;

  const CommentsSheet({
    super.key,
    required this.postId,
    required this.scrollController,
  });

  @override
  State<CommentsSheet> createState() => _CommentsSheetState();
}

class _CommentsSheetState extends State<CommentsSheet> {
  final _commentController = TextEditingController();
  List<dynamic> _comments = [];
  bool _isLoading = true;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _fetchComments();
  }

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _fetchComments() async {
    try {
      final comments = await context.read<AuthProvider>().api.getCommunityPostComments(widget.postId);
      if (mounted) {
        setState(() {
          _comments = comments;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _comments = [
            {'id': 1, 'user_handle': '@noir.stylist', 'content': 'Stunning silhouette and proportions! ✨', 'created_at': DateTime.now().subtract(const Duration(hours: 2)).toIso8601String()},
            {'id': 2, 'user_handle': '@amara.luxe', 'content': 'Is this piece still in stock at the atelier?', 'created_at': DateTime.now().subtract(const Duration(hours: 4)).toIso8601String()},
          ];
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _submitComment() async {
    final content = _commentController.text.trim();
    if (content.isEmpty) return;

    final client = context.read<AuthProvider>().api;

    setState(() => _isSubmitting = true);

    try {
      if (client.isAuthenticated) {
        final res = await client.addCommunityPostComment(widget.postId, content);
        if (mounted) {
          setState(() {
            _comments.add(res['comment']);
            _isSubmitting = false;
          });
        }
      } else {
        if (mounted) {
          setState(() {
            _comments.add({
              'id': DateTime.now().millisecondsSinceEpoch,
              'user_handle': '@noir.guest',
              'content': content,
              'created_at': DateTime.now().toIso8601String(),
            });
            _isSubmitting = false;
          });
        }
      }
      _commentController.clear();
      if (mounted) FocusScope.of(context).unfocus();
    } catch (e) {
      if (mounted) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to post comment.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          Container(
            margin: const EdgeInsets.symmetric(vertical: 12),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey.withValues(alpha: 0.3),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Text(
            'Comments',
            style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 16),
          ),
          const SizedBox(height: 12),
          const Divider(height: 1),
          Expanded(
            child: _isLoading
                ? Center(child: CircularProgressIndicator(color: NoirTheme.primary))
                : _comments.isEmpty
                    ? Center(
                        child: Text(
                          'No comments yet. Start the conversation!',
                          style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 14),
                        ),
                      )
                    : ListView.builder(
                        controller: widget.scrollController,
                        itemCount: _comments.length,
                        itemBuilder: (context, index) {
                          final c = _comments[index];
                          final handle = c['user_handle'] ?? '@member';
                          final text = c['content'] ?? '';
                          final date = DateTime.tryParse(c['created_at']?.toString() ?? '') ?? DateTime.now();

                          return ListTile(
                            leading: CircleAvatar(
                              backgroundColor: NoirTheme.primary.withValues(alpha: 0.15),
                              radius: 16,
                              child: Text(
                                handle.replaceAll('@', '').substring(0, 1).toUpperCase(),
                                style: TextStyle(fontSize: 12, color: NoirTheme.primary, fontWeight: FontWeight.bold),
                              ),
                            ),
                            title: Text(handle, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 13)),
                            subtitle: Text(text, style: GoogleFonts.urbanist(fontSize: 14)),
                            trailing: Text(
                              timeago.format(date, locale: 'en_short'),
                              style: GoogleFonts.urbanist(fontSize: 11, color: Colors.grey),
                            ),
                          );
                        },
                      ),
          ),
          const Divider(height: 1),
          Padding(
            padding: EdgeInsets.fromLTRB(16, 8, 16, MediaQuery.of(context).viewInsets.bottom + 12),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _commentController,
                    decoration: InputDecoration(
                      hintText: 'Add a comment...',
                      hintStyle: GoogleFonts.urbanist(color: Colors.grey, fontSize: 14),
                      border: InputBorder.none,
                    ),
                  ),
                ),
                _isSubmitting
                    ? SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2, color: NoirTheme.primary))
                    : IconButton(
                        icon: Icon(Icons.send_rounded, color: NoirTheme.primary),
                        onPressed: _submitComment,
                      ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
