<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NLi - Naive UI 列表项组件
 */
class NLi extends Component
{
    public function __construct()
    {
        parent::__construct('NLi');
    }

    public static function make(): static
    {
        return new static();
    }
}
