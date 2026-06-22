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
            error(__t('module.not_found'), null, 40102);
        }

        $result = $this->moduleService->enable($name);

        if (!$result) {
            error(__t('module.enable_failed'), null, 40000);
        }

        return success(__t('module.enabled'));
    }

    /**
     * 禁用模块
     */
    public function disable(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }

        $result = $this->moduleService->disable($name);

        if (!$result) {
            error(__t('module.disable_failed'), null, 40000);
        }

        return success(__t('module.disabled'));
    }

    /**
     * 安装模块
     */
    public function install(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }

        $result = $this->moduleService->install($name);

        if (!$result) {
            error(__t('module.install_failed'), null, 40000);
        }

        return success(__t('module.installed'));
    }

    /**
     * 卸载模块
     */
    public function uninstall(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }

        $result = $this->moduleService->uninstall($name);

        if (!$result) {
            error(__t('module.uninstall_failed'), null, 40000);
        }

        return success(__t('module.uninstalled'));
    }

    /**
     * 获取模块 Logo
     */
    public function logo(string $name)
    {
        $module = ModuleFacade::find($name);

        if (!$module) {
            abort(404, __t('module.not_found'));
        }

        $moduleJson = $module->json();
        $logoFile = $moduleJson->get('logo', '');

        if (empty($logoFile)) {
            abort(404, __t('module.logo_not_configured'));
        }

        // 构建完整路径（模块目录 + logo 文件名）
        $fullPath = $module->getPath() . '/' . $logoFile;

        if (!file_exists($fullPath)) {
            abort(404, __t('module.logo_not_found'));
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
            ->props(['title' => __t('title.module_market')])
            ->children([
                Result::make()
                    ->props([
                        'status' => 'info',
                        'title' => __t('title.coming_soon'),
                        'description' => __t('title.coming_soon_desc'),
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
            ->props(['title' => __t('title.installed_modules')])
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
                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.load_failed') . '" }}']),
                        ])
                        ->finally([
                            SetAction::make('loading', false),
                        ]),
                ],
                'handleEnable' => [
                    FetchAction::make('/modules/{{ $event }}/enable')
                        ->put()
                        ->then([
                            CallAction::make('$message.success', [__t('module.enabled')]),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.enable_failed') . '" }}']),
                        ]),
                ],
                'handleDisable' => [
                    FetchAction::make('/modules/{{ $event }}/disable')
                        ->put()
                        ->then([
                            CallAction::make('$message.success', [__t('module.disabled')]),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.disable_failed') . '" }}']),
                        ]),
                ],
                'handleInstall' => [
                    FetchAction::make('/modules/{{ $event }}/install')
                        ->put()
                        ->then([
                            CallAction::make('$message.success', [__t('module.installed')]),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.install_failed') . '" }}']),
                        ]),
                ],
                'handleUninstall' => [
                    FetchAction::make('/modules/{{ $event }}/uninstall')
                        ->put()
                        ->then([
                            CallAction::make('$message.success', [__t('module.uninstalled')]),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.uninstall_failed') . '" }}']),
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
                        ['key' => 'logo', 'title' => __t('column.logo'), 'width' => 60, 'slot' => [
                            Avatar::make()
                                ->if('slotData.row.logo')
                                ->props(['src' => '{{ routePrefix + "/modules/" + slotData.row.name + "/logo" }}', 'size' => 32, 'objectFit' => 'contain']),
                            SvgIcon::make('carbon:cube')
                                ->if('!slotData.row.logo')
                                ->props(['class' => 'text-2xl text-primary']),
                        ]],
                        ['key' => 'name', 'title' => __t('column.name'), 'width' => 150],
                        ['key' => 'version', 'title' => __t('column.version'), 'width' => 80],
                        ['key' => 'description', 'title' => __t('column.description'), 'ellipsis' => true],
                        ['key' => 'author', 'title' => __t('column.author'), 'width' => 100],
                        ['key' => 'website', 'title' => __t('column.website'), 'width' => 120, 'ellipsis' => true, 'slot' => [
                            Button::make()
                                ->if('slotData.row.website')
                                ->size('small')
                                ->props(['text' => true, 'type' => 'primary', 'tag' => 'a', 'href' => '{{ slotData.row.website }}', 'target' => '_blank'])
                                ->children([__t('button.visit')]),
                        ]],
                        ['key' => 'enabled', 'title' => __t('column.status'), 'width' => 80, 'slot' => [
                            Tag::make()
                                ->props([
                                    'type' => "{{ slotData.row.enabled ? 'success' : 'default' }}",
                                    'size' => 'small',
                                ])
                                ->children(["{{ slotData.row.enabled ? __t('tag.installed') : __t('tag.not_installed') }}"]),
                        ]],
                        ['key' => 'actions', 'title' => __t('column.actions'), 'width' => 160, 'slot' => [
                            Space::make()->children([
                                Button::make()
                                    ->if('!slotData.row.enabled')
                                    ->size('small')
                                    ->type('primary')
                                    ->props(['text' => true])
                                    ->on('click', ['call' => 'handleInstall', 'args' => ['{{ slotData.row.name }}']])
                                    ->text(__t('button.install')),
                                Button::make()
                                    ->if('slotData.row.enabled')
                                    ->size('small')
                                    ->type('warning')
                                    ->props(['text' => true])
                                    ->on('click', ['call' => 'handleDisable', 'args' => ['{{ slotData.row.name }}']])
                                    ->text(__t('tag.disabled')),
                                Popconfirm::make()
                                    ->if('slotData.row.enabled')
                                    ->on('positive-click', ['call' => 'handleUninstall', 'args' => ['{{ slotData.row.name }}']])
                                    ->slot('trigger', [
                                        Button::make()
                                            ->size('small')
                                            ->type('error')
                                            ->props(['text' => true])
                                            ->text(__t('button.uninstall')),
                                    ])
                                    ->children([__t('confirm.disable')]),
                            ]),
                        ]],
                    ]),
            ]);

        return success($schema->toArray());
    }
}
