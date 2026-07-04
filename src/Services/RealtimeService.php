<?php

namespace Lartrix\Services;

use Lartrix\Models\NotificationMessage;

/**
 * RealtimeService - 实时消息服务
 *
 * 提供通知轮询/WS 的数据接口，开发者可继承此类重写方法
 * 实现自定义的实时消息逻辑（如接入第三方推送、自定义 WS 协议等）
 */
class RealtimeService
{
    /**
     * 获取当前用户的新消息（用于轮询）
     */
    public function getNewMessages(int $userId, string $guard, int $sinceId = 0, string $type = ''): array
    {
        $query = NotificationMessage::where('guard_name', $guard)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->where('id', '>', $sinceId)
            // 轮询只提示未读消息：已读消息（可能在其他会话/设备已读）不应再次弹窗/触发 behaviors
            ->where('is_read', false);

        if ($type && $type !== 'all') {
            $query->where('category_key', $type);
        }

        return $query->orderBy('id', 'desc')->limit(10)->get()->toArray();
    }

    /**
     * 获取未读消息数量
     */
    public function getUnreadCount(int $userId, string $guard): int
    {
        return NotificationMessage::where('guard_name', $guard)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->where('is_read', false)
            ->count();
    }

    /**
     * 获取按消息类型聚合的未读数量
     */
    public function getUnreadCountByType(int $userId, string $guard): array
    {
        return NotificationMessage::where('guard_name', $guard)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->where('is_read', false)
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * 组装轮询响应
     */
    public function buildPollResponse(int $userId, string $guard, int $sinceId = 0, string $type = ''): array
    {
        $messages = $this->getNewMessages($userId, $guard, $sinceId, $type);
        $unreadCount = $this->getUnreadCount($userId, $guard);
        $unreadCountByType = $this->getUnreadCountByType($userId, $guard);
        $unreadCountByType['all'] = $unreadCount;

        return [
            'messages' => $messages,
            'unread_count' => $unreadCount,
            'unread_count_by_type' => $unreadCountByType,
            'has_new' => !empty($messages),
            'server_time' => now()->toDateTimeString(),
        ];
    }
}
