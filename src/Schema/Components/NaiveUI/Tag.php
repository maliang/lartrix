<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTag - Naive UI 标签组件
 */
class Tag extends Component
{
    public function __construct()
    {
        parent::__construct('NTag');
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

    public function closable(bool $closable = true): static
    {
        return $this->props(['closable' => $closable]);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function round(bool $round = true): static
    {
        return $this->props(['round' => $round]);
    }

    public function checkable(bool $checkable = true): static
    {
        return $this->props(['checkable' => $checkable]);
    }

    public function checked(bool|string $checked = true): static
    {
        return $this->props(['checked' => $checked]);
    }

    public function color(array $color): static
    {
        return $this->props(['color' => $color]);
    }

    public function text(string $text): static
    {
        $this->children = [$text];
        return $this;
    }
}
