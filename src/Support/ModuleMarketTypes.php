<?php

namespace Lartrix\Support;

/** 统一维护模块市场分类选项、标签和查询归一化规则。 */
class ModuleMarketTypes
{
    /** 返回模块分类选项。 */
    public function moduleOptions(): array
    {
        return [
            ['label' => '全部', 'value' => 'all'],
            ['label' => '基础能力', 'value' => 'core'],
            ['label' => '业务模块', 'value' => 'business'],
            ['label' => '外部集成', 'value' => 'integration'],
            ['label' => '界面组件', 'value' => 'ui'],
            ['label' => '开发工具', 'value' => 'tooling'],
        ];
    }

    /** 返回项目分类选项。 */
    public function projectOptions(): array
    {
        return [
            ['label' => '全部', 'value' => 'all'],
            ['label' => '起步模板', 'value' => 'starter'],
            ['label' => '行业方案', 'value' => 'solution'],
            ['label' => '演示项目', 'value' => 'demo'],
            ['label' => '结构模板', 'value' => 'template'],
            ['label' => '企业工程', 'value' => 'enterprise'],
        ];
    }

    /** 返回分类值对应的中文标签。 */
    public function label(string $type, string $kind): string
    {
        if ($type === '' || $type === '-') {
            return '-';
        }
        $options = $kind === 'project' ? $this->projectOptions() : $this->moduleOptions();
        foreach ($options as $option) {
            if ($option['value'] === $type) {
                return $option['label'];
            }
        }

        return $type;
    }

    /** 将“全部”转换为空筛选条件。 */
    public function normalize(mixed $type): string
    {
        $type = is_string($type) ? trim($type) : '';

        return $type === 'all' ? '' : $type;
    }
}
