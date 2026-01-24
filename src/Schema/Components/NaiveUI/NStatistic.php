<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NStatistic - Naive UI 统计数值组件
 */
class NStatistic extends Component
{
    public function __construct()
    {
        parent::__construct('NStatistic');
    }

    public static function make(): static
    {
        return new static();
    }

    public function label(string $label): static
    {
        return $this->props(['label' => $label]);
    }

    public function value(mixed $value): static
    {
        return $this->props(['value' => $value]);
    }

    public function tabularNums(bool $tabular = true): static
    {
        return $this->props(['tabularNums' => $tabular]);
    }
}
