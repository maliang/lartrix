<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NRadioGroup - Naive UI 单选框组组件
 */
class NRadioGroup extends Component
{
    public function __construct()
    {
        parent::__construct('NRadioGroup');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(mixed $value): static
    {
        return $this->props([
            'value' => is_string($value) && !str_starts_with($value, '{{') ? "{{ $value }}" : $value
        ]);
    }

    public function defaultValue(mixed $value): static
    {
        return $this->props(['default-value' => $value]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function name(string $name): static
    {
        return $this->props(['name' => $name]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }
}
