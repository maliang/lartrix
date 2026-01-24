<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCode - Naive UI 代码组件
 */
class NCode extends Component
{
    public function __construct()
    {
        parent::__construct('NCode');
    }

    public static function make(): static
    {
        return new static();
    }

    public function code(string $code): static
    {
        return $this->props(['code' => $code]);
    }

    public function language(string $language): static
    {
        return $this->props(['language' => $language]);
    }

    public function trim(bool $trim = true): static
    {
        return $this->props(['trim' => $trim]);
    }

    public function hljs(mixed $hljs): static
    {
        return $this->props(['hljs' => $hljs]);
    }

    public function wordWrap(bool $wrap = true): static
    {
        return $this->props(['wordWrap' => $wrap]);
    }

    public function showLineNumbers(bool $show = true): static
    {
        return $this->props(['showLineNumbers' => $show]);
    }
}
