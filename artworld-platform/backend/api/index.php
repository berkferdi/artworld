<?php

declare(strict_types=1);

/**
 * API front controller alias.
 * Prefer routing through /public/index.php in production (Nginx root).
 */
require dirname(__DIR__) . '/public/index.php';
