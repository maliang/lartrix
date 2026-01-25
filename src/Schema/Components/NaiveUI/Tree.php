<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NTree - Naive UI 树组件
 */
class Tree extends Component
{
    public function __construct()
    {
        parent::__construct('NTree');
    }

    public static function make(): static
    {
        return new static();
    }

    public function data(array|string $data): static
    {
        return $this->props([
            'data' => is_string($data) ? "{{ $data }}" : $data
        ]);
    }

    public function checkable(bool $checkable = true): static
    {
        return $this->props(['checkable' => $checkable]);
    }

    public function selectable(bool $selectable = true): static
    {
        return $this->props(['selectable' => $selectable]);
    }

    public function multiple(bool $multiple = true): static
    {
        return $this->props(['multiple' => $multiple]);
    }

    public function cascade(bool $cascade = true): static
    {
        return $this->props(['cascade' => $cascade]);
    }

    public function checkStrategy(string $strategy): static
    {
        return $this->props(['check-strategy' => $strategy]);
    }

    public function checkedKeys(array|string $keys): static
    {
        return $this->props([
            'checked-keys' => is_string($keys) ? "{{ $keys }}" : $keys
        ]);
    }

    public function selectedKeys(array|string $keys): static
    {
        return $this->props([
            'selected-keys' => is_string($keys) ? "{{ $keys }}" : $keys
        ]);
    }

    public function expandedKeys(array|string $keys): static
    {
        return $this->props([
            'expanded-keys' => is_string($keys) ? "{{ $keys }}" : $keys
        ]);
    }

    public function defaultExpandAll(bool $expand = true): static
    {
        return $this->props(['default-expand-all' => $expand]);
    }

    public function blockLine(bool $block = true): static
    {
        return $this->props(['block-line' => $block]);
    }

    public function blockNode(bool $block = true): static
    {
        return $this->props(['block-node' => $block]);
    }

    public function draggable(bool $draggable = true): static
    {
        return $this->props(['draggable' => $draggable]);
    }

    public function keyField(string $field): static
    {
        return $this->props(['key-field' => $field]);
    }

    public function labelField(string $field): static
    {
        return $this->props(['label-field' => $field]);
    }

    public function childrenField(string $field): static
    {
        return $this->props(['children-field' => $field]);
    }

    public function pattern(string $pattern): static
    {
        return $this->props(['pattern' => "{{ $pattern }}"]);
    }

    public function showIrrelevantNodes(bool $show = true): static
    {
        return $this->props(['show-irrelevant-nodes' => $show]);
    }
}
