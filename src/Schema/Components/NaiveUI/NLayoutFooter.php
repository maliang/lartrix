<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NLayoutFooter - Naive UI 布局底部组件
 */
class NLayoutFooter extends Component
{
    public function __construct()
    {
        parent::__construct('NLayoutFooter');
    }

    public static function make(): static
    {
        return new static();
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function inverted(bool $inverted = true): static
    {
        return $this->props(['inverted' => $inverted]);
    }

    public function position(string $position): static
    {
        return $this->props(['position' => $position]);
    }
}
