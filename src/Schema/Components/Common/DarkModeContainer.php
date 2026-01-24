<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * DarkModeContainer - trix 暗色模式容器组件
 */
class DarkModeContainer extends Component
{
    public function __construct()
    {
        parent::__construct('DarkModeContainer');
    }

    public static function make(): static
    {
        return new static();
    }

    public function inverted(bool $inverted = true): static
    {
        return $this->props(['inverted' => $inverted]);
    }
}
