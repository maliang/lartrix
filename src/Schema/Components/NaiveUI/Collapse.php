<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCollapse - Naive UI 折叠面板组件
 */
class Collapse extends Component
{
    public function __construct()
    {
        parent::__construct('NCollapse');
    }

    public static function make(): static
    {
        return new static();
    }

    public function accordion(bool $accordion = true): static
    {
        return $this->props(['accordion' => $accordion]);
    }

    public function arrowPlacement(string $placement): static
    {
        return $this->props(['arrowPlacement' => $placement]);
    }

    public function displayDirective(string $directive): static
    {
        return $this->props(['displayDirective' => $directive]);
    }

    public function triggerAreas(array $areas): static
    {
        return $this->props(['triggerAreas' => $areas]);
    }
}
