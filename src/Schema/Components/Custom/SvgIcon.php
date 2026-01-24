<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * SvgIcon - trix SVG 图标组件
 */
class SvgIcon extends Component
{
    public function __construct()
    {
        parent::__construct('SvgIcon');
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

    public function localIcon(string $name): static
    {
        return $this->props(['local-icon' => $name]);
    }
}
