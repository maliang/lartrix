<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDatePicker - Naive UI 日期选择器组件
 */
class DatePicker extends Component
{
    public function __construct()
    {
        parent::__construct('NDatePicker');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function value(mixed $value): static
    {
        return $this->props([
            'value' => is_string($value) ? "{{ $value }}" : $value
        ]);
    }

    public function placeholder(string $placeholder): static
    {
        return $this->props(['placeholder' => $placeholder]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function clearable(bool $clearable = true): static
    {
        return $this->props(['clearable' => $clearable]);
    }

    public function format(string $format): static
    {
        return $this->props(['format' => $format]);
    }

    public function valueFormat(string $format): static
    {
        return $this->props(['value-format' => $format]);
    }

    public function startPlaceholder(string $placeholder): static
    {
        return $this->props(['start-placeholder' => $placeholder]);
    }

    public function endPlaceholder(string $placeholder): static
    {
        return $this->props(['end-placeholder' => $placeholder]);
    }

    public function separator(string $separator): static
    {
        return $this->props(['separator' => $separator]);
    }

    public function actions(array $actions): static
    {
        return $this->props(['actions' => $actions]);
    }
}
