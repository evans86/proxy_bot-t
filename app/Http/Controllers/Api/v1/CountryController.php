<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiHelpers;
use App\Helpers\BotLogHelpers;
use App\Http\Controllers\Controller;
use App\Services\Activate\CountryService;
use App\Services\External\ProxyApi;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * @var CountryService
     */
    public CountryService $countryService;

    public function __construct()
    {
        $this->countryService = new CountryService();
    }

    public function pingProxy()
    {
        $proxyApi = new ProxyApi(config('services.key_proxy.key'));
        $result = $proxyApi->getcountry();
        dd($result);
    }


    /**
     * @param Request $request
     * @return array|string
     */
    public function getCountry(Request $request)
    {
        try {
            if (is_null($request->version))
                return ApiHelpers::error('Not found params: version');
//        if (is_null($request->public_key))
//            return ApiHelpers::error('Not found params: public_key');
//        if (is_null($request->user_secret_key))
//            return ApiHelpers::error('Not found params: user_secret_key');

            $result = $this->countryService->formingCountriesArray($request->version);

            return ApiHelpers::success($result);
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🔵Proxy): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Get country error');
        }
    }
}
