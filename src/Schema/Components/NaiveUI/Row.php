<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NRow - Naive UI 行组件
 */
class Row extends Component
{
    public function __construct()
    {
        parent::__construct('NRow');
    }

    public static function make(): static
    {
        return new static();
    }

    public function gutter(int|array $gutter): static
    {
        return $this->props(['gutter' => $gutter]);
    }

    public function wrap(bool $wrap = true): static
    {
        return $this->props(['wrap' => $wrap]);
    }
}
