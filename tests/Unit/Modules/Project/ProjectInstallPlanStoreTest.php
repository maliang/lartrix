<?php

namespace Lartrix\Tests\Unit\Modules\Project;

use Lartrix\Modules\Project\ProjectInstallPlanStore;
use PHPUnit\Framework\TestCase;

class ProjectInstallPlanStoreTest extends TestCase
{
    /** 验证安装计划只应用为唯一的 Laravel 项目配置。 */
    public function testAppliesAndReadsProjectConfiguration(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-project-plan-' . uniqid('', true);
        $path = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'trix-project.php';
        $store = new ProjectInstallPlanStore($path);

        self::assertSame($path, $store->apply([
            'project' => 'official.mall-starter',
            'version' => '1.0.0',
            'project_config' => ['site_name' => 'Mall'],
            'contract_bindings' => ['user.account' => ['provider_module' => 'official.user']],
            'modules' => [
                ['id' => 'official.user', 'selected_version' => '2.0.0', 'config' => ['guard' => 'admin']],
            ],
        ]));

        $config = $store->read();
        self::assertSame('official.mall-starter', $config['id']);
        self::assertSame('1.0.0', $config['version']);
        self::assertSame(['site_name' => 'Mall'], $config['project_config']);
        self::assertSame('2.0.0', $config['modules']['official.user']['version']);
        self::assertSame(['guard' => 'admin'], $config['modules']['official.user']['config']);
        self::assertSame($path, $store->path());
        self::assertFileExists($path);
        self::assertFileDoesNotExist($root . DIRECTORY_SEPARATOR . 'install-plan.json');
    }
}
