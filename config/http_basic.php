<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Basic для всех web-маршрутов (до /login и панели)
    |--------------------------------------------------------------------------
    |
    | В local/testing при пустых значениях middleware пропускает запрос.
    | В остальных окружениях без пары логин/пароль — 503.
    |
    */

    'username' => env('HTTP_BASIC_USERNAME'),

    'password' => env('HTTP_BASIC_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Уведомления в Telegram после HTTP Basic (как в проекте vpn, опционально)
    |--------------------------------------------------------------------------
    |
    | Нужны оба значения: токен бота от @BotFather и chat_id (напишите боту /start,
    | затем https://api.telegram.org/bot<TOKEN>/getUpdates).
    |
    */
    'notify_telegram_token' => env('ADMIN_HTTP_BASIC_NOTIFY_TELEGRAM_TOKEN'),
    'notify_telegram_chat_id' => env('ADMIN_HTTP_BASIC_NOTIFY_TELEGRAM_CHAT_ID'),

];
