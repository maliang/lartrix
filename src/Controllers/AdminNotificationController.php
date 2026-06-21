<?php

namespace Lartrix\Controllers;

use Lartrix\Models\NotificationMessage;
use Lartrix\Models\NotificationCategory;
use Lartrix\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * 主后台通知控制器
 * 用于向二级后台发送通知
 */
class AdminNotificationController extends Controller
{
    /**
     * 发送通知给二级后台
     */
    public function sendToBackend(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|max:50',
            'category_key' => 'required|string|max:100',
            'target_guards' => 'nullable|array',
            'target_guards.*' => 'string|max:50',
            'user_id' => 'nullable|integer|exists:admin_users,id',
            'extra' => 'nullable|array',
        ]);

        $guards = $validated['target_guards'] ?? [];

        // 如果未指定目标 guards，则发送给所有非 admin 的 guard
        if (empty($guards)) {
            $guards = NotificationMessage::query()
                ->distinct()
                ->pluck('guard_name')
                ->filter(fn($g) => $g !== 'admin')
                ->toArray();

            // 如果没有任何二级后台，至少发送给默认的后端
            if (empty($guards)) {
                $guards = ['merchant', 'vendor']; // 默认的二级后台
            }
        }

        $created = [];
        foreach ($guards as $guard) {
            NotificationMessage::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'type' => $validated['type'],
                'category_key' => $validated['category_key'],
                'guard_name' => $guard,
                'user_id' => $validated['user_id'] ?? null, // 为空表示发送给该 guard 下所有用户
                'from_user_id' => $request->user()->id,
                'target_guards' => $guards,
                'extra' => $validated['extra'] ?? null,
            ]);
            $created[] = $guard;
        }

        return success(__t('notification.sent') . '：' . implode(', ', $created), [
            'guards' => $created,
            'count' => count($created),
        ]);
    }

    /**
     * 查看已发送的通知
     */
    public function sentNotifications(Request $request): array
    {
        $query = NotificationMessage::query()
            ->where('from_user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // 按 target_guards 筛选
        if ($request->filled('guard_name')) {
            $query->whereJsonContains('target_guards', $request->input('guard_name'));
        }

        $perPage = $request->input('page_size', 15);
        $paginator = $query->paginate($perPage);

        return success([
            'list' => collect($paginator->items())->map->toArray()->values()->all(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ]);
    }

    /**
     * 获取所有可用的 guard 列表
     */
    public function availableGuards(): array
    {
        $guards = NotificationMessage::query()
            ->distinct()
            ->pluck('guard_name')
            ->filter(fn($g) => $g !== 'admin')
            ->map(fn($g) => [
                'label' => $this->getGuardLabel($g),
                'value' => $g,
            ])
            ->values()
            ->toArray();

        // 添加默认的二级后台选项
        $defaultGuards = [
            ['label' => __t('admin.opt_merchant'), 'value' => 'merchant'],
            ['label' => __t('admin.opt_vendor'), 'value' => 'vendor'],
        ];

        $existingValues = array_column($guards, 'value');
        foreach ($defaultGuards as $guard) {
            if (!in_array($guard['value'], $existingValues)) {
                $guards[] = $guard;
            }
        }

        return success($guards);
    }

    /**
     * 获取所有通知分类（用于发送通知时选择）
     */
    public function categories(): array
    {
        $guard = config('lartrix.guard', 'admin');

        $categories = NotificationCategory::query()
            ->where('guard_name', $guard)
            ->where('enabled', true)
            ->orderBy('sort')
            ->get()
            ->map(fn($c) => [
                'label' => $c->name,
                'value' => $c->key,
                'icon' => $c->icon,
                'color' => $c->color,
            ])
            ->toArray();

        return success($categories);
    }

    /**
     * 获取 guard 显示名称
     */
    protected function getGuardLabel(string $guard): string
    {
        $labels = [
            'admin' => __t('admin.main_backend'),
            'merchant' => __t('admin.opt_merchant'),
            'vendor' => __t('admin.opt_vendor'),
            'agent' => __t('admin.opt_agent'),
        ];

        return $labels[$guard] ?? ucfirst($guard);
    }
}
