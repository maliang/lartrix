<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NSkeleton - Naive UI 骨架屏组件
 */
class NSkeleton extends Component
{
    public function __construct()
    {
        parent::__construct('NSkeleton');
    }

    public static function make(): static
    {
        return new static();
    }

    public function text(bool $text = true): static
    {
        return $this->props(['text' => $text]);
    }

    public function round(bool $round = true): static
    {
        return $this->props(['round' => $round]);
    }

    public function circle(bool $circle = true): static
    {
        return $this->props(['circle' => $circle]);
    }

    public function height(string|int $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function width(string|int $width): static
    {
        return $this->props(['width' => $width]);
    }

    public function repeat(int $repeat): static
    {
        return $this->props(['repeat' => $repeat]);
    }

    public function animated(bool $animated = true): static
    {
        return $this->props(['animated' => $animated]);
    }

    public function sharp(bool $sharp = true): static
    {
        return $this->props(['sharp' => $sharp]);
    }
}
