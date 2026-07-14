<?php

namespace Lartrix\Tests\Unit\Services;

use Lartrix\Services\ModuleService;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class ModuleServiceActivatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testActivatorStateWinsOverEnabledLegacyAlias(): void
    {
        $json = Mockery::mock();
        $json->shouldReceive('getAttributes')->once()->andReturn([
            'name' => 'ModuleMarket',
            'alias' => 'Module Market',
            'enabled' => true,
        ]);

        $nwidartModule = Mockery::mock();
        $nwidartModule->shouldReceive('json')->once()->andReturn($json);
        $nwidartModule->shouldReceive('isEnabled')->once()->andReturnFalse();
        $nwidartModule->shouldNotReceive('enable');

        Mockery::mock('alias:Nwidart\Modules\Facades\Module')
            ->shouldReceive('all')->once()->andReturn(['ModuleMarket' => $nwidartModule]);

        $model = Mockery::mock('alias:Lartrix\Models\Module');
        $model->shouldReceive('updateOrCreate')->once()->with(
            ['name' => 'ModuleMarket'],
            Mockery::on(static fn (array $values): bool => $values['enabled'] === false),
        );
        $model->shouldReceive('whereNotIn')->once()->with('name', ['ModuleMarket'])->andReturnSelf();
        $model->shouldReceive('delete')->once();

        (new ModuleService())->syncModules();

        self::assertTrue(true);
    }
}
