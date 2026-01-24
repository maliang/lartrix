<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCarouselItem - Naive UI 轮播图项组件
 */
class NCarouselItem extends Component
{
    public function __construct()
    {
        parent::__construct('NCarouselItem');
    }

    public static function make(): static
    {
        return new static();
    }
}
