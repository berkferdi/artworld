import 'package:intl/intl.dart';

class DateFormatters {
  DateFormatters._();

  static final _dateTime = DateFormat('d MMMM yyyy, HH:mm', 'tr_TR');
  static final _date = DateFormat('d MMMM yyyy', 'tr_TR');
  static final _short = DateFormat('dd.MM.yyyy HH:mm', 'tr_TR');

  static String formatDateTime(String? raw) {
    final dt = tryParse(raw);
    if (dt == null) return raw ?? '';
    return _dateTime.format(dt);
  }

  static String formatDate(String? raw) {
    final dt = tryParse(raw);
    if (dt == null) return raw ?? '';
    return _date.format(dt);
  }

  static String formatShort(String? raw) {
    final dt = tryParse(raw);
    if (dt == null) return raw ?? '';
    return _short.format(dt);
  }

  static DateTime? tryParse(String? raw) {
    if (raw == null || raw.trim().isEmpty) return null;
    return DateTime.tryParse(raw.replaceFirst(' ', 'T'));
  }
}

class DurationFormatters {
  DurationFormatters._();

  static String fromSeconds(int? seconds) {
    if (seconds == null || seconds <= 0) return '0:00';
    final h = seconds ~/ 3600;
    final m = (seconds % 3600) ~/ 60;
    final s = seconds % 60;
    if (h > 0) {
      return '$h:${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
    }
    return '$m:${s.toString().padLeft(2, '0')}';
  }
}
