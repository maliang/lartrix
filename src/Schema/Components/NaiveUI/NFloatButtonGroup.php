<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NFloatButtonGroup - Naive UI 悬浮按钮组组件
 */
class NFloatButtonGroup extends Component
{
    public function __construct()
    {
        parent::__construct('NFloatButtonGroup');
    }

    public static function make(): static
    {
        return new static();
    }

    public function right(int|string $right): static
    {
        return $this->props(['right' => $right]);
    }

    public function bottom(int|string $bottom): static
    {
        return $this->props(['bottom' => $bottom]);
    }

    public function left(int|string $left): static
    {
        return $this->props(['left' => $left]);
    }

    public function top(int|string $top): static
    {
        return $this->props(['top' => $top]);
    }

    public function shape(string $shape): static
    {
        return $this->props(['shape' => $shape]);
    }

    public function position(string $position): static
    {
        return $this->props(['position' => $position]);
    }
}
