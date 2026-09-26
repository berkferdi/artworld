import 'package:flutter_test/flutter_test.dart';

import 'package:artworld_mobile/core/config/app_config.dart';
import 'package:artworld_mobile/core/network/api_client.dart';
import 'package:artworld_mobile/core/network/api_exception.dart';
import 'package:artworld_mobile/features/home/data/home_repository.dart';
import 'package:artworld_mobile/features/live/data/live_repository.dart';
import 'package:artworld_mobile/features/news/data/news_repository.dart';
import 'package:artworld_mobile/features/programs/data/programs_repository.dart';
import 'package:artworld_mobile/features/search/data/search_repository.dart';
import 'package:artworld_mobile/features/videos/data/videos_repository.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Real-network integration tests.
///
/// IMPORTANT: Do NOT call [TestWidgetsFlutterBinding.ensureInitialized] here.
/// That binding installs a mock HttpClient that returns HTTP 400 for every
/// request and blocks real network I/O.
///
/// Requires: `php -S 127.0.0.1:8080 router.php` in backend/public
void main() {
  SharedPreferences.setMockInitialValues({});

  late ApiClient api;
  var apiAvailable = false;

  setUpAll(() async {
    api = ApiClient();
    try {
      final home = await api.get('/home');
      apiAvailable = home['success'] == true;
    } catch (e) {
      apiAvailable = false;
      // ignore: avoid_print
      print('API probe failed: $e');
    }
    // ignore: avoid_print
    print('API available=$apiAvailable base=${AppConfig.apiBaseUrl}');
  });

  test('AppConfig exposes centralized API URLs', () {
    expect(AppConfig.apiBaseUrl, contains('/api/v1'));
    expect(AppConfig.mediaBaseUrl, isNotEmpty);
    expect(AppConfig.androidEmulatorApiBaseUrl, contains('10.0.2.2'));
  });

  test('GET /home → HomeData with news/videos/programs/live', () async {
    expect(apiAvailable, isTrue, reason: 'API must be running');

    final home = await HomeRepository(api).fetchHome(preferCacheOnError: false);

    expect(home.latestNews, isNotEmpty);
    expect(home.latestVideos, isNotEmpty);
    expect(home.programs, isNotEmpty);
    expect(home.liveStream, isNotNull);
    expect(home.liveStream!.streamUrl, isNotEmpty);
    expect(home.settings.appName, isNotEmpty);

    final newsTitles = [
      ...home.featuredNews.map((e) => e.title),
      ...home.latestNews.map((e) => e.title),
    ];
    expect(
      newsTitles.any(
        (t) => t.contains('Canlı Test Haberi') || t.contains('Art World'),
      ),
      isTrue,
    );

    final videoTitles = home.latestVideos.map((e) => e.title).toList();
    expect(
      videoTitles.any(
        (t) => t.contains('Canlı Test Videosu') || t.contains('Manşet'),
      ),
      isTrue,
    );
  });

  test('News list + detail for admin-created item', () async {
    expect(apiAvailable, isTrue);

    final newsRepo = NewsRepository(api);
    final list = await newsRepo.list(search: 'Canlı Test');
    expect(list, isNotEmpty);

    final detail = await newsRepo.detail('canli-test-haberi-122254');
    expect(detail.title, contains('Canlı Test Haberi'));
    expect(detail.content, isNotNull);
  });

  test('Videos expose MP4 and HLS URLs from API', () async {
    expect(apiAvailable, isTrue);

    final videos = VideosRepository(api);
    final list = await videos.list();
    expect(list, isNotEmpty);

    final mp4 = list.where((v) => v.videoType == 'mp4').toList();
    final hls = list.where((v) => v.videoType == 'hls').toList();
    expect(mp4, isNotEmpty);
    expect(hls, isNotEmpty);

    final mp4Detail = await videos.detail(mp4.first.slug);
    expect(mp4Detail.videoUrl, isNotNull);
    expect(mp4Detail.videoUrl!.toLowerCase(), contains('.mp4'));

    final hlsDetail = await videos.detail(hls.first.slug);
    expect(hlsDetail.videoUrl, isNotNull);
    expect(
      hlsDetail.videoUrl!.contains('.m3u8') || hlsDetail.videoType == 'hls',
      isTrue,
    );

    final testVideo = await videos.detail('canli-test-videosu');
    expect(testVideo.title, 'Canlı Test Videosu');
  });

  test('Programs + episodes from API', () async {
    expect(apiAvailable, isTrue);

    final programs = ProgramsRepository(api);
    final list = await programs.list();
    expect(list, isNotEmpty);

    final program = await programs.detail('canli-test-programi');
    expect(program.title, 'Canlı Test Programı');
    expect(program.episodes, isNotEmpty);
    expect(program.episodes.first.title, contains('Canlı Test Bölümü'));

    final episode = await programs.episodeDetail('canli-test-bolumu-1');
    expect(episode.videoUrl, isNotEmpty);
  });

  test('Live stream URL from API', () async {
    expect(apiAvailable, isTrue);

    final live = await LiveRepository(api).fetch();
    expect(live.streamUrl, isNotEmpty);
    expect(live.streamType, anyOf('hls', 'youtube', 'external'));
  });

  test('Search returns real API results', () async {
    expect(apiAvailable, isTrue);

    final results = await SearchRepository(api).search('sanat');
    expect(
      results.news.isNotEmpty ||
          results.videos.isNotEmpty ||
          results.programs.isNotEmpty,
      isTrue,
    );
  });

  test('Missing endpoint returns Turkish ApiException', () async {
    expect(apiAvailable, isTrue);

    try {
      await api.get('/this-endpoint-does-not-exist-xyz');
      fail('Expected ApiException');
    } on ApiException catch (e) {
      expect(e.message, isNotEmpty);
      final lower = e.message.toLowerCase();
      expect(
        lower.contains('bulunamadı') ||
            lower.contains('hata') ||
            lower.contains('endpoint'),
        isTrue,
      );
    }
  });

  test('resolveMediaUrl keeps absolute URLs and prefixes relative', () {
    expect(
      ApiClient.resolveMediaUrl('https://cdn.example.com/a.mp4'),
      'https://cdn.example.com/a.mp4',
    );
    expect(
      ApiClient.resolveMediaUrl('/images/x.jpg'),
      '${AppConfig.mediaBaseUrl}/images/x.jpg',
    );
  });
}
