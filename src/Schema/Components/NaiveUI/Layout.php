<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NLayout - Naive UI 布局组件
 */
class Layout extends Component
{
    public function __construct()
    {
        parent::__construct('NLayout');
    }

    public static function make(): static
    {
        return new static();
    }

    public function hasSider(bool $hasSider = true): static
    {
        return $this->props(['hasSider' => $hasSider]);
    }

    public function position(string $position): static
    {
        return $this->props(['position' => $position]);
    }

    public function contentStyle(array|string $style): static
    {
        return $this->props(['contentStyle' => $style]);
    }

    public function nativeScrollbar(bool $native = true): static
    {
        return $this->props(['nativeScrollbar' => $native]);
    }

    public function siderPlacement(string $placement): static
    {
        return $this->props(['siderPlacement' => $placement]);
    }
}
