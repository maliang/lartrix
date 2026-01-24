<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NSteps - Naive UI 步骤条组件
 */
class NSteps extends Component
{
    public function __construct()
    {
        parent::__construct('NSteps');
    }

    public static function make(): static
    {
        return new static();
    }

    public function current(int $current): static
    {
        return $this->props(['current' => $current]);
    }

    public function status(string $status): static
    {
        return $this->props(['status' => $status]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function vertical(bool $vertical = true): static
    {
        return $this->props(['vertical' => $vertical]);
    }
}
