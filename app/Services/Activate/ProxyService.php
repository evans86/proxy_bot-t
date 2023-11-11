<?php

namespace App\Services\Activate;

use App\Dto\BotDto;
use App\Models\Country\Country;
use App\Models\Order\Order;
use App\Models\Proxy\Proxy;
use App\Models\User\User;
use App\Services\External\BottApi;
use App\Services\External\ProxyApi;
use App\Services\MainService;
use AmrShawky\LaravelCurrency\Facade\Currency;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class ProxyService extends MainService
{
    /**
     * @param $count
     * @param $period
     * @param $country
     * @param $version
     * @param $type
     * @param BotDto $botDto
     * @param array $userData
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder($count, $period, $country, $version, $type, BotDto $botDto, array $userData)
    {
        $proxyApi = new ProxyApi($botDto->api_key);

        $user = User::query()->where(['telegram_id' => $userData['user']['telegram_id']])->first();
        if (is_null($user)) {
            throw new \RuntimeException('not found user');
        }

        $resultPrice = $this->getPrice($count, $period, $version, $botDto);

        if ($resultPrice['price'] > $userData['money']) {
            throw new \RuntimeException('Пополните баланс в боте');
        }

        $order = $proxyApi->buy($count, $period, $country, $version, $type);

        $lists = $order['list'];

        $country = Country::query()->where(['iso_two' => $order['country']])->first();
        $proxy = Proxy::query()->where(['version' => $order['version']])->first();

//        $amountStart = intval(floatval($order['price']) * 100);
//        $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;

        switch ($order['currency']) {
            case 'USD':
                $price = Currency::convert()->from('USD')->to('RUB')->amount($order['price'])->get();
                $amountStart = intval(floatval($price) * 100);
                $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;
                break;
            case 'RUB':
            default:
                $amountStart = intval(floatval($order['price']) * 100);
                $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;
                break;

        }

        $resultBalance = BottApi::subtractBalance($botDto, $userData, $amountFinal, 'Списание баланса для прокси ');

        $resultOrder = BottApi::createOrder($botDto, $userData, $amountFinal, 'Покупка прокси ');

        $response = [];
        foreach ($lists as $key => $list) {

            $data = [
                'user_id' => $user->id,
                'bot_id' => $botDto->id,
                'user_org_id' => $order['user_id'],
                'balance_org' => $order['balance'],
                'order_org_id' => $order['order_id'],
                'count' => $order['count'],
                'price' => $amountFinal,
                'period' => $order['period'],
                'proxy_id' => $proxy->id,
                'type' => $order['type'],
                'country_id' => $country->id,
                'prolong_org_id' => $list['id'],
                'ip' => $list['ip'],
                'host' => $list['host'],
                'port' => $list['port'],
                'user' => $list['user'],
                'pass' => $list['pass'],
                'status_org' => $list['active'],
                'start_time' => $list['unixtime'],
                'end_time' => $list['unixtime_end'],
            ];

            $order = Order::create($data);
            $order->save();

            array_push($response, [
                'order_org_id' => $order->prolong_org_id,
                'proxy' => $order->proxy->version,
                'country' => [
                    'org_id' => $order->country->iso_two,
                    'name_ru' => $order->country->name_ru,
                    'name_en' => $order->country->name_en,
                    'image' => $order->country->image
                ],
                'price' => $order->price,
                'host' => $order->host,
                'port' => $order->port,
                'user' => $order->user,
                'pass' => $order->pass,
                'type' => $order->type,
                'ip' => $order->ip,
                'status_org' => $list['active'],
                'start_time' => $order->start_time,
                'end_time' => $order->end_time
            ]);
        }

        return $response;
    }

    /**
     * @param $order_org_id
     * @param $period
     * @param $enter_amount
     * @param array $userData
     * @param BotDto $botDto
     * @return array|false
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function prolongProxy($order_org_id, $period, $enter_amount, array $userData, BotDto $botDto)
    {
        $proxyApi = new ProxyApi($botDto->api_key);
        $proxy = Order::query()->where(['prolong_org_id' => $order_org_id])->first();

        $user = User::query()->where(['telegram_id' => $userData['user']['telegram_id']])->first();
        if (is_null($user)) {
            throw new \RuntimeException('not found user');
        }

        if ($enter_amount > $userData['money']) {
            throw new \RuntimeException('Пополните баланс в боте');
        }

        $order = $proxyApi->prolong($period, $order_org_id);

        if ($order['count'] == 1) {
            $list = $order['list'];
            $list = current($list);

            switch ($order['currency']) {
                case 'USD':
                    $price = Currency::convert()->from('USD')->to('RUB')->amount($order['price'])->get();
                    $amountStart = intval(floatval($price) * 100);
                    $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;
                    break;
                case 'RUB':
                default:
                    $amountStart = intval(floatval($order['price']) * 100);
                    $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;
                    break;

            }

            $resultBalance = BottApi::subtractBalance($botDto, $userData, $amountFinal, 'Списание баланса для продления прокси '
                . $list['id']);

            $resultOrder = BottApi::createOrder($botDto, $userData, $amountFinal,
                'Продление прокси ' . $list['id']);

            $proxy->status_org = Order::ORDER_ACTIVE;
            $proxy->end_time = $list['unixtime_end'];
            $proxy->save();

            $result = [
                'order_org_id' => $order->prolong_org_id,
                'proxy' => $order->proxy->version,
                'country' => [
                    'org_id' => $order->country->iso_two,
                    'name_ru' => $order->country->name_ru,
                    'name_en' => $order->country->name_en,
                    'image' => $order->country->image
                ],
                'price' => $order->price,
                'host' => $order->host,
                'port' => $order->port,
                'user' => $order->user,
                'pass' => $order->pass,
                'type' => $order->type,
                'ip' => $order->ip,
                'status_org' => $proxy->status_org,
                'start_time' => $order->start_time,
                'end_time' => $order->end_time
            ];

            return $result;
        } else {
            return false;
        }

    }

    /**
     * @param array $userData
     * @return array
     */
    public function getOrders(array $userData)
    {
        $user = User::query()->where(['telegram_id' => $userData['user']['telegram_id']])->first();

        $statuses = [Order::ORDER_FINISH, Order::ORDER_ACTIVE];

        $proxies = Order::query()->whereIn('status_org', $statuses)
            ->where('user_id', $user->id)->get();

//        $proxies = Order::query()->where('status_org', 1)->where('user_id', $user->id)->get();

        $result = [];

        foreach ($proxies as $proxy) {
            array_push($result, [
                'order_org_id' => $proxy->prolong_org_id,
                'proxy' => $proxy->proxy->version,
                'country' => [
                    'org_id' => $proxy->country->iso_two,
                    'name_ru' => $proxy->country->name_ru,
                    'name_en' => $proxy->country->name_en,
                    'image' => $proxy->country->image
                ],
                'price' => $proxy->price,
                'host' => $proxy->host,
                'port' => $proxy->port,
                'user' => $proxy->user,
                'pass' => $proxy->pass,
                'type' => $proxy->type,
                'ip' => $proxy->ip,
                'status_org' => $proxy->status_org,
                'start_time' => $proxy->start_time,
                'end_time' => $proxy->end_time
            ]);
        }

        return $result;
    }

    /**
     * @param $order_org_id
     * @param BotDto $botDto
     * @return mixed
     */
    public function checkWork($order_org_id, BotDto $botDto)
    {
        $proxyApi = new ProxyApi($botDto->api_key);
        $status = $proxyApi->check($order_org_id);

        $result = $status['proxy_status'];

        return $result;
    }

    /**
     * @param $order_org_id
     * @param $type
     * @param BotDto $botDto
     * @return bool
     */
    public function updateType($order_org_id, $type, BotDto $botDto)
    {
        $proxyApi = new ProxyApi($botDto->api_key);
        $proxy = Order::query()->where(['prolong_org_id' => $order_org_id])->first();
        $result = $proxyApi->settype($order_org_id, $type);

        if ($result['status'] == 'no') {
            throw new \RuntimeException($result['error']);
        } else {
            $proxy->type = $type;
            $proxy->save();
            $result = true;
        }

        return $result;
    }

    /**
     * @param $order_org_id
     * @param BotDto $botDto
     * @return bool
     */
    public function deleteProxy($order_org_id, BotDto $botDto)
    {
        $proxyApi = new ProxyApi($botDto->api_key);
        $proxy = Order::query()->where(['prolong_org_id' => $order_org_id])->first();

        $result = $proxyApi->delete($order_org_id);

        $proxy->status_org = Order::ORDER_DELETE;
        $proxy->save();
        return true;

//        if ($result['count'] != 1) {
//            throw new \RuntimeException('Error delete in service');
//        } else {
//
//        }
    }

    /**
     * @param $country
     * @param $version
     * @param BotDto|null $botDto
     * @return mixed
     */
    public function getCount($country, $version, BotDto $botDto)
    {
        $proxyApi = new ProxyApi($botDto->api_key);

        $count = $proxyApi->getcount($country, $version);

        return $count['count'];
    }

    /**
     * @param $count
     * @param $period
     * @param $version
     * @param BotDto|null $botDto
     * @return array
     */
    public function getPrice($count, $period, $version, BotDto $botDto)
    {
        $proxyApi = new ProxyApi($botDto->api_key);

        $price_result = $proxyApi->getprice($count, $period, $version);

        switch ($price_result['currency']) {
            case 'USD':
                $price = Currency::convert()->from('USD')->to('RUB')->amount($price_result['price'])->get();
                $amountStart = intval(floatval($price) * 100);
                $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;
                break;
            case 'RUB':
            default:
                $amountStart = intval(floatval($price_result['price']) * 100);
                $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;
                break;

        }

        $result = [
            'price' => $amountFinal,
            'period' => $price_result['period'],
            'count' => $price_result['count'],
            'price_single' => $price_result['price_single'],
        ];

        return $result;
    }

    /**
     * @param BotDto|null $botDto
     * @return array
     */
    public function formingProxy(BotDto $botDto)
    {
        $proxyApi = new ProxyApi($botDto->api_key);

        $proxies = \Cache::get('proxies');
        if($proxies === null){
            $proxies = Proxy::all();
            \Cache::put('proxies', $proxies, 900);
        }

        $result = [];
        foreach ($proxies as $key => $proxy) {

            $countries = $proxyApi->getcountry($proxy->version);
            $countries = $countries['list'];

            $countriesArr = [];
            foreach ($countries as $country) {

                try {
                    $countryProxy = Country::query()->where(['iso_two' => $country])->first();

                    array_push($countriesArr, [
                        'org_id' => $countryProxy->iso_two,
                        'name_ru' => $countryProxy->name_ru,
                        'name_en' => $countryProxy->name_en,
                        'image' => $countryProxy->image
                    ]);
                } catch (\Exception $e) {
                    continue;
                }
            }

            array_push($result, [
                'title' => $proxy->title,
                'version' => $proxy->version,
                'countries' => $countriesArr
            ]);
        }

        return $result;
    }

    /**
     * Крон обновление статусов
     *
     * @return void
     */
    public function cronUpdateStatus()
    {
        try {
        $statuses = [Order::ORDER_ACTIVE];

        $orders = Order::query()->whereIn('status_org', $statuses)
            ->where('end_time', '<=', time())->get();

        echo "START count:" . count($orders) . PHP_EOL;

        $start_text = "Proxy Start count: " . count($orders) . PHP_EOL;
        $this->notifyTelegram($start_text);

        foreach ($orders as $key => $order) {
            echo $order->id . PHP_EOL;
            $order->status_org = Order::ORDER_FINISH;
            $order->save();
            echo "FINISH" . $order->id . PHP_EOL;
        }

            $finish_text = "Proxy finish count: " . count($orders) . PHP_EOL;
            $this->notifyTelegram($finish_text);

        } catch (\Exception $e) {
            $this->notifyTelegram('🔴' . $e->getMessage());
        }
    }

    public function notifyTelegram($text)
    {
        $client = new Client();

        $client->post('https://api.telegram.org/bot6794994258:AAHuRzPhDb2z11_j-BRhQIRzuwI7fC8S-14/sendMessage', [

            RequestOptions::JSON => [
                'chat_id' => 6715142449,
                'text' => $text,
            ]
        ]);
    }
}
