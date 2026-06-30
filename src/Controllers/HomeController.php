<?php

namespace Lartrix\Controllers;

use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Grid;
use Lartrix\Schema\Components\NaiveUI\GridItem;
use Lartrix\Schema\Components\NaiveUI\Card;
use Lartrix\Schema\Components\NaiveUI\Statistic;
use Lartrix\Schema\Components\NaiveUI\Timeline;
use Lartrix\Schema\Components\NaiveUI\TimelineItem;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Descriptions;
use Lartrix\Schema\Components\NaiveUI\DescriptionsItem;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\Custom\VueECharts;
use Lartrix\Schema\Actions\CallAction;

class HomeController extends Controller
{
    /**
     * 首页仪表盘 UI Schema
     */
    public function dashboard(): array
    {
        $stats = $this->getStats();
        $activities = $this->getActivities();

        $schema = Space::make()
            ->props(['vertical' => true, 'size' => 'large'])
            ->data([
                'stats' => $stats,
                'recentActivities' => $activities,
            ])
            ->children([
                $this->buildStatsGrid(),
                $this->buildChartsGrid(),
                $this->buildRecentActivities(),
                $this->buildQuickActionsGrid(),
            ]);

        return success($schema->toArray());
    }

    /**
     * 获取统计数据
     */
    protected function getStats(): array
    {
        $userModel = config('lartrix.models.user', \Lartrix\Models\AdminUser::class);

        return [
            'totalUsers' => $userModel::count(),
            'activeUsers' => $userModel::where('status', true)->count(),
            'totalOrders' => 0,
            'revenue' => 0,
            'testApiKey' => 'sk_test_1234567890abcdefghijklmnopqrstuvwxyz',
        ];
    }

    /**
     * 获取最近活动
     */
    protected function getActivities(): array
    {
        return [
                ['type' => 'success', 'title' => __t('home.system_started'), 'time' => now()->subMinutes(5)->format('Y-m-d H:i'), 'content' => __t('home.system_started_content')],
                ['type' => 'info', 'title' => __t('home.user_login'), 'time' => now()->subMinutes(10)->format('Y-m-d H:i'), 'content' => __t('home.admin_logged_in')],
        ];
    }

    /**
     * 构建统计卡片网格
     */
    protected function buildStatsGrid()
    {
        return Grid::make()
            ->props([
                'cols' => '1 s:2 m:4',
                'xGap' => 16,
                'yGap' => 16,
                'responsive' => 'screen',
            ])
            ->children([
                $this->buildStatCard(__t('home.total_users'), '{{ stats.totalUsers }}', 'carbon:user-multiple', 'text-primary'),
                $this->buildStatCard(__t('home.active_users'), '{{ stats.activeUsers }}', 'carbon:activity', 'text-success'),
                $this->buildStatCard(__t('home.total_orders'), '{{ stats.totalOrders }}', 'carbon:shopping-cart', 'text-warning'),
                $this->buildStatCard(__t('home.revenue'), '{{ stats.revenue }}', 'carbon:currency-dollar', 'text-error'),
            ]);
    }

    /**
     * 构建单个统计卡片
     */
    protected function buildStatCard(string $label, string $value, string $icon, string $colorClass)
    {
        return GridItem::make()->children([
            Card::make()->props(['class' => 'h-full'])->children([
                Statistic::make()
                    ->props(['label' => $label, 'value' => $value])
                    ->slot('prefix', [
                        SvgIcon::make($icon)->props(['class' => "{$colorClass} text-2xl mr-2"]),
                    ]),
            ]),
        ]);
    }

