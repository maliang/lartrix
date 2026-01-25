<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NPopover - Naive UI 弹出信息组件
 */
class Popover extends Component
{
    public function __construct()
    {
        parent::__construct('NPopover');
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

    public function raw(bool $raw = true): static
    {
        return $this->props(['raw' => $raw]);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }
}
