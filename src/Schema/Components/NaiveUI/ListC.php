<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NList - Naive UI 列表组件
 * 
 * 注意：类名使用 ListC 因为 list 是 PHP 保留字
 */
class ListC extends Component
{
    public function __construct()
    {
        parent::__construct('NList');
    }

    public static function make(): static
    {
        return new static();
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function clickable(bool $clickable = true): static
    {
        return $this->props(['clickable' => $clickable]);
    }

    public function hoverable(bool $hoverable = true): static
    {
        return $this->props(['hoverable' => $hoverable]);
    }

    public function showDivider(bool $show = true): static
    {
        return $this->props(['showDivider' => $show]);
    }
}
