<?php

namespace Lartrix\Schema\Components\Common;

use Lartrix\Schema\Components\Component;

/**
 * HeaderNotification - 头部通知组件
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

    public function badgeMode(string $mode): static
    {
        return $this->props(['badgeMode' => $mode]);
    }

    public function pageSize(int $size): static
    {
        return $this->props(['pageSize' => $size]);
    }

    public function enableWs(bool $enable): static
    {
        return $this->props(['enableWs' => $enable]);
    }

    public function enableNotification(bool $enable): static
    {
        return $this->props(['enableNotification' => $enable]);
    }

    public function notificationDuration(int $duration): static
    {
        return $this->props(['notificationDuration' => $duration]);
    }

    public function fetchApi(string $api): static
    {
        return $this->props(['fetchApi' => $api]);
    }

    public function readApi(string $api): static
    {
        return $this->props(['readApi' => $api]);
    }

    public function readAllApi(string $api): static
    {
        return $this->props(['readAllApi' => $api]);
    }

    public function tabs(array $tabs): static
    {
        return $this->props(['tabs' => $tabs]);
    }
}
