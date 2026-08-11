<?php

namespace Tests\Unit;

use App\Support\SafeLog;
use Tests\TestCase;

class SafeLogTest extends TestCase
{
    public function test_sanitize_removes_sensitive_query_params(): void
    {
        $input = 'Client error: `GET https://api.bot-t.com/v1/module/user/check-secret?public_key=abc&private_key=secret123&secret_key=hexkey` resulted in a 400';

        $sanitized = SafeLog::sanitize($input);

        self::assertStringNotContainsString('secret123', $sanitized);
        self::assertStringNotContainsString('hexkey', $sanitized);
        self::assertStringContainsString('private_key=***', $sanitized);
        self::assertStringContainsString('secret_key=***', $sanitized);
    }

    public function test_sanitize_masks_proxy_credentials(): void
    {
        $input = 'proxy http://proxy6relay:Proxy6Relay2026xK9@167.179.34.13:3128 failed';

        $sanitized = SafeLog::sanitize($input);

        self::assertStringNotContainsString('Proxy6Relay2026xK9', $sanitized);
        self::assertStringContainsString('proxy6relay:***@', $sanitized);
    }
}
