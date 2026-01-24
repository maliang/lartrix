<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NImage - Naive UI 图片组件
 */
class NImage extends Component
{
    public function __construct()
    {
        parent::__construct('NImage');
    }

    public static function make(): static
    {
        return new static();
    }

    public function src(string $src): static
    {
        return $this->props(['src' => $src]);
    }

    public function alt(string $alt): static
    {
        return $this->props(['alt' => $alt]);
    }

    public function width(string|int $width): static
    {
        return $this->props(['width' => $width]);
    }

    public function height(string|int $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function previewSrc(string $src): static
    {
        return $this->props(['previewSrc' => $src]);
    }

    public function showToolbar(bool $show = true): static
    {
        return $this->props(['showToolbar' => $show]);
    }

    public function showToolbarTooltip(bool $show = true): static
    {
        return $this->props(['showToolbarTooltip' => $show]);
    }

    public function lazy(bool $lazy = true): static
    {
        return $this->props(['lazy' => $lazy]);
    }

    public function objectFit(string $fit): static
    {
        return $this->props(['objectFit' => $fit]);
    }

    public function previewDisabled(bool $disabled = true): static
    {
        return $this->props(['previewDisabled' => $disabled]);
    }
}
