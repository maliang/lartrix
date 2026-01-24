<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NAnchor - Naive UI 锚点组件
 */
class NAnchor extends Component
{
    public function __construct()
    {
        parent::__construct('NAnchor');
    }

    public static function make(): static
    {
        return new static();
    }

    public function affix(bool $affix = true): static
    {
        return $this->props(['affix' => $affix]);
    }

    public function bound(int $bound): static
    {
        return $this->props(['bound' => $bound]);
    }

    public function ignoreGap(bool $ignore = true): static
    {
        return $this->props(['ignoreGap' => $ignore]);
    }

    public function offsetTarget(string $target): static
    {
        return $this->props(['offsetTarget' => $target]);
    }

    public function showBackground(bool $show = true): static
    {
        return $this->props(['showBackground' => $show]);
    }

    public function showRail(bool $show = true): static
    {
        return $this->props(['showRail' => $show]);
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }
}
