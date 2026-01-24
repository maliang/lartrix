<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDropdown - Naive UI 下拉菜单组件
 */
class NDropdown extends Component
{
    public function __construct()
    {
        parent::__construct('NDropdown');
    }

    public static function make(): static
    {
        return new static();
    }

    public function options(array $options): static
    {
        return $this->props(['options' => $options]);
    }

    public function trigger(string $trigger): static
    {
        return $this->props(['trigger' => $trigger]);
    }

    public function placement(string $placement): static
    {
        return $this->props(['placement' => $placement]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function showArrow(bool $show = true): static
    {
        return $this->props(['showArrow' => $show]);
    }
}
