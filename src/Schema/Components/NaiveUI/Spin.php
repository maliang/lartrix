<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NSpin - Naive UI 加载组件
 */
class Spin extends Component
{
    public function __construct()
    {
        parent::__construct('NSpin');
    }

    public static function make(): static
    {
        return new static();
    }

    public function show(bool $show = true): static
    {
        return $this->props(['show' => $show]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function description(string $description): static
    {
        return $this->props(['description' => $description]);
    }

    public function strokeWidth(int $width): static
    {
        return $this->props(['strokeWidth' => $width]);
    }

    public function rotate(bool $rotate = true): static
    {
        return $this->props(['rotate' => $rotate]);
    }
}
