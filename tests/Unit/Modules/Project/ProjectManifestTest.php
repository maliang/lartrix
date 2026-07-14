<?php

namespace Lartrix\Tests\Unit\Modules\Project;

use InvalidArgumentException;
use Lartrix\Modules\Project\ProjectManifest;
use PHPUnit\Framework\TestCase;

class ProjectManifestTest extends TestCase
{
    /** 验证项目清单通过统一入口加载和校验。 */
    public function testLoadsAValidProjectManifest(): void
    {
        $path = $this->writeManifest([
            'schema_version' => 'trix.project.v1',
            'id' => 'official.mall',
            'name' => 'Mall',
            'version' => '1.0.0',
            'author' => 'Trix',
            'modules' => [],
        ]);

        $manifest = ProjectManifest::load($path);
        self::assertSame('official.mall', $manifest->id());
        self::assertSame('1.0.0', $manifest->version());
    }

    /** 验证无效项目清单无法绕过统一校验。 */
    public function testRejectsAnInvalidProjectManifest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProjectManifest::load($this->writeManifest(['schema_version' => 'legacy']));
    }

    /** 写入临时项目清单。 */
    private function writeManifest(array $manifest): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trix-project-' . uniqid('', true) . '.json';
        file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

        return $path;
    }
}
