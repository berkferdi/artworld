import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/network_image.dart';
import '../../home/providers/home_providers.dart';

class MainShell extends ConsumerWidget {
  const MainShell({
    super.key,
    required this.navigationShell,
    required this.scaffoldKey,
  });

  final StatefulNavigationShell navigationShell;
  final GlobalKey<ScaffoldState> scaffoldKey;

  void _onTap(int index) {
    navigationShell.goBranch(
      index,
      initialLocation: index == navigationShell.currentIndex,
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      key: scaffoldKey,
      drawer: const AppDrawer(),
      body: navigationShell,
      bottomNavigationBar: _BottomNav(
        currentIndex: navigationShell.currentIndex,
        onTap: _onTap,
      ),
    );
  }
}

class _BottomNav extends StatelessWidget {
  const _BottomNav({required this.currentIndex, required this.onTap});

  final int currentIndex;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        border: Border(top: BorderSide(color: AppTheme.borderGray)),
        color: Colors.white,
      ),
      child: SafeArea(
        child: SizedBox(
          height: 64,
          child: Row(
            children: [
              _NavItem(
                icon: Icons.home_outlined,
                activeIcon: Icons.home,
                label: 'Ana Sayfa',
                selected: currentIndex == 0,
                onTap: () => onTap(0),
              ),
              _NavItem(
                icon: Icons.article_outlined,
                activeIcon: Icons.article,
                label: 'Haberler',
                selected: currentIndex == 1,
                onTap: () => onTap(1),
              ),
              _LiveNavItem(
                selected: currentIndex == 2,
                onTap: () => onTap(2),
              ),
              _NavItem(
                icon: Icons.play_circle_outline,
                activeIcon: Icons.play_circle,
                label: 'Videolar',
                selected: currentIndex == 3,
                onTap: () => onTap(3),
              ),
              _NavItem(
                icon: Icons.tv_outlined,
                activeIcon: Icons.tv,
                label: 'Programlar',
                selected: currentIndex == 4,
                onTap: () => onTap(4),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final IconData activeIcon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppTheme.brandRed : AppTheme.mutedGray;
    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(selected ? activeIcon : icon, color: color, size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 10,
                fontWeight: selected ? FontWeight.w800 : FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LiveNavItem extends StatelessWidget {
  const _LiveNavItem({required this.selected, required this.onTap});

  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: AppTheme.brandRed,
                border: Border.all(
                  color: selected ? AppTheme.nearBlack : AppTheme.brandRed,
                  width: selected ? 2 : 0,
                ),
              ),
              child: const Icon(Icons.sensors, color: Colors.white, size: 26),
            ),
            const SizedBox(height: 2),
            Text(
              'Canlı',
              style: TextStyle(
                color: selected ? AppTheme.brandRed : AppTheme.nearBlack,
                fontSize: 10,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class AppDrawer extends ConsumerWidget {
  const AppDrawer({super.key});

  Future<void> _openUrl(String? url) async {
    if (url == null || url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(settingsFromHomeProvider);
    final logo = settings?.appLogo;

    return Drawer(
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
              color: AppTheme.nearBlack,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (logo != null && logo.isNotEmpty)
                    SizedBox(
                      height: 40,
                      child: AppNetworkImage(
                        url: logo,
                        fit: BoxFit.contain,
                        height: 40,
                      ),
                    )
                  else
                    Text(
                      AppStrings.appName,
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            color: Colors.white,
                          ),
                    ),
                  const SizedBox(height: 8),
                  Text(
                    settings?.appName ?? AppStrings.appName,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                children: [
                  _DrawerItem(
                    icon: Icons.home_outlined,
                    label: 'Ana Sayfa',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.sensors,
                    label: 'Canlı Yayın',
                    accent: true,
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/live');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.flash_on,
                    label: 'Son Dakika',
                    onTap: () {
                      Navigator.pop(context);
                      context.push('/breaking');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.article_outlined,
                    label: 'Haberler',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/news');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.category_outlined,
                    label: 'Kategoriler',
                    onTap: () {
                      Navigator.pop(context);
                      context.push('/categories');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.video_library_outlined,
                    label: 'Video Galeri',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/videos');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.tv_outlined,
                    label: 'Programlar',
                    onTap: () {
                      Navigator.pop(context);
                      context.go('/programs');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.history,
                    label: 'Geçmiş Programlar',
                    onTap: () {
                      Navigator.pop(context);
                      context.push('/programs/past');
                    },
                  ),
                  const Divider(),
                  _DrawerItem(
                    icon: Icons.info_outline,
                    label: 'Hakkımızda',
                    onTap: () {
                      Navigator.pop(context);
                      context.push('/about');
                    },
                  ),
                  _DrawerItem(
                    icon: Icons.mail_outline,
                    label: 'İletişim',
                    onTap: () {
                      Navigator.pop(context);
                      context.push('/contact');
                    },
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              child: Row(
                children: [
                  _SocialIcon(
                    icon: Icons.facebook,
                    onTap: () => _openUrl(settings?.facebookUrl),
                  ),
                  _SocialIcon(
                    icon: Icons.camera_alt_outlined,
                    onTap: () => _openUrl(settings?.instagramUrl),
                  ),
                  _SocialIcon(
                    icon: Icons.ondemand_video,
                    onTap: () => _openUrl(settings?.youtubeUrl),
                  ),
                  _SocialIcon(
                    icon: Icons.alternate_email,
                    onTap: () => _openUrl(settings?.xUrl),
                  ),
                ],
              ),
            ),
            FutureBuilder<PackageInfo>(
              future: PackageInfo.fromPlatform(),
              builder: (context, snap) {
                final version = snap.data?.version ?? '1.0.0';
                final build = snap.data?.buildNumber ?? '1';
                return Padding(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
                  child: Text(
                    'v$version ($build)',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  const _DrawerItem({
    required this.icon,
    required this.label,
    required this.onTap,
    this.accent = false,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool accent;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(
        icon,
        color: accent ? AppTheme.brandRed : AppTheme.nearBlack,
      ),
      title: Text(
        label,
        style: TextStyle(
          fontWeight: accent ? FontWeight.w800 : FontWeight.w600,
          color: accent ? AppTheme.brandRed : AppTheme.nearBlack,
        ),
      ),
      onTap: onTap,
    );
  }
}

class _SocialIcon extends StatelessWidget {
  const _SocialIcon({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: onTap,
      icon: Icon(icon, color: AppTheme.nearBlack),
    );
  }
}
