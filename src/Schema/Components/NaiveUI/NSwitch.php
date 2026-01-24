<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NSwitch - Naive UI 开关组件
 */
class NSwitch extends Component
{
    public function __construct()
    {
        parent::__construct('NSwitch');
    }

    public static function make(): static
    {
        return new static();
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }

    public function round(bool $round = true): static
    {
        return $this->props(['round' => $round]);
    }

    public function checkedValue(mixed $value): static
    {
        return $this->props(['checked-value' => $value]);
    }

    public function uncheckedValue(mixed $value): static
    {
        return $this->props(['unchecked-value' => $value]);
    }

    public function railStyle(string $style): static
    {
        return $this->props(['rail-style' => $style]);
    }
}
