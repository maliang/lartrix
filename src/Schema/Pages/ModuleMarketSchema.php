<?php

namespace Lartrix\Schema\Pages;

use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Components\Custom\Html;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\NaiveUI\Avatar;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Card;
use Lartrix\Schema\Components\NaiveUI\Flex;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\Modal;
use Lartrix\Schema\Components\NaiveUI\Pagination;
use Lartrix\Schema\Components\NaiveUI\Result;
use Lartrix\Schema\Components\NaiveUI\Select;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\TabPane;
use Lartrix\Schema\Components\NaiveUI\Tabs;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Support\ModuleMarketTypes;

/** 构建独立模块市场页面、弹窗、卡片和分页 Schema。 */
class ModuleMarketSchema
{
    /** 注入统一市场分类规则。 */
    public function __construct(private readonly ModuleMarketTypes $types)
    {
    }

    public function moduleTypeOptions(): array
    {
        return $this->types->moduleOptions();
    }


    public function projectTypeOptions(): array
    {
        return $this->types->projectOptions();
    }


    public function typeLabel(string $type, string $kind): string
    {
        return $this->types->label($type, $kind);
    }


    public function normalizeType(mixed $type): string
    {
        return $this->types->normalize($type);
    }


    public function market(): array
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


    public function modal(): Modal
    {
        return Modal::make()
            ->show('marketVisible')
            ->title('模块市场')
            ->preset('card')
            ->style(['width' => '1080px'])
            ->props(['content-style' => ['height' => '682px', 'padding' => '16px 20px 12px', 'overflow' => 'hidden', 'boxSizing' => 'border-box']])
            ->on('update:show', SetAction::make('marketVisible', '{{ $event }}'))
            ->children([
                Tabs::make()
                    ->type('line')
                    ->model(['value' => 'marketActiveTab'])
                    ->props(['style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column']])
                    ->children([
                        TabPane::make()->name('modules')->tab('模块')->children($this->marketModulesPane()),
                        TabPane::make()->name('projects')->tab('项目')->children($this->marketProjectsPane()),
                    ]),
            ]);
    }


    protected function marketModulesPane(): array
    {
        return $this->marketPane('marketModules', 'marketModuleType', 'marketModuleKeyword', 'marketModuleTypeOptions', 'searchMarketModules', 'module', 'marketModulePage', 'marketModulePageSize', 'marketModuleTotal', 'handleMarketModulePageChange');
    }


    protected function marketProjectsPane(): array
    {
        return $this->marketPane('marketProjects', 'marketProjectType', 'marketProjectKeyword', 'marketProjectTypeOptions', 'searchMarketProjects', 'project', 'marketProjectPage', 'marketProjectPageSize', 'marketProjectTotal', 'handleMarketProjectPageChange');
    }


    protected function marketPane(string $itemsPath, string $typePath, string $keywordPath, string $optionsPath, string $searchMethod, string $kind, string $pagePath, string $pageSizePath, string $totalPath, string $pageMethod): array
    {
        return [
            Flex::make()
                ->vertical()
                ->props(['style' => ['height' => '700px', 'overflow' => 'hidden']])
                ->children([
                    Space::make()
                        ->props(['style' => 'margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #eef2f6;'])
                        ->children([
                            Select::make()
                                ->model(['value' => $typePath])
                                ->props([
                                    'placeholder' => $kind === 'project' ? '全部项目分类' : '全部模块分类',
                                    'clearable' => false,
                                    'options' => "{{ {$optionsPath} }}",
                                    'style' => ['width' => '160px'],
                                ]),
                            Input::make()
                                ->model(['value' => $keywordPath])
                                ->props([
                                    'placeholder' => $kind === 'project' ? '搜索项目名称、ID 或描述' : '搜索模块名称、ID 或描述',
                                    'clearable' => true,
                                    'style' => ['width' => '280px'],
                                ]),
                            Button::make()->type('primary')->on('click', ['call' => $searchMethod])->text('搜索'),
                        ]),
                    $this->marketCardGrid($itemsPath, $kind),
                    $this->marketPagination($pagePath, $pageSizePath, $totalPath, $pageMethod),
                ]),
        ];
    }


    protected function marketCardGrid(string $itemsPath, string $kind): Html
    {
        $detailMethod = $kind === 'project' ? 'showMarketProjectDetail' : 'showMarketModuleDetail';
        $emptyText = $kind === 'project' ? '暂无匹配项目' : '暂无匹配模块';
        $icon = $kind === 'project' ? 'carbon:template' : 'carbon:cube';

        return Html::div()
            ->props(['style' => [
                'flex' => '1 1 0%',
                'overflowY' => 'auto',
                'display' => 'grid',
                'gridTemplateColumns' => 'repeat(4, minmax(0, 1fr))',
                'gridAutoRows' => '136px',
                'gap' => '10px',
                'alignContent' => 'start',
                'padding' => '2px 4px 2px 0',
            ]])
            ->children([
                Card::make()
                    ->for("item in {$itemsPath}", 'item.id')
                    ->hoverable()
                    ->bordered(true)
                    ->size('small')
                    ->props([
                        'style' => ['height' => '136px', 'cursor' => 'pointer', 'borderColor' => '#e5e7eb', 'background' => '#ffffff'],
                        'content-style' => ['height' => '100%', 'padding' => '10px 12px', 'boxSizing' => 'border-box'],
                    ])
                    ->on('click', ['call' => $detailMethod, 'args' => ['{{ item }}']])
                    ->children([
                        Flex::make()->props(['align' => 'center', 'style' => ['gap' => '10px', 'marginBottom' => '7px']])->children([
                            Avatar::make()->if('item.logo')->props(['src' => '{{ item.logo }}', 'size' => 36, 'objectFit' => 'contain', 'style' => ['background' => '#f8fafc', 'border' => '1px solid #eef2f6']]),
                            SvgIcon::make($icon)->if('!item.logo')->props(['class' => 'text-2xl text-primary']),
                            Html::div()->props(['style' => ['minWidth' => 0, 'flex' => 1]])->children([
                                Html::div()->props(['style' => ['fontWeight' => 600, 'fontSize' => '14px', 'lineHeight' => '20px', 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.title }}']),
                                Html::div()->props(['style' => ['fontSize' => '12px', 'lineHeight' => '18px', 'color' => '#667085', 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.id }}']),
                            ]),
                        ]),
                        Html::div()->props(['style' => ['height' => '38px', 'fontSize' => '12px', 'lineHeight' => '19px', 'color' => '#475467', 'overflow' => 'hidden']])->children(['{{ item.summary || "暂无简介" }}']),
                        Flex::make()->props(['justify' => 'space-between', 'align' => 'center', 'style' => ['marginTop' => '8px']])->children([
                            Tag::make()->props(['size' => 'small', 'bordered' => false])->children(['{{ item.type_label || item.type || "-" }}']),
                            Tag::make()->props(['size' => 'small', 'type' => '{{ item.installed ? "success" : "default" }}'])->children(['{{ item.installed ? "已安装" : item.version }}']),
                        ]),
                    ]),
                Html::div()
                    ->if("!{$itemsPath} || {$itemsPath}.length === 0")
                    ->props(['style' => ['gridColumn' => '1 / -1', 'height' => '260px', 'display' => 'flex', 'alignItems' => 'center', 'justifyContent' => 'center', 'color' => '#667085']])
                    ->children([$emptyText]),
            ]);
    }


    protected function marketPagination(string $pagePath, string $pageSizePath, string $totalPath, string $handler): Flex
    {
        return Flex::make()
            ->props(['justify' => 'end', 'align' => 'center', 'style' => ['height' => '48px', 'flex' => '0 0 48px', 'paddingTop' => '10px', 'borderTop' => '1px solid #e5e7eb', 'boxSizing' => 'border-box', 'background' => '#fff']])
            ->children([
                Pagination::make()
                    ->props([
                        'page' => "{{ {$pagePath} }}",
                        'pageSize' => "{{ {$pageSizePath} }}",
                        'itemCount' => "{{ {$totalPath} }}",
                        'showSizePicker' => false,
                    ])
                    ->on('update:page', ['call' => $handler, 'args' => ['{{ $event }}']]),
            ]);
    }


    public function detailModal(): Modal
    {
        return Modal::make()
            ->show('marketDetailVisible')
            ->title('{{ marketDetailKind === "project" ? "项目详情" : "模块详情" }}')
            ->preset('card')
            ->style(['width' => '720px'])
            ->on('update:show', SetAction::make('marketDetailVisible', '{{ $event }}'))
            ->children([
                Flex::make()->vertical()->props(['style' => ['gap' => '14px']])->children([
                    Flex::make()->props(['align' => 'center', 'style' => ['gap' => '12px']])->children([
                        Avatar::make()->if('marketDetailItem?.logo')->props(['src' => '{{ marketDetailItem.logo }}', 'size' => 48, 'objectFit' => 'contain']),
                        SvgIcon::make('carbon:cube')->if('!marketDetailItem?.logo')->props(['class' => 'text-4xl text-primary']),
                        Html::div()->children([
                            Html::div()->props(['style' => ['fontSize' => '18px', 'fontWeight' => 700]])->children(['{{ marketDetailItem?.title || "-" }}']),
                            Html::div()->props(['style' => ['fontSize' => '12px', 'color' => '#667085']])->children(['{{ marketDetailItem?.id || "-" }}']),
                        ]),
                    ]),
                    Html::div()->props(['style' => ['lineHeight' => '22px', 'color' => '#344054']])->children(['{{ marketDetailItem?.summary || "暂无简介" }}']),
                    Space::make()->props(['wrap' => true])->children([
                        Tag::make()->children(['{{ marketDetailItem?.type_label || marketDetailItem?.type || "-" }}']),
                        Tag::make()->children(['版本 {{ marketDetailItem?.version || "-" }}']),
                        Tag::make()->children(['{{ marketDetailItem?.license || "-" }}']),
                        Tag::make()->if('marketDetailItem?.author')->children(['{{ marketDetailItem.author }}']),
                    ]),
                    Html::div()->props(['style' => ['fontSize' => '12px', 'color' => '#667085']])->children(['{{ marketDetailKind === "project" ? "项目可作为多个模块的组合安装入口。" : "模块安装请在本地命令行执行对应安装命令。" }}']),
                    Space::make()->props(['justify' => 'end'])->children([
                        Button::make()->if('marketDetailKind === "module" && !marketDetailItem?.installed')->type('primary')->on('click', ['call' => 'handleInstallMarketModule'])->text('安装'),
                        Button::make()->if('marketDetailKind === "project"')->type('primary')->on('click', ['call' => 'handleInstallMarketProject'])->text('安装项目'),
                        Button::make()->on('click', SetAction::make('marketDetailVisible', false))->text('关闭'),
                    ]),
                ]),
            ]);
    }
}
