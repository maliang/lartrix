<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NButtonGroup - Naive UI 按钮组组件
 */
class ButtonGroup extends Component
{
    public function __construct()
    {
        parent::__construct('NButtonGroup');
    }

    public static function make(): static
    {
        return new static();
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function vertical(bool $vertical = true): static
    {
        return $this->props(['vertical' => $vertical]);
    }
}
