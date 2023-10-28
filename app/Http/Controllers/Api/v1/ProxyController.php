<?php

namespace App\Http\Controllers\Api\v1;

use App\Dto\BotFactory;
use App\Helpers\ApiHelpers;
use App\Helpers\BotLogHelpers;
use App\Http\Controllers\Controller;
use App\Models\Bot\Bot;
use App\Models\User\User;
use App\Services\Activate\ProxyService;
use App\Services\External\BottApi;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    /**
     * @var ProxyService
     */
    public ProxyService $proxyService;

    public function __construct()
    {
        $this->proxyService = new ProxyService();
    }

    /**
     * @param Request $request
     * @return array|string
     */
    public function getProxy(Request $request)
    {
        try {
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $botDto = BotFactory::fromEntity($bot);

            $result = $this->proxyService->formingProxy($botDto);

            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error('Ошибка получения данных провайдера');
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Get proxy error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCount(Request $request)
    {
        try {
            if (is_null($request->country))
                return ApiHelpers::error('Not found params: country');
            if (is_null($request->version))
                return ApiHelpers::error('Not found params: version');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $botDto = BotFactory::fromEntity($bot);

            $result = $this->proxyService->getCount($request->country, $request->version, $botDto);

            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error('Ошибка получения данных провайдера');
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Get count error');
        }
    }

    /**
     *
     * @param Request $request
     * @return array|string
     */
    public function getPrice(Request $request)
    {
        try {
            if (is_null($request->count))
                return ApiHelpers::error('Not found params: count');
            if (is_null($request->version))
                return ApiHelpers::error('Not found params: version');
            if (is_null($request->period))
                return ApiHelpers::error('Not found params: period');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $botDto = BotFactory::fromEntity($bot);

            $result = $this->proxyService->getPrice($request->count, $request->period, $request->version, $botDto);

            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error('Ошибка получения данных провайдера');
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Get price error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function buyProxy(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = User::query()->where(['telegram_id' => $request->user_id])->first();
            if (is_null($request->count))
                return ApiHelpers::error('Not found params: count');
            if (is_null($request->period))
                return ApiHelpers::error('Not found params: period');
            if (is_null($request->country))
                return ApiHelpers::error('Not found params: country');
            if (is_null($request->version))
                return ApiHelpers::error('Not found params: version');
            if (is_null($request->type))
                return ApiHelpers::error('Not found params: type');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');

            $botDto = BotFactory::fromEntity($bot);
            $result = BottApi::checkUser(
                $request->user_id,
                $request->user_secret_key,
                $botDto->public_key,
                $botDto->private_key
            );
            if (!$result['result']) {
                throw new \RuntimeException($result['message']);
            }
            if ($result['data']['money'] == 0) {
                throw new \RuntimeException('Пополните баланс в боте');
            }

            $response = $this->proxyService->createOrder(
                $request->count,
                $request->period,
                $request->country,
                $request->version,
                $request->type,
                $botDto,
                $result['data']
            );

            return ApiHelpers::success($response);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Buy proxy error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrders(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = User::query()->where(['telegram_id' => $request->user_id])->first();
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');

            $botDto = BotFactory::fromEntity($bot);
            $result = BottApi::checkUser(
                $request->user_id,
                $request->user_secret_key,
                $botDto->public_key,
                $botDto->private_key
            );
            if (!$result['result']) {
                throw new \RuntimeException($result['message']);
            }

            $result = $this->proxyService->getOrders(
                $result['data']
            );
            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Get orders error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkWork(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = User::query()->where(['telegram_id' => $request->user_id])->first();
            if (is_null($request->order_org_id))
                return ApiHelpers::error('Not found params: order_org_id');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $botDto = BotFactory::fromEntity($bot);
            $result = BottApi::checkUser(
                $request->user_id,
                $request->user_secret_key,
                $botDto->public_key,
                $botDto->private_key
            );
            if (!$result['result']) {
                throw new \RuntimeException($result['message']);
            }

            $result = $this->proxyService->checkWork(
                $request->order_org_id,
                $botDto
            );
            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Check work error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateType(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = User::query()->where(['telegram_id' => $request->user_id])->first();
            if (is_null($request->order_org_id))
                return ApiHelpers::error('Not found params: order_org_id');
            if (is_null($request->type))
                return ApiHelpers::error('Not found params: type');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $botDto = BotFactory::fromEntity($bot);
            $result = BottApi::checkUser(
                $request->user_id,
                $request->user_secret_key,
                $botDto->public_key,
                $botDto->private_key
            );
            if (!$result['result']) {
                throw new \RuntimeException($result['message']);
            }

            $result = $this->proxyService->updateType(
                $request->order_org_id,
                $request->type,
                $botDto
            );
            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Update type error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteProxy(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = User::query()->where(['telegram_id' => $request->user_id])->first();
            if (is_null($request->order_org_id))
                return ApiHelpers::error('Not found params: order_org_id');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $botDto = BotFactory::fromEntity($bot);
            $result = BottApi::checkUser(
                $request->user_id,
                $request->user_secret_key,
                $botDto->public_key,
                $botDto->private_key
            );
            if (!$result['result']) {
                throw new \RuntimeException($result['message']);
            }

            $result = $this->proxyService->deleteProxy(
                $request->order_org_id,
                $botDto
            );

            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Delete proxy error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function prolongProxy(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = User::query()->where(['telegram_id' => $request->user_id])->first();
            if (is_null($request->period))
                return ApiHelpers::error('Not found params: period');
            if (is_null($request->enter_amount))
                return ApiHelpers::error('Not found params: enter_amount');
            if (is_null($request->order_org_id))
                return ApiHelpers::error('Not found params: order_org_id');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');
            $bot = Bot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $botDto = BotFactory::fromEntity($bot);
            $result = BottApi::checkUser(
                $request->user_id,
                $request->user_secret_key,
                $botDto->public_key,
                $botDto->private_key
            );
            if (!$result['result']) {
                throw new \RuntimeException($result['message']);
            }
            if ($result['data']['money'] == 0) {
                throw new \RuntimeException('Пополните баланс в боте');
            }

            $result = $this->proxyService->prolongProxy(
                $request->order_org_id,
                $request->period,
                $request->enter_amount,
                $result['data'],
                $botDto
            );

            return ApiHelpers::success($result);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🔵R ' . __FUNCTION__ . ' Proxy): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Prolong proxy error');
        }
    }
}
