<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NPagination - Naive UI 分页组件
 */
class Pagination extends Component
{
    public function __construct()
    {
        parent::__construct('NPagination');
    }

    public static function make(): static
    {
        return new static();
    }

    public function page(int|string $page): static
    {
        return $this->props([
            'page' => is_string($page) ? "{{ $page }}" : $page
        ]);
    }

    public function pageSize(int|string $size): static
    {
        return $this->props([
            'page-size' => is_string($size) ? "{{ $size }}" : $size
        ]);
    }

    public function itemCount(int|string $count): static
    {
        return $this->props([
            'item-count' => is_string($count) ? "{{ $count }}" : $count
        ]);
    }

    public function pageCount(int|string $count): static
    {
        return $this->props([
            'page-count' => is_string($count) ? "{{ $count }}" : $count
        ]);
    }

    public function pageSizes(array $sizes): static
    {
        return $this->props(['page-sizes' => $sizes]);
    }

    public function showSizePicker(bool $show = true): static
    {
        return $this->props(['show-size-picker' => $show]);
    }

    public function showQuickJumper(bool $show = true): static
    {
        return $this->props(['show-quick-jumper' => $show]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function simple(bool $simple = true): static
    {
        return $this->props(['simple' => $simple]);
    }

    public function displayOrder(array $order): static
    {
        return $this->props(['display-order' => $order]);
    }

    public function prefix(string $prefix): static
    {
        return $this->props(['prefix' => "{{ $prefix }}"]);
    }

    public function suffix(string $suffix): static
    {
        return $this->props(['suffix' => "{{ $suffix }}"]);
    }
}
