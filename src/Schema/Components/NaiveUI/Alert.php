<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NAlert - Naive UI 警告提示组件
 */
class Alert extends Component
{
    public function __construct()
    {
        parent::__construct('NAlert');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function closable(bool $closable = true): static
    {
        return $this->props(['closable' => $closable]);
    }

    public function showIcon(bool $show = true): static
    {
        return $this->props(['show-icon' => $show]);
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }
}
