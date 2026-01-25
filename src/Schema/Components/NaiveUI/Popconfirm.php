<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NPopconfirm - Naive UI 弹出确认组件
 */
class Popconfirm extends Component
{
    public function __construct()
    {
        parent::__construct('NPopconfirm');
    }

    public static function make(): static
    {
        return new static();
    }

    public function positiveText(string $text): static
    {
        return $this->props(['positiveText' => $text]);
    }

    public function negativeText(string $text): static
    {
        return $this->props(['negativeText' => $text]);
    }

    public function showIcon(bool $show = true): static
    {
        return $this->props(['showIcon' => $show]);
    }
}
