<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * ThemeButton - 主题设置按钮组件
 */
class ThemeButton extends Component
{
    public function __construct()
    {
        parent::__construct('ThemeButton');
    }

    public static function make(): static
    {
        return new static();
    }
}
