<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NListItem - Naive UI 列表项组件
 */
class NListItem extends Component
{
    public function __construct()
    {
        parent::__construct('NListItem');
    }

    public static function make(): static
    {
        return new static();
    }
}
