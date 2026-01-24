<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCheckbox - Naive UI 复选框组件
 */
class NCheckbox extends Component
{
    public function __construct()
    {
        parent::__construct('NCheckbox');
    }

    public static function make(): static
    {
        return new static();
    }

    public function checked(bool|string $checked = true): static
    {
        return $this->props(['checked' => $checked]);
    }

    public function defaultChecked(bool $checked = true): static
    {
        return $this->props(['default-checked' => $checked]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function indeterminate(bool|string $indeterminate = true): static
    {
        return $this->props(['indeterminate' => $indeterminate]);
    }

    public function label(string $label): static
    {
        return $this->props(['label' => $label]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function value(mixed $value): static
    {
        return $this->props(['value' => $value]);
    }
}
