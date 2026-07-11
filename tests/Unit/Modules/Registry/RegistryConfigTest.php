<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;

class RegistryConfigTest extends TestCase
{
    public function testModuleRegistryConfigProvidesUrlAndSignatureKeyDefaults(): void
    {
        $config = require __DIR__ . '/../../../../config/lartrix.php';

        self::assertArrayHasKey('module_registry', $config);
        self::assertArrayHasKey('url', $config['module_registry']);
        self::assertArrayHasKey('signature_key', $config['module_registry']);
        self::assertSame('', $config['module_registry']['url']);
        self::assertSame('', $config['module_registry']['signature_key']);
    }
}