    /**
     * 构建图表网格
     */
    protected function buildChartsGrid()
    {
        return Grid::make()
            ->props([
                'cols' => '1 m:2',
                'xGap' => 16,
                'yGap' => 16,
                'responsive' => 'screen',
            ])
            ->children([
                GridItem::make()->children([
                    Card::make()
            ->props(['title' => __t('home.visit_trend'), 'class' => 'h-400px'])
                        ->children([
                            VueECharts::make()
                                ->props(['option' => $this->getVisitTrendChartOption(), 'style' => ['height' => '100%']]),
                        ]),
                ]),
                GridItem::make()->children([
                    Card::make()
            ->props(['title' => __t('home.sales_stats'), 'class' => 'h-400px'])
                        ->children([
                            VueECharts::make()
                                ->props(['option' => $this->getSalesChartOption(), 'style' => ['height' => '100%']]),
                        ]),
                ]),
            ]);
    }

    /**
     * 获取访问趋势图表配置
     */
    protected function getVisitTrendChartOption(): array
    {
        return [
            'tooltip' => ['trigger' => 'axis'],
            'legend' => ['data' => [__t('home.visit_count'), __t('home.unique_users')], 'top' => 0],
            'grid' => ['left' => '3%', 'right' => '4%', 'top' => '15%', 'bottom' => '3%', 'containLabel' => true],
            'xAxis' => ['type' => 'category', 'boundaryGap' => false, 'data' => [__t('home.mon'), __t('home.tue'), __t('home.wed'), __t('home.thu'), __t('home.fri'), __t('home.sat'), __t('home.sun')]],
            'yAxis' => ['type' => 'value'],
            'series' => [
                ['name' => __t('home.visit_count'), 'type' => 'line', 'smooth' => true, 'areaStyle' => ['opacity' => 0.3], 'data' => [820, 932, 901, 1234, 1290, 1330, 1520]],
                ['name' => __t('home.unique_users'), 'type' => 'line', 'smooth' => true, 'areaStyle' => ['opacity' => 0.3], 'data' => [320, 432, 401, 634, 690, 730, 820]],
            ],
        ];
    }

    /**
     * 获取销售统计图表配置
     */
    protected function getSalesChartOption(): array
    {
        return [
            'tooltip' => ['trigger' => 'axis', 'axisPointer' => ['type' => 'shadow']],
            'legend' => ['data' => [__t('home.sales_amount'), __t('home.order_count')], 'top' => 0],
            'grid' => ['left' => '3%', 'right' => '4%', 'top' => '15%', 'bottom' => '3%', 'containLabel' => true],
            'xAxis' => ['type' => 'category', 'data' => data_get(app(\Lartrix\Services\TranslationService::class)->getTranslations(app()->getLocale()), 'home.months', [])],
            'yAxis' => [
                ['type' => 'value', 'name' => __t('home.sales_amount'), 'axisLabel' => ['formatter' => '¥{value}']],
                ['type' => 'value', 'name' => __t('home.order_count'), 'position' => 'right'],
            ],
            'series' => [
                ['name' => __t('home.sales_amount'), 'type' => 'bar', 'data' => [12000, 15000, 18000, 22000, 28000, 35000], 'itemStyle' => ['borderRadius' => [4, 4, 0, 0]]],
                ['name' => __t('home.order_count'), 'type' => 'line', 'yAxisIndex' => 1, 'smooth' => true, 'data' => [120, 150, 180, 220, 280, 350]],
            ],
        ];
    }

    /**
     * 构建最近活动
     */
    protected function buildRecentActivities()
    {
        return Card::make()
            ->props(['title' => __t('home.recent_activities')])
            ->children([
                Timeline::make()->children([
                    TimelineItem::make()
                        ->for('item in recentActivities')
                        ->props([
                            'type' => "{{ item.type || 'default' }}",
                            'title' => '{{ item.title }}',
                            'time' => '{{ item.time }}',
                        ])
                        ->children(['{{ item.content }}']),
                ]),
            ]);
    }

