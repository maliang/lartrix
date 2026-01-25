<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NLayoutSider - Naive UI 布局侧边栏组件
 */
class LayoutSider extends Component
{
    public function __construct()
    {
        parent::__construct('NLayoutSider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function collapsed(bool $collapsed = true): static
    {
        return $this->props(['collapsed' => $collapsed]);
    }

    public function collapsedWidth(int $width): static
    {
        return $this->props(['collapsedWidth' => $width]);
    }

    public function collapseMode(string $mode): static
    {
        return $this->props(['collapseMode' => $mode]);
    }

    public function width(int|string $width): static
    {
        return $this->props(['width' => $width]);
    }

    public function inverted(bool $inverted = true): static
    {
        return $this->props(['inverted' => $inverted]);
    }

    public function nativeScrollbar(bool $native = true): static
    {
        return $this->props(['nativeScrollbar' => $native]);
    }

    public function position(string $position): static
    {
        return $this->props(['position' => $position]);
    }

    public function showCollapsedContent(bool $show = true): static
    {
        return $this->props(['showCollapsedContent' => $show]);
    }

    public function showTrigger(bool|string $show = true): static
    {
        return $this->props(['showTrigger' => $show]);
    }

    public function triggerStyle(array|string $style): static
    {
        return $this->props(['triggerStyle' => $style]);
    }
}
