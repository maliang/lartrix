<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * ThemeSchemaSwitch - 主题模式切换组件
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
}
