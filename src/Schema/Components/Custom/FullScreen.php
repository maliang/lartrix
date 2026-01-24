<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * FullScreen - 全屏切换组件
 */
class FullScreen extends Component
{
    public function __construct()
    {
        parent::__construct('FullScreen');
    }

    public static function make(): static
    {
        return new static();
    }
}
