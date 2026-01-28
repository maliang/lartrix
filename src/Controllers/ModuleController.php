<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Lartrix\Services\ModuleService;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Lartrix\Schema\Components\NaiveUI\Card;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Schema\Components\NaiveUI\Result;
use Lartrix\Schema\Components\NaiveUI\Avatar;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\Business\DataTable;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\Custom\Html;
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
     * 获取模块 Logo
     */
    public function logo(string $name)
    {
        $module = ModuleFacade::find($name);

        if (!$module) {
            abort(404, '模块不存在');
        }

        $moduleJson = $module->json();
        $logoFile = $moduleJson->get('logo', '');

        if (empty($logoFile)) {
            abort(404, 'Logo 未配置');
        }

        // 构建完整路径（模块目录 + logo 文件名）
        $fullPath = $module->getPath() . '/' . $logoFile;

        if (!file_exists($fullPath)) {
            abort(404, 'Logo 文件不存在');
        }

        // 获取 MIME 类型
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        return Response::file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
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
        $routePrefix = '/' . config('lartrix.route_prefix', 'api/admin');

        $schema = Card::make()
            ->props(['title' => '已安装模块'])
            ->data([
                'modules' => [],
                'loading' => false,
                'routePrefix' => $routePrefix,
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
                DataTable::make()
                    ->dataSource('modules')
                    ->loading('loading')
                    ->rowKey('name')
                    ->columns([
                        ['key' => 'logo', 'title' => 'Logo', 'width' => 60, 'slot' => [
                            Avatar::make()
                                ->if('slotData.row.logo')
                                ->props(['src' => '{{ routePrefix + "/modules/" + slotData.row.name + "/logo" }}', 'size' => 32, 'objectFit' => 'contain']),
                            SvgIcon::make('carbon:cube')
                                ->if('!slotData.row.logo')
                                ->props(['class' => 'text-2xl text-primary']),
                        ]],
                        ['key' => 'name', 'title' => '模块名称', 'width' => 150],
                        ['key' => 'version', 'title' => '版本', 'width' => 80],
                        ['key' => 'description', 'title' => '描述', 'ellipsis' => true],
                        ['key' => 'author', 'title' => '作者', 'width' => 100],
                        ['key' => 'website', 'title' => '网址', 'width' => 120, 'ellipsis' => true, 'slot' => [
                            Button::make()
                                ->if('slotData.row.website')
                                ->size('small')
                                ->props(['text' => true, 'type' => 'primary', 'tag' => 'a', 'href' => '{{ slotData.row.website }}', 'target' => '_blank'])
                                ->children(['访问']),
                        ]],
                        ['key' => 'enabled', 'title' => '状态', 'width' => 80, 'slot' => [
                            Tag::make()
                                ->props([
                                    'type' => "{{ slotData.row.enabled ? 'success' : 'default' }}",
                                    'size' => 'small',
                                ])
                                ->children(["{{ slotData.row.enabled ? '已启用' : '已禁用' }}"]),
                        ]],
                        ['key' => 'actions', 'title' => '操作', 'width' => 120, 'slot' => [
                            Space::make()->children([
                                Button::make()
                                    ->if('!slotData.row.enabled')
                                    ->size('small')
                                    ->type('primary')
                                    ->props(['text' => true])
                                    ->on('click', ['call' => 'handleEnable', 'args' => ['{{ slotData.row.name }}']])
                                    ->text('启用'),
                                Popconfirm::make()
                                    ->if('slotData.row.enabled')
                                    ->on('positive-click', ['call' => 'handleDisable', 'args' => ['{{ slotData.row.name }}']])
                                    ->slot('trigger', [
                                        Button::make()
                                            ->size('small')
                                            ->type('warning')
                                            ->props(['text' => true])
                                            ->text('禁用'),
                                    ])
                                    ->children(['确定禁用该模块？']),
                            ]),
                        ]],
                    ]),
            ]);

        return success($schema->toArray());
    }
}
