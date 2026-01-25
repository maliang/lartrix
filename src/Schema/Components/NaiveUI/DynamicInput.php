<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDynamicInput - Naive UI 动态录入组件
 */
class DynamicInput extends Component
{
    public function __construct()
    {
        parent::__construct('NDynamicInput');
    }

    public static function make(): static
    {
        return new static();
    }

    public function preset(string $preset): static
    {
        return $this->props(['preset' => $preset]);
    }

    public function min(int $min): static
    {
        return $this->props(['min' => $min]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function keyPlaceholder(string $placeholder): static
    {
        return $this->props(['keyPlaceholder' => $placeholder]);
    }

    public function valuePlaceholder(string $placeholder): static
    {
        return $this->props(['valuePlaceholder' => $placeholder]);
    }

    public function showSortButton(bool $show = true): static
    {
        return $this->props(['showSortButton' => $show]);
    }
}
