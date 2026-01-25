<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * Ol - Naive UI 有序列表组件
 */
class Ol extends Component
{
    public function __construct()
    {
        parent::__construct('NOl');
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
