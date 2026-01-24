<?php

namespace Lartrix\Tests\Unit\Services;

use Lartrix\Tests\TestCase;
use Lartrix\Services\ModuleService;
use Lartrix\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleService $moduleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleService = new ModuleService();
    }

    /** @test */
    public function it_can_get_module_list(): void
    {
        Module::create([
            'name' => 'Blog',
            'title' => '博客模块',
            'enabled' => true,
        ]);

        Module::create([
            'name' => 'Shop',
            'title' => '商城模块',
            'enabled' => false,
        ]);

        $modules = $this->moduleService->getList();

        $this->assertCount(2, $modules);
    }

    /** @test */
    public function it_can_enable_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => '博客模块',
            'enabled' => false,
        ]);

        $this->moduleService->enable($module->name);

        $this->assertTrue((bool) $module->fresh()->enabled);
    }

    /** @test */
    public function it_can_disable_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => '博客模块',
            'enabled' => true,
        ]);

        $this->moduleService->disable($module->name);

        $this->assertFalse((bool) $module->fresh()->enabled);
    }

    /** @test */
    public function it_can_sync_module_status(): void
    {
        Module::create([
            'name' => 'Blog',
            'title' => '博客模块',
            'enabled' => true,
        ]);

        // 同步操作应该不会抛出异常
        $this->moduleService->sync();

        $this->assertTrue(true);
    }
}
