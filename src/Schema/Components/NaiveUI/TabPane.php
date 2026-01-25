<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTabPane - Naive UI 标签面板组件
 */
class TabPane extends Component
{
    public function __construct()
    {
        parent::__construct('NTabPane');
    }

    public static function make(): static
    {
        return new static();
    }

    public function name(string $name): static
    {
        return $this->props(['name' => $name]);
    }

    public function tab(string $tab): static
    {
        return $this->props(['tab' => $tab]);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function closable(bool $closable = true): static
    {
        return $this->props(['closable' => $closable]);
    }

    public function displayDirective(string $directive): static
    {
        return $this->props(['display-directive' => $directive]);
    }
}
