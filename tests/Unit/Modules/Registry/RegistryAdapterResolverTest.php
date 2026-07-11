<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryAdapterResolver;
use PHPUnit\Framework\TestCase;

class RegistryAdapterResolverTest extends TestCase
{
    public function testResolvesInstallableLaravelAdapter(): void
    {
        $resolver = new RegistryAdapterResolver('php', 'laravel');

        $result = $resolver->resolve([
            'version' => '1.0.0',
            'adapters' => [
                [
                    'language' => 'php',
                    'framework' => 'laravel',
                    'status' => 'stable',
                    'package_type' => 'composer',
                    'package_url' => 'https://registry.example/modules/official.cms-laravel.zip',
                    'checksum' => 'sha256:abc',
                ],
                [
                    'language' => 'php',
                    'framework' => 'thinkphp',
                    'status' => 'compatible',
                    'package_type' => 'composer',
                    'package_url' => 'https://registry.example/modules/official.cms-thinkphp.zip',
                ],
            ],
        ]);

        self::assertTrue($result['installable']);
        self::assertSame('php', $result['adapter']['language']);
        self::assertSame('laravel', $result['adapter']['framework']);
        self::assertSame('stable', $result['adapter']['status']);
        self::assertSame('composer', $result['adapter']['package_type']);
        self::assertSame('https://registry.example/modules/official.cms-laravel.zip', $result['adapter']['package_url']);
    }

    public function testRejectsPlannedLaravelAdapter(): void
    {
        $resolver = new RegistryAdapterResolver('php', 'laravel');

        $result = $resolver->resolve([
            'adapters' => [
                [
                    'language' => 'php',
                    'framework' => 'laravel',
                    'status' => 'planned',
                ],
            ],
        ]);

        self::assertFalse($result['installable']);
        self::assertSame('adapter_not_installable', $result['reason']);
        self::assertStringContainsString('planned', $result['message']);
    }

    public function testRejectsMissingLaravelAdapter(): void
    {
        $resolver = new RegistryAdapterResolver('php', 'laravel');

        $result = $resolver->resolve([
            'adapters' => [
                [
                    'language' => 'php',
                    'framework' => 'thinkphp',
                    'status' => 'stable',
                ],
            ],
        ]);

        self::assertFalse($result['installable']);
        self::assertSame('adapter_missing', $result['reason']);
        self::assertStringContainsString('laravel', $result['message']);
        self::assertStringContainsString('available adapters: php/thinkphp', $result['message']);
    }
}
