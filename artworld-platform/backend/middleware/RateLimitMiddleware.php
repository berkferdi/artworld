<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Database;
use App\Core\Response;
use App\Core\Security;

final class RateLimitMiddleware
{
    public static function handle(string $endpoint): void
    {
        $limit = Config::int('API_RATE_LIMIT_PER_MINUTE', 120);
        $ipHash = Security::hashIp(Security::clientIp());
        $pdo = Database::connection();

        $windowStart = gmdate('Y-m-d H:i:00');

        $stmt = $pdo->prepare(
            'INSERT INTO api_rate_limits (ip_hash, endpoint, hit_count, window_start)
             VALUES (?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE hit_count = hit_count + 1'
        );
        $stmt->execute([$ipHash, $endpoint, $windowStart]);

        $check = $pdo->prepare(
            'SELECT hit_count FROM api_rate_limits WHERE ip_hash = ? AND endpoint = ? AND window_start = ? LIMIT 1'
        );
        $check->execute([$ipHash, $endpoint, $windowStart]);
        $row = $check->fetch();
        $hits = (int) ($row['hit_count'] ?? 1);

        if ($hits > $limit) {
            Response::tooManyRequests();
        }

        // Opportunistic cleanup of old windows
        if (random_int(1, 50) === 1) {
            $pdo->exec('DELETE FROM api_rate_limits WHERE window_start < (UTC_TIMESTAMP() - INTERVAL 2 HOUR)');
        }
    }
}
