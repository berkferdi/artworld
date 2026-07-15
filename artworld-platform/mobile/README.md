# Art World Mobile

Flutter client for the Art World news & internet TV platform.

## Configuration

API base URL is set in `lib/core/config/app_config.dart`:

```dart
static const String apiBaseUrl = 'http://127.0.0.1:8080/api/v1';
static const String mediaBaseUrl = 'http://127.0.0.1:8080';
```

For production, change both to your HTTPS endpoints and remove cleartext exceptions
in `android/app/src/main/res/xml/network_security_config.xml` / iOS ATS exceptions.

## Run

```bash
export PATH="$HOME/flutter-sdk/flutter/bin:$PATH"
cd mobile
flutter pub get
flutter run
```

## Architecture

Feature-based Riverpod + Dio + go_router. Home responses are cached in
SharedPreferences (~5 min TTL) for offline display.
