<?php

namespace Lartrix\Tests\Unit\Modules\Project;

use Lartrix\Modules\Project\ProjectInstallPlanStore;
use PHPUnit\Framework\TestCase;

class ProjectInstallPlanStoreTest extends TestCase
{
    public function testSavesAndReadsProjectInstallPlanArtifacts(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-project-plan-' . uniqid('', true);
        $store = new ProjectInstallPlanStore($root);

        $paths = $store->save('official.mall-starter', '1.0.0', [
            'project' => 'official.mall-starter',
            'version' => '1.0.0',
            'project_config' => ['site_name' => 'Mall'],
            'contract_bindings' => ['user.account' => ['provider_module' => 'official.user']],
            'setup' => ['commands' => ['php artisan migrate']],
            'modules' => [
                ['id' => 'official.user', 'config' => ['guard' => 'admin']],
            ],
        ]);

        self::assertFileExists($paths['install_plan']);
        self::assertFileExists($paths['project_config']);
        self::assertFileExists($paths['contract_bindings']);
        self::assertFileExists($paths['setup']);
        self::assertFileExists($paths['module_config:official.user']);
        self::assertSame(['site_name' => 'Mall'], $store->projectConfig('official.mall-starter', '1.0.0'));
        self::assertSame(['guard' => 'admin'], $store->moduleConfig('official.mall-starter', '1.0.0', 'official.user'));
        self::assertSame(['user.account' => ['provider_module' => 'official.user']], $store->contractBindings('official.mall-starter', '1.0.0'));
    }
}
