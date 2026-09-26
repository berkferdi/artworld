import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/categories/screens/categories_screens.dart';
import '../../features/home/screens/home_screen.dart';
import '../../features/live/screens/live_screen.dart';
import '../../features/news/screens/news_screens.dart';
import '../../features/programs/screens/programs_screens.dart';
import '../../features/search/screens/search_screen.dart';
import '../../features/settings/screens/settings_screens.dart';
import '../../features/shell/screens/main_shell.dart';
import '../../features/shell/shell_scaffold_key.dart';
import '../../features/splash/screens/splash_screen.dart';
import '../../features/videos/screens/videos_screens.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final scaffoldKey = ref.watch(shellScaffoldKeyProvider);

  return GoRouter(
    initialLocation: '/splash',
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return MainShell(
            navigationShell: navigationShell,
            scaffoldKey: scaffoldKey,
          );
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/',
                builder: (context, state) => const HomeScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/news',
                builder: (context, state) => const NewsListScreen(),
                routes: [
                  GoRoute(
                    path: ':idOrSlug',
                    builder: (context, state) => NewsDetailScreen(
                      idOrSlug: state.pathParameters['idOrSlug']!,
                    ),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/live',
                builder: (context, state) => const LiveScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/videos',
                builder: (context, state) => const VideosListScreen(),
                routes: [
                  GoRoute(
                    path: ':idOrSlug',
                    builder: (context, state) => VideoDetailScreen(
                      idOrSlug: state.pathParameters['idOrSlug']!,
                    ),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/programs',
                builder: (context, state) => const ProgramsListScreen(),
                routes: [
                  GoRoute(
                    path: 'past',
                    builder: (context, state) =>
                        const ProgramsListScreen(pastOnly: true),
                  ),
                  GoRoute(
                    path: ':idOrSlug',
                    builder: (context, state) => ProgramDetailScreen(
                      idOrSlug: state.pathParameters['idOrSlug']!,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
      GoRoute(
        path: '/breaking',
        builder: (context, state) => const BreakingNewsListScreen(),
      ),
      GoRoute(
        path: '/categories',
        builder: (context, state) => const CategoriesScreen(),
        routes: [
          GoRoute(
            path: ':slug',
            builder: (context, state) => CategoryNewsScreen(
              slug: state.pathParameters['slug']!,
            ),
          ),
        ],
      ),
      GoRoute(
        path: '/search',
        builder: (context, state) => const SearchScreen(),
      ),
      GoRoute(
        path: '/about',
        builder: (context, state) => const AboutScreen(),
      ),
      GoRoute(
        path: '/contact',
        builder: (context, state) => const ContactScreen(),
      ),
      GoRoute(
        path: '/episodes/:idOrSlug',
        builder: (context, state) => EpisodePlayerScreen(
          idOrSlug: state.pathParameters['idOrSlug']!,
        ),
      ),
      // Nested under shell for deep links when opened from banners etc.
      GoRoute(
        path: '/news-detail/:idOrSlug',
        redirect: (context, state) =>
            '/news/${state.pathParameters['idOrSlug']}',
      ),
    ],
  );
});
