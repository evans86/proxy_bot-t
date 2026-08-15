<?php
/**
 * Hotfix: getProxy падает из‑за 429 от px6.link (4 запроса getcountry подряд).
 * Запуск на сервере:
 *   php /opt/apps/proxy6/scripts/hotfix-forming-proxy-429.php
 */

$path = dirname(__DIR__) . '/app/Services/Activate/ProxyService.php';
$content = file_get_contents($path);

if ($content === false) {
    fwrite(STDERR, "Cannot read: {$path}\n");
    exit(1);
}

if (str_contains($content, 'formingProxy: getcountry failed')) {
    echo "Already patched.\n";
    exit(0);
}

$old = <<<'OLD'
        $result = [];
        foreach ($proxies as $key => $proxy) {

            $countries = $proxyApi->getcountry($proxy->version);
//            BotLogHelpers::notifyBotLog('(🔵E ' . __FUNCTION__ . ' Proxy): ' . json_encode($countries));
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
OLD;

$new = <<<'NEW'
        $result = [];
        $isFirstRequest = true;
        foreach ($proxies as $key => $proxy) {
            if (! $isFirstRequest) {
                usleep(350000);
            }
            $isFirstRequest = false;

            $cacheKey = 'proxy6_countries_' . md5($botDto->api_key) . '_v' . $proxy->version;
            try {
                $countryCodes = \Cache::remember($cacheKey, 900, function () use ($proxyApi, $proxy) {
                    $response = $proxyApi->getcountry($proxy->version);
                    if (! is_array($response) || ! isset($response['list']) || ! is_array($response['list'])) {
                        return [];
                    }

                    return $response['list'];
                });
            } catch (\Throwable $e) {
                Log::warning('formingProxy: getcountry failed', [
                    'version' => $proxy->version,
                    'message' => $e->getMessage(),
                ]);
                continue;
            }

            if ($countryCodes === []) {
                continue;
            }

            $countriesArr = [];
            foreach ($countryCodes as $country) {
                $countryProxy = Country::query()->where(['iso_two' => $country])->first();
                if ($countryProxy === null) {
                    continue;
                }

                $countriesArr[] = [
                    'org_id' => $countryProxy->iso_two,
                    'name_ru' => $countryProxy->name_ru,
                    'name_en' => $countryProxy->name_en,
                    'image' => $countryProxy->image,
                ];
            }

            $result[] = [
                'title' => $proxy->title,
                'version' => $proxy->version,
                'countries' => $countriesArr,
            ];
        }

        return $result;
NEW;

if (! str_contains($content, 'foreach ($proxies as $key => $proxy) {')) {
    fwrite(STDERR, "Unexpected ProxyService.php format — patch manually.\n");
    exit(1);
}

$updated = str_replace($old, $new, $content, $count);
if ($count !== 1) {
    fwrite(STDERR, "Patch failed (matches: {$count}). Update app/Services/Activate/ProxyService.php from git.\n");
    exit(1);
}

file_put_contents($path, $updated);
echo "Patched: {$path}\n";
echo "Run: php artisan cache:clear\n";
