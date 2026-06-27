<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

class HeaderCustomItem extends Component
{
    public function __construct()
    {
        parent::__construct('HeaderCustomItem');
    }

    public static function make(): static
    {
        return new static();
    }

    public function icon(string $name): static
    {
        return $this->props(['icon' => $name]);
    }

    public function tooltip(string $text): static
    {
        return $this->props(['tooltip' => $text]);
    }

    public function badge(array $config): static
    {
        return $this->props(['badge' => $config]);
    }

    public function click(string $type): static
    {
        return $this->props(['click' => $type]);
    }

    public function clickTarget(string $target): static
    {
        return $this->props(['clickTarget' => $target]);
    }

    public function target(string $target): static
    {
        return $this->props(['target' => $target]);
    }

    public function schemaApi(string $api): static
    {
        return $this->props(['schemaApi' => $api]);
    }
}
