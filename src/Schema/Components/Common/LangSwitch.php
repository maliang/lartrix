<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * LangSwitch - trix 语言切换组件
 */
class LangSwitch extends Component
{
    public function __construct()
    {
        parent::__construct('LangSwitch');
    }

    public static function make(): static
    {
        return new static();
    }
}
