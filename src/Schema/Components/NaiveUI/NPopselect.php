<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NPopselect - Naive UI 弹出选择组件
 */
class NPopselect extends Component
{
    public function __construct()
    {
        parent::__construct('NPopselect');
    }

    public static function make(): static
    {
        return new static();
    }

    public function options(array $options): static
    {
        return $this->props(['options' => $options]);
    }

    public function multiple(bool $multiple = true): static
    {
        return $this->props(['multiple' => $multiple]);
    }

    public function scrollable(bool $scrollable = true): static
    {
        return $this->props(['scrollable' => $scrollable]);
    }
}
