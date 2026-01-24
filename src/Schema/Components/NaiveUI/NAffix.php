<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NAffix - Naive UI 固钉组件
 */
class NAffix extends Component
{
    public function __construct()
    {
        parent::__construct('NAffix');
    }

    public static function make(): static
    {
        return new static();
    }

    public function top(int $top): static
    {
        return $this->props(['top' => $top]);
    }

    public function bottom(int $bottom): static
    {
        return $this->props(['bottom' => $bottom]);
    }

    public function triggerTop(int $top): static
    {
        return $this->props(['triggerTop' => $top]);
    }

    public function triggerBottom(int $bottom): static
    {
        return $this->props(['triggerBottom' => $bottom]);
    }

    public function position(string $position): static
    {
        return $this->props(['position' => $position]);
    }

    public function listenTo(string $target): static
    {
        return $this->props(['listenTo' => $target]);
    }
}
