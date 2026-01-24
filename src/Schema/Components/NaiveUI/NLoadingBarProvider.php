<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NLoadingBarProvider - Naive UI 加载条提供者组件
 */
class NLoadingBarProvider extends Component
{
    public function __construct()
    {
        parent::__construct('NLoadingBarProvider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function to(string $target): static
    {
        return $this->props(['to' => $target]);
    }

    public function containerStyle(array|string $style): static
    {
        return $this->props(['containerStyle' => $style]);
    }

    public function loadingBarStyle(array $style): static
    {
        return $this->props(['loadingBarStyle' => $style]);
    }
}
