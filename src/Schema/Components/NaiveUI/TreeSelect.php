<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTreeSelect - Naive UI 树选择器组件
 */
class TreeSelect extends Component
{
    public function __construct()
    {
        parent::__construct('NTreeSelect');
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

    public function filterable(bool $filterable = true): static
    {
        return $this->props(['filterable' => $filterable]);
    }

    public function multiple(bool $multiple = true): static
    {
        return $this->props(['multiple' => $multiple]);
    }

    public function checkable(bool $checkable = true): static
    {
        return $this->props(['checkable' => $checkable]);
    }

    public function cascade(bool $cascade = true): static
    {
        return $this->props(['cascade' => $cascade]);
    }

    public function checkStrategy(string $strategy): static
    {
        return $this->props(['check-strategy' => $strategy]);
    }

    public function defaultExpandAll(bool $expand = true): static
    {
        return $this->props(['default-expand-all' => $expand]);
    }

    public function keyField(string $field): static
    {
        return $this->props(['key-field' => $field]);
    }

    public function labelField(string $field): static
    {
        return $this->props(['label-field' => $field]);
    }

    public function childrenField(string $field): static
    {
        return $this->props(['children-field' => $field]);
    }
}
