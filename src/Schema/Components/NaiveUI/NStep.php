<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NStep - Naive UI 步骤项组件
 */
class NStep extends Component
{
    public function __construct()
    {
        parent::__construct('NStep');
    }

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function description(string $description): static
    {
        return $this->props(['description' => $description]);
    }

    public function status(string $status): static
    {
        return $this->props(['status' => $status]);
    }
}
