<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDrawerContent - Naive UI 抽屉内容组件
 */
class NDrawerContent extends Component
{
    public function __construct()
    {
        parent::__construct('NDrawerContent');
    }

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function closable(bool $closable = true): static
    {
        return $this->props(['closable' => $closable]);
    }

    public function nativeScrollbar(bool $native = true): static
    {
        return $this->props(['nativeScrollbar' => $native]);
    }

    public function bodyContentStyle(array|string $style): static
    {
        return $this->props(['bodyContentStyle' => $style]);
    }

    public function headerStyle(array|string $style): static
    {
        return $this->props(['headerStyle' => $style]);
    }

    public function footerStyle(array|string $style): static
    {
        return $this->props(['footerStyle' => $style]);
    }
}
