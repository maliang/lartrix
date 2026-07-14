<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Lartrix\Modules\Registry\RegistryClient;
use Lartrix\Modules\Registry\RegistryPackagePipeline;
use Mockery;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class RegistryPackagePipelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Container::setInstance(new Container());
        Container::getInstance()->instance('config', new Repository([
            'lartrix.module_market.signature_key' => '',
        ]));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Container::setInstance(null);
        parent::tearDown();
    }

    public function testPreparesDownloadedPackageWithAStableSuccessShape(): void
    {
        $version = '1.0.' . random_int(1000, 9999);
        $package = $this->makePackage(version: $version);
        $result = $this->pipeline($package)->prepare($this->adapter($package), 'official.cms', $version);

        self::assertSame(['ok', 'reason', 'message', 'path', 'manifest', 'security', 'package_path'], array_keys($result));
        self::assertTrue($result['ok']);
        self::assertNull($result['reason']);
        self::assertDirectoryExists($result['path']);
        self::assertSame('official.cms/module.json', $result['manifest']);
        self::assertSame(['writes_files' => true], $result['security']);
        self::assertFileExists($result['package_path']);
    }

    /**
     * @dataProvider criticalFailureProvider
     */
    public function testNormalizesCriticalFailuresToOneShape(string $failure, string $reason): void
    {
        $version = '1.0.' . random_int(1000, 9999);
        $package = match ($failure) {
            'download' => $this->makePackage(version: $version),
            'stage' => $this->makeZip(['../escape.php' => '<?php', 'official.cms/module.json' => '{}']),
            'verify' => $this->makePackage('other.cms', $version),
        };
        $adapter = $this->adapter($package);
        if ($failure === 'download') {
            $adapter['checksum'] = 'sha256:' . str_repeat('0', 64);
        }

        $result = $this->pipeline($package)->prepare($adapter, 'official.cms', $version);

        self::assertSame(['ok', 'reason', 'message', 'path', 'manifest', 'security'], array_keys($result));
        self::assertFalse($result['ok']);
        self::assertSame($reason, $result['reason']);
        self::assertIsString($result['message']);
        self::assertNotSame('', $result['message']);
        self::assertNull($result['path']);
        self::assertNull($result['manifest']);
        self::assertSame([], $result['security']);
    }

    public static function criticalFailureProvider(): array
    {
        return [
            'download validation' => ['download', 'checksum_mismatch'],
            'staging preflight' => ['stage', 'unsafe_path'],
            'manifest verification' => ['verify', 'module_id_mismatch'],
        ];
    }

    private function pipeline(string $package): RegistryPackagePipeline
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturnTrue();
        $response->shouldReceive('body')->once()->andReturn($package);

        $request = Mockery::mock(PendingRequest::class);
        $request->shouldReceive('withOptions')->once()->with(['allow_redirects' => false])->andReturnSelf();
        $request->shouldReceive('get')->once()->andReturn($response);

        return new RegistryPackagePipeline(new RegistryClient(
            'https://registry.example',
            requestFactory: static fn (): PendingRequest => $request,
        ));
    }

    private function adapter(string $package): array
    {
        return [
            'language' => 'php',
            'framework' => 'laravel',
            'package_url' => 'https://registry.example/packages/official.cms.zip',
            'checksum' => 'sha256:' . hash('sha256', $package),
        ];
    }

    private function makePackage(string $id = 'official.cms', string $version = '1.0.0'): string
    {
        return $this->makeZip([
            'official.cms/module.json' => json_encode([
                'name' => 'CMS',
                'alias' => 'cms',
                'description' => 'CMS module',
                'keywords' => [],
                'priority' => 0,
                'providers' => [],
                'files' => [],
                'trix' => [
                    'schema_version' => 'trix.module.v1',
                    'id' => $id,
                    'name' => 'CMS',
                    'version' => $version,
                    'type' => 'contract',
                    'adapter' => ['language' => 'php', 'framework' => 'laravel', 'package_type' => 'composer'],
                    'security' => ['writes_files' => true],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    private function makeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lartrix-pipeline-package-') . '.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return (string) file_get_contents($path);
    }
}
