<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NIconWrapper - Naive UI 图标包装组件
 */
class NIconWrapper extends Component
{
    public function __construct()
    {
        parent::__construct('NIconWrapper');
    }

    public static function make(): static
    {
        return new static();
    }

    public function size(int $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function borderRadius(int $radius): static
    {
        return $this->props(['borderRadius' => $radius]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }

    public function iconColor(string $color): static
    {
        return $this->props(['iconColor' => $color]);
    }
}
