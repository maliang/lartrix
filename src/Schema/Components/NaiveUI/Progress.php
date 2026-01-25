<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NProgress - Naive UI 进度条组件
 */
class Progress extends Component
{
    public function __construct()
    {
        parent::__construct('NProgress');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function percentage(int|float $percentage): static
    {
        return $this->props(['percentage' => $percentage]);
    }

    public function status(string $status): static
    {
        return $this->props(['status' => $status]);
    }

    public function indicatorPlacement(string $placement): static
    {
        return $this->props(['indicatorPlacement' => $placement]);
    }

    public function showIndicator(bool $show = true): static
    {
        return $this->props(['showIndicator' => $show]);
    }

    public function strokeWidth(int $width): static
    {
        return $this->props(['strokeWidth' => $width]);
    }

    public function railColor(string $color): static
    {
        return $this->props(['railColor' => $color]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }
}
