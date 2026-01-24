<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTimeline - Naive UI 时间线组件
 */
class NTimeline extends Component
{
    public function __construct()
    {
        parent::__construct('NTimeline');
    }

    public static function make(): static
    {
        return new static();
    }

    public function horizontal(bool $horizontal = true): static
    {
        return $this->props(['horizontal' => $horizontal]);
    }

    public function itemPlacement(string $placement): static
    {
        return $this->props(['itemPlacement' => $placement]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }
}
