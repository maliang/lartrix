<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTooltip - Naive UI 文字提示组件
 */
class Tooltip extends Component
{
    public function __construct()
    {
        parent::__construct('NTooltip');
    }

    public static function make(): static
    {
        return new static();
    }

    public function trigger(string $trigger): static
    {
        return $this->props(['trigger' => $trigger]);
    }

    public function placement(string $placement): static
    {
        return $this->props(['placement' => $placement]);
    }

    public function showArrow(bool $show = true): static
    {
        return $this->props(['showArrow' => $show]);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }
}
