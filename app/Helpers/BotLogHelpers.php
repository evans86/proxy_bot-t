<?php

namespace App\Helpers;

use App\Support\SafeLog;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class BotLogHelpers
{
    public static function notifyBotLog($text)
    {
        $client = new Client([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);

        $ids = [6715142449];
        $bots = [
            config('services.bot_api_keys.modules_log_bot_1'),
            config('services.bot_api_keys.modules_log_bot_2'),
        ];

        $message = ($text === '') ? '[Empty message]' : SafeLog::sanitize((string) $text);

        $lastError = null;

        foreach ($bots as $botToken) {
            try {
                foreach ($ids as $id) {
                    $client->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        RequestOptions::JSON => [
                            'chat_id' => $id,
                            'text' => $message,
                        ],
                    ]);
                }

                return true;
            } catch (\Exception $e) {
                $lastError = $e;
                continue;
            }
        }

        if ($lastError !== null) {
            error_log('Telegram send failed: ' . SafeLog::exceptionMessage($lastError));
        }

        return false;
    }
}
