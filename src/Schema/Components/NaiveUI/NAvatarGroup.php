<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NAvatarGroup - Naive UI 头像组组件
 */
class NAvatarGroup extends Component
{
    public function __construct()
    {
        parent::__construct('NAvatarGroup');
    }

    public static function make(): static
    {
        return new static();
    }

    public function options(array $options): static
    {
        return $this->props(['options' => $options]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function size(string|int $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function vertical(bool $vertical = true): static
    {
        return $this->props(['vertical' => $vertical]);
    }

    public function expandOnHover(bool $expand = true): static
    {
        return $this->props(['expandOnHover' => $expand]);
    }
}
