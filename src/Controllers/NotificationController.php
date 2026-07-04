<?php

namespace Lartrix\Controllers;

use Lartrix\Models\NotificationMessage;
use Lartrix\Models\NotificationCategory;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Lartrix\Schema\Components\NaiveUI\{Input, SwitchC, Button, Space, Select, Tag, DataTable};
use Lartrix\Schema\Components\Business\{CrudPage, OptForm};
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction, IfAction};

/**
 * 通知消息控制器
 */
use Lartrix\Services\RealtimeService;

class NotificationController extends CrudController
{
    /**
     * 获取模型类名
     */
    protected function getModelClass(): string
    {
        return NotificationMessage::class;
    }

    /**
     * 获取资源名称
     */
    protected function getResourceName(): string
    {
        return __t('notification.resource_name');
    }

    /**
     * 获取默认排序
     */
    protected function getDefaultOrder(): array
    {
        return ['created_at', 'desc'];
    }

    /**
     * 应用筛选条件
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        $user = $request->user();
        $guard = config('lartrix.guard', 'admin');

        // 基础过滤：只查询当前 guard 的数据
        $query->where('guard_name', $guard);

        // 主后台可以查看所有用户的通知，二级后台用户只能查看自己的
        if ($guard !== 'admin') {
            $query->where(function($q) use ($user) {
                $q->whereNull('user_id')  // 发给所有用户
                  ->orWhere('user_id', $user->id); // 发给当前用户
            });
        }

        // 按分类筛选
        if ($request->filled('category_key')) {
            $query->where('category_key', $request->input('category_key'));
        }

        // 按类型筛选
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $types = $this->normalizeTypes($request->input('types'));
        if (!empty($types)) {
            $query->whereIn('type', $types);
        }

        // 按已读状态筛选
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // 按标题搜索
        if ($request->filled('keyword')) {
            $query->where('title', 'like', '%' . $request->input('keyword') . '%');
        }
    }

    /**
     * 获取当前用户的通知列表（重写 list 方法）
     */
    protected function list(Request $request): array
    {
        $user = $request->user();
        $guard = config('lartrix.guard', 'admin');

        $query = NotificationMessage::query()
            ->with('category') // 预加载分类信息
            ->where('guard_name', $guard)
            ->where(function($q) use ($user) {
                $q->whereNull('user_id')  // 发给所有用户
                  ->orWhere('user_id', $user->id); // 发给当前用户
            });

        // 按分类筛选
        if ($request->filled('category_key')) {
            $query->where('category_key', $request->input('category_key'));
        }

        // 按类型筛选
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // 按已读状态筛选
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $perPage = $request->input('page_size', $request->input('pageSize', 15));
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return success([
            'list' => collect($paginator->items())->map(function($item) {
                return $item->toArray();
            })->values()->all(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
            'pageSize' => $paginator->perPage(),
        ]);
    }

    /**
     * 获取创建验证规则
     */
    protected function getStoreRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|max:50',
            'category_key' => 'required|string|max:100',
            'user_id' => 'nullable|integer|exists:admin_users,id',
            'extra' => 'nullable|array',
        ];
    }

    /**
     * 准备创建数据
     */
    protected function prepareStoreData(array $validated): array
    {
        $validated['guard_name'] = config('lartrix.guard', 'admin');
        $validated['from_user_id'] = request()->user()->id;
        $validated['is_read'] = false;

        return $validated;
    }

    /**
     * 列表页 UI Schema
     */
    protected function listUi(): array
    {
        // 获取分类选项
        $categoryOptions = array_merge(
            [['label' => __t('role.filter_all'), 'value' => '']],
            \Lartrix\Models\NotificationCategory::query()
                ->where('guard_name', 'admin')
                ->where('enabled', true)
                ->orderBy('sort')
                ->get()
                ->map(fn($c) => ['label' => $c->name, 'value' => $c->key])
                ->toArray()
        );

        $form = OptForm::make('formData')
            ->labelWidth(80)
            ->fields([
                [__t('column.title'), 'title', Input::make()->props(['placeholder' => __t('placeholder.notification_title'), 'clearable' => true])],
                [__t('column.content'), 'content', Input::make()->props(['placeholder' => __t('placeholder.notification_content'), 'clearable' => true, 'type' => 'textarea', 'rows' => 4])],
                [__t('column.type'), 'type', Select::make()->props([
                    'placeholder' => __t('placeholder.message_type'),
                    'options' => [
                        ['label' => __t('notification.type_system'), 'value' => 'system'],
                        ['label' => __t('notification.type_notice'), 'value' => 'notice'],
                        ['label' => __t('notification.type_message'), 'value' => 'message'],
                        ['label' => __t('notification.type_todo'), 'value' => 'todo'],
                    ],
                ]), 'system'],
                [__t('column.category'), 'category_key', Select::make()->props([
                    'placeholder' => __t('placeholder.category'),
                    'options' => $categoryOptions,
                ])],
                [__t('column.target_users'), 'user_id', Select::make()->props([
                    'placeholder' => __t('placeholder.target_users'),
                    'clearable' => true,
                ])],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('button.cancel')),
                Button::make()->type('primary')->on('click', ['call' => 'handleSubmit'])->text(__t('button.confirm')),
            ]);

        $schema = CrudPage::make(__t('title.notification'))
            ->apiPrefix('/notifications')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => __t('column.title')],
                ['key' => 'content', 'title' => __t('column.content'), 'width' => 300, 'ellipsis' => true],
                ['key' => 'category', 'title' => __t('column.category'), 'slot' => [
                    Tag::make()->props(['style' => '{{ "background-color:" + (slotData.row.category?.color || "#ccc") + ";color:#fff" }}'])->children('{{ slotData.row.category?.name || slotData.row.categoryKey || "-" }}'),
                ]],
                ['key' => 'is_read', 'title' => __t('column.status'), 'slot' => [
                    Tag::make()->props([
                        'type' => "{{ slotData.row.isRead ? 'default' : 'warning' }}",
                    ])->children("{{ slotData.row.isRead ? __t('tag.read') : __t('tag.unread') }}"),
                ]],
                ['key' => 'created_at', 'title' => __t('column.created_at'), 'width' => 180],
            ])
            ->search([
                [__t('search.keyword'), 'keyword', Input::make()->props(['placeholder' => __t('search.title_or_content'), 'clearable' => true])],
                [__t('column.category'), 'category_key', Select::make()->props([
                    'placeholder' => __t('placeholder.category'),
                    'clearable' => true,
                    'options' => $categoryOptions,
                    'style' => ['min-width' => '150px'],
                ])],
                [__t('search.read_status'), 'is_read', Select::make()->props([
                    'placeholder' => __t('placeholder.select'),
                    'clearable' => true,
                    'options' => [
                        ['label' => __t('role.filter_all'), 'value' => ''],
                        ['label' => __t('tag.read'), 'value' => '1'],
                        ['label' => __t('tag.unread'), 'value' => '0'],
                    ],
                    'style' => ['min-width' => '150px'],
                ])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [SetAction::make('formVisible', true)])
                    ->text(__t('title.notif_send')),
                Button::make()
                    ->type('info')
                    ->on('click', [FetchAction::make('/notifications/mark-all-read')->post()->then([
                        CallAction::make('$message.success', [__t('notification.all_marked_read')]),
                        CallAction::make('loadData'),
                    ])])
                    ->text(__t('notification.mark_all_read')),
            ])
            ->data([
                'formData' => $form->getDefaultData(),
            ])
            ->methods([
                'handleSubmit' => [
                    FetchAction::make('/notifications')
                        ->post()
                        ->body('{{ formData }}')
                        ->then([
                            CallAction::make('$message.success', [__t('notification.sent')]),
                            SetAction::make('formVisible', false),
                            CallAction::make('loadData'),
                        ]),
                ],
            ])
            ->modal('form', __t('title.notif_send'), $form);

        return success($schema->build());
    }

    /**
     * 标记已读
     */
    public function markAsRead(Request $request, int $id): array
    {
        $user = $request->user();
        $guard = config('lartrix.guard', 'admin');

        $message = NotificationMessage::where('guard_name', $guard)
            ->where(function($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->findOrFail($id);

        $message->is_read = true;
        $message->read_at = now();
        $message->save();

        return success(__t('notification.marked_read'));
    }

    /**
     * 全部已读
     */
    public function markAllAsRead(Request $request): array
    {
        $user = $request->user();
        $guard = config('lartrix.guard', 'admin');

        NotificationMessage::query()
            ->where('guard_name', $guard)
            ->where(function($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->where('is_read', false)
            ->when(!empty($types = $this->normalizeTypes($request->input('types'))), function ($q) use ($types) {
                $q->whereIn('type', $types);
            })
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return success(__t('notification.all_marked_read'));
    }

    /**
     * 删除前回调
     */
    protected function beforeDelete(mixed $model): void
    {
        // 只能删除自己发送的通知
        $user = request()->user();
        if ($model->from_user_id !== $user->id && $user->guard_name !== 'admin') {
            throw new \Lartrix\Exceptions\ApiException(__t('notification.not_owner'), 403);
        }
    }

    /**
     * 消息轮询接口
     * GET /notifications/poll?since_id=0&type=all
     */
    public function poll(Request $request): array
    {
        $user = $request->user();
        $guard = config('lartrix.guard', 'admin');
        $sinceId = (int) $request->input('since_id', 0);
        $type = $request->input('type', 'all');

        $realtime = app(RealtimeService::class);
        $data = $realtime->buildPollResponse($user->id, $guard, $sinceId, $type);

        return success($data);
    }

    protected function normalizeTypes(mixed $types): array
    {
        if (is_string($types)) {
            $types = explode(',', $types);
        }

        if (!is_array($types)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($type) => trim((string) $type),
            $types
        )));
    }
}
