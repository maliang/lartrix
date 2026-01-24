<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * ThemeSchemaSwitch - trix 主题切换组件
 */
class ThemeSchemaSwitch extends Component
{
    public function __construct()
    {
        parent::__construct('ThemeSchemaSwitch');
    }

    public static function make(): static
    {
        return new static();
    }

    public function showTooltip(bool $show = true): static
    {
        return $this->props(['show-tooltip' => $show]);
    }
}
