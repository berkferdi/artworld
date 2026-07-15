class CategoryRef {
  const CategoryRef({required this.name, required this.slug});

  final String name;
  final String slug;

  factory CategoryRef.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const CategoryRef(name: '', slug: '');
    }
    return CategoryRef(
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
    );
  }
}

class Category {
  const Category({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.image,
    this.sortOrder = 0,
  });

  final int id;
  final String name;
  final String slug;
  final String? description;
  final String? image;
  final int sortOrder;

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      image: json['image'] as String?,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }
}

class NewsItem {
  const NewsItem({
    required this.id,
    required this.title,
    required this.slug,
    this.summary,
    this.coverImage,
    this.author,
    this.isFeatured = false,
    this.isBreaking = false,
    this.publishedAt,
    this.category,
    this.content,
    this.categoryId,
    this.status,
    this.gallery = const [],
    this.related = const [],
  });

  final int id;
  final String title;
  final String slug;
  final String? summary;
  final String? coverImage;
  final String? author;
  final bool isFeatured;
  final bool isBreaking;
  final String? publishedAt;
  final CategoryRef? category;
  final String? content;
  final int? categoryId;
  final String? status;
  final List<NewsGalleryItem> gallery;
  final List<NewsItem> related;

  factory NewsItem.fromJson(Map<String, dynamic> json) {
    final galleryJson = json['gallery'];
    final relatedJson = json['related'];
    return NewsItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      summary: json['summary'] as String?,
      coverImage: json['cover_image'] as String?,
      author: json['author'] as String?,
      isFeatured: json['is_featured'] == true || json['is_featured'] == 1,
      isBreaking: json['is_breaking'] == true || json['is_breaking'] == 1,
      publishedAt: json['published_at'] as String?,
      category: json['category'] is Map
          ? CategoryRef.fromJson(Map<String, dynamic>.from(json['category'] as Map))
          : null,
      content: json['content'] as String?,
      categoryId: (json['category_id'] as num?)?.toInt(),
      status: json['status'] as String?,
      gallery: galleryJson is List
          ? galleryJson
              .whereType<Map>()
              .map((e) => NewsGalleryItem.fromJson(Map<String, dynamic>.from(e)))
              .toList()
          : const [],
      related: relatedJson is List
          ? relatedJson
              .whereType<Map>()
              .map((e) => NewsItem.fromJson(Map<String, dynamic>.from(e)))
              .toList()
          : const [],
    );
  }
}

class NewsGalleryItem {
  const NewsGalleryItem({
    required this.id,
    required this.imageUrl,
    this.caption,
    this.sortOrder = 0,
  });

  final int id;
  final String imageUrl;
  final String? caption;
  final int sortOrder;

  factory NewsGalleryItem.fromJson(Map<String, dynamic> json) {
    return NewsGalleryItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      imageUrl: json['image_url'] as String? ?? '',
      caption: json['caption'] as String?,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }
}

class BreakingNewsItem {
  const BreakingNewsItem({
    required this.id,
    required this.title,
    this.newsId,
    this.newsSlug,
    this.targetUrl,
    this.sortOrder = 0,
  });

  final int id;
  final String title;
  final int? newsId;
  final String? newsSlug;
  final String? targetUrl;
  final int sortOrder;

  factory BreakingNewsItem.fromJson(Map<String, dynamic> json) {
    return BreakingNewsItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      newsId: (json['news_id'] as num?)?.toInt(),
      newsSlug: json['news_slug'] as String?,
      targetUrl: json['target_url'] as String?,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }
}

class BannerItem {
  const BannerItem({
    required this.id,
    required this.title,
    this.image,
    this.targetType,
    this.targetId,
    this.targetUrl,
    this.position,
    this.sortOrder = 0,
  });

  final int id;
  final String title;
  final String? image;
  final String? targetType;
  final int? targetId;
  final String? targetUrl;
  final String? position;
  final int sortOrder;

  factory BannerItem.fromJson(Map<String, dynamic> json) {
    return BannerItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      image: json['image'] as String?,
      targetType: json['target_type'] as String?,
      targetId: (json['target_id'] as num?)?.toInt(),
      targetUrl: json['target_url'] as String?,
      position: json['position'] as String?,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }
}

class VideoItem {
  const VideoItem({
    required this.id,
    required this.title,
    required this.slug,
    this.description,
    this.thumbnail,
    this.videoUrl,
    this.videoType,
    this.durationSeconds,
    this.isFeatured = false,
    this.publishedAt,
    this.category,
    this.categoryId,
    this.related = const [],
  });

