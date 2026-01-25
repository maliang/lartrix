<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NAvatar - Naive UI 头像组件
 */
class Avatar extends Component
{
    public function __construct()
    {
        parent::__construct('NAvatar');
    }

    public static function make(): static
    {
        return new static();
    }

    public function src(string $src): static
    {
        return $this->props(['src' => $src]);
    }

    public function size(string|int $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function round(bool $round = true): static
    {
        return $this->props(['round' => $round]);
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function fallbackSrc(string $src): static
    {
        return $this->props(['fallback-src' => $src]);
    }

    public function objectFit(string $fit): static
    {
        return $this->props(['object-fit' => $fit]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }

    public function imgProps(array $props): static
    {
        return $this->props(['img-props' => $props]);
    }
}
