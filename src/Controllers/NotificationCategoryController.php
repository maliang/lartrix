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
        return __t('notification.category_resource');
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
                [__t('column.name'), 'name', Input::make()->props(['placeholder' => __t('placeholder.category_name'), 'clearable' => true])],
                [__t('column.code'), 'key', Input::make()->props(['placeholder' => __t('placeholder.category_key'), 'clearable' => true])],
                [__t('form.icon'), 'icon', Input::make()->props(['placeholder' => __t('placeholder.icon_example'), 'clearable' => true])],
                [__t('column.color'), 'color', Input::make()->props(['placeholder' => __t('placeholder.color_example'), 'type' => 'color']), '#18a058'],
                [__t('column.type'), 'message_types', Select::make()->props([
                    'placeholder' => __t('placeholder.message_type'),
                    'multiple' => true,
                    'options' => [
                        ['label' => __t('notification.type_system'), 'value' => 'system'],
                        ['label' => __t('notification.type_notice'), 'value' => 'notice'],
                        ['label' => __t('notification.type_message'), 'value' => 'message'],
                        ['label' => __t('notification.type_todo'), 'value' => 'todo'],
                    ],
                ]), ['system']],
                [__t('column.module'), 'guard_name', Select::make()->props([
                    'placeholder' => __t('placeholder.backend'),
                    'options' => [
                        ['label' => __t('admin.main_backend'), 'value' => 'admin'],
                        ['label' => __t('admin.opt_merchant'), 'value' => 'merchant'],
                        ['label' => __t('admin.opt_vendor'), 'value' => 'vendor'],
                        ['label' => __t('admin.opt_agent'), 'value' => 'agent'],
                    ],
                ]), 'admin'],
                [__t('column.sort'), 'sort', Input::make()->props(['placeholder' => __t('placeholder.sort'), 'type' => 'number']), 0],
                [__t('form.is_enabled'), 'enabled', SwitchC::make(), true],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('button.cancel')),
                Button::make()->type('primary')->on('click', ['call' => 'handleSubmit'])->text(__t('button.confirm')),
            ]);

        $schema = CrudPage::make(__t('title.notification_category'))
            ->apiPrefix('/notification-categories')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'name', 'title' => __t('column.name')],
                ['key' => 'message_types', 'title' => __t('column.type'), 'slot' => [
                    Tag::make()->props(['size' => 'small'])->children('{{ (slotData.row.messageTypes || []).join(", ") }}'),
                ]],
                ['key' => 'key', 'title' => __t('column.code')],
                ['key' => 'icon', 'title' => __t('form.icon'), 'width' => 100, 'slot' => [
                    Icon::make('{{ slotData.row.icon }}')->size(20),
                ]],
                ['key' => 'color', 'title' => __t('column.color'), 'width' => 100, 'slot' => [
                    Tag::make()->props(['style' => 'background-color: {{ slotData.row.color }};color:#fff'])->children('{{ slotData.row.color }}'),
                ]],
                ['key' => 'guard_name', 'title' => __t('column.module')],
                ['key' => 'enabled', 'title' => __t('column.status'), 'slot' => [
                    SwitchC::make()
                        ->props(['value' => '{{ slotData.row.enabled }}'])
                        ->on('update:value',
                            FetchAction::make('/notification-categories/{{ slotData.row.id }}')
                                ->put()
                                ->body(['action_type' => 'status', 'enabled' => '{{ $event }}'])
                                ->then([CallAction::make('$message.success', [__t('crud.updated')])])
                        ),
                ]],
                ['key' => 'sort', 'title' => __t('column.sort'), 'width' => 100],
                ['key' => 'actions', 'title' => __t('column.actions'), 'width' => 150, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()
                            ->size('small')
                            ->props(['type' => 'primary', 'text' => true])
                            ->on('click', [
                                SetAction::make('formData', '{{ slotData.row }}'),
                                SetAction::make('editingId', '{{ slotData.row.id }}'),
                                SetAction::make('formVisible', true),
                            ])
                            ->text(__t('button.edit')),
                        Popconfirm::make()
                            ->props([
                                'positiveText' => __t('button.confirm'),
                                'negativeText' => __t('button.cancel'),
                            ])
                            ->on('positive-click',
                                FetchAction::make('/notification-categories/{{ slotData.row.id }}')
                                    ->delete()
                                    ->then([
                                        CallAction::make('$message.success', [__t('crud.deleted')]),
                                        CallAction::make('loadData'),
                                    ])
                                    ->catch([
                        CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.delete_failed') . '" }}']),
                                    ])
                            )
                            ->slot('trigger', [
                                Button::make()
                                    ->size('small')
                                    ->props(['type' => 'error', 'text' => true])
                                    ->text(__t('button.delete')),
                            ])
                            ->children(__t('confirm.delete_notification_category')),
                    ]),
                ]],
            ])
            ->search([
                [__t('search.keyword'), 'keyword', Input::make()->props(['placeholder' => __t('search.category'), 'clearable' => true])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::make('formData', $form->getDefaultData()),
                        SetAction::make('editingId', null),
                        SetAction::make('formVisible', true),
                    ])
                    ->text(__t('button.create')),
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
                                CallAction::make('$message.success', [__t('crud.updated')]),
                                SetAction::make('formVisible', false),
                                CallAction::make('loadData'),
                            ]),
                    ], 'else' => [
                        FetchAction::make('/notification-categories')
                            ->post()
                            ->body('{{ formData }}')
                            ->then([
                                CallAction::make('$message.success', [__t('crud.created')]),
                                SetAction::make('formVisible', false),
                                CallAction::make('loadData'),
                            ]),
                    ]],
                ],
            ])
            ->modal('form', '{{ editingId ? "' . __t('title.edit_notification_category') . '" : "' . __t('title.create_notification_category') . '" }}', $form);

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

        return success(__t('crud.status_updated'), ['enabled' => $category->enabled]);
    }
}