    /**
     * 构建快捷操作网格
     */
    protected function buildQuickActionsGrid()
    {
        return Grid::make()
            ->props([
                'cols' => '1 m:3',
                'xGap' => 16,
                'yGap' => 16,
                'responsive' => 'screen',
            ])
            ->children([
                GridItem::make()->children([
                    Card::make()->props(['title' => __t('home.quick_actions')])->children([
                        Space::make()->props(['wrap' => true])->children([
                            $this->buildQuickButton(__t('title.user_management'), 'primary', '/system/user'),
                            $this->buildQuickButton(__t('title.role_manage'), 'info', '/system/role'),
                            $this->buildQuickButton(__t('title.menu_manage'), 'success', '/system/menu'),
                            $this->buildQuickButton(__t('title.system_settings'), 'warning', '/system/setting'),
                        ]),
                    ]),
                ]),
                GridItem::make()->children([
        Card::make()->props(['title' => __t('home.copy_test')])->children([
                        $this->buildCopyActionTest(),
                    ]),
                ]),
                GridItem::make()->children([
                    Card::make()->props(['title' => __t('home.system_info')])->children([
                        Descriptions::make()->props(['column' => 1, 'labelPlacement' => 'left'])->children([
                            DescriptionsItem::make()->props(['label' => __t('home.system_version')])->children(['1.0.0']),
                            DescriptionsItem::make()->props(['label' => 'Laravel'])->children([app()->version()]),
                            DescriptionsItem::make()->props(['label' => 'PHP'])->children([PHP_VERSION]),
                        ]),
                    ]),
                ]),
                GridItem::make()->children([
                    Card::make()->props(['title' => __t('home.project_info')])->children([
                        Descriptions::make()->props(['column' => 1, 'labelPlacement' => 'left'])->children([
                            DescriptionsItem::make()->props(['label' => __t('home.project_name')])->children([config('lartrix.theme.appTitle', 'Lartrix Admin')]),
                            DescriptionsItem::make()->props(['label' => __t('home.tech_stack')])->children(['Laravel + Vue 3']),
                            DescriptionsItem::make()->props(['label' => __t('home.render_engine')])->children(['vschema-ui']),
                        ]),
                    ]),
                ]),
            ]);
    }

    /**
     * 构建快捷按钮
     */
    protected function buildQuickButton(string $label, string $type, string $route)
    {
        return Button::make()
            ->props(['type' => $type, 'secondary' => true])
            ->on('click', CallAction::make('$methods.$nav.push', [$route]))
            ->text($label);
    }

    /**
     * 构建 CopyAction 测试
     */
    protected function buildCopyActionTest()
    {
        return Space::make()
            ->props(['vertical' => true, 'size' => 'small'])
            ->children([
                Input::make()->props([
                    'value' => '{{ stats.testApiKey }}',
                    'readonly' => true,
                    'size' => 'small',
                ]),
                Space::make()->props(['size' => 'small'])->children([
                    Button::make()
                        ->props(['type' => 'primary', 'size' => 'small'])
                ->text(__t('home.copy_api_key'))
                        ->on('click', [
                            [
                                'script' => 'console.log("API Key:", state.stats.testApiKey);',
                            ],
                            [
                                'copy' => '{{ stats.testApiKey }}',
                                'then' => [
                        ['call' => '$methods.$message.success', 'args' => [__t('home.api_key_copied')]],
                                ],
                                'catch' => [
                        ['call' => '$methods.$message.error', 'args' => [__t('home.copy_failed')]],
                                ],
                            ],
                        ]),
                    Button::make()
                        ->props(['size' => 'small'])
                ->text(__t('home.verified_copy'))
                        ->on('click', [
                            [
                                'if' => 'stats && stats.testApiKey',
                                'then' => [
                                    [
                                        'copy' => '{{ stats.testApiKey }}',
                                        'then' => [
                        ['call' => '$methods.$message.success', 'args' => [__t('home.copy_success')]],
                                        ],
                                    ],
                                ],
                                'else' => [
                        ['call' => '$methods.$message.error', 'args' => [__t('home.nothing_to_copy')]],
                                ],
                            ],
                        ]),
                ]),
            ]);
    }
}
