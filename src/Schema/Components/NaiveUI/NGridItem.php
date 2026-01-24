<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NGridItem - Naive UI 栅格项组件
 */
class NGridItem extends Component
{
    public function __construct()
    {
        parent::__construct('NGridItem');
    }

    public static function make(): static
    {
        return new static();
    }

    public function span(int|string $span): static
    {
        return $this->props(['span' => $span]);
    }

    public function offset(int $offset): static
    {
        return $this->props(['offset' => $offset]);
    }

    public function suffix(bool $suffix = true): static
    {
        return $this->props(['suffix' => $suffix]);
    }
}
