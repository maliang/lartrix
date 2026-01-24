<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NA - Naive UI 链接组件
 */
class NA extends Component
{
    public function __construct()
    {
        parent::__construct('NA');
    }

    public static function make(): static
    {
        return new static();
    }

    public function href(string $href): static
    {
        return $this->props(['href' => $href]);
    }
}
