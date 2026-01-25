<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NBreadcrumbItem - Naive UI 面包屑项组件
 */
class BreadcrumbItem extends Component
{
    public function __construct()
    {
        parent::__construct('NBreadcrumbItem');
    }

    public static function make(): static
    {
        return new static();
    }

    public function href(string $href): static
    {
        return $this->props(['href' => $href]);
    }

    public function clickable(bool $clickable = true): static
    {
        return $this->props(['clickable' => $clickable]);
    }
}
