<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NText - Naive UI 文本组件
 */
class Text extends Component
{
    public function __construct()
    {
        parent::__construct('NText');
    }

    public static function make(): static
    {
        return new static();
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function strong(bool $strong = true): static
    {
        return $this->props(['strong' => $strong]);
    }

    public function italic(bool $italic = true): static
    {
        return $this->props(['italic' => $italic]);
    }

    public function underline(bool $underline = true): static
    {
        return $this->props(['underline' => $underline]);
    }

    public function delete(bool $delete = true): static
    {
        return $this->props(['delete' => $delete]);
    }

    public function code(bool $code = true): static
    {
        return $this->props(['code' => $code]);
    }

    public function depth(int|string $depth): static
    {
        return $this->props(['depth' => $depth]);
    }

    public function tag(string $tag): static
    {
        return $this->props(['tag' => $tag]);
    }
}
