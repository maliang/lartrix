<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDrawer - Naive UI 抽屉组件
 */
class Drawer extends Component
{
    public function __construct()
    {
        parent::__construct('NDrawer');
    }

    public static function make(): static
    {
        return new static();
    }

    public function show(string $show): static
    {
        return $this->props(['show' => "{{ $show }}"]);
    }

    public function width(int|string $width): static
    {
        return $this->props(['width' => $width]);
    }

    public function height(int|string $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function placement(string $placement): static
    {
        return $this->props(['placement' => $placement]);
    }

    public function maskClosable(bool $closable = true): static
    {
        return $this->props(['mask-closable' => $closable]);
    }

    public function closeOnEsc(bool $close = true): static
    {
        return $this->props(['close-on-esc' => $close]);
    }

    public function showMask(bool|string $show = true): static
    {
        return $this->props(['show-mask' => $show]);
    }

    public function autoFocus(bool $focus = true): static
    {
        return $this->props(['auto-focus' => $focus]);
    }

    public function trapFocus(bool $trap = true): static
    {
        return $this->props(['trap-focus' => $trap]);
    }

    public function resizable(bool $resizable = true): static
    {
        return $this->props(['resizable' => $resizable]);
    }

    public function defaultWidth(int|string $width): static
    {
        return $this->props(['default-width' => $width]);
    }

    public function defaultHeight(int|string $height): static
    {
        return $this->props(['default-height' => $height]);
    }
}
