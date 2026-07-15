import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/models/models.dart';
import '../../../core/theme/app_theme.dart';

class BreakingNewsTicker extends StatefulWidget {
  const BreakingNewsTicker({super.key, required this.items});

  final List<BreakingNewsItem> items;

  @override
  State<BreakingNewsTicker> createState() => _BreakingNewsTickerState();
}

class _BreakingNewsTickerState extends State<BreakingNewsTicker>
    with SingleTickerProviderStateMixin {
  late final ScrollController _scroll;
  late final AnimationController _pulse;

  @override
  void initState() {
    super.initState();
    _scroll = ScrollController();
    _pulse = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat(reverse: true);
    WidgetsBinding.instance.addPostFrameCallback((_) => _startMarquee());
  }

  Future<void> _startMarquee() async {
    if (!mounted || !_scroll.hasClients) return;
    while (mounted) {
      await Future<void>.delayed(const Duration(milliseconds: 800));
      if (!mounted || !_scroll.hasClients) return;
      final max = _scroll.position.maxScrollExtent;
      if (max <= 0) continue;
      await _scroll.animateTo(
        max,
        duration: Duration(milliseconds: (max * 18).clamp(8000, 28000).toInt()),
        curve: Curves.linear,
      );
      if (!mounted) return;
      await _scroll.animateTo(0, duration: Duration.zero, curve: Curves.linear);
    }
  }

  @override
  void dispose() {
    _pulse.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _open(BreakingNewsItem item) {
    if (item.newsSlug != null && item.newsSlug!.isNotEmpty) {
      context.push('/news/${item.newsSlug}');
    } else if (item.newsId != null) {
      context.push('/news/${item.newsId}');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.items.isEmpty) return const SizedBox.shrink();

    final text = widget.items.map((e) => e.title).join('   •   ');

    return Container(
      height: 40,
      color: AppTheme.nearBlack,
      child: Row(
        children: [
          FadeTransition(
            opacity: Tween(begin: 0.55, end: 1.0).animate(_pulse),
            child: Container(
              color: AppTheme.brandRed,
              padding: const EdgeInsets.symmetric(horizontal: 10),
              alignment: Alignment.center,
              child: const Text(
                'SON DAKİKA',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 11,
                  letterSpacing: 0.5,
                ),
              ),
            ),
          ),
          Expanded(
            child: ListView(
              controller: _scroll,
              scrollDirection: Axis.horizontal,
              physics: const NeverScrollableScrollPhysics(),
              children: [
                const SizedBox(width: 12),
                Center(
                  child: GestureDetector(
                    onTap: () {
                      final first = widget.items.first;
                      _open(first);
                    },
                    child: Text(
                      text,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 48),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class LiveWatchCta extends StatefulWidget {
  const LiveWatchCta({super.key, this.enabled = true});

  final bool enabled;

  @override
  State<LiveWatchCta> createState() => _LiveWatchCtaState();
}

class _LiveWatchCtaState extends State<LiveWatchCta>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!widget.enabled) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
      child: Material(
        color: AppTheme.brandRed,
        child: InkWell(
          onTap: () => context.go('/live'),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                ScaleTransition(
                  scale: Tween(begin: 0.85, end: 1.1).animate(
                    CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
                  ),
                  child: Container(
                    width: 10,
                    height: 10,
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Text(
                  'CANLI YAYINI İZLE',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.8,
                      ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
