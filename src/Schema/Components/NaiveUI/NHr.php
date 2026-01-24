<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NHr - Naive UI 水平分割线组件
 */
class NHr extends Component
{
    public function __construct()
    {
        parent::__construct('NHr');
    }

    public static function make(): static
    {
        return new static();
    }
}
