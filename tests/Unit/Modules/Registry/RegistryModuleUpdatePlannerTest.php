<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Manifest\ModuleManifest;
use Lartrix\Modules\Registry\RegistryModuleUpdatePlanner;
use PHPUnit\Framework\TestCase;

class RegistryModuleUpdatePlannerTest extends TestCase
{
    /** 验证目标版本较新时生成更新计划。 */
    public function testPlansUpdateWhenTargetVersionIsNewer(): void
    {
        $plan = (new RegistryModuleUpdatePlanner())->plan($this->manifest('1.0.0'), $this->manifest('1.1.0'));
        self::assertTrue($plan['allowed']);
        self::assertSame('update_available', $plan['action']);
    }

    /** 验证相同版本无需更新。 */
    public function testNoopsWhenTargetVersionMatchesCurrentVersion(): void
    {
        $plan = (new RegistryModuleUpdatePlanner())->plan($this->manifest('1.1.0'), $this->manifest('1.1.0'));
        self::assertFalse($plan['allowed']);
        self::assertSame('already_current', $plan['action']);
    }

    /** 验证默认禁止降级。 */
    public function testRejectsDowngradeByDefault(): void
    {
        $plan = (new RegistryModuleUpdatePlanner())->plan($this->manifest('1.2.0'), $this->manifest('1.1.0'));
        self::assertFalse($plan['allowed']);
        self::assertSame('downgrade_blocked', $plan['action']);
    }

    /** 验证显式允许时可以降级。 */
    public function testAllowsDowngradeWhenExplicitlyRequested(): void
    {
        $plan = (new RegistryModuleUpdatePlanner())->plan($this->manifest('1.2.0'), $this->manifest('1.1.0'), true);
        self::assertTrue($plan['allowed']);
        self::assertSame('downgrade_allowed', $plan['action']);
    }

    /** 构造合法的 Trix 模块清单。 */
    private function manifest(string $version): ModuleManifest
    {
        return ModuleManifest::fromValidatedArray([
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => $version,
            'type' => 'contract',
            'adapter' => ['language' => 'php', 'framework' => 'laravel', 'package_type' => 'composer'],
        ]);
    }
}
