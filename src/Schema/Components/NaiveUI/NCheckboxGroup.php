<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCheckboxGroup - Naive UI 复选框组组件
 */
class NCheckboxGroup extends Component
{
    public function __construct()
    {
        parent::__construct('NCheckboxGroup');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(array|string $value): static
    {
        return $this->props([
            'value' => is_string($value) ? "{{ $value }}" : $value
        ]);
    }

    public function defaultValue(array $value): static
    {
        return $this->props(['default-value' => $value]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function min(int $min): static
    {
        return $this->props(['min' => $min]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }
}
