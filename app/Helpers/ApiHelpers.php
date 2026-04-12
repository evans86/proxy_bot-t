<?php

namespace App\Helpers;

class ApiHelpers
{
    /**
     * @param $result
     * @return array
     */
    public static function success($result): array
    {
        return [
            'result' => true,
            'data' => $result
        ];
    }

    /**
     * Ошибка API в том же «каркасе», что и успех: всегда есть ключ data (пустой массив),
     * чтобы клиенты вроде Bot-t после json_decode могли безопасно ожидать массив в data.
     *
     * @return array{result: bool, message: string, data: array}
     */
    public static function error(string $message): array
    {
        return [
            'result' => false,
            'message' => $message,
            'data' => [],
        ];
    }

    /**
     * @param string $result
     * @return array
     */
    public static function successStr(string $result): array
    {
        return [
            'result' => true,
            'data' => $result
        ];
    }

    /**
     * @return array{result: bool, message: string, data: array}
     */
    public static function errorNew(string $message): array
    {
        return [
            'result' => false,
            'message' => $message,
            'data' => [],
        ];
    }

    /**
     * @param array $params
     * @param string $token
     * @return string
     */
    public static function generateSignature(array $params, string $token): string
    {
        $str = '';
        ksort($params);
        foreach ($params as $key => $param) {
            if (is_array($param))
                continue;
            $str .= $param . ':';
        }
        $str .= $token;
        return md5($str);
    }

    /**
     * @param array $gets
     * @param string $token
     * @return bool
     */
    public static function checkSignature(array $gets, string $token): bool
    {
        $signature = $gets['signature'];
        unset($gets['signature']);
        unset($gets['notification_id']);
        return self::generateSignature($gets, $token) === $signature;
    }
}
