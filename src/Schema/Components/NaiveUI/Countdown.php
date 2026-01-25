<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCountdown - Naive UI 倒计时组件
 */
class Countdown extends Component
{
    public function __construct()
    {
        parent::__construct('NCountdown');
    }

    public static function make(): static
    {
        return new static();
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
}
