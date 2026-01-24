<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTimelineItem - Naive UI 时间线项组件
 */
class NTimelineItem extends Component
{
    public function __construct()
    {
        parent::__construct('NTimelineItem');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function content(string $content): static
    {
        return $this->props(['content' => $content]);
    }

    public function time(string $time): static
    {
        return $this->props(['time' => $time]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }

    public function lineType(string $type): static
    {
        return $this->props(['lineType' => $type]);
    }
}
