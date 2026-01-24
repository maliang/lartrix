<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Component;

/**
 * FlowEditor - trix 流程图编辑器组件
 */
class FlowEditor extends Component
{
    public function __construct()
    {
        parent::__construct('FlowEditor');
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
