<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDescriptionsItem - Naive UI 描述列表项组件
 */
class DescriptionsItem extends Component
{
    public function __construct()
    {
        parent::__construct('NDescriptionsItem');
    }

    public static function make(): static
    {
        return new static();
    }

    public function label(string $label): static
    {
        return $this->props(['label' => $label]);
    }

    public function span(int $span): static
    {
        return $this->props(['span' => $span]);
    }

    public function labelStyle(array|string $style): static
    {
        return $this->props(['labelStyle' => $style]);
    }

    public function contentStyle(array|string $style): static
    {
        return $this->props(['contentStyle' => $style]);
    }
}
