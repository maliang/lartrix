<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * CountTo - trix 数字动画组件
 */
class CountTo extends Component
{
    public function __construct()
    {
        parent::__construct('CountTo');
    }

    public static function make(int|float $endVal): static
    {
        return (new static())->props(['end-val' => $endVal]);
    }

    public function startVal(int|float $val): static
    {
        return $this->props(['start-val' => $val]);
    }

    public function endVal(int|float $val): static
    {
        return $this->props(['end-val' => $val]);
    }

    public function duration(int $duration): static
    {
        return $this->props(['duration' => $duration]);
    }

    public function autoplay(bool $autoplay = true): static
    {
        return $this->props(['autoplay' => $autoplay]);
    }

    public function decimals(int $decimals): static
    {
        return $this->props(['decimals' => $decimals]);
    }

    public function decimal(string $decimal): static
    {
        return $this->props(['decimal' => $decimal]);
    }

    public function separator(string $separator): static
    {
        return $this->props(['separator' => $separator]);
    }

    public function prefix(string $prefix): static
    {
        return $this->props(['prefix' => $prefix]);
    }

    public function suffix(string $suffix): static
    {
        return $this->props(['suffix' => $suffix]);
    }
}
