<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NHighlight - Naive UI 高亮文本组件
 */
class NHighlight extends Component
{
    public function __construct()
    {
        parent::__construct('NHighlight');
    }

    public static function make(): static
    {
        return new static();
    }

    public function text(string $text): static
    {
        return $this->props(['text' => $text]);
    }

    public function patterns(array $patterns): static
    {
        return $this->props(['patterns' => $patterns]);
    }

    public function caseSensitive(bool $sensitive = true): static
    {
        return $this->props(['caseSensitive' => $sensitive]);
    }

    public function autoEscape(bool $escape = true): static
    {
        return $this->props(['autoEscape' => $escape]);
    }

    public function highlightTag(string $tag): static
    {
        return $this->props(['highlightTag' => $tag]);
    }

    public function highlightClass(string $class): static
    {
        return $this->props(['highlightClass' => $class]);
    }

    public function highlightStyle(array|string $style): static
    {
        return $this->props(['highlightStyle' => $style]);
    }
}
