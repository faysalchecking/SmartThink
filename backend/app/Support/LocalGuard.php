<?php

namespace App\Support;

/**
 * Development-only commands use this check. It is true only for local APP_ENV on the dev database.
 */
final class LocalGuard
{
    public const DEV_DATABASE = 'smartthink_dev';

    public static function allows(): bool
    {
        return app()->environment('local')
            && config('database.connections.pgsql.database') === self::DEV_DATABASE;
    }
}
