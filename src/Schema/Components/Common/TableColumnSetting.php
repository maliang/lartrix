<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * TableColumnSetting - trix 表格列设置组件
 * 
 * 支持的 Props：
 * - size: 按钮大小 (small/medium/large)
 * - type: 按钮类型 (default/primary/info/success/warning/error)
 * - showIcon: 是否显示图标
 * - icon: 自定义图标
 * - text: 按钮文字
 * 
 * 支持的插槽：
 * - default: 自定义按钮内容
 * - itemPrefix: 列表项前缀
 * - itemSuffix: 列表项后缀
 */
class TableColumnSetting extends Component
{
    public function __construct()
    {
        parent::__construct('TableColumnSetting');
    }

    public static function make(): static
    {
        return new static();
    }

    /**
     * 绑定列数据（v-model:columns）
     */
    public function columns(string $path): static
    {
        return $this->model(['columns' => $path]);
    }

    /**
     * 设置按钮大小
     */
    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    /**
     * 设置按钮类型
     */
    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    /**
     * 是否显示图标
     */
    public function showIcon(bool $show = true): static
    {
        return $this->props(['showIcon' => $show]);
    }

    /**
     * 设置自定义图标
     */
    public function icon(string $icon): static
    {
        return $this->props(['icon' => $icon]);
    }

    /**
     * 设置按钮文字
     */
    public function text(string $text): static
    {
        return $this->props(['text' => $text]);
    }
}
