<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NEmpty - Naive UI 空状态组件
 */
class NEmpty extends Component
{
    public function __construct()
    {
        parent::__construct('NEmpty');
    }

    public static function make(): static
    {
        return new static();
    }

    public function description(string $description): static
    {
        return $this->props(['description' => $description]);
    }

    public function showDescription(bool $show = true): static
    {
        return $this->props(['showDescription' => $show]);
    }

    public function showIcon(bool $show = true): static
    {
        return $this->props(['showIcon' => $show]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }
}
