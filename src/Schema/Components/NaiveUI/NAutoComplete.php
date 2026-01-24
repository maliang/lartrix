<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NAutoComplete - Naive UI 自动完成组件
 */
class NAutoComplete extends Component
{
    public function __construct()
    {
        parent::__construct('NAutoComplete');
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

    public function value(string $value): static
    {
        return $this->props(['value' => "{{ $value }}"]);
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

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }

    public function blurAfterSelect(bool $blur = true): static
    {
        return $this->props(['blur-after-select' => $blur]);
    }

    public function clearAfterSelect(bool $clear = true): static
    {
        return $this->props(['clear-after-select' => $clear]);
    }

    public function getShow(string $fn): static
    {
        return $this->props(['get-show' => "{{ $fn }}"]);
    }
}
