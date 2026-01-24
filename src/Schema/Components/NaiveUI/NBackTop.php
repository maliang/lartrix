<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NBackTop - Naive UI 回到顶部组件
 */
class NBackTop extends Component
{
    public function __construct()
    {
        parent::__construct('NBackTop');
    }

    public static function make(): static
    {
        return new static();
    }

    public function right(int|string $right): static
    {
        return $this->props(['right' => $right]);
    }

    public function bottom(int|string $bottom): static
    {
        return $this->props(['bottom' => $bottom]);
    }

    public function visibilityHeight(int $height): static
    {
        return $this->props(['visibilityHeight' => $height]);
    }

    public function listenTo(string $target): static
    {
        return $this->props(['listenTo' => $target]);
    }

    public function show(bool $show = true): static
    {
        return $this->props(['show' => $show]);
    }
}
