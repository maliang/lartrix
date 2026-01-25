<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCol - Naive UI 列组件
 */
class Col extends Component
{
    public function __construct()
    {
        parent::__construct('NCol');
    }

    public static function make(): static
    {
        return new static();
    }

    public function span(int $span): static
    {
        return $this->props(['span' => $span]);
    }

    public function offset(int $offset): static
    {
        return $this->props(['offset' => $offset]);
    }

    public function push(int $push): static
    {
        return $this->props(['push' => $push]);
    }

    public function pull(int $pull): static
    {
        return $this->props(['pull' => $pull]);
    }
}
