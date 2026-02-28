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
        return '通知消息';
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

        $perPage = $request->input('page_size', 15);
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return success([
            'items' => collect($paginator->items())->map(function($item) {
                return $item->toArray();
            })->values()->all(),
            'total' => $paginator->total(),
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
        $form = OptForm::make('formData')
            ->labelWidth(80)
            ->fields([
                ['标题', 'title', Input::make()->props(['placeholder' => '请输入通知标题', 'clearable' => true])],
                ['内容', 'content', Input::make()->props(['placeholder' => '请输入通知内容', 'clearable' => true, 'type' => 'textarea', 'rows' => 4])],
                ['消息类型', 'type', Select::make()->props([
                    'placeholder' => '请选择消息类型',
                    'options' => [
                        ['label' => '系统', 'value' => 'system'],
                        ['label' => '通知', 'value' => 'notice'],
                        ['label' => '消息', 'value' => 'message'],
                        ['label' => '待办', 'value' => 'todo'],
                    ],
                ])],
                ['所属分类', 'category_key', Select::make()->props([
                    'placeholder' => '请选择分类',
                    'options' => [],
                ])],
                ['接收用户', 'user_id', Select::make()->props([
                    'placeholder' => '指定用户（为空表示发送给所有人）',
                    'clearable' => true,
                ])],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('通知消息管理')
            ->apiPrefix('/notifications')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => '标题'],
                ['key' => 'content', 'title' => '内容', 'width' => 300, 'ellipsis' => true],
                ['key' => 'type', 'title' => '类型', 'slot' => [
                    Tag::make()->props(['type' => '{{ row.type === "system" ? "info" : (row.type === "notice" ? "warning" : (row.type === "todo" ? "error" : "success")) }}'])
                        ->children('{{ row.type }}'),
                ]],
                ['key' => 'category_key', 'title' => '分类'],
                ['key' => 'is_read', 'title' => '状态', 'slot' => [
                    Tag::make()->props([
                        'type' => "{{ row.is_read ? 'default' : 'warning' }}",
                    ])->children("{{ row.is_read ? '已读' : '未读' }}"),
                ]],
                ['key' => 'created_at', 'title' => '创建时间', 'width' => 180],
            ])
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索标题或内容', 'clearable' => true])],
                ['类型', 'type', Select::make()->props([
                    'placeholder' => '请选择类型',
                    'clearable' => true,
                    'options' => [
                        ['label' => '全部', 'value' => ''],
                        ['label' => '系统', 'value' => 'system'],
                        ['label' => '通知', 'value' => 'notice'],
                        ['label' => '消息', 'value' => 'message'],
                        ['label' => '待办', 'value' => 'todo'],
                    ],
                    'style' => ['min-width' => '150px'],
                ])],
                ['已读状态', 'is_read', Select::make()->props([
                    'placeholder' => '请选择',
                    'clearable' => true,
                    'options' => [
                        ['label' => '全部', 'value' => ''],
                        ['label' => '已读', 'value' => '1'],
                        ['label' => '未读', 'value' => '0'],
                    ],
                    'style' => ['min-width' => '150px'],
                ])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [SetAction::make('formVisible', true)])
                    ->text('发送通知'),
                Button::make()
                    ->type('info')
                    ->on('click', [FetchAction::make('/notifications/mark-all-read')->post()->then([
                        CallAction::make('$message.success', ['全部标记为已读']),
                        CallAction::make('loadData'),
                    ])])
                    ->text('全部已读'),
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
                            CallAction::make('$message.success', ['发送成功']),
                            SetAction::make('formVisible', false),
                            CallAction::make('loadData'),
                        ]),
                ],
            ])
            ->modal('form', '发送通知', $form);

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

        return success('已标记为已读');
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
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return success('全部标记为已读');
    }

    /**
     * 删除前回调
     */
    protected function beforeDelete(mixed $model): void
    {
        // 只能删除自己发送的通知
        $user = request()->user();
        if ($model->from_user_id !== $user->id && $user->guard_name !== 'admin') {
            throw new \Lartrix\Exceptions\ApiException('无权删除此通知', 403);
        }
    }
}
