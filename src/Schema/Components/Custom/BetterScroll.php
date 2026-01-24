<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * BetterScroll - trix 滚动容器组件
 */
class BetterScroll extends Component
{
    public function __construct()
    {
        parent::__construct('BetterScroll');
    }

    public static function make(): static
    {
        return new static();
    }

    public function options(array $options): static
    {
        return $this->props(['options' => $options]);
    }

    public function click(bool $click = true): static
    {
        return $this->props(['click' => $click]);
    }

    public function probeType(int $type): static
    {
        return $this->props(['probe-type' => $type]);
    }
}
