import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../network/api_client.dart';
import '../theme/app_theme.dart';

class AppNetworkImage extends StatelessWidget {
  const AppNetworkImage({
    super.key,
    required this.url,
    this.fit = BoxFit.cover,
    this.width,
    this.height,
    this.borderRadius,
  });

  final String? url;
  final BoxFit fit;
  final double? width;
  final double? height;
  final BorderRadius? borderRadius;

  @override
  Widget build(BuildContext context) {
    final resolved = ApiClient.resolveMediaUrl(url);
    final child = resolved.isEmpty
        ? _placeholder()
        : CachedNetworkImage(
            imageUrl: resolved,
            fit: fit,
            width: width,
            height: height,
            memCacheWidth: width != null && width!.isFinite
                ? (width! * MediaQuery.devicePixelRatioOf(context)).round()
                : null,
            placeholder: (_, __) => _placeholder(),
            errorWidget: (_, __, ___) => _placeholder(isError: true),
          );

    if (borderRadius != null) {
      return ClipRRect(borderRadius: borderRadius!, child: child);
    }
    return child;
  }

  Widget _placeholder({bool isError = false}) {
    return Container(
      width: width,
      height: height,
      color: AppTheme.softGray,
      alignment: Alignment.center,
      child: Icon(
        isError ? Icons.broken_image_outlined : Icons.image_outlined,
        color: AppTheme.mutedGray,
        size: 28,
      ),
    );
  }
}
