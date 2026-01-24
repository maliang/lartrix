<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * GlobalSearch - 全局搜索组件
 */
class GlobalSearch extends Component
{
    public function __construct()
    {
        parent::__construct('GlobalSearch');
    }

    public static function make(): static
    {
        return new static();
    }
}
