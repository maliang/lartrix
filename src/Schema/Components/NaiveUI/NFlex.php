<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NFlex - Naive UI 弹性布局组件
 */
class NFlex extends Component
{
    public function __construct()
    {
        parent::__construct('NFlex');
    }

    public static function make(): static
    {
        return new static();
    }

    public function vertical(bool $vertical = true): static
    {
        return $this->props(['vertical' => $vertical]);
    }

    public function align(string $align): static
    {
        return $this->props(['align' => $align]);
    }

    public function justify(string $justify): static
    {
        return $this->props(['justify' => $justify]);
    }

    public function wrap(bool $wrap = true): static
    {
        return $this->props(['wrap' => $wrap]);
    }

    public function size(int|string|array $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function inline(bool $inline = true): static
    {
        return $this->props(['inline' => $inline]);
    }
}
