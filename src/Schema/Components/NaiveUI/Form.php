<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NForm - Naive UI 表单组件
 */
class Form extends Component
{
    public function __construct()
    {
        parent::__construct('NForm');
    }

    public static function make(): static
    {
        return new static();
    }

    public function model(array|string $model): static
    {
        // NForm 只支持字符串格式的 model
        if (is_array($model)) {
            $model = array_key_first($model);
        }
        return $this->props(['model' => "{{ $model }}"]);
    }

    public function rules(array|string $rules): static
    {
        return $this->props([
            'rules' => is_string($rules) ? "{{ $rules }}" : $rules
        ]);
    }

    public function labelWidth(int|string $width): static
    {
        return $this->props(['label-width' => $width]);
    }

    public function labelPlacement(string $placement): static
    {
        return $this->props(['label-placement' => $placement]);
    }

    public function labelAlign(string $align): static
    {
        return $this->props(['label-align' => $align]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function inline(bool $inline = true): static
    {
        return $this->props(['inline' => $inline]);
    }

    public function showFeedback(bool $show = true): static
    {
        return $this->props(['show-feedback' => $show]);
    }

    public function showLabel(bool $show = true): static
    {
        return $this->props(['show-label' => $show]);
    }

    public function showRequireMark(bool $show = true): static
    {
        return $this->props(['show-require-mark' => $show]);
    }

    public function requireMarkPlacement(string $placement): static
    {
        return $this->props(['require-mark-placement' => $placement]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }
}
