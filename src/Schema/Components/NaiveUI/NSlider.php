<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NSlider - Naive UI 滑块组件
 */
class NSlider extends Component
{
    public function __construct()
    {
        parent::__construct('NSlider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(mixed $value): static
    {
        return $this->props([
            'value' => is_string($value) ? "{{ $value }}" : $value
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

    public function min(int|float $min): static
    {
        return $this->props(['min' => $min]);
    }

    public function max(int|float $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function step(int|float|string $step): static
    {
        return $this->props(['step' => $step]);
    }

    public function range(bool $range = true): static
    {
        return $this->props(['range' => $range]);
    }

    public function vertical(bool $vertical = true): static
    {
        return $this->props(['vertical' => $vertical]);
    }

    public function reverse(bool $reverse = true): static
    {
        return $this->props(['reverse' => $reverse]);
    }

    public function showTooltip(bool $show = true): static
    {
        return $this->props(['show-tooltip' => $show]);
    }

    public function marks(array $marks): static
    {
        return $this->props(['marks' => $marks]);
    }
}
