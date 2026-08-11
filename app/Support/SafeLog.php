<?php

namespace App\Support;

use Throwable;

class SafeLog
{
    /** @var string[] */
    private const SENSITIVE_PARAM_NAMES = [
        'private_key',
        'secret_key',
        'user_secret_key',
        'secret_user_key',
        'api_key',
        'password',
        'PROXY6_OUTBOUND_PROXY',
    ];

    public static function sanitize(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        foreach (self::SENSITIVE_PARAM_NAMES as $name) {
            $text = preg_replace(
                '/(' . preg_quote($name, '/') . '=)([^&\s"\']+)/i',
                '$1***',
                $text
            ) ?? $text;
        }

        // http://user:pass@host
        $text = preg_replace(
            '#https?://([^:@/\s"\']+):([^@\s"\']+)@#i',
            'http://$1:***@',
            $text
        ) ?? $text;

        // /api/{api_key}/method
        $text = preg_replace(
            '#(/api/)([a-f0-9\-]{20,})(/)#i',
            '$1***$3',
            $text
        ) ?? $text;

        // Длинные hex-строки (secret keys)
        $text = preg_replace(
            '/\b[a-f0-9]{32,}\b/i',
            '***',
            $text
        ) ?? $text;

        return $text;
    }

    public static function exceptionMessage(Throwable $e): string
    {
        return self::sanitize($e->getMessage());
    }
}
