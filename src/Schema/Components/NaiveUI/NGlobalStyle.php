<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NGlobalStyle - Naive UI 全局样式组件
 */
class NGlobalStyle extends Component
{
    public function __construct()
    {
        parent::__construct('NGlobalStyle');
    }

    public static function make(): static
    {
        return new static();
    }
}
