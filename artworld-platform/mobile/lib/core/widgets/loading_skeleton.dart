import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class LoadingSkeleton extends StatelessWidget {
  const LoadingSkeleton({
    super.key,
    this.height = 16,
    this.width,
    this.borderRadius = 2,
  });

  final double height;
  final double? width;
  final double borderRadius;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      width: width,
      decoration: BoxDecoration(
        color: AppTheme.softGray,
        borderRadius: BorderRadius.circular(borderRadius),
      ),
    );
  }
}

class HomeLoadingSkeleton extends StatelessWidget {
  const HomeLoadingSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const NeverScrollableScrollPhysics(),
      children: const [
        LoadingSkeleton(height: 220, borderRadius: 0),
        SizedBox(height: 12),
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 16),
          child: LoadingSkeleton(height: 48),
        ),
        SizedBox(height: 12),
        LoadingSkeleton(height: 36, borderRadius: 0),
        SizedBox(height: 16),
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 16),
          child: Column(
            children: [
              LoadingSkeleton(height: 18, width: 160),
              SizedBox(height: 12),
              LoadingSkeleton(height: 100),
              SizedBox(height: 12),
              LoadingSkeleton(height: 100),
              SizedBox(height: 12),
              LoadingSkeleton(height: 100),
            ],
          ),
        ),
      ],
    );
  }
}

class ListLoadingSkeleton extends StatelessWidget {
  const ListLoadingSkeleton({super.key, this.itemCount = 6});

  final int itemCount;

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: itemCount,
      separatorBuilder: (_, __) => const SizedBox(height: 12),
      itemBuilder: (_, __) => const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          LoadingSkeleton(height: 84, width: 112),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                LoadingSkeleton(height: 14, width: 80),
                SizedBox(height: 8),
                LoadingSkeleton(height: 16),
                SizedBox(height: 6),
                LoadingSkeleton(height: 16, width: 180),
                SizedBox(height: 8),
                LoadingSkeleton(height: 12, width: 100),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
