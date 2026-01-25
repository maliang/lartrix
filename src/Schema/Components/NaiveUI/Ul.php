<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * Ul - Naive UI 无序列表组件
 */
class Ul extends Component
{
    public function __construct()
    {
        parent::__construct('NUl');
    }

    public static function make(): static
    {
        return new static();
    }

    public function alignText(bool $alignText = true): static
    {
        return $this->props(['align-text' => $alignText]);
    }
}