  final int id;
  final String title;
  final String slug;
  final String? description;
  final String? thumbnail;
  final String? videoUrl;
  final String? videoType;
  final int? durationSeconds;
  final bool isFeatured;
  final String? publishedAt;
  final CategoryRef? category;
  final int? categoryId;
  final List<VideoItem> related;

  factory VideoItem.fromJson(Map<String, dynamic> json) {
    final relatedJson = json['related'];
    return VideoItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      thumbnail: json['thumbnail'] as String?,
      videoUrl: json['video_url'] as String?,
      videoType: json['video_type'] as String?,
      durationSeconds: (json['duration_seconds'] as num?)?.toInt(),
      isFeatured: json['is_featured'] == true || json['is_featured'] == 1,
      publishedAt: json['published_at'] as String?,
      category: json['category'] is Map
          ? CategoryRef.fromJson(Map<String, dynamic>.from(json['category'] as Map))
          : null,
      categoryId: (json['category_id'] as num?)?.toInt(),
      related: relatedJson is List
          ? relatedJson
              .whereType<Map>()
              .map((e) => VideoItem.fromJson(Map<String, dynamic>.from(e)))
              .toList()
          : const [],
    );
  }
}

class ProgramItem {
  const ProgramItem({
    required this.id,
    required this.title,
    required this.slug,
    this.description,
    this.coverImage,
    this.presenter,
    this.broadcastDay,
    this.broadcastTime,
    this.sortOrder = 0,
    this.episodes = const [],
  });

  final int id;
  final String title;
  final String slug;
  final String? description;
  final String? coverImage;
  final String? presenter;
  final String? broadcastDay;
  final String? broadcastTime;
  final int sortOrder;
  final List<EpisodeItem> episodes;

  factory ProgramItem.fromJson(Map<String, dynamic> json) {
    final episodesJson = json['episodes'];
    return ProgramItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      coverImage: json['cover_image'] as String?,
      presenter: json['presenter'] as String?,
      broadcastDay: json['broadcast_day'] as String?,
      broadcastTime: json['broadcast_time'] as String?,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
      episodes: episodesJson is List
          ? episodesJson
              .whereType<Map>()
              .map((e) => EpisodeItem.fromJson(Map<String, dynamic>.from(e)))
              .toList()
          : const [],
    );
  }
}

class EpisodeItem {
  const EpisodeItem({
    required this.id,
    required this.programId,
    required this.title,
    required this.slug,
    this.description,
    this.thumbnail,
    this.videoUrl,
    this.videoType,
    this.episodeNumber,
    this.durationSeconds,
    this.publishedAt,
  });

  final int id;
  final int programId;
  final String title;
  final String slug;
  final String? description;
  final String? thumbnail;
  final String? videoUrl;
  final String? videoType;
  final int? episodeNumber;
  final int? durationSeconds;
  final String? publishedAt;

  factory EpisodeItem.fromJson(Map<String, dynamic> json) {
    return EpisodeItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      programId: (json['program_id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      thumbnail: json['thumbnail'] as String?,
      videoUrl: json['video_url'] as String?,
      videoType: json['video_type'] as String?,
      episodeNumber: (json['episode_number'] as num?)?.toInt(),
      durationSeconds: (json['duration_seconds'] as num?)?.toInt(),
      publishedAt: json['published_at'] as String?,
    );
  }
}

class LiveStream {
  const LiveStream({
    required this.id,
    required this.title,
    this.description,
    this.streamUrl,
    this.streamType,
    this.posterImage,
    this.isActive = false,
  });

  final int id;
  final String title;
  final String? description;
  final String? streamUrl;
  final String? streamType;
  final String? posterImage;
  final bool isActive;

  factory LiveStream.fromJson(Map<String, dynamic> json) {
    return LiveStream(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      streamUrl: json['stream_url'] as String?,
      streamType: json['stream_type'] as String?,
      posterImage: json['poster_image'] as String?,
      isActive: json['is_active'] == true || json['is_active'] == 1,
    );
  }
}

class AppSettings {
  const AppSettings({
    required this.appName,
    this.appLogo,
    this.primaryColor,
    this.secondaryColor,
    this.breakingNewsEnabled = true,
    this.liveStreamEnabled = true,
    this.maintenanceMode = false,
    this.contactEmail,
    this.contactPhone,
    this.websiteUrl,
    this.facebookUrl,
    this.instagramUrl,
    this.youtubeUrl,
    this.xUrl,
    this.aboutText,
  });

  final String appName;
  final String? appLogo;
  final String? primaryColor;
  final String? secondaryColor;
  final bool breakingNewsEnabled;
  final bool liveStreamEnabled;
  final bool maintenanceMode;
  final String? contactEmail;
  final String? contactPhone;
  final String? websiteUrl;
  final String? facebookUrl;
  final String? instagramUrl;
  final String? youtubeUrl;
  final String? xUrl;
  final String? aboutText;

