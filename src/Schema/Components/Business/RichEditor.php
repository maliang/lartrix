<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Component;

/**
 * RichEditor - trix 富文本编辑器组件
 */
class RichEditor extends Component
{
    public function __construct()
    {
        parent::__construct('RichEditor');
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

    public function toolbarConfig(array $config): static
    {
        return $this->props(['toolbar-config' => $config]);
    }

    public function editorConfig(array $config): static
    {
        return $this->props(['editor-config' => $config]);
    }
}
