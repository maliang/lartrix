<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NMention - Naive UI 提及组件
 */
class NMention extends Component
{
    public function __construct()
    {
        parent::__construct('NMention');
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

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function prefix(string|array $prefix): static
    {
        return $this->props(['prefix' => $prefix]);
    }

    public function autosize(bool|array $autosize = true): static
    {
        return $this->props(['autosize' => $autosize]);
    }

    public function rows(int $rows): static
    {
        return $this->props(['rows' => $rows]);
    }

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }
}
