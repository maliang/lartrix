<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * Icon - trix 图标组件（@iconify/vue）
 */
class Icon extends Component
{
    public function __construct()
    {
        parent::__construct('Icon');
    }

    public static function make(string $icon): static
    {
        return (new static())->props(['icon' => $icon]);
    }

    public function icon(string $icon): static
    {
        return $this->props(['icon' => $icon]);
    }

    public function size(int|string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }
}
