<?php

namespace Lartrix\Schema\Components\Json;

use Lartrix\Schema\Components\Component;

/**
 * SchemaEditor - trix Schema 编辑器组件
 */
class SchemaEditor extends Component
{
    public function __construct()
    {
        parent::__construct('SchemaEditor');
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

    public function readonly(bool $readonly = true): static
    {
        return $this->props(['readonly' => $readonly]);
    }
}
