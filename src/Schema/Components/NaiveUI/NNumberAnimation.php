<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NNumberAnimation - Naive UI 数字动画组件
 */
class NNumberAnimation extends Component
{
    public function __construct()
    {
        parent::__construct('NNumberAnimation');
    }

    public static function make(): static
    {
        return new static();
    }

    public function from(int|float $from): static
    {
        return $this->props(['from' => $from]);
    }

    public function to(int|float $to): static
    {
        return $this->props(['to' => $to]);
    }

    public function duration(int $duration): static
    {
        return $this->props(['duration' => $duration]);
    }

    public function active(bool $active = true): static
    {
        return $this->props(['active' => $active]);
    }

    public function precision(int $precision): static
    {
        return $this->props(['precision' => $precision]);
    }

    public function showSeparator(bool $show = true): static
    {
        return $this->props(['showSeparator' => $show]);
    }

    public function locale(string $locale): static
    {
        return $this->props(['locale' => $locale]);
    }
}
