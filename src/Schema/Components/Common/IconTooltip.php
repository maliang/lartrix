<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * IconTooltip - trix 图标提示组件
 */
class IconTooltip extends Component
{
    public function __construct()
    {
        parent::__construct('IconTooltip');
    }

    public static function make(string $icon): static
    {
        return (new static())->props(['icon' => $icon]);
    }

    public function icon(string $icon): static
    {
        return $this->props(['icon' => $icon]);
    }

    public function content(string $content): static
    {
        return $this->props(['content' => $content]);
    }

    public function placement(string $placement): static
    {
        return $this->props(['placement' => $placement]);
    }
}
