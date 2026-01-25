<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NBreadcrumb - Naive UI 面包屑组件
 */
class Breadcrumb extends Component
{
    public function __construct()
    {
        parent::__construct('NBreadcrumb');
    }

    public static function make(): static
    {
        return new static();
    }

    public function separator(string $separator): static
    {
        return $this->props(['separator' => $separator]);
    }
}
