<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDivider - Naive UI 分割线组件
 */
class NDivider extends Component
{
    public function __construct()
    {
        parent::__construct('NDivider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function vertical(bool $vertical = true): static
    {
        return $this->props(['vertical' => $vertical]);
    }

    public function dashed(bool $dashed = true): static
    {
        return $this->props(['dashed' => $dashed]);
    }

    public function titlePlacement(string $placement): static
    {
        return $this->props(['title-placement' => $placement]);
    }
}
