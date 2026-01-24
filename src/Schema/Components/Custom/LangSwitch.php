<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * LangSwitch - 语言切换组件
 */
class LangSwitch extends Component
{
    public function __construct()
    {
        parent::__construct('LangSwitch');
    }

    public static function make(): static
    {
        return new static();
    }

    public function langOptions(array $options): static
    {
        return $this->props(['langOptions' => $options]);
    }

    public function defaultLang(string $lang): static
    {
        return $this->props(['defaultLang' => $lang]);
    }

    public function submitUrl(string $url): static
    {
        return $this->props(['submitUrl' => $url]);
    }
}
