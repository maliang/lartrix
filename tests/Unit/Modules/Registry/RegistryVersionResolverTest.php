<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryVersionResolver;
use PHPUnit\Framework\TestCase;

class RegistryVersionResolverTest extends TestCase
{
    public function testResolvesFirstVersionFromPaginatedRegistryResponse(): void
    {
        $resolver = new RegistryVersionResolver('php', 'laravel');

        $result = $resolver->resolveLatest([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'items' => [
                    [
                        'version' => '1.0.0',
                        'adapters' => [
                            [
                                'language' => 'php',
                                'framework' => 'laravel',
                                'status' => 'stable',
                                'package_type' => 'composer',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($result['installable']);
        self::assertSame('1.0.0', $result['version']['version']);
    }

    public function testRejectsEmptyRegistryVersionList(): void
    {
        $resolver = new RegistryVersionResolver('php', 'laravel');

        $result = $resolver->resolveLatest([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'items' => [],
            ],
        ]);

        self::assertFalse($result['installable']);
        self::assertSame('registry_version_missing', $result['reason']);
    }
}
