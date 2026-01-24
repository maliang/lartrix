<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NEllipsis - Naive UI 文本省略组件
 */
class NEllipsis extends Component
{
    public function __construct()
    {
        parent::__construct('NEllipsis');
    }

    public static function make(): static
    {
        return new static();
    }

    public function expandTrigger(string $trigger): static
    {
        return $this->props(['expandTrigger' => $trigger]);
    }

    public function lineClamp(int|string $clamp): static
    {
        return $this->props(['lineClamp' => $clamp]);
    }

    public function tooltip(bool|array $tooltip = true): static
    {
        return $this->props(['tooltip' => $tooltip]);
    }
}
