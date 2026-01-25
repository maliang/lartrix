<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDynamicTags - Naive UI 动态标签组件
 */
class DynamicTags extends Component
{
    public function __construct()
    {
        parent::__construct('NDynamicTags');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function round(bool $round = true): static
    {
        return $this->props(['round' => $round]);
    }

    public function closable(bool $closable = true): static
    {
        return $this->props(['closable' => $closable]);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function tagStyle(array|string $style): static
    {
        return $this->props(['tagStyle' => $style]);
    }

    public function inputStyle(array|string $style): static
    {
        return $this->props(['inputStyle' => $style]);
    }
}
