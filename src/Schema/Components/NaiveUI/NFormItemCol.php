<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NFormItemCol - Naive UI 表单项列组件
 */
class NFormItemCol extends Component
{
    public function __construct()
    {
        parent::__construct('NFormItemCol');
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

    public function span(int $span): static
    {
        return $this->props(['span' => $span]);
    }

    public function rule(array $rule): static
    {
        return $this->props(['rule' => $rule]);
    }

    public function required(bool $required = true): static
    {
        return $this->props(['required' => $required]);
    }
}
