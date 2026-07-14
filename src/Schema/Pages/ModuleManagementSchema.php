<?php

namespace Lartrix\Schema\Pages;

use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Components\Business\DataTable;
use Lartrix\Schema\Components\Custom\Html;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\NaiveUI\Avatar;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Card;
use Lartrix\Schema\Components\NaiveUI\Flex;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\Modal;
use Lartrix\Schema\Components\NaiveUI\Pagination;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\NaiveUI\Result;
use Lartrix\Schema\Components\NaiveUI\Select;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\TabPane;
use Lartrix\Schema\Components\NaiveUI\Tabs;
use Lartrix\Schema\Components\NaiveUI\Tag;

/** 构建已安装模块页面及共享的模块市场弹窗 Schema。 */
class ModuleManagementSchema
{
    /** 注入独立的模块市场 Schema。 */
    public function __construct(private readonly ModuleMarketSchema $market)
    {
    }

    /** 返回项目分类筛选选项。 */
        /** 执行 marketTypeLabel 方法对应的具体职责。 */
        /** 将输入值归一化为内部标准格式。 */
        /** 执行 marketUi 方法对应的具体职责。 */
        /** 执行模块或项目安装流程。 */
    public function installed(): array
    {
        $routePrefix = '/' . trim((string) config('lartrix.api_prefix', 'api/admin'), '/');

        $schema = Card::make()
            ->props(['title' => __t('title.installed_modules')])
            ->data([
                'modules' => [],
                'loading' => false,
                'routePrefix' => $routePrefix,
                'marketVisible' => false,
                'marketActiveTab' => 'modules',
                'marketModuleKeyword' => '',
                'marketModuleType' => 'all',
                'marketProjectKeyword' => '',
                'marketProjectType' => 'all',
                'marketModuleTypeOptions' => $this->market->moduleTypeOptions(),
                'marketProjectTypeOptions' => $this->market->projectTypeOptions(),
                'marketModules' => [],
                'marketProjects' => [],
                'marketModulePage' => 1,
                'marketProjectPage' => 1,
                'marketModulePageSize' => 16,
                'marketProjectPageSize' => 16,
                'marketModuleTotal' => 0,
                'marketProjectTotal' => 0,
                'marketModuleLoading' => false,
                'marketProjectLoading' => false,
                'marketDetailVisible' => false,
                'marketDetailKind' => 'module',
                'marketDetailItem' => null,
                'marketRegistryUrl' => rtrim((string) config('lartrix.module_market.url', ''), '/'),
            ])
            ->methods($this->installedUiMethods())
            ->onMounted(CallAction::make('loadData'))
            ->children([
                Space::make()
                    ->props(['justify' => 'end', 'style' => 'margin-bottom: 16px'])
                    ->children([
                        Button::make()
                            ->type('info')
                            ->on('click', ['call' => 'handlePublishProject'])
                            ->text('上传当前项目'),
                        Button::make()
                            ->type('primary')
                            ->on('click', ['call' => 'openModuleMarket'])
                            ->text('模块市场'),
                ]),
                $this->installedModulesTable(),
                $this->market->modal(),
                $this->market->detailModal(),
            ]);

        return success($schema->toArray());
    }

