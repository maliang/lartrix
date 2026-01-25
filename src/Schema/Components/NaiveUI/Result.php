<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NResult - Naive UI 结果页组件
 */
class Result extends Component
{
    public function __construct()
    {
        parent::__construct('NResult');
    }

    public static function make(): static
    {
        return new static();
    }

    public function status(string $status): static
    {
        return $this->props(['status' => $status]);
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function description(string $description): static
    {
        return $this->props(['description' => $description]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }
}
