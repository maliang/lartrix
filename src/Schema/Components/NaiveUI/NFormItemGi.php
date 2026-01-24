<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NFormItemGi - Naive UI 表单项栅格组件
 */
class NFormItemGi extends Component
{
    public function __construct()
    {
        parent::__construct('NFormItemGi');
    }

    public static function make(): static
    {
        return new static();
    }

    public function label(string $label): static
    {
        return $this->props(['label' => $label]);
    }

    public function path(string $path): static
    {
        return $this->props(['path' => $path]);
    }

    public function span(int|string $span): static
    {
        return $this->props(['span' => $span]);
    }

    public function offset(int $offset): static
    {
        return $this->props(['offset' => $offset]);
    }

    public function rule(array $rule): static
    {
        return $this->props(['rule' => $rule]);
    }

    public function required(bool $required = true): static
    {
        return $this->props(['required' => $required]);
    }

    public function showFeedback(bool $show = true): static
    {
        return $this->props(['showFeedback' => $show]);
    }

    public function showLabel(bool $show = true): static
    {
        return $this->props(['showLabel' => $show]);
    }

    public function showRequireMark(bool|string $show = true): static
    {
        return $this->props(['showRequireMark' => $show]);
    }

    public function labelWidth(int|string $width): static
    {
        return $this->props(['labelWidth' => $width]);
    }

    public function labelAlign(string $align): static
    {
        return $this->props(['labelAlign' => $align]);
    }

    public function labelPlacement(string $placement): static
    {
        return $this->props(['labelPlacement' => $placement]);
    }

    public function labelStyle(array|string $style): static
    {
        return $this->props(['labelStyle' => $style]);
    }

    public function first(bool $first = true): static
    {
        return $this->props(['first' => $first]);
    }

    public function validationStatus(string $status): static
    {
        return $this->props(['validationStatus' => $status]);
    }

    public function feedback(string $feedback): static
    {
        return $this->props(['feedback' => $feedback]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function ignorePathChange(bool $ignore = true): static
    {
        return $this->props(['ignorePathChange' => $ignore]);
    }
}
