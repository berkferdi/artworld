import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../theme/app_theme.dart';
import 'app_video_player.dart';
import 'error_view.dart';
import 'network_image.dart';

/// Chooses playback strategy from API `source_type` / `video_type`.
/// - file_server / mp4 / hls → [AppVideoPlayer]
/// - youtube → in-app launch via YouTube URL (thumbnail + play)
class SmartVideoPlayer extends StatelessWidget {
  const SmartVideoPlayer({
    super.key,
    required this.url,
    this.sourceType,
    this.videoType,
    this.posterUrl,
    this.title,
    this.autoPlay = true,
  });

  final String url;
  final String? sourceType;
  final String? videoType;
  final String? posterUrl;
  final String? title;
  final bool autoPlay;

  bool get _isYoutube {
    final source = (sourceType ?? '').toLowerCase();
    final type = (videoType ?? '').toLowerCase();
    if (source == 'youtube' || type == 'youtube') return true;
    return url.contains('youtu.be') || url.contains('youtube.com');
  }

  static String? youtubeId(String url) {
    final patterns = [
      RegExp(r'youtu\.be/([A-Za-z0-9_-]{6,})'),
      RegExp(r'youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})'),
      RegExp(r'youtube\.com/embed/([A-Za-z0-9_-]{6,})'),
      RegExp(r'youtube\.com/shorts/([A-Za-z0-9_-]{6,})'),
    ];
    for (final p in patterns) {
      final m = p.firstMatch(url);
      if (m != null) return m.group(1);
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) {
      return const AspectRatio(
        aspectRatio: 16 / 9,
        child: ColoredBox(
          color: Colors.black,
          child: ErrorView(message: 'Video adresi bulunamadı.'),
        ),
      );
    }

    if (_isYoutube) {
      return _YoutubeLaunchPlayer(
        url: url,
        posterUrl: posterUrl,
        title: title,
      );
    }

    return AppVideoPlayer(
      url: url,
      autoPlay: autoPlay,
      posterUrl: posterUrl,
      title: title,
    );
  }
}

class _YoutubeLaunchPlayer extends StatelessWidget {
  const _YoutubeLaunchPlayer({
    required this.url,
    this.posterUrl,
    this.title,
  });

  final String url;
  final String? posterUrl;
  final String? title;

  Future<void> _open() async {
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final id = SmartVideoPlayer.youtubeId(url);
    final thumb = (posterUrl != null && posterUrl!.isNotEmpty)
        ? posterUrl!
        : (id != null ? 'https://img.youtube.com/vi/$id/hqdefault.jpg' : null);

    return AspectRatio(
      aspectRatio: 16 / 9,
      child: ColoredBox(
        color: Colors.black,
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (thumb != null)
              AppNetworkImage(url: thumb, fit: BoxFit.cover)
            else
              const ColoredBox(color: Colors.black),
            Container(
              alignment: Alignment.center,
              color: Colors.black45,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  IconButton.filled(
                    onPressed: _open,
                    style: IconButton.styleFrom(
                      backgroundColor: AppTheme.brandRed,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.all(16),
                    ),
                    icon: const Icon(Icons.play_arrow, size: 36),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    title ?? 'YouTube’da izle',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
