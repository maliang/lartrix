<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NButton - Naive UI 按钮组件
 */
class Button extends Component
{
    public function __construct()
    {
        parent::__construct('NButton');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function text(string $text): static
    {
        $this->children = [$text];
        return $this;
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }

    public function ghost(bool $ghost = true): static
    {
        return $this->props(['ghost' => $ghost]);
    }

    public function circle(bool $circle = true): static
    {
        return $this->props(['circle' => $circle]);
    }

    public function round(bool $round = true): static
    {
        return $this->props(['round' => $round]);
    }

    public function secondary(bool $secondary = true): static
    {
        return $this->props(['secondary' => $secondary]);
    }

    public function tertiary(bool $tertiary = true): static
    {
        return $this->props(['tertiary' => $tertiary]);
    }

    public function quaternary(bool $quaternary = true): static
    {
        return $this->props(['quaternary' => $quaternary]);
    }

    public function dashed(bool $dashed = true): static
    {
        return $this->props(['dashed' => $dashed]);
    }
}
