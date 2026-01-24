<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NFloatButton - Naive UI 悬浮按钮组件
 */
class NFloatButton extends Component
{
    public function __construct()
    {
        parent::__construct('NFloatButton');
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

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function width(int|string $width): static
    {
        return $this->props(['width' => $width]);
    }

    public function height(int|string $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function menuTrigger(string $trigger): static
    {
        return $this->props(['menuTrigger' => $trigger]);
    }
}
