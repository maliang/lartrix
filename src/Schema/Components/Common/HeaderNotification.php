<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * HeaderNotification - trix 头部通知组件
 */
class HeaderNotification extends Component
{
    public function __construct()
    {
        parent::__construct('HeaderNotification');
    }

    public static function make(): static
    {
        return new static();
    }
}
