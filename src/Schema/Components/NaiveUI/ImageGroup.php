<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NImageGroup - Naive UI 图片组组件
 */
class ImageGroup extends Component
{
    public function __construct()
    {
        parent::__construct('NImageGroup');
    }

    public static function make(): static
    {
        return new static();
    }

    public function showToolbar(bool $show = true): static
    {
        return $this->props(['showToolbar' => $show]);
    }

    public function showToolbarTooltip(bool $show = true): static
    {
        return $this->props(['showToolbarTooltip' => $show]);
    }
}
