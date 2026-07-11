<?php

namespace Lartrix\Tests\Unit\Models;

use Lartrix\Tests\TestCase;
use Lartrix\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => '鍗氬妯″潡',
            'description' => '鍗氬绠＄悊妯″潡',
            'version' => '1.0.0',
            'author' => 'Lartrix',
            'enabled' => true,
        ]);

        $this->assertDatabaseHas('modules', [
            'name' => 'Blog',
            'title' => '鍗氬妯″潡',
            'enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_enable_and_disable_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => '鍗氬妯″潡',
            'enabled' => false,
        ]);

        $this->assertFalse((bool) $module->enabled);

        $module->update(['enabled' => true]);
        
        $this->assertTrue((bool) $module->fresh()->enabled);
    }

    /** @test */
    public function it_can_store_config_as_json(): void
    {
        $config = [
            'posts_per_page' => 10,
            'allow_comments' => true,
        ];

        $module = Module::create([
            'name' => 'Blog',
            'title' => '鍗氬妯″潡',
            'config' => $config,
        ]);

        $this->assertEquals($config, $module->config);
    }
}
