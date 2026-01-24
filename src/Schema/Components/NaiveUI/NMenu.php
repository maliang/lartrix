<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NMenu - Naive UI 菜单组件
 */
class NMenu extends Component
{
    public function __construct()
    {
        parent::__construct('NMenu');
    }

    public static function make(): static
    {
        return new static();
    }

    public function options(array $options): static
    {
        return $this->props(['options' => $options]);
    }

    public function mode(string $mode): static
    {
        return $this->props(['mode' => $mode]);
    }

    public function collapsed(bool $collapsed = true): static
    {
        return $this->props(['collapsed' => $collapsed]);
    }

    public function collapsedWidth(int $width): static
    {
        return $this->props(['collapsedWidth' => $width]);
    }

    public function collapsedIconSize(int $size): static
    {
        return $this->props(['collapsedIconSize' => $size]);
    }

    public function accordion(bool $accordion = true): static
    {
        return $this->props(['accordion' => $accordion]);
    }

    public function indent(int $indent): static
    {
        return $this->props(['indent' => $indent]);
    }
}
