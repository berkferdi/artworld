<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap(false);

use App\Core\Auth;

Auth::logout();
redirect(admin_url('login.php'));
