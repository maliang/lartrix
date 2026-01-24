<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NModal - Naive UI 模态框组件
 */
class NModal extends Component
{
    public function __construct()
    {
        parent::__construct('NModal');
    }

    public static function make(): static
    {
        return new static();
    }

    public function show(string $show): static
    {
        return $this->props(['show' => "{{ $show }}"]);
    }

    public function title(string $title): static
    {
        return $this->props(['title' => $title]);
    }

    public function preset(string $preset): static
    {
        return $this->props(['preset' => $preset]);
    }

    public function maskClosable(bool $closable = true): static
    {
        return $this->props(['mask-closable' => $closable]);
    }

    public function closeOnEsc(bool $close = true): static
    {
        return $this->props(['close-on-esc' => $close]);
    }

    public function transformOrigin(string $origin): static
    {
        return $this->props(['transform-origin' => $origin]);
    }

    public function displayDirective(string $directive): static
    {
        return $this->props(['display-directive' => $directive]);
    }

    public function autoFocus(bool $focus = true): static
    {
        return $this->props(['auto-focus' => $focus]);
    }

    public function trapFocus(bool $trap = true): static
    {
        return $this->props(['trap-focus' => $trap]);
    }

    public function style(string|array $style): static
    {
        return $this->props(['style' => $style]);
    }
}
