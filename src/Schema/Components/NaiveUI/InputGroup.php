<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NInputGroup - Naive UI 输入框组组件
 */
class InputGroup extends Component
{
    public function __construct()
    {
        parent::__construct('NInputGroup');
    }

    public static function make(): static
    {
        return new static();
    }
}
