<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NPerformantEllipsis - Naive UI 高性能文本省略组件
 */
class NPerformantEllipsis extends Component
{
    public function __construct()
    {
        parent::__construct('NPerformantEllipsis');
    }

    public static function make(): static
    {
        return new static();
    }

    public function lineClamp(int $clamp): static
    {
        return $this->props(['lineClamp' => $clamp]);
    }

    public function expandTrigger(string $trigger): static
    {
        return $this->props(['expandTrigger' => $trigger]);
    }

    public function tooltip(bool|array $tooltip = true): static
    {
        return $this->props(['tooltip' => $tooltip]);
    }
}
