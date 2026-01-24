<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NLayoutContent - Naive UI 布局内容组件
 */
class NLayoutContent extends Component
{
    public function __construct()
    {
        parent::__construct('NLayoutContent');
    }

    public static function make(): static
    {
        return new static();
    }

    public function contentStyle(array|string $style): static
    {
        return $this->props(['contentStyle' => $style]);
    }

    public function nativeScrollbar(bool $native = true): static
    {
        return $this->props(['nativeScrollbar' => $native]);
    }

    public function position(string $position): static
    {
        return $this->props(['position' => $position]);
    }
}
