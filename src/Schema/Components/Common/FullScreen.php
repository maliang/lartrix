<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * FullScreen - trix 全屏切换组件
 */
class FullScreen extends Component
{
    public function __construct()
    {
        parent::__construct('FullScreen');
    }

    public static function make(): static
    {
        return new static();
    }

    public function full(bool|string $full = true): static
    {
        return $this->props(['full' => $full]);
    }
}
