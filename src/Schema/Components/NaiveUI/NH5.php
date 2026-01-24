<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NH5 - Naive UI 五级标题组件
 */
class NH5 extends Component
{
    public function __construct()
    {
        parent::__construct('NH5');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function prefix(string $prefix): static
    {
        return $this->props(['prefix' => $prefix]);
    }

    public function alignText(bool $align = true): static
    {
        return $this->props(['alignText' => $align]);
    }
}
