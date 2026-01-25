<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NUploadTrigger - Naive UI 上传触发器组件
 */
class UploadTrigger extends Component
{
    public function __construct()
    {
        parent::__construct('NUploadTrigger');
    }

    public static function make(): static
    {
        return new static();
    }

    public function abstract(bool $abstract = true): static
    {
        return $this->props(['abstract' => $abstract]);
    }
}
