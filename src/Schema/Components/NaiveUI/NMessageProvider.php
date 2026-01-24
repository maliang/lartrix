<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NMessageProvider - Naive UI 信息提供者组件
 */
class NMessageProvider extends Component
{
    public function __construct()
    {
        parent::__construct('NMessageProvider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function to(string $target): static
    {
        return $this->props(['to' => $target]);
    }

    public function duration(int $duration): static
    {
        return $this->props(['duration' => $duration]);
    }

    public function keepAliveOnHover(bool $keep = true): static
    {
        return $this->props(['keepAliveOnHover' => $keep]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function placement(string $placement): static
    {
        return $this->props(['placement' => $placement]);
    }

    public function closable(bool $closable = true): static
    {
        return $this->props(['closable' => $closable]);
    }
}
