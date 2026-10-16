import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';
import 'package:video_player/video_player.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

import '../../providers/auth_provider.dart';
import '../../providers/cart_provider.dart';
import '../../providers/currency_provider.dart';
import '../../providers/settings_provider.dart';
import '../../theme.dart';
import '../../utils/noir_share.dart';
import '../../models/product.dart';
import 'product_detail_screen.dart';

const List<Map<String, String>> _soundtracks = [
  {
    'title': 'Noir Runway Live • Paris SS26',
    'artist': 'Noir Haute Couture Sound',
    'url': 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
  },
  {
    'title': 'Obsidian Catwalk • Milan Atelier',
    'artist': 'Milan Fashion Week Beats',
    'url': 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
  },
  {
    'title': 'Minimalist Silk Rhythm • Tokyo Drop',
    'artist': 'Tokyo Runway Collective',
    'url': 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
  },
  {
    'title': 'Savile Row Tailoring Vibe • London',
    'artist': 'Noir Bespoke Sound',
    'url': 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3',
  },
  {
    'title': 'Lagos Luxe Street Beat • Drop 04',
    'artist': 'Afro-Luxury Runway Sound',
    'url': 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-5.mp3',
  },
];

class VideoCommerceScreen extends StatefulWidget {
  final bool isActive;
  final VoidCallback? onBackPressed;
  const VideoCommerceScreen({super.key, this.isActive = true, this.onBackPressed});

  @override
  State<VideoCommerceScreen> createState() => _VideoCommerceScreenState();
}

class _VideoCommerceScreenState extends State<VideoCommerceScreen> {
  final PageController _pageController = PageController();
  List<Map<String, dynamic>> _videos = [];
  bool _isLoading = true;
  bool _isMuted = false;
  int _currentIndex = 0;

