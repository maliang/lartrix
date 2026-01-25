<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\ModuleService;
use Lartrix\Schema\Components\NaiveUI\Card;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Grid;
use Lartrix\Schema\Components\NaiveUI\GridItem;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Schema\Components\NaiveUI\Result;
use Lartrix\Schema\Components\NaiveUI\Descriptions;
use Lartrix\Schema\Components\NaiveUI\DescriptionsItem;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;

class ModuleController extends Controller
{
    public function __construct(
        protected ModuleService $moduleService
    ) {}

    /**
     * 模块入口（支持 action_type 分发）
     */
    public function index(Request $request): array
    {
        $actionType = $request->input('action_type', 'list');

        return match ($actionType) {
            'market_ui' => $this->marketUi(),
            'installed_ui' => $this->installedUi(),
            default => $this->list(),
        };
    }

    /**
     * 模块列表
     */
    protected function list(): array
    {
        $modules = $this->moduleService->getModules();
        return success($modules);
    }

    /**
     * 启用模块
     */
    public function enable(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error('模块不存在', null, 40102);
        }

        $result = $this->moduleService->enable($name);

        if (!$result) {
            error('启用失败', null, 40000);
        }

        return success('启用成功');
    }

    /**
     * 禁用模块
     */
    public function disable(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error('模块不存在', null, 40102);
        }

        $result = $this->moduleService->disable($name);

        if (!$result) {
            error('禁用失败', null, 40000);
        }

        return success('禁用成功');
    }

    /**
     * 模块市场 UI Schema
     */
    protected function marketUi(): array
    {
        $schema = Card::make()
            ->props(['title' => '模块市场'])
            ->children([
                Result::make()
                    ->props([
                        'status' => 'info',
                        'title' => '敬请期待',
                        'description' => '模块市场正在开发中，即将上线...',
                    ])
                    ->slot('icon', [
                        SvgIcon::make('carbon:store')->props(['class' => 'text-6xl text-primary']),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 已安装模块 UI Schema
     */
    protected function installedUi(): array
    {
        $schema = Card::make()
            ->props(['title' => '已安装模块'])
            ->data([
                'modules' => [],
                'loading' => false,
            ])
            ->methods([
                'loadData' => [
                    SetAction::make('loading', true),
                    FetchAction::make('/modules')
                        ->get()
                        ->then([
                            SetAction::make('modules', '{{ $response.data || [] }}'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "加载失败" }}']),
                        ])
                        ->finally([
                            SetAction::make('loading', false),
                        ]),
                ],
                'handleEnable' => [
                    FetchAction::make('/modules/{{ $event }}/enable')
                        ->put()
                        ->then([
                            CallAction::make('$message.success', ['启用成功']),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "启用失败" }}']),
                        ]),
                ],
                'handleDisable' => [
                    FetchAction::make('/modules/{{ $event }}/disable')
                        ->put()
                        ->then([
                            CallAction::make('$message.success', ['禁用成功']),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "禁用失败" }}']),
                        ]),
                ],
            ])
            ->onMounted(CallAction::make('loadData'))
            ->children([
                Space::make()
                    ->props(['vertical' => true, 'size' => 'large'])
                    ->children([
                        // 无模块时显示空状态
                        Result::make()
                            ->if('modules.length === 0 && !loading')
                            ->props([
                                'status' => 'info',
                                'title' => '暂无已安装模块',
                                'description' => '您可以从模块市场安装模块',
                            ]),
                        // 模块列表
                        Grid::make()
                            ->if('modules.length > 0')
                            ->props([
                                'cols' => '1 s:2 m:3',
                                'xGap' => 16,
                                'yGap' => 16,
                                'responsive' => 'screen',
                            ])
                            ->children([
                                GridItem::make()
                                    ->for('module in modules', '{{ module.name }}')
                                    ->children([
                                        $this->buildModuleCard(),
                                    ]),
                            ]),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 构建模块卡片
     */
    protected function buildModuleCard()
    {
        return Card::make()
            ->props(['class' => 'h-full'])
            ->children([
                Space::make()
                    ->props(['vertical' => true, 'size' => 'medium'])
                    ->children([
                        // 模块名称和状态
                        Space::make()
                            ->props(['justify' => 'space-between', 'align' => 'center', 'style' => ['width' => '100%']])
                            ->children([
                                Space::make()->children([
                                    SvgIcon::make('carbon:cube')->props(['class' => 'text-xl text-primary']),
                                    '{{ module.name }}',
                                ]),
                                Tag::make()
                                    ->props([
                                        'type' => "{{ module.enabled ? 'success' : 'default' }}",
                                        'size' => 'small',
                                    ])
                                    ->children(["{{ module.enabled ? '已启用' : '已禁用' }}"]),
                            ]),
                        // 模块描述
                        Descriptions::make()
                            ->props(['column' => 1, 'labelPlacement' => 'left', 'size' => 'small'])
                            ->children([
                                DescriptionsItem::make()->props(['label' => '版本'])->children(['{{ module.version || "-" }}']),
                                DescriptionsItem::make()->props(['label' => '描述'])->children(['{{ module.description || "-" }}']),
                            ]),
                        // 操作按钮
                        Space::make()->children([
                            Button::make()
                                ->if('!module.enabled')
                                ->size('small')
                                ->type('primary')
                                ->on('click', ['call' => 'handleEnable', 'args' => ['{{ module.name }}']])
                                ->text('启用'),
                            Button::make()
                                ->if('module.enabled')
                                ->size('small')
                                ->type('warning')
                                ->on('click', ['call' => 'handleDisable', 'args' => ['{{ module.name }}']])
                                ->text('禁用'),
                        ]),
                    ]),
            ]);
    }
}
