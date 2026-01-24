<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCollapseItem - Naive UI 折叠面板项组件
 */
class NCollapseItem extends Component
{
    public function __construct()
    {
        parent::__construct('NCollapseItem');
    }

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function name(string $name): static
    {
        return $this->props(['name' => $name]);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }
}
