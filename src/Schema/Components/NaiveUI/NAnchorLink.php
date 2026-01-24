<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NAnchorLink - Naive UI 锚点链接组件
 */
class NAnchorLink extends Component
{
    public function __construct()
    {
        parent::__construct('NAnchorLink');
    }

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function href(string $href): static
    {
        return $this->props(['href' => $href]);
    }
}
