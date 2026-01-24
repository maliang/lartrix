<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NIcon - Naive UI 图标组件
 */
class NIcon extends Component
{
    public function __construct()
    {
        parent::__construct('NIcon');
    }

    public static function make(): static
    {
        return new static();
    }

    public function size(int|string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }

    public function depth(int|string $depth): static
    {
        return $this->props(['depth' => $depth]);
    }

    public function component(mixed $component): static
    {
        return $this->props(['component' => $component]);
    }
}
