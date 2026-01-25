<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NColorPicker - Naive UI 颜色选择器组件
 */
class ColorPicker extends Component
{
    public function __construct()
    {
        parent::__construct('NColorPicker');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(string $value): static
    {
        return $this->props(['value' => "{{ $value }}"]);
    }

    public function defaultValue(string $value): static
    {
        return $this->props(['default-value' => $value]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function modes(array $modes): static
    {
        return $this->props(['modes' => $modes]);
    }

    public function showAlpha(bool $show = true): static
    {
        return $this->props(['show-alpha' => $show]);
    }

    public function showPreview(bool $show = true): static
    {
        return $this->props(['show-preview' => $show]);
    }

    public function swatches(array $swatches): static
    {
        return $this->props(['swatches' => $swatches]);
    }

    public function actions(array $actions): static
    {
        return $this->props(['actions' => $actions]);
    }
}
