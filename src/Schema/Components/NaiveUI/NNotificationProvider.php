<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NNotificationProvider - Naive UI 通知提供者组件
 */
class NNotificationProvider extends Component
{
    public function __construct()
    {
        parent::__construct('NNotificationProvider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function to(string $target): static
    {
        return $this->props(['to' => $target]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function placement(string $placement): static
    {
        return $this->props(['placement' => $placement]);
    }

    public function keepAliveOnHover(bool $keep = true): static
    {
        return $this->props(['keepAliveOnHover' => $keep]);
    }

    public function containerStyle(array|string $style): static
    {
        return $this->props(['containerStyle' => $style]);
    }
}
