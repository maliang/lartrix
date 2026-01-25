<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NGrid - Naive UI 栅格组件
 */
class Grid extends Component
{
    public function __construct()
    {
        parent::__construct('NGrid');
    }

    public static function make(): static
    {
        return new static();
    }

    public function cols(int|string $cols): static
    {
        return $this->props(['cols' => $cols]);
    }

    public function xGap(int|string $gap): static
    {
        return $this->props(['x-gap' => $gap]);
    }

    public function yGap(int|string $gap): static
    {
        return $this->props(['y-gap' => $gap]);
    }

    public function responsive(string $responsive): static
    {
        return $this->props(['responsive' => $responsive]);
    }

    public function itemResponsive(bool $responsive = true): static
    {
        return $this->props(['item-responsive' => $responsive]);
    }

    public function collapsed(bool|string $collapsed = true): static
    {
        return $this->props(['collapsed' => $collapsed]);
    }

    public function collapsedRows(int $rows): static
    {
        return $this->props(['collapsed-rows' => $rows]);
    }
}
