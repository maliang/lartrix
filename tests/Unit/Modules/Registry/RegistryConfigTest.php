<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;

class RegistryConfigTest extends TestCase
{
    public function testModuleRegistryConfigProvidesUrlAndSignatureKeyDefaults(): void
    {
        $config = require __DIR__ . '/../../../../config/lartrix.php';

        self::assertArrayNotHasKey('module_registry', $config);
        self::assertArrayHasKey('module_market', $config);
        foreach (['enabled', 'url', 'auth_key', 'signature_key', 'timeout', 'cache_ttl'] as $key) {
            self::assertArrayHasKey($key, $config['module_market']);
        }
    }
}
