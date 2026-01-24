<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * ButtonIcon - trix 图标按钮组件
 */
class ButtonIcon extends Component
{
    public function __construct()
    {
        parent::__construct('ButtonIcon');
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

    public function tooltipContent(string $content): static
    {
        return $this->props(['tooltip-content' => $content]);
    }

    public function tooltipPlacement(string $placement): static
    {
        return $this->props(['tooltip-placement' => $placement]);
    }
}
