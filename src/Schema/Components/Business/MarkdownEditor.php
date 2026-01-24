<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Component;

/**
 * MarkdownEditor - trix Markdown 编辑器组件
 */
class MarkdownEditor extends Component
{
    public function __construct()
    {
        parent::__construct('MarkdownEditor');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(string $value): static
    {
        return $this->props(['value' => "{{ $value }}"]);
    }

    public function height(int|string $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function placeholder(string $placeholder): static
    {
        return $this->props(['placeholder' => $placeholder]);
    }

    public function readonly(bool $readonly = true): static
    {
        return $this->props(['readonly' => $readonly]);
    }

    public function preview(bool $preview = true): static
    {
        return $this->props(['preview' => $preview]);
    }

    public function toolbars(array $toolbars): static
    {
        return $this->props(['toolbars' => $toolbars]);
    }
}
