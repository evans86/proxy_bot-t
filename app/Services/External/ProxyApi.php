<?php

namespace App\Services\External;

use GuzzleHttp\Client;

class ProxyApi
{
    const HOST = 'https://proxy6.net/api/';

    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function createClient(): Client
    {
        $options = [
            'base_uri' => self::HOST,
            'timeout' => 15,
            'connect_timeout' => 5,
        ];

        $proxy = config('services.proxy6_outbound_proxy');
        if (!empty($proxy)) {
            $options['proxy'] = $proxy;
        }

        return new Client($options);
    }

    private function request(string $method, array $params = []): array
    {
        $client = $this->createClient();

        $url = $this->apiKey . '/' . $method;
        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        $response = $client->get($url);
        $result = $response->getBody()->getContents();

        return json_decode($result, true);
    }

    //Проверка соединения
    public function ping()
    {
        $client = $this->createClient();
        $response = $client->get($this->apiKey);

        $result = $response->getBody()->getContents();
        return json_decode($result, true);
    }

    //Получение информации о сумме заказа;
    public function getprice($count, $period, $version = 6)
    {
        return $this->request(__FUNCTION__, [
            'count' => $count,
            'period' => $period,
            'version' => $version,
        ]);
    }

    //Получение информации о доступном кол-ве прокси для конкретной страны;
    public function getcount($country, $version = 6)
    {
        return $this->request(__FUNCTION__, [
            'country' => $country,
            'version' => $version,
        ]);
    }

    //Получение списка доступных стран;
    public function getcountry($version = 6)
    {
        return $this->request(__FUNCTION__, [
            'version' => $version,
        ]);
    }

    //Получение списка ваших прокси;
    public function getproxy($descr, $state = 'all', $page = 1, $limit = 1000)
    {
        return $this->request(__FUNCTION__, [
            'state' => $state,
            'descr' => $descr,
            'limit' => $limit,
        ]);
    }

    //Изменение типа (протокола) прокси;
    public function settype($ids, $type = 'http')
    {
        return $this->request(__FUNCTION__, [
            'ids' => $ids,
            'type' => $type,
        ]);
    }

    //Обновление технического комментария;
    public function setdescr($new, $old, $ids)
    {
        return $this->request(__FUNCTION__, [
            'new' => $new,
            'old' => $old,
            'ids' => $ids,
        ]);
    }

    //Покупка прокси;
    public function buy($count, $period, $country, $version = 6, $type = 'http', $descr = null)
    {
        return $this->request(__FUNCTION__, [
            'count' => $count,
            'period' => $period,
            'country' => $country,
            'descr' => $descr,
            'version' => $version,
            'type' => $type,
        ]);
    }

    //Продление списка прокси;
    public function prolong($period, $ids)
    {
        return $this->request(__FUNCTION__, [
            'period' => $period,
            'ids' => $ids,
        ]);
    }

    //Удаление прокси;
    public function delete($ids, $descr = null)
    {
        return $this->request(__FUNCTION__, [
            'ids' => $ids,
            'descr' => $descr,
        ]);
    }

    //Проверка валидности прокси.
    public function check($ids)
    {
        return $this->request(__FUNCTION__, [
            'ids' => $ids,
        ]);
    }

    //Привязка/удаление авторизации прокси по ip.
    public function ipauth($ip)
    {
        return $this->request(__FUNCTION__, [
            'ip' => $ip,
        ]);
    }

}
