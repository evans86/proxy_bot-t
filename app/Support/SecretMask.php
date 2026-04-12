<?php

namespace App\Support;

class SecretMask
{
    /**
     * Маскирует секрет для отображения в UI (оставляет только хвост).
     */
    public static function mask(?string $value, int $tail = 4): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $len = strlen($value);
        if ($len <= $tail) {
            return str_repeat('•', max(1, $len));
        }

        return str_repeat('•', $len - $tail) . substr($value, -$tail);
    }
}