  factory AppSettings.fromJson(Map<String, dynamic> json) {
    return AppSettings(
      appName: json['app_name'] as String? ?? 'Art World Mobile',
      appLogo: json['app_logo'] as String?,
      primaryColor: json['primary_color'] as String?,
      secondaryColor: json['secondary_color'] as String?,
      breakingNewsEnabled:
          json['breaking_news_enabled'] == true || json['breaking_news_enabled'] == 1,
      liveStreamEnabled:
          json['live_stream_enabled'] == true || json['live_stream_enabled'] == 1,
      maintenanceMode:
          json['maintenance_mode'] == true || json['maintenance_mode'] == 1,
      contactEmail: json['contact_email'] as String?,
      contactPhone: json['contact_phone'] as String?,
      websiteUrl: json['website_url'] as String?,
      facebookUrl: json['facebook_url'] as String?,
      instagramUrl: json['instagram_url'] as String?,
      youtubeUrl: json['youtube_url'] as String?,
      xUrl: json['x_url'] as String?,
      aboutText: json['about_text'] as String?,
    );
  }

  static const empty = AppSettings(appName: 'Art World Mobile');
}

class HomeData {
  const HomeData({
    required this.settings,
    this.breakingNews = const [],
    this.banners = const [],
    this.featuredNews = const [],
    this.latestNews = const [],
    this.featuredVideos = const [],
    this.latestVideos = const [],
    this.programs = const [],
    this.liveStream,
    this.fromCache = false,
  });

  final AppSettings settings;
  final List<BreakingNewsItem> breakingNews;
  final List<BannerItem> banners;
  final List<NewsItem> featuredNews;
  final List<NewsItem> latestNews;
  final List<VideoItem> featuredVideos;
  final List<VideoItem> latestVideos;
  final List<ProgramItem> programs;
  final LiveStream? liveStream;
  final bool fromCache;

  HomeData copyWith({bool? fromCache}) {
    return HomeData(
      settings: settings,
      breakingNews: breakingNews,
      banners: banners,
      featuredNews: featuredNews,
      latestNews: latestNews,
      featuredVideos: featuredVideos,
      latestVideos: latestVideos,
      programs: programs,
      liveStream: liveStream,
      fromCache: fromCache ?? this.fromCache,
    );
  }

  factory HomeData.fromJson(Map<String, dynamic> json, {bool fromCache = false}) {
    List<T> mapList<T>(String key, T Function(Map<String, dynamic>) mapper) {
      final raw = json[key];
      if (raw is! List) return const [];
      return raw
          .whereType<Map>()
          .map((e) => mapper(Map<String, dynamic>.from(e)))
          .toList();
    }

    return HomeData(
      settings: json['settings'] is Map
          ? AppSettings.fromJson(Map<String, dynamic>.from(json['settings'] as Map))
          : AppSettings.empty,
      breakingNews: mapList('breaking_news', BreakingNewsItem.fromJson),
      banners: mapList('banners', BannerItem.fromJson),
      featuredNews: mapList('featured_news', NewsItem.fromJson),
      latestNews: mapList('latest_news', NewsItem.fromJson),
      featuredVideos: mapList('featured_videos', VideoItem.fromJson),
      latestVideos: mapList('latest_videos', VideoItem.fromJson),
      programs: mapList('programs', ProgramItem.fromJson),
      liveStream: json['live_stream'] is Map
          ? LiveStream.fromJson(Map<String, dynamic>.from(json['live_stream'] as Map))
          : null,
      fromCache: fromCache,
    );
  }

  Map<String, dynamic> toCacheJson() {
    // We store the raw API data map instead; helper exists for completeness.
    return {};
  }
}

class SearchResults {
  const SearchResults({
    required this.query,
    this.news = const [],
    this.videos = const [],
    this.programs = const [],
  });

  final String query;
  final List<NewsItem> news;
  final List<VideoItem> videos;
  final List<ProgramItem> programs;

  bool get isEmpty => news.isEmpty && videos.isEmpty && programs.isEmpty;

  factory SearchResults.fromJson(Map<String, dynamic> json) {
    List<T> mapList<T>(String key, T Function(Map<String, dynamic>) mapper) {
      final raw = json[key];
      if (raw is! List) return const [];
      return raw
          .whereType<Map>()
          .map((e) => mapper(Map<String, dynamic>.from(e)))
          .toList();
    }

    return SearchResults(
      query: json['query'] as String? ?? '',
      news: mapList('news', NewsItem.fromJson),
      videos: mapList('videos', VideoItem.fromJson),
      programs: mapList('programs', ProgramItem.fromJson),
    );
  }
}
