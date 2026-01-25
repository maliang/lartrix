<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NThing - Naive UI 东西组件
 */
class Thing extends Component
{
    public function __construct()
    {
        parent::__construct('NThing');
    }

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function titleExtra(string $extra): static
    {
        return $this->props(['titleExtra' => $extra]);
    }

    public function description(string $description): static
    {
        return $this->props(['description' => $description]);
    }

    public function contentStyle(array|string $style): static
    {
        return $this->props(['contentStyle' => $style]);
    }

    public function contentIndented(bool $indented = true): static
    {
        return $this->props(['contentIndented' => $indented]);
    }
}
