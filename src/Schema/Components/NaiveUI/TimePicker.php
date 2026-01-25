<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTimePicker - Naive UI 时间选择器组件
 */
class TimePicker extends Component
{
    public function __construct()
    {
        parent::__construct('NTimePicker');
    }

    public static function make(): static
    {
        return new static();
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

    public function use12Hours(bool $use = true): static
    {
        return $this->props(['use-12-hours' => $use]);
    }

    public function actions(array $actions): static
    {
        return $this->props(['actions' => $actions]);
    }
}
