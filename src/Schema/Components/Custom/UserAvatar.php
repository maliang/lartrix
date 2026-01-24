<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * UserAvatar - 用户头像菜单组件
 */
class UserAvatar extends Component
{
    public function __construct()
    {
        parent::__construct('UserAvatar');
    }

    public static function make(): static
    {
        return new static();
    }

    public function menuItems(array $items): static
    {
        return $this->props(['menuItems' => $items]);
    }
}
