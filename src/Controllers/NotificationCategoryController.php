<?php

namespace Lartrix\Controllers;

use Lartrix\Models\NotificationCategory;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Lartrix\Schema\Components\NaiveUI\{Input, SwitchC, Button, Space, Select, Tag, Popconfirm};
use Lartrix\Schema\Components\Business\{CrudPage, OptForm};
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\Custom\Icon;
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};

/**
 * 通知分类控制器
 */
class NotificationCategoryController extends CrudController
{
    /**
     * 获取模型类名
     */
    protected function getModelClass(): string
    {
        return NotificationCategory::class;
    }

    /**
     * 获取资源名称
     */
    protected function getResourceName(): string
    {
        return '通知分类';
    }

    /**
     * 获取默认排序
     */
    protected function getDefaultOrder(): array
    {
        return ['sort', 'asc'];
    }

    /**
     * 应用筛选条件
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        $guard = config('lartrix.guard', 'admin');

        // 主后台可以查看所有 guard 的分类，二级后台只能查看自己的
        if ($guard !== 'admin') {
            $query->where('guard_name', $guard);
        }

        // 按启用状态筛选
        if ($request->filled('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        // 按名称搜索
        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('keyword') . '%')
                  ->orWhere('key', 'like', '%' . $request->input('keyword') . '%');
            });
        }
    }

    /**
     * 获取创建验证规则
     */
    protected function getStoreRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:100',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'sort' => 'nullable|integer',
            'message_types' => 'nullable|array',
            'guard_name' => 'required|string|max:50',
            'enabled' => 'nullable|boolean',
        ];
    }

    /**
     * 获取更新验证规则
     */
    protected function getUpdateRules(int $id): array
    {
        $rules = $this->getStoreRules();
        $rules['key'] = 'required|string|max:100|unique:notification_categories,key,' . $id;
        return $rules;
    }

    /**
     * 准备创建数据
     */
    protected function prepareStoreData(array $validated): array
    {
        // 如果是主后台创建，默认设置 guard_name 为 admin
        if (!isset($validated['guard_name'])) {
            $validated['guard_name'] = 'admin';
        }

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
                ['分类名称', 'name', Input::make()->props(['placeholder' => '请输入分类名称', 'clearable' => true])],
                ['分类标识', 'key', Input::make()->props(['placeholder' => '请输入分类标识 (如：system)', 'clearable' => true])],
                ['图标', 'icon', Input::make()->props(['placeholder' => '请输入图标 (如：ph:bell)', 'clearable' => true])],
                ['颜色', 'color', Input::make()->props(['placeholder' => '请输入颜色 (如：#18a058)', 'type' => 'color']), '#18a058'],
                ['消息类型', 'message_types', Select::make()->props([
                    'placeholder' => '请选择消息类型',
                    'multiple' => true,
                    'options' => [
                        ['label' => '系统', 'value' => 'system'],
                        ['label' => '通知', 'value' => 'notice'],
                        ['label' => '消息', 'value' => 'message'],
                        ['label' => '待办', 'value' => 'todo'],
                    ],
                ]), ['system']],
                ['所属后台', 'guard_name', Select::make()->props([
                    'placeholder' => '请选择所属后台',
                    'options' => [
                        ['label' => '主后台', 'value' => 'admin'],
                        ['label' => '商户后台', 'value' => 'merchant'],
                        ['label' => '供应商后台', 'value' => 'vendor'],
                        ['label' => '代理商后台', 'value' => 'agent'],
                    ],
                ]), 'admin'],
                ['排序', 'sort', Input::make()->props(['placeholder' => '请输入排序', 'type' => 'number']), 0],
                ['是否启用', 'enabled', SwitchC::make(), true],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('通知分类管理')
            ->apiPrefix('/notification-categories')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'name', 'title' => '分类名称'],
                ['key' => 'message_types', 'title' => '消息类型', 'slot' => [
                    Tag::make()->props(['size' => 'small'])->children('{{ (slotData.row.messageTypes || []).join(", ") }}'),
                ]],
                ['key' => 'key', 'title' => '标识'],
                ['key' => 'icon', 'title' => '图标', 'width' => 100, 'slot' => [
                    Icon::make('{{ slotData.row.icon }}')->size(20),
                ]],
                ['key' => 'color', 'title' => '颜色', 'width' => 100, 'slot' => [
                    Tag::make()->props(['style' => 'background-color: {{ slotData.row.color }};color:#fff'])->children('{{ slotData.row.color }}'),
                ]],
                ['key' => 'guard_name', 'title' => '所属后台'],
                ['key' => 'enabled', 'title' => '状态', 'slot' => [
                    SwitchC::make()
                        ->props(['value' => '{{ slotData.row.enabled }}'])
                        ->on('update:value',
                            FetchAction::make('/notification-categories/{{ slotData.row.id }}')
                                ->put()
                                ->body(['action_type' => 'status', 'enabled' => '{{ $event }}'])
                                ->then([CallAction::make('$message.success', ['更新成功'])])
                        ),
                ]],
                ['key' => 'sort', 'title' => '排序', 'width' => 100],
                ['key' => 'actions', 'title' => '操作', 'width' => 150, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()
                            ->size('small')
                            ->props(['type' => 'primary', 'text' => true])
                            ->on('click', [
                                SetAction::make('formData', '{{ slotData.row }}'),
                                SetAction::make('editingId', '{{ slotData.row.id }}'),
                                SetAction::make('formVisible', true),
                            ])
                            ->text('编辑'),
                        Popconfirm::make()
                            ->props([
                                'positiveText' => '确定',
                                'negativeText' => '取消',
                            ])
                            ->on('positive-click',
                                FetchAction::make('/notification-categories/{{ slotData.row.id }}')
                                    ->delete()
                                    ->then([
                                        CallAction::make('$message.success', ['删除成功']),
                                        CallAction::make('loadData'),
                                    ])
                                    ->catch([
                                        CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}']),
                                    ])
                            )
                            ->slot('trigger', [
                                Button::make()
                                    ->size('small')
                                    ->props(['type' => 'error', 'text' => true])
                                    ->text('删除'),
                            ])
                            ->children('确定要删除此分类吗？'),
                    ]),
                ]],
            ])
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索分类名称或标识', 'clearable' => true])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::make('formData', $form->getDefaultData()),
                        SetAction::make('editingId', null),
                        SetAction::make('formVisible', true),
                    ])
                    ->text('新增分类'),
            ])
            ->data([
                'formData' => $form->getDefaultData(),
                'editingId' => null,
            ])
            ->methods([
                'handleSubmit' => [
                    ['if' => 'editingId', 'then' => [
                        FetchAction::make('/notification-categories/{{ editingId }}')
                            ->put()
                            ->body('{{ formData }}')
                            ->then([
                                CallAction::make('$message.success', ['更新成功']),
                                SetAction::make('formVisible', false),
                                CallAction::make('loadData'),
                            ]),
                    ], 'else' => [
                        FetchAction::make('/notification-categories')
                            ->post()
                            ->body('{{ formData }}')
                            ->then([
                                CallAction::make('$message.success', ['创建成功']),
                                SetAction::make('formVisible', false),
                                CallAction::make('loadData'),
                            ]),
                    ]],
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑分类" : "新增分类" }}', $form);

        return success($schema->build());
    }

    /**
     * 更新状态
     */
    public function updateStatus(Request $request, int $id): array
    {
        $category = NotificationCategory::findOrFail($id);
        $category->enabled = $request->boolean('enabled');
        $category->save();

        return success('状态更新成功', ['enabled' => $category->enabled]);
    }
}