  @override
  void didUpdateWidget(VideoCommerceScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.isActive != widget.isActive) {
      setState(() {});
    }
  }

  @override
  void initState() {
    super.initState();
    _fetchVideos();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  List<Map<String, dynamic>> _getFallbackReels() {
    final fallbackClips = [
      'https://videos.pexels.com/video-files/3771849/3771849-hd_1080_1920_30fps.mp4',
      'https://videos.pexels.com/video-files/3756907/3756907-hd_1080_1920_30fps.mp4',
      'https://videos.pexels.com/video-files/4612505/4612505-hd_1080_1920_30fps.mp4',
      'https://videos.pexels.com/video-files/4620559/4620559-hd_1080_1920_30fps.mp4',
      'https://videos.pexels.com/video-files/5705474/5705474-hd_1080_1920_24fps.mp4',
      'https://videos.pexels.com/video-files/6786957/6786957-hd_1080_1920_25fps.mp4',
      'https://videos.pexels.com/video-files/6536561/6536561-hd_1080_1920_25fps.mp4',
      'https://videos.pexels.com/video-files/4765919/4765919-hd_1080_1920_25fps.mp4',
    ];

    final fallbackThumbs = [
      'https://images.unsplash.com/photo-1521223890158-f9f7c3d5d504?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&q=80&w=800',
      'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&q=80&w=800',
    ];

    final titles = [
      'Styling the Noir Minimalist Leather Jacket | Runway Edit',
      'Noir Sculptural Blazer & Tailored Trousers | Runway 2026',
      'Atelier Silk Trench Coat in Obsidian | Autumn Preview',
      'Vintage Streetwear Meets Contemporary Luxury | Noir Homme',
      'Monochrome Velvet Evening Gown | Noir Couture',
      'Bespoke Wool Suit Tailoring | Noir Sartorial Collection',
      'Winter Cashmere & Fur Coat Fitting | Noir Atelier',
      'Sleek Architectural Minimalist Dress | Noir Femme',
      'Artisanal Accessories & Leather Tote Styling | Noir Living',
      'Earthy Tones Linen Summer Blazer | Warm Palette 2026',
    ];

    return List.generate(fallbackClips.length, (i) {
      final t = titles[i % titles.length].replaceAll('Atelier', 'Luxury').replaceAll('Noir Atelier', 'Noir');
      final thumb = fallbackThumbs[i % fallbackThumbs.length];
      final clip = fallbackClips[i];
      return {
        'id': i + 1,
        'title': t,
        'user': '@noir.official',
        'likes_count': 340 + i * 115,
        'comments_count': 24 + i * 8,
        'products_count': 1,
        'url': clip,
        'thumbnail': thumb,
        'is_liked': false,
        'products': [
          {
            'id': i + 10,
            'name': t.split('|').first.trim(),
            'price': 45000 + (i * 7500),
            'image': thumb,
          }
        ]
      };
    });
  }

  Future<void> _fetchVideos() async {
    try {
      final api = context.read<AuthProvider>().api;
      final list = await api.getVideoCommerce();
      if (mounted) {
        setState(() {
          final mapped = list.map((e) => Map<String, dynamic>.from(e)).toList();
          _videos = mapped.isNotEmpty ? mapped : _getFallbackReels();
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _videos = _getFallbackReels();
          _isLoading = false;
        });
      }
    }
  }

  void _toggleMute() {
    setState(() => _isMuted = !_isMuted);
  }

  void _toggleLike(int index) {
    if (index >= 0 && index < _videos.length) {
      setState(() {
        final isLiked = _videos[index]['is_liked'] == true;
        _videos[index]['is_liked'] = !isLiked;
        final currentLikes = (_videos[index]['likes_count'] as num?)?.toInt() ?? 0;
        _videos[index]['likes_count'] = isLiked ? (currentLikes - 1) : (currentLikes + 1);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        backgroundColor: Colors.black,
        body: Center(
          child: CircularProgressIndicator(color: NoirTheme.primary),
        ),
      );
    }

    if (_videos.isEmpty) {
      return Scaffold(
        backgroundColor: Colors.black,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          leading: const BackButton(color: Colors.white),
        ),
        body: Center(
          child: Text(
            'No fashion reels available.',
            style: GoogleFonts.urbanist(color: Colors.white70, fontSize: 16),
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // Fullscreen vertical reels PageView with 3D depth transitions
          PageView.builder(
            controller: _pageController,
            scrollDirection: Axis.vertical,
            physics: const BouncingScrollPhysics(),
            itemCount: _videos.length,
            onPageChanged: (idx) {
              setState(() => _currentIndex = idx);
            },
            itemBuilder: (context, index) {
              return AnimatedBuilder(
                animation: _pageController,
                builder: (context, child) {
                  double scale = 1.0;
                  double opacity = 1.0;
                  if (_pageController.position.haveDimensions) {
                    final page = _pageController.page ?? _currentIndex.toDouble();
                    final diff = (page - index).abs();
                    scale = (1.0 - (diff * 0.12)).clamp(0.88, 1.0);
                    opacity = (1.0 - (diff * 0.4)).clamp(0.0, 1.0);
                  }
                  return Transform.scale(
                    scale: scale,
                    child: Opacity(
                      opacity: opacity,
                      child: child,
                    ),
                  );
                },
                child: _ReelItem(
                  key: ValueKey(_videos[index]['id'] ?? index),
                  data: _videos[index],
                  isActive: widget.isActive && index == _currentIndex,
                  isMuted: _isMuted,
                  onLike: () => _toggleLike(index),
                ),
              );
            },
          ),

          // Top navigation overlay
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: EdgeInsets.fromLTRB(16, MediaQuery.of(context).padding.top + 8, 16, 16),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.black87, Colors.transparent],
                ),
              ),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
                    onPressed: () {
                      if (widget.onBackPressed != null) {
                        widget.onBackPressed!();
                      } else if (Navigator.of(context).canPop()) {
                        Navigator.of(context).pop();
                      }
                    },
                  ),
                  const Spacer(),
                  Text(
                    '${context.read<SettingsProvider>().brandName.toUpperCase()} REELS',
                    style: GoogleFonts.urbanist(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 2.0,
                      fontSize: 16,
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: Icon(
                      _isMuted ? Icons.volume_off_rounded : Icons.volume_up_rounded,
                      color: Colors.white,
                      size: 22,
                    ),
                    onPressed: _toggleMute,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ReelItem extends StatefulWidget {
  final Map<String, dynamic> data;
  final bool isActive;
  final bool isMuted;
  final VoidCallback onLike;

  const _ReelItem({
    super.key,
    required this.data,
    required this.isActive,
    required this.isMuted,
    required this.onLike,
  });

  @override
  State<_ReelItem> createState() => _ReelItemState();
}

class _ReelItemState extends State<_ReelItem> with TickerProviderStateMixin {
  VideoPlayerController? _controller;
  VideoPlayerController? _audioController;
  bool _isInitialized = false;
  bool _isPlaying = true;
  bool _showPlayStateIcon = false;
  late AnimationController _heartAnimController;
  late AnimationController _motionController;
  late AnimationController _discAnimController;
  late AnimationController _entranceController;
  late Animation<double> _scaleAnimation;
  late Animation<Alignment> _alignmentAnimation;
  late Animation<Offset> _slideAnimation;
  late Animation<Offset> _sideSlideAnimation;
  late Animation<double> _fadeAnimation;

  double _progress = 0.0;
  bool _isBuffering = false;

  Map<String, String> get _currentSound {
    final sIndex = ((widget.data['id'] as num?)?.toInt() ?? 0) % _soundtracks.length;
    return _soundtracks[sIndex];
  }

  @override
  void initState() {
    super.initState();
    _heartAnimController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );

    _motionController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 8),
    )..repeat(reverse: true);

    _discAnimController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 4),
    );

    _entranceController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 650),
    );

    _scaleAnimation = Tween<double>(begin: 1.0, end: 1.15).animate(
      CurvedAnimation(parent: _motionController, curve: Curves.easeInOutSine),
    );

    _alignmentAnimation = AlignmentTween(
      begin: const Alignment(0.0, -0.15),
      end: const Alignment(0.0, 0.15),
    ).animate(
      CurvedAnimation(parent: _motionController, curve: Curves.easeInOutSine),
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0.0, 0.35),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _entranceController,
      curve: Curves.easeOutCubic,
    ));

    _sideSlideAnimation = Tween<Offset>(
      begin: const Offset(0.35, 0.0),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _entranceController,
      curve: Curves.easeOutCubic,
    ));

    _fadeAnimation = CurvedAnimation(
      parent: _entranceController,
      curve: Curves.easeIn,
    );

    if (widget.isActive) {
      _entranceController.forward();
    }

    _initPlayer();
    _initAudio();
  }

  void _initAudio() {
    final sound = _currentSound;
    final soundUrl = sound['url']!;
    _audioController = VideoPlayerController.networkUrl(
      Uri.parse(soundUrl),
      videoPlayerOptions: VideoPlayerOptions(mixWithOthers: true),
    )..initialize().then((_) {
        if (mounted) {
          _audioController?.setLooping(true);
          _audioController?.setVolume(widget.isMuted ? 0.0 : 0.85);
          if (widget.isActive && _isPlaying) {
            _audioController?.play();
            if (!widget.isMuted) _discAnimController.repeat();
          }
        }
      }).catchError((e) {
        debugPrint('Audio stream error: $e');
      });
  }

  @override
  void didUpdateWidget(_ReelItem oldWidget) {
    super.didUpdateWidget(oldWidget);

    if (oldWidget.isActive != widget.isActive) {
      if (widget.isActive) {
        _entranceController.forward(from: 0.0);
        if (_controller == null) {
          _initPlayer();
        } else if (_isInitialized) {
          _controller?.setVolume(widget.isMuted ? 0.0 : 1.0);
          _controller?.play();
          setState(() => _isPlaying = true);
        }
        _audioController?.setVolume(widget.isMuted ? 0.0 : 0.85);
        _audioController?.play();
        if (!widget.isMuted) _discAnimController.repeat();
        if (!_motionController.isAnimating) _motionController.repeat(reverse: true);
      } else {
        _entranceController.reset();
        _controller?.setVolume(0.0);
        _controller?.pause();
        _audioController?.setVolume(0.0);
        _audioController?.pause();
        _discAnimController.stop();
        _motionController.stop();
        setState(() => _isPlaying = false);
      }
    }

    if (oldWidget.isMuted != widget.isMuted) {
      if (_controller != null) {
        _controller?.setVolume(widget.isMuted ? 0.0 : 1.0);
      }
      _audioController?.setVolume(widget.isMuted ? 0.0 : 0.85);
      if (widget.isMuted) {
        _discAnimController.stop();
      } else if (widget.isActive && _isPlaying) {
        _discAnimController.repeat();
      }
    }
  }

  @override
  void deactivate() {
    _controller?.setVolume(0.0);
    _controller?.pause();
    _audioController?.setVolume(0.0);
    _audioController?.pause();
    _discAnimController.stop();
    _motionController.stop();
    _isPlaying = false;
    super.deactivate();
  }

  @override
  void dispose() {
    _controller?.setVolume(0.0);
    _controller?.pause();
    _controller?.dispose();
    _audioController?.setVolume(0.0);
    _audioController?.pause();
    _audioController?.dispose();
    _heartAnimController.dispose();
    _discAnimController.dispose();
    _motionController.dispose();
    _entranceController.dispose();
    super.dispose();
  }

  void _initPlayer() {
    String rawUrl = widget.data['url']?.toString() ?? '';
    if (rawUrl.startsWith('/')) {
      final baseUrl = dotenv.env['API_BASE_URL'] ?? 'https://noir-73fy.onrender.com';
      rawUrl = '$baseUrl$rawUrl';
    }
    if (rawUrl.isEmpty || !rawUrl.startsWith('http')) {
      setState(() {
        _isInitialized = false;
        _isPlaying = true;
      });
      return;
    }

    setState(() => _isBuffering = true);

    _controller = VideoPlayerController.networkUrl(
      Uri.parse(rawUrl),
      httpHeaders: const {
        'User-Agent': 'Mozilla/5.0 (Linux; Android 12; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Mobile Safari/537.36',
        'Referer': 'https://www.pexels.com/',
      },
      videoPlayerOptions: VideoPlayerOptions(mixWithOthers: true),
    )..initialize().then((_) {
        if (mounted) {
          _controller?.setLooping(true);
          _controller?.setVolume(widget.isMuted ? 0.0 : 1.0);
          _controller?.addListener(() {
            if (mounted && _controller != null) {
              final isP = _controller!.value.isPlaying;
              final isBuf = _controller!.value.isBuffering;
              final dur = _controller!.value.duration.inMilliseconds;
              final pos = _controller!.value.position.inMilliseconds;
              final prg = dur > 0 ? (pos / dur).clamp(0.0, 1.0) : 0.0;
              if (isP != _isPlaying || isBuf != _isBuffering || (prg - _progress).abs() > 0.01) {
                setState(() {
                  _isPlaying = isP;
                  _isBuffering = isBuf;
                  _progress = prg;
                });
              }
            }
          });
          setState(() {
            _isInitialized = true;
            _isBuffering = false;
          });
          if (widget.isActive) {
            _controller?.play();
            setState(() {
              _isPlaying = true;
            });
          }
        }
      }).catchError((err) {
        debugPrint('Video error: $err');
        if (mounted) {
          setState(() {
            _isInitialized = false;
            _isBuffering = false;
          });
        }
      });
  }

  void _togglePlayPause() {
    setState(() {
      if (_controller != null && _isInitialized) {
        if (_controller!.value.isPlaying) {
          _controller!.pause();
          _isPlaying = false;
          _audioController?.pause();
          _discAnimController.stop();
          _motionController.stop();
        } else {
          _controller!.play();
          _isPlaying = true;
          _audioController?.play();
          if (!widget.isMuted) _discAnimController.repeat();
          _motionController.repeat(reverse: true);
        }
      } else {
        _isPlaying = !_isPlaying;
        if (_isPlaying) {
          _audioController?.play();
          if (!widget.isMuted) _discAnimController.repeat();
          _motionController.repeat(reverse: true);
        } else {
          _audioController?.pause();
          _discAnimController.stop();
          _motionController.stop();
        }
      }
      _showPlayStateIcon = true;
    });

    Future.delayed(const Duration(milliseconds: 700), () {
      if (mounted) setState(() => _showPlayStateIcon = false);
    });
  }

  void _onDoubleTap() {
    widget.onLike();
    _heartAnimController.forward().then((_) => _heartAnimController.reverse());
  }

  void _shareReel() {
    final title = widget.data['title'] ?? 'Noir Atelier Reel';
    final user = widget.data['user'] ?? '@noir.atelier';
    NoirShare.share(
      context,
      text: 'Watch this luxury runway reel by $user on ${context.read<SettingsProvider>().brandName}:\n"$title"\n\nhttps://noir-73fy.onrender.com',
      subject: '${context.read<SettingsProvider>().brandName} Reel',
    );
  }

  void _showCommentsModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.65,
        minChildSize: 0.4,
        maxChildSize: 0.95,
        builder: (_, scrollController) => _ReelCommentsSheet(
          title: widget.data['title'] ?? 'Noir Reel',
          user: widget.data['user'] ?? '@noir.atelier',
          scrollController: scrollController,
        ),
      ),
    );
  }

  String _formatCount(int count) {
    if (count >= 1000000) return '${(count / 1000000).toStringAsFixed(1)}M';
    if (count >= 1000) return '${(count / 1000).toStringAsFixed(1)}k';
    return count.toString();
  }

  Widget _buildEqualizerBars() {
    if (widget.isMuted || !_isPlaying) {
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(4, (i) => Container(
          width: 2.5,
          height: 8.0,
          margin: const EdgeInsets.only(right: 2),
          decoration: BoxDecoration(color: const Color(0xFFC49A45).withValues(alpha: 0.4), borderRadius: BorderRadius.circular(2)),
        )),
      );
    }

    return AnimatedBuilder(
      animation: _discAnimController,
      builder: (context, _) {
        final val = _discAnimController.value;
        final h1 = 5.0 + 9.0 * (0.5 + 0.5 * math.sin(val * 6.28)).abs();
        final h2 = 4.0 + 10.0 * (0.5 + 0.5 * math.sin((val + 0.25) * 6.28)).abs();
        final h3 = 6.0 + 8.0 * (0.5 + 0.5 * math.sin((val + 0.5) * 6.28)).abs();
        final h4 = 4.0 + 11.0 * (0.5 + 0.5 * math.sin((val + 0.75) * 6.28)).abs();

        return Row(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            _eqBar(h1),
            _eqBar(h2),
            _eqBar(h3),
            _eqBar(h4),
          ],
        );
      },
    );
  }

  Widget _eqBar(double h) {
    return Container(
      width: 2.5,
      height: h.clamp(4.0, 16.0),
      margin: const EdgeInsets.only(right: 2),
      decoration: BoxDecoration(
        color: const Color(0xFFC49A45),
        borderRadius: BorderRadius.circular(2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isLiked = widget.data['is_liked'] == true;
    final likesCount = (widget.data['likes_count'] as num?)?.toInt() ?? 0;
    final commentsCount = (widget.data['comments_count'] as num?)?.toInt() ?? 0;
    final rawThumb = widget.data['thumbnail']?.toString() ?? widget.data['featured_image']?.toString() ?? '';
    final thumb = Product.fixImageUrl(rawThumb.isNotEmpty ? rawThumb : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800');
    final user = widget.data['user']?.toString() ?? '@noir.atelier';
    final title = widget.data['title']?.toString() ?? 'Noir Signature Collection';
    final products = widget.data['products'] as List<dynamic>? ?? [];
    final sound = _currentSound;
    final badge = widget.data['badge']?.toString() ?? 'RUNWAY EXCLUSIVE';

    return GestureDetector(
      onTap: _togglePlayPause,
      onDoubleTap: _onDoubleTap,
      child: Stack(
        fit: StackFit.expand,
        children: [
          // ── Video Showcase with Cinematic Movement ──
          Container(
            color: Colors.black,
            child: Stack(
              fit: StackFit.expand,
              children: [
                // 1. Moving Background / Preview (Cinematic Pan & Scale)
                AnimatedBuilder(
                  animation: _motionController,
                  builder: (context, child) {
                    return Transform.scale(
                      scale: _scaleAnimation.value,
                      alignment: _alignmentAnimation.value,
                      child: CachedNetworkImage(
                        imageUrl: thumb,
                        fit: BoxFit.cover,
                        placeholder: (_, _) => Container(color: const Color(0xFF131511)),
                        errorWidget: (_, _, _) => Container(color: const Color(0xFF131511)),
                      ),
                    );
                  },
                ),

                // 2. Real Video Player Stream with smooth fade-in
                if (_isInitialized && _controller != null && _controller!.value.isInitialized)
                  Positioned.fill(
                    child: AnimatedOpacity(
                      opacity: _isInitialized ? 1.0 : 0.0,
                      duration: const Duration(milliseconds: 400),
                      child: FittedBox(
                        fit: BoxFit.cover,
                        child: SizedBox(
                          width: _controller!.value.size.width > 0 ? _controller!.value.size.width : 400,
                          height: _controller!.value.size.height > 0 ? _controller!.value.size.height : 700,
                          child: VideoPlayer(_controller!),
                        ),
                      ),
                    ),
                  ),

                // 3. Subtle Light-Sweep Shimmer across the reel
                AnimatedBuilder(
                  animation: _motionController,
                  builder: (context, _) {
                    return Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment(-1.8 + _motionController.value * 3.6, -1.0),
                          end: Alignment(-0.8 + _motionController.value * 3.6, 1.0),
                          colors: [
                            Colors.transparent,
                            Colors.white.withValues(alpha: 0.05),
                            Colors.transparent,
                          ],
                          stops: const [0.0, 0.5, 1.0],
                        ),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),

          // ── Cinematic Vignette & Ambient Glow ──
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  stops: const [0.0, 0.25, 0.6, 1.0],
                  colors: [
                    Colors.black.withValues(alpha: 0.6),
                    Colors.transparent,
                    Colors.transparent,
                    Colors.black.withValues(alpha: 0.9),
                  ],
                ),
              ),
            ),
          ),

          // ── Central Play/Pause Flash Indicator ──
          if (!_isPlaying || _showPlayStateIcon)
            Center(
              child: AnimatedOpacity(
                duration: const Duration(milliseconds: 200),
                opacity: (!_isPlaying || _showPlayStateIcon) ? 1.0 : 0.0,
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.65),
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFFC49A45).withValues(alpha: 0.8), width: 1.5),
                    boxShadow: [
                      BoxShadow(color: const Color(0xFFC49A45).withValues(alpha: 0.25), blurRadius: 16),
                    ],
                  ),
                  child: Icon(
                    _isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded,
                    color: Colors.white,
                    size: 48,
                  ),
                ),
              ),
            ),

          // ── Double Tap Floating Heart Animation ──
          Center(
            child: ScaleTransition(
              scale: Tween<double>(begin: 0.0, end: 1.4).animate(
                CurvedAnimation(parent: _heartAnimController, curve: Curves.elasticOut),
              ),
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Icon(
                    Icons.favorite_rounded,
                    color: const Color(0xFFE53935).withValues(alpha: 0.4),
                    size: 130,
                  ),
                  const Icon(
                    Icons.favorite_rounded,
                    color: Color(0xFFE53935),
                    size: 110,
                  ),
                ],
              ),
            ),
          ),

          // ── Right Action Rail with Animated Slide & Fade Entrance ──
          Positioned(
            right: 12,
            bottom: 80,
            child: SlideTransition(
              position: _sideSlideAnimation,
              child: FadeTransition(
                opacity: _fadeAnimation,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // User Avatar with Gold Border
                    Container(
                      padding: const EdgeInsets.all(2),
                      decoration: const BoxDecoration(
                        color: Color(0xFFC49A45),
                        shape: BoxShape.circle,
                      ),
                      child: const CircleAvatar(
                        radius: 22,
                        backgroundColor: Color(0xFF1E201B),
                        child: Icon(Icons.person, color: Colors.white70, size: 24),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Like Button
                    IconButton(
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      icon: Icon(
                        isLiked ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                        color: isLiked ? const Color(0xFFE53935) : Colors.white,
                        size: 32,
                      ),
                      onPressed: widget.onLike,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _formatCount(likesCount),
                      style: GoogleFonts.urbanist(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Comments Button
                    IconButton(
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      icon: const Icon(Icons.mode_comment_outlined, color: Colors.white, size: 30),
                      onPressed: _showCommentsModal,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _formatCount(commentsCount),
                      style: GoogleFonts.urbanist(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Share Button (Native Sharing)
                    IconButton(
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      icon: const Icon(Icons.share_outlined, color: Colors.white, size: 30),
                      onPressed: _shareReel,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Share',
                      style: GoogleFonts.urbanist(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                        fontSize: 11,
                      ),
                    ),
                    const SizedBox(height: 22),

                    // ── Rotating Luxury Vinyl Sound Disc with Pulsing Glow ──
                    RotationTransition(
                      turns: _discAnimController,
                      child: Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: const RadialGradient(
                            colors: [
                              Color(0xFF2C3026),
                              Color(0xFF0D0E0B),
                              Color(0xFF1E211A),
                              Color(0xFF0D0E0B),
                            ],
                            stops: [0.0, 0.4, 0.6, 1.0],
                          ),
                          border: Border.all(color: const Color(0xFFC49A45), width: 1.5),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFC49A45).withValues(alpha: 0.3),
                              blurRadius: 10,
                              spreadRadius: 1,
                            ),
                          ],
                        ),
                        child: Center(
                          child: Container(
                            width: 16,
                            height: 16,
                            decoration: const BoxDecoration(
                              color: Color(0xFFC49A45),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.music_note_rounded, color: Colors.black, size: 10),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          // ── Bottom Content, Product Capsule & Music Track ──
          Positioned(
            left: 16,
            right: 76,
            bottom: 24,
            child: SlideTransition(
              position: _slideAnimation,
              child: FadeTransition(
                opacity: _fadeAnimation,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Username tag
                    Row(
                      children: [
                        Text(
                          user,
                          style: GoogleFonts.urbanist(
                            color: Colors.white,
                            fontWeight: FontWeight.w900,
                            fontSize: 15,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFC49A45).withValues(alpha: 0.3),
                            borderRadius: BorderRadius.circular(4),
                            border: Border.all(color: const Color(0xFFC49A45), width: 0.5),
                          ),
                          child: Text(
                            badge,
                            style: const TextStyle(
                              fontSize: 8.5,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFFE5D5BA),
                              letterSpacing: 0.8,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),

                    // Caption
                    Text(
                      title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.urbanist(
                        color: Colors.white.withValues(alpha: 0.9),
                        fontSize: 13,
                        height: 1.3,
                      ),
                    ),
                    const SizedBox(height: 8),

                    // Soundtrack ticker with Real Equalizer Animation
                    Row(
                      children: [
                        _buildEqualizerBars(),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '♫ ${sound['title']!} • ${sound['artist']!}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: GoogleFonts.urbanist(
                              color: const Color(0xFFE5D5BA),
                              fontWeight: FontWeight.w600,
                              fontSize: 12,
                              letterSpacing: 0.2,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Attached Product Card
                    if (products.isNotEmpty)
                      _buildProductCapsule(products.first),
                  ],
                ),
              ),
            ),
          ),

          // ── Video Scrubber / Progress Line ──
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: SizedBox(
              height: 3,
              child: LinearProgressIndicator(
                value: _progress,
                backgroundColor: Colors.white12,
                valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFFC49A45)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProductCapsule(dynamic productRaw) {
    final p = productRaw is Map<String, dynamic> ? productRaw : <String, dynamic>{};
    final pId = (p['id'] as num?)?.toInt() ?? 1;
    final name = p['name']?.toString() ?? 'Noir Piece';
    final price = (p['price'] as num?)?.toDouble() ?? 0.0;
    final rawImg = p['image']?.toString() ?? p['featured_image']?.toString() ?? '';
    final image = Product.fixImageUrl(rawImg.isNotEmpty ? rawImg : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=200');

    String priceStr;
    try {
      priceStr = Provider.of<CurrencyProvider>(context, listen: false).format(price);
    } catch (_) {
      priceStr = '₦${price.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}';
    }

    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.65),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white24, width: 0.8),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: CachedNetworkImage(
              imageUrl: image,
              width: 44,
              height: 44,
              fit: BoxFit.cover,
              errorWidget: (_, _, _) => Container(color: Colors.grey.shade800),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: InkWell(
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => ProductDetailScreen(productId: pId),
                  ),
                );
              },
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.urbanist(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    priceStr,
                    style: GoogleFonts.urbanist(
                      color: NoirTheme.primary,
                      fontWeight: FontWeight.w800,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
          InkWell(
            onTap: () {
              context.read<CartProvider>().addItem(
                CartItem(
                  productId: pId,
                  name: name,
                  price: price,
                  image: image,
                  quantity: 1,
                ),
              );
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('Added $name to your bag!'),
                  backgroundColor: NoirTheme.primary,
                  behavior: SnackBarBehavior.floating,
                ),
              );
            },
            borderRadius: BorderRadius.circular(20),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: NoirTheme.primary,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.shopping_bag_outlined, color: Colors.white, size: 14),
                  const SizedBox(width: 4),
                  Text(
                    'Bag',
                    style: GoogleFonts.urbanist(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ReelCommentsSheet extends StatefulWidget {
  final String title;
  final String user;
  final ScrollController scrollController;

  const _ReelCommentsSheet({
    required this.title,
    required this.user,
    required this.scrollController,
  });

  @override
  State<_ReelCommentsSheet> createState() => _ReelCommentsSheetState();
}

class _ReelCommentsSheetState extends State<_ReelCommentsSheet> {
  final TextEditingController _textCtrl = TextEditingController();
  final List<Map<String, dynamic>> _comments = [
    {
      'user': '@temi.fashion',
      'text': 'The drape and tailoring on this piece are extraordinary! 🔥',
      'time': '2h ago'
    },
    {
      'user': '@zainab.style',
      'text': 'Are sizes restocked for the capsule collection?',
      'time': '4h ago'
    },
    {
      'user': '@noir.atelier',
      'text': 'All sizes are currently available online & in-app with express delivery.',
      'time': '1h ago'
    },
  ];

  @override
  void dispose() {
    _textCtrl.dispose();
    super.dispose();
  }

  void _addComment() {
    final val = _textCtrl.text.trim();
    if (val.isEmpty) return;

    setState(() {
      _comments.insert(0, {
        'user': '@noir.member',
        'text': val,
        'time': 'Just now',
      });
    });
    _textCtrl.clear();
    FocusScope.of(context).unfocus();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
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
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Reel Comments (${_comments.length})',
                  style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded, size: 20),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          Expanded(
            child: ListView.builder(
              controller: widget.scrollController,
              padding: const EdgeInsets.all(16),
              itemCount: _comments.length,
              itemBuilder: (ctx, i) {
                final c = _comments[i];
                return Padding(
                  padding: const EdgeInsets.only(bottom: 16.0),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      CircleAvatar(
                        radius: 16,
                        backgroundColor: NoirTheme.primary.withValues(alpha: 0.2),
                        child: Text(
                          c['user'].toString().replaceAll('@', '').substring(0, 1).toUpperCase(),
                          style: TextStyle(color: NoirTheme.primary, fontWeight: FontWeight.bold, fontSize: 12),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  c['user'] ?? '',
                                  style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 13),
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  c['time'] ?? '',
                                  style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 11),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              c['text'] ?? '',
                              style: GoogleFonts.urbanist(fontSize: 14),
                            ),
                          ],
                        ),
                      ),
                    ],
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
                    controller: _textCtrl,
                    decoration: InputDecoration(
                      hintText: 'Add a comment...',
                      hintStyle: GoogleFonts.urbanist(color: Colors.grey, fontSize: 14),
                      border: InputBorder.none,
                    ),
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.send_rounded, color: NoirTheme.primary),
                  onPressed: _addComment,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
