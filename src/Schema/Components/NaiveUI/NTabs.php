<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTabs - Naive UI 标签页组件
 */
class NTabs extends Component
{
    public function __construct()
    {
        parent::__construct('NTabs');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(string $value): static
    {
        return $this->props(['value' => "{{ $value }}"]);
    }

    public function defaultValue(string $value): static
    {
        return $this->props(['default-value' => $value]);
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

    public function addable(bool|array $addable = true): static
    {
        return $this->props(['addable' => $addable]);
    }

    public function justifyContent(string $justify): static
    {
        return $this->props(['justify-content' => $justify]);
    }

    public function placement(string $placement): static
    {
        return $this->props(['placement' => $placement]);
    }

    public function animated(bool $animated = true): static
    {
        return $this->props(['animated' => $animated]);
    }

    public function tabStyle(string|array $style): static
    {
        return $this->props(['tab-style' => $style]);
    }

    public function paneStyle(string|array $style): static
    {
        return $this->props(['pane-style' => $style]);
    }
}
