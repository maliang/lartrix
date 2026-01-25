<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDialogProvider - Naive UI 对话框提供者组件
 */
class DialogProvider extends Component
{
    public function __construct()
    {
        parent::__construct('NDialogProvider');
    }

    public static function make(): static
    {
        return new static();
    }

    public function to(string $target): static
    {
        return $this->props(['to' => $target]);
    }
}
