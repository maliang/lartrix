<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NBlockquote - Naive UI 引用组件
 */
class Blockquote extends Component
{
    public function __construct()
    {
        parent::__construct('NBlockquote');
    }

    public static function make(): static
    {
        return new static();
    }

    public function alignText(bool $align = true): static
    {
        return $this->props(['alignText' => $align]);
    }
}
