<?php

namespace Lartrix\Controllers;

use function Lartrix\Support\success;

class HomeController extends Controller
{
    /**
     * 首页仪表盘 UI Schema
     */
    public function dashboard(): array
    {
        // 获取统计数据
        $stats = $this->getStats();
        $activities = $this->getActivities();

        $schema = [
            'data' => [
                'stats' => $stats,
                'recentActivities' => $activities,
            ],
            'com' => 'NSpace',
            'props' => [
                'vertical' => true,
                'size' => 'large',
            ],
            'children' => [
                // 统计卡片
                $this->buildStatsGrid(),
                // 图表区域
                $this->buildChartsGrid(),
                // 最近活动
                $this->buildRecentActivities(),
                // 快捷操作和系统信息
                $this->buildQuickActionsGrid(),
            ],
        ];

        return success($schema);
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
    protected function buildStatsGrid(): array
    {
        return [
            'com' => 'NGrid',
            'props' => [
                'cols' => '1 s:2 m:4',
                'xGap' => 16,
                'yGap' => 16,
                'responsive' => 'screen',
            ],
            'children' => [
                $this->buildStatCard('总用户数', '{{ stats.totalUsers }}', 'carbon:user-multiple', 'text-primary'),
                $this->buildStatCard('活跃用户', '{{ stats.activeUsers }}', 'carbon:activity', 'text-success'),
                $this->buildStatCard('总订单数', '{{ stats.totalOrders }}', 'carbon:shopping-cart', 'text-warning'),
                $this->buildStatCard('总收入', '{{ stats.revenue }}', 'carbon:currency-dollar', 'text-error'),
            ],
        ];
    }

    /**
     * 构建单个统计卡片
     */
    protected function buildStatCard(string $label, string $value, string $icon, string $colorClass): array
    {
        return [
            'com' => 'NGridItem',
            'children' => [
                [
                    'com' => 'NCard',
                    'props' => ['class' => 'h-full'],
                    'children' => [
                        [
                            'com' => 'NStatistic',
                            'props' => [
                                'label' => $label,
                                'value' => $value,
                            ],
                            'slots' => [
                                'prefix' => [
                                    [
                                        'com' => 'SvgIcon',
                                        'props' => [
                                            'icon' => $icon,
                                            'class' => "{$colorClass} text-2xl mr-2",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 构建图表网格
     */
    protected function buildChartsGrid(): array
    {
        return [
            'com' => 'NGrid',
            'props' => [
                'cols' => '1 m:2',
                'xGap' => 16,
                'yGap' => 16,
                'responsive' => 'screen',
            ],
            'children' => [
                // 访问趋势图
                [
                    'com' => 'NGridItem',
                    'children' => [
                        [
                            'com' => 'NCard',
                            'props' => [
                                'title' => '访问趋势',
                                'class' => 'h-400px',
                            ],
                            'children' => [
                                [
                                    'com' => 'VueECharts',
                                    'props' => [
                                        'option' => $this->getVisitTrendChartOption(),
                                    ],
                                    'style' => ['height' => '100%'],
                                ],
                            ],
                        ],
                    ],
                ],
                // 销售统计图
                [
                    'com' => 'NGridItem',
                    'children' => [
                        [
                            'com' => 'NCard',
                            'props' => [
                                'title' => '销售统计',
                                'class' => 'h-400px',
                            ],
                            'children' => [
                                [
                                    'com' => 'VueECharts',
                                    'props' => [
                                        'option' => $this->getSalesChartOption(),
                                    ],
                                    'style' => ['height' => '100%'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 获取访问趋势图表配置
     */
    protected function getVisitTrendChartOption(): array
    {
        return [
            'tooltip' => ['trigger' => 'axis'],
            'legend' => [
                'data' => ['访问量', '独立用户'],
                'top' => 0,
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'top' => '15%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'data' => ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
            ],
            'yAxis' => ['type' => 'value'],
            'series' => [
                [
                    'name' => '访问量',
                    'type' => 'line',
                    'smooth' => true,
                    'areaStyle' => ['opacity' => 0.3],
                    'data' => [820, 932, 901, 1234, 1290, 1330, 1520],
                ],
                [
                    'name' => '独立用户',
                    'type' => 'line',
                    'smooth' => true,
                    'areaStyle' => ['opacity' => 0.3],
                    'data' => [320, 432, 401, 634, 690, 730, 820],
                ],
            ],
        ];
    }

    /**
     * 获取销售统计图表配置
     */
    protected function getSalesChartOption(): array
    {
        return [
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => ['type' => 'shadow'],
            ],
            'legend' => [
                'data' => ['销售额', '订单量'],
                'top' => 0,
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'top' => '15%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => ['1月', '2月', '3月', '4月', '5月', '6月'],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => '销售额',
                    'axisLabel' => ['formatter' => '¥{value}'],
                ],
                [
                    'type' => 'value',
                    'name' => '订单量',
                    'position' => 'right',
                ],
            ],
            'series' => [
                [
                    'name' => '销售额',
                    'type' => 'bar',
                    'data' => [12000, 15000, 18000, 22000, 28000, 35000],
                    'itemStyle' => ['borderRadius' => [4, 4, 0, 0]],
                ],
                [
                    'name' => '订单量',
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'smooth' => true,
                    'data' => [120, 150, 180, 220, 280, 350],
                ],
            ],
        ];
    }

    /**
     * 构建最近活动
     */
    protected function buildRecentActivities(): array
    {
        return [
            'com' => 'NCard',
            'props' => ['title' => '最近活动'],
            'children' => [
                [
                    'com' => 'NTimeline',
                    'children' => [
                        [
                            'com' => 'NTimelineItem',
                            'for' => 'item in recentActivities',
                            'props' => [
                                'type' => '{{ item.type || \'default\' }}',
                                'title' => '{{ item.title }}',
                                'time' => '{{ item.time }}',
                            ],
                            'children' => '{{ item.content }}',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 构建快捷操作网格
     */
    protected function buildQuickActionsGrid(): array
    {
        return [
            'com' => 'NGrid',
            'props' => [
                'cols' => '1 m:3',
                'xGap' => 16,
                'yGap' => 16,
                'responsive' => 'screen',
            ],
            'children' => [
                // 快捷操作
                [
                    'com' => 'NGridItem',
                    'children' => [
                        [
                            'com' => 'NCard',
                            'props' => ['title' => '快捷操作'],
                            'children' => [
                                [
                                    'com' => 'NSpace',
                                    'props' => ['wrap' => true],
                                    'children' => [
                                        $this->buildQuickButton('用户管理', 'primary', '/system/user'),
                                        $this->buildQuickButton('角色管理', 'info', '/system/role'),
                                        $this->buildQuickButton('菜单管理', 'success', '/system/menu'),
                                        $this->buildQuickButton('系统设置', 'warning', '/system/setting'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // 系统信息
                [
                    'com' => 'NGridItem',
                    'children' => [
                        [
                            'com' => 'NCard',
                            'props' => ['title' => '系统信息'],
                            'children' => [
                                [
                                    'com' => 'NDescriptions',
                                    'props' => [
                                        'column' => 1,
                                        'labelPlacement' => 'left',
                                    ],
                                    'children' => [
                                        ['com' => 'NDescriptionsItem', 'props' => ['label' => '系统版本'], 'children' => '1.0.0'],
                                        ['com' => 'NDescriptionsItem', 'props' => ['label' => 'Laravel'], 'children' => app()->version()],
                                        ['com' => 'NDescriptionsItem', 'props' => ['label' => 'PHP'], 'children' => PHP_VERSION],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // 项目信息
                [
                    'com' => 'NGridItem',
                    'children' => [
                        [
                            'com' => 'NCard',
                            'props' => ['title' => '项目信息'],
                            'children' => [
                                [
                                    'com' => 'NDescriptions',
                                    'props' => [
                                        'column' => 1,
                                        'labelPlacement' => 'left',
                                    ],
                                    'children' => [
                                        ['com' => 'NDescriptionsItem', 'props' => ['label' => '项目名称'], 'children' => config('lartrix.app_title', 'Lartrix Admin')],
                                        ['com' => 'NDescriptionsItem', 'props' => ['label' => '技术栈'], 'children' => 'Laravel + Vue 3'],
                                        ['com' => 'NDescriptionsItem', 'props' => ['label' => '渲染引擎'], 'children' => 'vue-json-schema'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 构建快捷按钮
     */
    protected function buildQuickButton(string $label, string $type, string $route): array
    {
        return [
            'com' => 'NButton',
            'props' => [
                'type' => $type,
                'secondary' => true,
            ],
            'events' => [
                'click' => ['call' => '$methods.$nav.push', 'args' => [$route]],
            ],
            'children' => $label,
        ];
    }
}
