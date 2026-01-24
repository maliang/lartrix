<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NElement - Naive UI 元素组件
 */
class NElement extends Component
{
    public function __construct()
    {
        parent::__construct('NElement');
    }

    public static function make(): static
    {
        return new static();
    }

    public function tag(string $tag): static
    {
        return $this->props(['tag' => $tag]);
    }
}
