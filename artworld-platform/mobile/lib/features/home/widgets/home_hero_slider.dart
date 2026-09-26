import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/models/models.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/network_image.dart';

class HomeHeroSlider extends StatefulWidget {
  const HomeHeroSlider({
    super.key,
    required this.featuredNews,
    required this.banners,
  });

  final List<NewsItem> featuredNews;
  final List<BannerItem> banners;

  @override
  State<HomeHeroSlider> createState() => _HomeHeroSliderState();
}

class _HomeHeroSliderState extends State<HomeHeroSlider> {
  final _controller = PageController();
  int _index = 0;

  List<_HeroSlide> get _slides {
    if (widget.featuredNews.isNotEmpty) {
      return widget.featuredNews
          .map(
            (n) => _HeroSlide(
              title: n.title,
              image: n.coverImage,
              category: n.category?.name,
              onTap: () => context.push('/news/${n.slug}'),
            ),
          )
          .toList();
    }
    return widget.banners
        .map(
          (b) => _HeroSlide(
            title: b.title,
            image: b.image,
            onTap: () => _openBanner(b),
          ),
        )
        .toList();
  }

  void _openBanner(BannerItem b) {
    final type = b.targetType;
    if (type == 'news' && b.targetId != null) {
      context.push('/news/${b.targetId}');
      return;
    }
    if (type == 'url' && (b.targetUrl?.contains('live') ?? false)) {
      context.go('/live');
      return;
    }
    if (b.targetUrl != null && b.targetUrl!.startsWith('/')) {
      context.push(b.targetUrl!);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final slides = _slides;
    if (slides.isEmpty) return const SizedBox.shrink();

    return Column(
      children: [
        AspectRatio(
          aspectRatio: 16 / 10,
          child: PageView.builder(
            controller: _controller,
            itemCount: slides.length,
            onPageChanged: (i) => setState(() => _index = i),
            itemBuilder: (context, i) {
              final slide = slides[i];
              return GestureDetector(
                onTap: slide.onTap,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    AppNetworkImage(url: slide.image),
                    const DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.transparent,
                            Color(0xCC000000),
                          ],
                          stops: [0.35, 1],
                        ),
                      ),
                    ),
                    Positioned(
                      left: 16,
                      right: 16,
                      bottom: 20,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (slide.category != null && slide.category!.isNotEmpty)
                            Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 3,
                              ),
                              color: AppTheme.brandRed,
                              child: Text(
                                slide.category!.toUpperCase(),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.6,
                                ),
                              ),
                            ),
                          Text(
                            slide.title,
                            maxLines: 3,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w700,
                                  height: 1.15,
                                  shadows: const [
                                    Shadow(blurRadius: 8, color: Colors.black54),
                                  ],
                                ),
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
        if (slides.length > 1)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(slides.length, (i) {
                final active = i == _index;
                return AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  width: active ? 18 : 6,
                  height: 6,
                  decoration: BoxDecoration(
                    color: active ? AppTheme.brandRed : AppTheme.borderGray,
                    borderRadius: BorderRadius.circular(1),
                  ),
                );
              }),
            ),
          ),
      ],
    );
  }
}

class _HeroSlide {
  const _HeroSlide({
    required this.title,
    this.image,
    this.category,
    required this.onTap,
  });

  final String title;
  final String? image;
  final String? category;
  final VoidCallback onTap;
}
