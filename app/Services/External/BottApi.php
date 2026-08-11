<?php

namespace App\Services\External;

use App\Dto\BotDto;
use GuzzleHttp\Client;

class BottApi
{
    const HOST = 'https://api.bot-t.com/';

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function getModuleUser(string $action, array $params): array
    {
        $client = new Client([
            'base_uri' => self::HOST,
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);

        $response = $client->request('GET', 'v1/module/user/' . $action, [
            'query' => $params,
            'http_errors' => false,
        ]);

        return self::decodeResponse($response->getStatusCode(), (string) $response->getBody()->getContents());
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function postModuleUser(string $action, array $params): array
    {
        $client = new Client([
            'base_uri' => self::HOST,
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);

        $response = $client->request('POST', 'v1/module/user/' . $action, [
            'form_params' => $params,
            'http_errors' => false,
        ]);

        return self::decodeResponse($response->getStatusCode(), (string) $response->getBody()->getContents());
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeResponse(int $statusCode, string $body): array
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            return [
                'result' => false,
                'message' => 'Некорректный ответ BOT-T API',
                'data' => [],
            ];
        }

        if ($statusCode >= 400 && empty($decoded['message'])) {
            $decoded['result'] = false;
            $decoded['message'] = 'Ошибка BOT-T API (HTTP ' . $statusCode . ')';
        }

        if ($statusCode >= 400 && ! isset($decoded['result'])) {
            $decoded['result'] = false;
        }

        return $decoded;
    }

    /**
     * Проверка $secret_key
     *
     * @param int $telegram_id
     * @param string $secret_key
     * @param string $public_key
     * @param string $private_key
     * @return array<string, mixed>
     */
    public static function checkUser(int $telegram_id, string $secret_key, string $public_key, string $private_key): array
    {
        if ($secret_key === '') {
            return ['result' => false, 'message' => 'Секретный ключ не указан', 'data' => []];
        }

        $result = self::getModuleUser('check-secret', [
            'public_key' => $public_key,
            'private_key' => $private_key,
            'id' => $telegram_id,
            'secret_key' => $secret_key,
        ]);

        if (($result['result'] ?? false) === false) {
            $result['message'] = self::normalizeUserErrorMessage($result['message'] ?? 'Ошибка проверки пользователя');
        }

        return $result;
    }

    /**
     * Получение $secret_key
     *
     * @param int $telegram_id
     * @param string $public_key
     * @param string $private_key
     * @return array<string, mixed>
     */
    public static function get(int $telegram_id, string $public_key, string $private_key): array
    {
        return self::getModuleUser('get', [
            'public_key' => $public_key,
            'private_key' => $private_key,
            'id' => $telegram_id,
        ]);
    }

    /**
     * Списание баланса
     *
     * @param BotDto $botDto
     * @param array $userData
     * @param int $amount
     * @param string $comment
     * @return array<string, mixed>
     */
    public static function subtractBalance(BotDto $botDto, array $userData, int $amount, string $comment): array
    {
        return self::postModuleUser('subtract-balance', [
            'public_key' => $botDto->public_key,
            'private_key' => $botDto->private_key,
            'user_id' => $userData['user']['telegram_id'],
            'secret_key' => $userData['secret_user_key'],
            'amount' => $amount,
            'comment' => $comment,
        ]);
    }

    /**
     * Пополнение баланса
     *
     * @param BotDto $botDto
     * @param array $userData
     * @param int $amount
     * @param string $comment
     * @return array<string, mixed>
     */
    public static function addBalance(BotDto $botDto, array $userData, int $amount, string $comment): array
    {
        return self::postModuleUser('add-balance', [
            'public_key' => $botDto->public_key,
            'private_key' => $botDto->private_key,
            'user_id' => $userData['user']['telegram_id'],
            'secret_key' => $userData['secret_user_key'],
            'amount' => $amount,
            'comment' => $comment,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function createOrder(BotDto $botDto, array $userData, int $amount, string $product): array
    {
        $client = new Client([
            'base_uri' => 'https://api.bot-t.com/v1/module/shop/',
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);

        $response = $client->request('POST', 'order-create', [
            'form_params' => [
                'public_key' => $botDto->public_key,
                'private_key' => $botDto->private_key,
                'user_id' => $userData['user']['telegram_id'],
                'secret_key' => $userData['secret_user_key'],
                'amount' => $amount,
                'count' => 1,
                'category_id' => $botDto->category_id,
                'product' => $product,
            ],
            'headers' => [
                'User-Agent' => $product,
            ],
            'http_errors' => false,
        ]);

        $decoded = json_decode((string) $response->getBody()->getContents(), true);

        return is_array($decoded) ? $decoded : ['result' => false, 'message' => 'Некорректный ответ BOT-T API'];
    }

    private static function normalizeUserErrorMessage(string $message): string
    {
        if (stripos($message, 'secret key not valid') !== false) {
            return 'Неверный секретный ключ пользователя или telegram_id';
        }

        return $message;
    }
}
