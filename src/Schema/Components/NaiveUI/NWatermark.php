<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NWatermark - Naive UI 水印组件
 */
class NWatermark extends Component
{
    public function __construct()
    {
        parent::__construct('NWatermark');
    }

    public static function make(): static
    {
        return new static();
    }

    public function content(string $content): static
    {
        return $this->props(['content' => $content]);
    }

    public function fontSize(int $size): static
    {
        return $this->props(['fontSize' => $size]);
    }

    public function fontFamily(string $family): static
    {
        return $this->props(['fontFamily' => $family]);
    }

    public function fontColor(string $color): static
    {
        return $this->props(['fontColor' => $color]);
    }

    public function fontStyle(string $style): static
    {
        return $this->props(['fontStyle' => $style]);
    }

    public function fontWeight(int|string $weight): static
    {
        return $this->props(['fontWeight' => $weight]);
    }

    public function lineHeight(int $height): static
    {
        return $this->props(['lineHeight' => $height]);
    }

    public function width(int $width): static
    {
        return $this->props(['width' => $width]);
    }

    public function height(int $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function xGap(int $gap): static
    {
        return $this->props(['xGap' => $gap]);
    }

    public function yGap(int $gap): static
    {
        return $this->props(['yGap' => $gap]);
    }

    public function xOffset(int $offset): static
    {
        return $this->props(['xOffset' => $offset]);
    }

    public function yOffset(int $offset): static
    {
        return $this->props(['yOffset' => $offset]);
    }

    public function rotate(int $rotate): static
    {
        return $this->props(['rotate' => $rotate]);
    }

    public function zIndex(int $zIndex): static
    {
        return $this->props(['zIndex' => $zIndex]);
    }

    public function globalRotate(int $rotate): static
    {
        return $this->props(['globalRotate' => $rotate]);
    }

    public function fullscreen(bool $fullscreen = true): static
    {
        return $this->props(['fullscreen' => $fullscreen]);
    }

    public function cross(bool $cross = true): static
    {
        return $this->props(['cross' => $cross]);
    }

    public function selectable(bool $selectable = true): static
    {
        return $this->props(['selectable' => $selectable]);
    }

    public function debug(bool $debug = true): static
    {
        return $this->props(['debug' => $debug]);
    }
}
