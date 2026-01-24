<?php

namespace Lartrix\Tests\Unit\Models;

use Lartrix\Tests\TestCase;
use Lartrix\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_setting(): void
    {
        $setting = Setting::create([
            'group' => 'site',
            'key' => 'site_name',
            'title' => '站点名称',
            'type' => 'string',
            'value' => 'Lartrix Admin',
            'default_value' => 'Admin',
            'description' => '网站名称',
            'sort' => 1,
        ]);

        $this->assertDatabaseHas('admin_settings', [
            'key' => 'site_name',
            'value' => 'Lartrix Admin',
        ]);
    }

    /** @test */
    public function it_can_get_setting_value(): void
    {
        Setting::create([
            'group' => 'site',
            'key' => 'site_name',
            'title' => '站点名称',
            'type' => 'string',
            'value' => 'Lartrix Admin',
        ]);

        $value = Setting::get('site_name');
        
        $this->assertEquals('Lartrix Admin', $value);
    }

    /** @test */
    public function it_returns_default_when_setting_not_found(): void
    {
        $value = Setting::get('non_existent', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    /** @test */
    public function it_can_set_setting_value(): void
    {
        Setting::create([
            'group' => 'site',
            'key' => 'site_name',
            'title' => '站点名称',
            'type' => 'string',
            'value' => 'Old Name',
        ]);

        Setting::set('site_name', 'New Name');
        
        $this->assertEquals('New Name', Setting::get('site_name'));
    }

    /** @test */
    public function it_can_get_settings_by_group(): void
    {
        Setting::create([
            'group' => 'site',
            'key' => 'site_name',
            'title' => '站点名称',
            'type' => 'string',
            'value' => 'Lartrix',
        ]);

        Setting::create([
            'group' => 'site',
            'key' => 'site_logo',
            'title' => '站点Logo',
            'type' => 'string',
            'value' => '/logo.png',
        ]);

        Setting::create([
            'group' => 'email',
            'key' => 'smtp_host',
            'title' => 'SMTP主机',
            'type' => 'string',
            'value' => 'smtp.example.com',
        ]);

        $siteSettings = Setting::getByGroup('site');
        
        $this->assertCount(2, $siteSettings);
    }
}
