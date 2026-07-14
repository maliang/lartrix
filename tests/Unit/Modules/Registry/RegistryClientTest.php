<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Lartrix\Modules\Registry\RegistryClient;
use PHPUnit\Framework\TestCase;

class RegistryClientTest extends TestCase
{
    /** 验证客户端统一拼接地址、携带 Auth Key 并解析响应。 */
    public function testRequestsRegistryWithAuthKey(): void
    {
        $factory = new Factory();
        $factory->fake([
            'https://registry.example/api/registry/modules*' => $factory->response(['code' => 0, 'data' => ['items' => []]], 200),
        ]);
        $client = new RegistryClient('https://registry.example/api/', 'trx_test', 15, fn () => $factory->acceptJson()->timeout(15));

        $result = $client->getJson('/registry/modules', ['language' => 'php']);

        self::assertTrue($result['ok']);
        self::assertSame(200, $result['status']);
        $factory->assertSent(fn (Request $request): bool => $request->url() === 'https://registry.example/api/registry/modules?language=php'
            && $request->hasHeader('Authorization', 'Bearer trx_test'));
    }

    /** 验证未配置市场地址时返回稳定失败结构。 */
    public function testRejectsMissingRegistryUrl(): void
    {
        $result = (new RegistryClient('', ''))->getJson('/registry/modules');
        self::assertFalse($result['ok']);
        self::assertSame('registry_url_missing', $result['reason']);
    }

    /** 验证下载地址跨域时不会发送 Auth Key 或发起请求。 */
    public function testRejectsCrossOriginPackageUrl(): void
    {
        $factory = new Factory();
        $factory->fake();
        $client = new RegistryClient('https://registry.example/api', 'trx_secret', 15, fn () => $factory->acceptJson());

        self::assertNull($client->download('https://attacker.example/module.zip'));
        $factory->assertNothingSent();
    }
}
