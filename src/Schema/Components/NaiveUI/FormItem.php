<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NFormItem - Naive UI 表单项组件
 */
class FormItem extends Component
{
    public function __construct()
    {
        parent::__construct('NFormItem');
    }

    public static function make(): static
    {
        return new static();
    }

    public function label(string $label): static
    {
        return $this->props(['label' => $label]);
    }

    public function path(string $path): static
    {
        return $this->props(['path' => $path]);
    }

    public function rule(array|string $rule): static
    {
        return $this->props([
            'rule' => is_string($rule) ? "{{ $rule }}" : $rule
        ]);
    }

    public function first(bool $first = true): static
    {
        return $this->props(['first' => $first]);
    }

    public function labelWidth(int|string $width): static
    {
        return $this->props(['label-width' => $width]);
    }

    public function labelPlacement(string $placement): static
    {
        return $this->props(['label-placement' => $placement]);
    }

    public function labelAlign(string $align): static
    {
        return $this->props(['label-align' => $align]);
    }

    public function showFeedback(bool $show = true): static
    {
        return $this->props(['show-feedback' => $show]);
    }

    public function showLabel(bool $show = true): static
    {
        return $this->props(['show-label' => $show]);
    }

    public function showRequireMark(bool|string $show = true): static
    {
        return $this->props(['show-require-mark' => $show]);
    }

    public function requireMarkPlacement(string $placement): static
    {
        return $this->props(['require-mark-placement' => $placement]);
    }
}
