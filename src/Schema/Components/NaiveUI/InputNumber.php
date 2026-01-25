<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NInputNumber - Naive UI 数字输入框组件
 */
class InputNumber extends Component
{
    public function __construct()
    {
        parent::__construct('NInputNumber');
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

    public function placeholder(string $placeholder): static
    {
        return $this->props(['placeholder' => $placeholder]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function clearable(bool $clearable = true): static
    {
        return $this->props(['clearable' => $clearable]);
    }

    public function min(int|float $min): static
    {
        return $this->props(['min' => $min]);
    }

    public function max(int|float $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function step(int|float $step): static
    {
        return $this->props(['step' => $step]);
    }

    public function precision(int $precision): static
    {
        return $this->props(['precision' => $precision]);
    }

    public function showButton(bool $show = true): static
    {
        return $this->props(['show-button' => $show]);
    }

    public function buttonPlacement(string $placement): static
    {
        return $this->props(['button-placement' => $placement]);
    }
}
