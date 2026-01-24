<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NInput - Naive UI 输入框组件
 */
class NInput extends Component
{
    public function __construct()
    {
        parent::__construct('NInput');
    }

    public static function make(): static
    {
        return new static();
    }

    public function placeholder(string $text): static
    {
        return $this->props(['placeholder' => $text]);
    }

    public function type(string $type): static
    {
        return $this->props(['type' => $type]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function clearable(bool $clearable = true): static
    {
        return $this->props(['clearable' => $clearable]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function readonly(bool $readonly = true): static
    {
        return $this->props(['readonly' => $readonly]);
    }

    public function showPasswordOn(string $trigger): static
    {
        return $this->props(['show-password-on' => $trigger]);
    }

    public function maxlength(int $length): static
    {
        return $this->props(['maxlength' => $length]);
    }

    public function showCount(bool $show = true): static
    {
        return $this->props(['show-count' => $show]);
    }

    public function rows(int $rows): static
    {
        return $this->props(['rows' => $rows]);
    }

    public function autosize(bool|array $autosize = true): static
    {
        return $this->props(['autosize' => $autosize]);
    }
}
