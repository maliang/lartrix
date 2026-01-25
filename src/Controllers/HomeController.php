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
        ];
    }

    /**
     * 获取最近活动
     */
    protected function getActivities(): array
    {
        return [
            ['type' => 'success', 'title' => '系统启动', 'time' => now()->subMinutes(5)->format('Y-m-d H:i'), 'content' => '系统已成功启动'],
            ['type' => 'info', 'title' => '用户登录', 'time' => now()->subMinutes(10)->format('Y-m-d H:i'), 'content' => '管理员登录系统'],
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
                $this->buildStatCard('总用户数', '{{ stats.totalUsers }}', 'carbon:user-multiple', 'text-primary'),
                $this->buildStatCard('活跃用户', '{{ stats.activeUsers }}', 'carbon:activity', 'text-success'),
                $this->buildStatCard('总订单数', '{{ stats.totalOrders }}', 'carbon:shopping-cart', 'text-warning'),
                $this->buildStatCard('总收入', '{{ stats.revenue }}', 'carbon:currency-dollar', 'text-error'),
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
                        ->props(['title' => '访问趋势', 'class' => 'h-400px'])
                        ->children([
                            VueECharts::make()
                                ->props(['option' => $this->getVisitTrendChartOption(), 'style' => ['height' => '100%']]),
                        ]),
                ]),
                GridItem::make()->children([
                    Card::make()
                        ->props(['title' => '销售统计', 'class' => 'h-400px'])
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
            'legend' => ['data' => ['访问量', '独立用户'], 'top' => 0],
            'grid' => ['left' => '3%', 'right' => '4%', 'top' => '15%', 'bottom' => '3%', 'containLabel' => true],
            'xAxis' => ['type' => 'category', 'boundaryGap' => false, 'data' => ['周一', '周二', '周三', '周四', '周五', '周六', '周日']],
            'yAxis' => ['type' => 'value'],
            'series' => [
                ['name' => '访问量', 'type' => 'line', 'smooth' => true, 'areaStyle' => ['opacity' => 0.3], 'data' => [820, 932, 901, 1234, 1290, 1330, 1520]],
                ['name' => '独立用户', 'type' => 'line', 'smooth' => true, 'areaStyle' => ['opacity' => 0.3], 'data' => [320, 432, 401, 634, 690, 730, 820]],
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
            'legend' => ['data' => ['销售额', '订单量'], 'top' => 0],
            'grid' => ['left' => '3%', 'right' => '4%', 'top' => '15%', 'bottom' => '3%', 'containLabel' => true],
            'xAxis' => ['type' => 'category', 'data' => ['1月', '2月', '3月', '4月', '5月', '6月']],
            'yAxis' => [
                ['type' => 'value', 'name' => '销售额', 'axisLabel' => ['formatter' => '¥{value}']],
                ['type' => 'value', 'name' => '订单量', 'position' => 'right'],
            ],
            'series' => [
                ['name' => '销售额', 'type' => 'bar', 'data' => [12000, 15000, 18000, 22000, 28000, 35000], 'itemStyle' => ['borderRadius' => [4, 4, 0, 0]]],
                ['name' => '订单量', 'type' => 'line', 'yAxisIndex' => 1, 'smooth' => true, 'data' => [120, 150, 180, 220, 280, 350]],
            ],
        ];
    }

    /**
     * 构建最近活动
     */
    protected function buildRecentActivities()
    {
        return Card::make()
            ->props(['title' => '最近活动'])
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
                    Card::make()->props(['title' => '快捷操作'])->children([
                        Space::make()->props(['wrap' => true])->children([
                            $this->buildQuickButton('用户管理', 'primary', '/system/user'),
                            $this->buildQuickButton('角色管理', 'info', '/system/role'),
                            $this->buildQuickButton('菜单管理', 'success', '/system/menu'),
                            $this->buildQuickButton('系统设置', 'warning', '/system/setting'),
                        ]),
                    ]),
                ]),
                GridItem::make()->children([
                    Card::make()->props(['title' => '系统信息'])->children([
                        Descriptions::make()->props(['column' => 1, 'labelPlacement' => 'left'])->children([
                            DescriptionsItem::make()->props(['label' => '系统版本'])->children(['1.0.0']),
                            DescriptionsItem::make()->props(['label' => 'Laravel'])->children([app()->version()]),
                            DescriptionsItem::make()->props(['label' => 'PHP'])->children([PHP_VERSION]),
                        ]),
                    ]),
                ]),
                GridItem::make()->children([
                    Card::make()->props(['title' => '项目信息'])->children([
                        Descriptions::make()->props(['column' => 1, 'labelPlacement' => 'left'])->children([
                            DescriptionsItem::make()->props(['label' => '项目名称'])->children([config('lartrix.app_title', 'Lartrix Admin')]),
                            DescriptionsItem::make()->props(['label' => '技术栈'])->children(['Laravel + Vue 3']),
                            DescriptionsItem::make()->props(['label' => '渲染引擎'])->children(['vschema-ui']),
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
}
