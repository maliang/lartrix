<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryStagedManifestVerifier;
use PHPUnit\Framework\TestCase;

class RegistryStagedManifestVerifierTest extends TestCase
{
    /** 验证暂存包的 ID、版本和技术适配器一致时通过。 */
    public function testAcceptsManifestMatchingExpectedModuleVersionAndAdapter(): void
    {
        $root = $this->makeStage('official.cms', '1.0.0', 'laravel');
        $result = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify($root, 'official.cms/module.json', 'official.cms', '1.0.0');

        self::assertTrue($result['ok']);
        self::assertSame('official.cms', $result['manifest_id']);
        self::assertSame(['writes_files' => true], $result['security']);
    }

    /** 验证拒绝模块 ID 不一致的包。 */
    public function testRejectsMismatchedModuleId(): void
    {
        $root = $this->makeStage('evil.cms', '1.0.0', 'laravel');
        $result = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify($root, 'official.cms/module.json', 'official.cms', '1.0.0');
        self::assertFalse($result['ok']);
        self::assertSame('module_id_mismatch', $result['reason']);
    }

    /** 验证拒绝框架不匹配的包。 */
    public function testRejectsMismatchedAdapter(): void
    {
        $root = $this->makeStage('official.cms', '1.0.0', 'thinkphp');
        $result = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify($root, 'official.cms/module.json', 'official.cms', '1.0.0');
        self::assertFalse($result['ok']);
        self::assertSame('manifest_adapter_invalid', $result['reason']);
    }

    /** 创建符合 Nwidart 根结构的暂存模块。 */
    private function makeStage(string $id, string $version, string $framework): string
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-verify-stage-' . uniqid('', true);
        $directory = $root . DIRECTORY_SEPARATOR . 'official.cms';
        mkdir($directory, 0775, true);
        file_put_contents($directory . DIRECTORY_SEPARATOR . 'module.json', json_encode([
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
                'adapter' => ['language' => 'php', 'framework' => $framework, 'package_type' => 'composer'],
                'security' => ['writes_files' => true],
            ],
        ], JSON_THROW_ON_ERROR));

        return $root;
    }
}
