<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDescriptions - Naive UI 描述列表组件
 */
class Descriptions extends Component
{
    public function __construct()
    {
        parent::__construct('NDescriptions');
    }

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function column(int $column): static
    {
        return $this->props(['column' => $column]);
    }

    public function labelPlacement(string $placement): static
    {
        return $this->props(['labelPlacement' => $placement]);
    }

    public function labelAlign(string $align): static
    {
        return $this->props(['labelAlign' => $align]);
    }

    public function separator(string $separator): static
    {
        return $this->props(['separator' => $separator]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }
}
