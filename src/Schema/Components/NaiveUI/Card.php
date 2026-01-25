<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCard - Naive UI 卡片组件
 */
class Card extends Component
{
    public function __construct()
    {
        parent::__construct('NCard');
    }

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function hoverable(bool $hoverable = true): static
    {
        return $this->props(['hoverable' => $hoverable]);
    }

    public function segmented(array|bool $segmented = true): static
    {
        return $this->props(['segmented' => $segmented]);
    }

    public function closable(bool $closable = true): static
    {
        return $this->props(['closable' => $closable]);
    }

    public function headerStyle(string|array $style): static
    {
        return $this->props(['header-style' => $style]);
    }

    public function contentStyle(string|array $style): static
    {
        return $this->props(['content-style' => $style]);
    }

    public function footerStyle(string|array $style): static
    {
        return $this->props(['footer-style' => $style]);
    }
}
