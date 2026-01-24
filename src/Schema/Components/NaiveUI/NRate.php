<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NRate - Naive UI 评分组件
 */
class NRate extends Component
{
    public function __construct()
    {
        parent::__construct('NRate');
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

    public function defaultValue(int|float $value): static
    {
        return $this->props(['default-value' => $value]);
    }

    public function count(int $count): static
    {
        return $this->props(['count' => $count]);
    }

    public function size(string|int $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function readonly(bool $readonly = true): static
    {
        return $this->props(['readonly' => $readonly]);
    }

    public function allowHalf(bool $allow = true): static
    {
        return $this->props(['allow-half' => $allow]);
    }

    public function clearable(bool $clearable = true): static
    {
        return $this->props(['clearable' => $clearable]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }
}
