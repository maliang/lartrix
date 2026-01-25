<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NBadge - Naive UI 徽标组件
 */
class Badge extends Component
{
    public function __construct()
    {
        parent::__construct('NBadge');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(int|string $value): static
    {
        return $this->props([
            'value' => is_string($value) && !is_numeric($value) ? "{{ $value }}" : $value
        ]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function dot(bool $dot = true): static
    {
        return $this->props(['dot' => $dot]);
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function show(bool|string $show = true): static
    {
        return $this->props(['show' => $show]);
    }

    public function showZero(bool $show = true): static
    {
        return $this->props(['show-zero' => $show]);
    }

    public function processing(bool $processing = true): static
    {
        return $this->props(['processing' => $processing]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }

    public function offset(array $offset): static
    {
        return $this->props(['offset' => $offset]);
    }
}
