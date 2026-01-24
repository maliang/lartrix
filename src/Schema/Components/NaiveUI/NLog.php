<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NLog - Naive UI 日志组件
 */
class NLog extends Component
{
    public function __construct()
    {
        parent::__construct('NLog');
    }

    public static function make(): static
    {
        return new static();
    }

    public function log(string $log): static
    {
        return $this->props(['log' => $log]);
    }

    public function language(string $language): static
    {
        return $this->props(['language' => $language]);
    }

    public function rows(int $rows): static
    {
        return $this->props(['rows' => $rows]);
    }

    public function loading(bool $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }

    public function trim(bool $trim = true): static
    {
        return $this->props(['trim' => $trim]);
    }

    public function hljs(mixed $hljs): static
    {
        return $this->props(['hljs' => $hljs]);
    }

    public function lineHeight(int $height): static
    {
        return $this->props(['lineHeight' => $height]);
    }
}
