<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NScrollbar - Naive UI 滚动条组件
 */
class Scrollbar extends Component
{
    public function __construct()
    {
        parent::__construct('NScrollbar');
    }

    public static function make(): static
    {
        return new static();
    }

    public function xScrollable(bool $scrollable = true): static
    {
        return $this->props(['xScrollable' => $scrollable]);
    }

    public function trigger(string $trigger): static
    {
        return $this->props(['trigger' => $trigger]);
    }

    public function size(int $size): static
    {
        return $this->props(['size' => $size]);
    }
}
