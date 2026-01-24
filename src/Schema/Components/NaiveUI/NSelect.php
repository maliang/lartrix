<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NSelect - Naive UI 选择器组件
 */
class NSelect extends Component
{
    public function __construct()
    {
        parent::__construct('NSelect');
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

    public function placeholder(string $text): static
    {
        return $this->props(['placeholder' => $text]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function multiple(bool $multiple = true): static
    {
        return $this->props(['multiple' => $multiple]);
    }

    public function clearable(bool $clearable = true): static
    {
        return $this->props(['clearable' => $clearable]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function filterable(bool $filterable = true): static
    {
        return $this->props(['filterable' => $filterable]);
    }

    public function remote(bool $remote = true): static
    {
        return $this->props(['remote' => $remote]);
    }

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }

    public function labelField(string $field): static
    {
        return $this->props(['label-field' => $field]);
    }

    public function valueField(string $field): static
    {
        return $this->props(['value-field' => $field]);
    }
}
