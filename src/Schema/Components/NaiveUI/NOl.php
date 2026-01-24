<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NOl - Naive UI 有序列表组件
 */
class NOl extends Component
{
    public function __construct()
    {
        parent::__construct('NOl');
    }

    public static function make(): static
    {
        return new static();
    }

    public function alignText(bool $align = true): static
    {
        return $this->props(['alignText' => $align]);
    }
}