        /** 执行模块或项目安装流程。 */
    protected function installedUiMethods(): array
    {
        return [
            'openModuleMarket' => [
                SetAction::make('marketVisible', true),
                SetAction::make('marketModulePage', 1),
                SetAction::make('marketProjectPage', 1),
                CallAction::make('loadMarketModules'),
                CallAction::make('loadMarketProjects'),
            ],
            'loadMarketModules' => [
                SetAction::make('marketModuleLoading', true),
                FetchAction::make('/modules/market/modules')
                    ->get()
                    ->params(['keyword' => '{{ marketModuleKeyword }}', 'type' => '{{ marketModuleType }}', 'language' => 'php', 'framework' => 'laravel', 'page' => '{{ marketModulePage }}', 'page_size' => '{{ marketModulePageSize }}'])
                    ->then([
                        SetAction::make('marketModules', '{{ $response.data.items || [] }}'),
                        SetAction::make('marketModuleTotal', '{{ $response.data.total || 0 }}'),
                        SetAction::make('marketModulePage', '{{ $response.data.page || marketModulePage }}'),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "模块市场加载失败" }}'])])
                    ->finally([SetAction::make('marketModuleLoading', false)]),
            ],
            'loadMarketProjects' => [
                SetAction::make('marketProjectLoading', true),
                FetchAction::make('/modules/market/projects')
                    ->get()
                    ->params(['keyword' => '{{ marketProjectKeyword }}', 'type' => '{{ marketProjectType }}', 'language' => 'php', 'framework' => 'laravel', 'page' => '{{ marketProjectPage }}', 'page_size' => '{{ marketProjectPageSize }}'])
                    ->then([
                        SetAction::make('marketProjects', '{{ $response.data.items || [] }}'),
                        SetAction::make('marketProjectTotal', '{{ $response.data.total || 0 }}'),
                        SetAction::make('marketProjectPage', '{{ $response.data.page || marketProjectPage }}'),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目市场加载失败" }}'])])
                    ->finally([SetAction::make('marketProjectLoading', false)]),
            ],
            'loadData' => [
                SetAction::make('loading', true),
                FetchAction::make('/modules')
                    ->get()
                    ->then([SetAction::make('modules', '{{ $response.data || [] }}')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.load_failed') . '" }}'])])
                    ->finally([SetAction::make('loading', false)]),
            ],
            'searchMarketModules' => [
                SetAction::make('marketModulePage', 1),
                CallAction::make('loadMarketModules'),
            ],
            'searchMarketProjects' => [
                SetAction::make('marketProjectPage', 1),
                CallAction::make('loadMarketProjects'),
            ],
            'handleMarketModulePageChange' => [
                SetAction::make('marketModulePage', '{{ $event }}'),
                CallAction::make('loadMarketModules'),
            ],
            'handleMarketProjectPageChange' => [
                SetAction::make('marketProjectPage', '{{ $event }}'),
                CallAction::make('loadMarketProjects'),
            ],
            'showMarketModuleDetail' => [
                SetAction::make('marketDetailKind', 'module'),
                SetAction::make('marketDetailItem', '{{ $event }}'),
                SetAction::make('marketDetailVisible', true),
            ],
            'showMarketProjectDetail' => [
                SetAction::make('marketDetailKind', 'project'),
                SetAction::make('marketDetailItem', '{{ $event }}'),
                SetAction::make('marketDetailVisible', true),
            ],
            'handleEnable' => [
                FetchAction::make('/modules/{{ $event }}/enable')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.enabled')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.enable_failed') . '" }}'])]),
            ],
            'handleDisable' => [
                FetchAction::make('/modules/{{ $event }}/disable')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.disabled')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.disable_failed') . '" }}'])]),
            ],
            'handleInstall' => [
                FetchAction::make('/modules/{{ $event }}/install')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.installed')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.install_failed') . '" }}'])]),
            ],
            'handleUninstall' => [
                FetchAction::make('/modules/{{ $event }}/uninstall')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.uninstalled')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.uninstall_failed') . '" }}'])]),
            ],
            'handlePublishModule' => [
                FetchAction::make('/modules/{{ $event }}/publish')
                    ->post()
                    ->then([CallAction::make('$message.success', ['{{ $response.msg || "已上传到模块市场，等待审核" }}'])])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "上传失败，请检查 TRIX_AUTH_KEY 和模块市场配置" }}'])]),
            ],
            'handlePublishProject' => [
                FetchAction::make('/modules/projects/publish')
                    ->post()
                    ->then([CallAction::make('$message.success', ['{{ $response.msg || "当前项目已上传到模块市场，等待审核" }}'])])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目上传失败，请检查 trix-project.json、TRIX_AUTH_KEY 和模块市场配置" }}'])]),
            ],
            'handleInstallMarketModule' => [
                FetchAction::make('/modules/market/modules/{{ marketDetailItem.id }}/install')
                    ->post()
                    ->then([
                        CallAction::make('$message.success', ['{{ $response.msg || "模块包已下载并暂存" }}']),
                        SetAction::make('marketDetailVisible', false),
                        CallAction::make('loadMarketModules'),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "市场模块安装准备失败" }}'])]),
            ],
            'handleInstallMarketProject' => [
                FetchAction::make('/modules/market/projects/{{ marketDetailItem.id }}/install')
                    ->post()
                    ->then([
                        CallAction::make('$message.success', ['{{ $response.msg || "已获取项目安装计划" }}']),
                        SetAction::make('marketDetailVisible', false),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目安装计划获取失败" }}'])]),
            ],
        ];
    }

        /** 执行模块或项目安装流程。 */
    protected function installedModulesTable(): DataTable
    {
        $enabledLabel = json_encode('已启用', JSON_UNESCAPED_UNICODE);
        $disabledLabel = json_encode('已禁用', JSON_UNESCAPED_UNICODE);

        return DataTable::make()
            ->dataSource('modules')
            ->loading('loading')
            ->rowKey('name')
            ->columns([
                ['key' => 'logo', 'title' => __t('column.logo'), 'width' => 60, 'slot' => [
                    Avatar::make()
                        ->if('slotData.row.logo')
                        ->props(['src' => '{{ slotData.row.logo }}', 'size' => 32, 'objectFit' => 'contain']),
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
                        ->props(['type' => "{{ slotData.row.enabled ? 'success' : 'default' }}", 'size' => 'small'])
                        ->children(["{{ slotData.row.enabled ? {$enabledLabel} : {$disabledLabel} }}"]),
                ]],
                ['key' => 'actions', 'title' => __t('column.actions'), 'width' => 220, 'slot' => [
                    Space::make()->children([
                        Button::make()
                            ->if('slotData.row.can_publish')
                            ->size('small')
                            ->type('primary')
                            ->props(['text' => true])
                            ->on('click', ['call' => 'handlePublishModule', 'args' => ['{{ slotData.row.name }}']])
                            ->text('上传'),
                        Button::make()
                            ->if('!slotData.row.enabled')
                            ->size('small')
                            ->type('primary')
                            ->props(['text' => true])
                            ->on('click', ['call' => 'handleEnable', 'args' => ['{{ slotData.row.name }}']])
                            ->text(__t('tag.enabled')),
                        Button::make()
                            ->if('slotData.row.enabled')
                            ->size('small')
                            ->type('warning')
                            ->props(['text' => true])
                            ->on('click', ['call' => 'handleDisable', 'args' => ['{{ slotData.row.name }}']])
                            ->text(__t('tag.disabled')),
                        Popconfirm::make()
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
            ]);
    }

        /** 执行 moduleMarketModal 方法对应的具体职责。 */
        /** 执行 marketModulesPane 方法对应的具体职责。 */
        /** 执行 marketProjectsPane 方法对应的具体职责。 */
        /** 执行 marketPane 方法对应的具体职责。 */
        /** 执行 marketCardGrid 方法对应的具体职责。 */
        /** 执行 marketPagination 方法对应的具体职责。 */
        /** 执行 marketDetailModal 方法对应的具体职责。 */
}
