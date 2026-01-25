<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * VueECharts - ECharts 图表组件
 */
class VueECharts extends Component
{
    public function __construct()
    {
        parent::__construct('VueECharts');
    }

    public static function make(): static
    {
        return new static();
    }

    public function option(array $option): static
    {
        return $this->props(['option' => $option]);
    }

    public function autoresize(bool $autoresize = true): static
    {
        return $this->props(['autoresize' => $autoresize]);
    }

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }

    public function theme(string $theme): static
    {
        return $this->props(['theme' => $theme]);
    }
}
