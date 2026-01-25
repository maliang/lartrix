<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NConfigProvider - Naive UI 全局配置组件
 */
class ConfigProvider extends Component
{
    public function __construct()
    {
        parent::__construct('NConfigProvider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function theme(mixed $theme): static
    {
        return $this->props(['theme' => $theme]);
    }

    public function themeOverrides(array $overrides): static
    {
        return $this->props(['themeOverrides' => $overrides]);
    }

    public function locale(mixed $locale): static
    {
        return $this->props(['locale' => $locale]);
    }

    public function dateLocale(mixed $locale): static
    {
        return $this->props(['dateLocale' => $locale]);
    }

    public function abstract(bool $abstract = true): static
    {
        return $this->props(['abstract' => $abstract]);
    }

    public function breakpoints(array $breakpoints): static
    {
        return $this->props(['breakpoints' => $breakpoints]);
    }

    public function inlineThemeDisabled(bool $disabled = true): static
    {
        return $this->props(['inlineThemeDisabled' => $disabled]);
    }

    public function preflight(bool $preflight = true): static
    {
        return $this->props(['preflight' => $preflight]);
    }
}
