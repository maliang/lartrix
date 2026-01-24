<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NInputGroupLabel - Naive UI 输入框组标签组件
 */
class NInputGroupLabel extends Component
{
    public function __construct()
    {
        parent::__construct('NInputGroupLabel');
    }

    public static function make(): static
    {
        return new static();
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
