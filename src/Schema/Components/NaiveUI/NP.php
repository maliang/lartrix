<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NP - Naive UI 段落组件
 */
class NP extends Component
{
    public function __construct()
    {
        parent::__construct('NP');
    }

    public static function make(): static
    {
        return new static();
    }

    public function depth(int|string $depth): static
    {
        return $this->props(['depth' => $depth]);
    }
}
