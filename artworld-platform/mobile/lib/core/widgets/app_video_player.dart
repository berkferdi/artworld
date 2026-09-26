import 'package:chewie/chewie.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:video_player/video_player.dart';

import '../theme/app_theme.dart';
import 'error_view.dart';

/// Network video player supporting MP4 and HLS (.m3u8).
/// Streams progressively — does not load the entire file into memory.
class AppVideoPlayer extends StatefulWidget {
  const AppVideoPlayer({
    super.key,
    required this.url,
    this.autoPlay = true,
    this.aspectRatio,
    this.posterUrl,
    this.title,
  });

  final String url;
  final bool autoPlay;
  final double? aspectRatio;
  final String? posterUrl;
  final String? title;

  @override
  State<AppVideoPlayer> createState() => _AppVideoPlayerState();
}

class _AppVideoPlayerState extends State<AppVideoPlayer> {
  VideoPlayerController? _videoController;
  ChewieController? _chewieController;
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void didUpdateWidget(covariant AppVideoPlayer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.url != widget.url) {
      _disposePlayers();
      _init();
    }
  }

  Future<void> _init() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final uri = Uri.parse(widget.url);
      final controller = VideoPlayerController.networkUrl(uri);
      await controller.initialize();

      final chewie = ChewieController(
        videoPlayerController: controller,
        autoPlay: widget.autoPlay,
        looping: false,
        allowFullScreen: true,
        allowMuting: true,
        showControls: true,
        aspectRatio: widget.aspectRatio ?? controller.value.aspectRatio,
        materialProgressColors: ChewieProgressColors(
          playedColor: AppTheme.brandRed,
          handleColor: AppTheme.brandRed,
          bufferedColor: AppTheme.borderGray,
          backgroundColor: Colors.black26,
        ),
        deviceOrientationsOnEnterFullScreen: const [
          DeviceOrientation.landscapeLeft,
          DeviceOrientation.landscapeRight,
          DeviceOrientation.portraitUp,
        ],
        deviceOrientationsAfterFullScreen: const [
          DeviceOrientation.portraitUp,
        ],
        errorBuilder: (context, errorMessage) {
          return ErrorView(
            message: 'Video yüklenemedi. Lütfen tekrar deneyin.',
            onRetry: _retry,
          );
        },
      );

      if (!mounted) {
        controller.dispose();
        chewie.dispose();
        return;
      }

      setState(() {
        _videoController = controller;
        _chewieController = chewie;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = 'Video oynatılamadı. Bağlantıyı kontrol edip tekrar deneyin.';
      });
    }
  }

  void _retry() {
    _disposePlayers();
    _init();
  }

  void _disposePlayers() {
    _chewieController?.dispose();
    _videoController?.dispose();
    _chewieController = null;
    _videoController = null;
  }

  @override
  void dispose() {
    _disposePlayers();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return AspectRatio(
        aspectRatio: widget.aspectRatio ?? 16 / 9,
        child: const ColoredBox(
          color: Colors.black,
          child: Center(
            child: CircularProgressIndicator(color: AppTheme.brandRed),
          ),
        ),
      );
    }

    if (_error != null || _chewieController == null) {
      return AspectRatio(
        aspectRatio: widget.aspectRatio ?? 16 / 9,
        child: ColoredBox(
          color: Colors.black,
          child: ErrorView(
            message: _error ?? 'Video yüklenemedi.',
            onRetry: _retry,
          ),
        ),
      );
    }

    return AspectRatio(
      aspectRatio: widget.aspectRatio ?? _chewieController!.aspectRatio ?? 16 / 9,
      child: ColoredBox(
        color: Colors.black,
        child: Chewie(controller: _chewieController!),
      ),
    );
  }
}
