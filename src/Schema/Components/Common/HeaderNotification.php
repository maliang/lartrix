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

    /**
     * 获取消息列表的 API 端点
     */
    public function fetchApi(string $api): static
    {
        return $this->props(['fetchApi' => $api]);
    }

    /**
     * 标记单条消息为已读的 API 端点
     */
    public function readApi(string $api): static
    {
        return $this->props(['readApi' => $api]);
    }

    /**
     * 标记所有消息为已读的 API 端点
     */
    public function readAllApi(string $api): static
    {
        return $this->props(['readAllApi' => $api]);
    }

    /**
     * 静态标签页配置
     * @param array $tabs NotificationTabConfig[] 格式：[['key' => 'all', 'label' => '全部', 'icon' => 'ph:bell', 'types' => []]]
     */
    public function tabs(array $tabs): static
    {
        return $this->props(['tabs' => $tabs]);
    }

    /**
     * 标签页未读徽章背景色
     */
    public function tabBadgeColor(string $color): static
    {
        return $this->props(['tabBadgeColor' => $color]);
    }

    /**
     * 是否启用详情展示
     */
    public function enableDetail(bool $enable): static
    {
        return $this->props(['enableDetail' => $enable]);
    }

    /**
     * WebSocket 连接地址
     */
    public function wsUrl(string $url): static
    {
        return $this->props(['wsUrl' => $url]);
    }
}
