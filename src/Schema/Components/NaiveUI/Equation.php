<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NEquation - Naive UI 公式组件
 */
class Equation extends Component
{
    public function __construct()
    {
        parent::__construct('NEquation');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(string $value): static
    {
        return $this->props(['value' => $value]);
    }

    public function katex(mixed $katex): static
    {
        return $this->props(['katex' => $katex]);
    }

    public function katexOptions(array $options): static
    {
        return $this->props(['katexOptions' => $options]);
    }
}
