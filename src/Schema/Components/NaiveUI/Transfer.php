<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTransfer - Naive UI 穿梭框组件
 */
class Transfer extends Component
{
    public function __construct()
    {
        parent::__construct('NTransfer');
    }

    public static function make(): static
    {
        return new static();
    }

    public function options(array|string $options): static
    {
        return $this->props([
            'options' => is_string($options) ? "{{ $options }}" : $options
        ]);
    }

    public function value(array|string $value): static
    {
        return $this->props([
            'value' => is_string($value) ? "{{ $value }}" : $value
        ]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function filterable(bool $filterable = true): static
    {
        return $this->props(['filterable' => $filterable]);
    }

    public function sourceTitle(string $title): static
    {
        return $this->props(['source-title' => $title]);
    }

    public function targetTitle(string $title): static
    {
        return $this->props(['target-title' => $title]);
    }

    public function sourceFilterPlaceholder(string $placeholder): static
    {
        return $this->props(['source-filter-placeholder' => $placeholder]);
    }

    public function targetFilterPlaceholder(string $placeholder): static
    {
        return $this->props(['target-filter-placeholder' => $placeholder]);
    }
}
