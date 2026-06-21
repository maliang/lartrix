<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\InputNumber;
use Lartrix\Schema\Components\NaiveUI\Select;
use Lartrix\Schema\Components\NaiveUI\SwitchC;
use Lartrix\Schema\Components\NaiveUI\TreeSelect;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Components\Business\OptForm;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use Lartrix\Schema\Actions\IfAction;

class MenuController extends CrudController
{
    // ==================== 配置方法 ====================

    protected function getModelClass(): string
    {
        return config('lartrix.models.menu', \Lartrix\Models\Menu::class);
    }

    protected function getResourceName(): string
    {
        return __t('menu.resource_name');
    }

    protected function getTable(): string
    {
        return config('lartrix.tables.menus', 'admin_menus');
    }

    protected function getDefaultOrder(): array
    {
        return ['order', 'asc'];
    }

    // ==================== 路由方法重写 ====================

    public function index(Request $request): mixed
    {
        $actionType = $request->input('action_type', 'list');

        return match ($actionType) {
            'all' => $this->all(),
            'list_ui' => $this->listUi(),
            'form_ui' => $this->formUi(),
            default => $this->list($request),
        };
    }

    /**
     * 当前用户可见菜单（MenuRoute 格式）
     */
    protected function list(Request $request): array
    {
        $modelClass = $this->getModelClass();
        $guard = config('lartrix.guard', 'admin');
        $routes = $modelClass::getRoutesForUser($request->user(), $guard);
        return success($routes);
    }

    // ==================== 验证规则 ====================

    protected function getStoreRules(): array
    {
        $table = $this->getTable();
        return [
            'parent_id' => "nullable|integer|exists:{$table},id",
            'name' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'component' => 'nullable|string|max:255',
            'redirect' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer',
            'hide_in_menu' => 'boolean',
            'keep_alive' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'use_json_renderer' => 'boolean',
            'schema_source' => 'nullable|string|max:255',
            'layout_type' => 'nullable|string|in:normal,blank',
            'open_type' => 'nullable|string|in:normal,iframe,newWindow',
            'href' => 'nullable|string|max:255',
            'is_default_after_login' => 'boolean',
            'requires_auth' => 'boolean',
            'active_menu' => 'nullable|string|max:255',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        $table = $this->getTable();
        return [
            'parent_id' => "nullable|integer|exists:{$table},id",
            'name' => 'string|max:255',
            'path' => 'string|max:255',
            'component' => 'nullable|string|max:255',
            'redirect' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer',
            'hide_in_menu' => 'boolean',
            'keep_alive' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'use_json_renderer' => 'boolean',
            'schema_source' => 'nullable|string|max:255',
            'layout_type' => 'nullable|string|in:normal,blank',
            'open_type' => 'nullable|string|in:normal,iframe,newWindow',
            'href' => 'nullable|string|max:255',
            'is_default_after_login' => 'boolean',
            'requires_auth' => 'boolean',
            'active_menu' => 'nullable|string|max:255',
        ];
    }

    protected function validateUpdate(Request $request, int $id): array
    {
        $validated = parent::validateUpdate($request, $id);

        // 防止设置自己为父级
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            throw new \Lartrix\Exceptions\ApiException(__t('menu.cannot_parent_self'), 40022);
        }

        return $validated;
    }

    protected function beforeDelete(mixed $model): void
    {
        if ($model->children()->exists()) {
            throw new \Lartrix\Exceptions\ApiException(__t('menu.delete_children_first'), 40022);
        }
    }

    /**
     * 创建时自动设置 guard_name
     */
    protected function prepareStoreData(array $validated): array
    {
        $validated['guard_name'] = config('lartrix.guard', 'admin');
        return $validated;
    }

    // ==================== 自定义方法 ====================

    /**
     * 所有菜单（管理用）
     */
    protected function all(): array
    {
        $modelClass = $this->getModelClass();
        $guard = config('lartrix.guard', 'admin');

        $menus = $modelClass::query()
            ->forGuard($guard)
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('order')
            ->get();

        $result = $this->transformMenuChildren($menus->toArray());

        return success($result);
    }

    /**
     * 递归转换菜单子节点字段名
     */
    protected function transformMenuChildren(array $menus): array
    {
        return array_map(function ($menu) {
            if (isset($menu['all_children'])) {
                $menu['children'] = $this->transformMenuChildren($menu['all_children']);
                unset($menu['all_children']);
            }
            return $menu;
        }, $menus);
    }

    /**
     * 菜单排序（action_type=sort）
     */
    protected function updateSort(Request $request, int $id): array
    {
        $table = $this->getTable();
        $modelClass = $this->getModelClass();

        $validated = $request->validate([
            'items' => 'required|array',
            "items.*.id" => "required|integer|exists:{$table},id",
            'items.*.order' => 'required|integer',
            "items.*.parent_id" => "nullable|integer|exists:{$table},id",
        ]);

        foreach ($validated['items'] as $item) {
            $modelClass::where('id', $item['id'])->update([
                'order' => $item['order'],
                'parent_id' => $item['parent_id'] ?? null,
            ]);
        }

        return success(__t('crud.sorted'));
    }

    // ==================== UI Schema ====================

    protected function listUi(): array
    {
        // 菜单表单
        $menuForm = OptForm::make('formData')
            ->fields([
                [__t('form.parent_id'), 'parent_id', TreeSelect::make()->props([
                    'placeholder' => __t('placeholder.parent'),
                    'clearable' => true,
                    'options' => '{{ menuTreeOptions }}',
                    'keyField' => 'id',
                    'labelField' => 'title',
                    'childrenField' => 'children',
                ])],
                [__t('column.name'), 'name', Input::make()->props(['placeholder' => __t('placeholder.name')])],
                [__t('form.title'), 'title', Input::make()->props(['placeholder' => __t('placeholder.title')])],
                [__t('form.path'), 'path', Input::make()->props(['placeholder' => __t('placeholder.path_example')])],
                [__t('form.icon'), 'icon', Input::make()->props(['placeholder' => __t('placeholder.icon_example')])],
                [__t('form.redirect'), 'redirect', Input::make()->props(['placeholder' => __t('placeholder.redirect')])],
                [__t('column.sort'), 'order', InputNumber::make()->props(['min' => 0]), 0],
                [__t('form.layout_type'), 'layout_type', Select::make()->props([
                    'clearable' => true,
                    'options' => [
                        ['label' => __t('title.normal_layout'), 'value' => 'normal'],
                        ['label' => __t('title.blank_layout'), 'value' => 'blank'],
                    ],
                ])],
                [__t('form.open_type'), 'open_type', Select::make()->props([
                    'clearable' => true,
                    'options' => [
                        ['label' => __t('title.normal_open'), 'value' => 'normal'],
                        ['label' => 'iframe 嵌入', 'value' => 'iframe'],
                        ['label' => __t('title.new_window'), 'value' => 'newWindow'],
                    ],
                ])],
                [__t('form.href'), 'href', Input::make()->props(['placeholder' => __t('placeholder.href')]), '', "formData.open_type === 'iframe' || formData.open_type === 'newWindow'"],
                [__t('form.use_json_renderer'), 'use_json_renderer', SwitchC::make(), false],
                ['Schema 来源', 'schema_source', Input::make()->props(['placeholder' => 'API 地址或静态文件路径']), '', 'formData.use_json_renderer'],
                [__t('form.hide_in_menu'), 'hide_in_menu', SwitchC::make(), false],
                ['缓存页面', 'keep_alive', SwitchC::make(), false],
                [__t('form.requires_auth'), 'requires_auth', SwitchC::make(), true],
                [__t('form.is_default_after_login'), 'is_default_after_login', SwitchC::make(), false],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('button.confirm')),
            ]);

        $schema = CrudPage::make(__t('title.menu_manage'))
            ->apiPrefix('/menus')
            ->apiParams(['action_type' => 'all'])
            ->columns($this->getTableColumns())
            ->scrollX(1200)
            ->pagination(false)
            ->tree()
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::batch([
                            'editingId' => null,
                            'formData' => $menuForm->getDefaultData(),
                            'formVisible' => true,
                        ]),
                        CallAction::make('loadMenuTree'),
                    ])
                    ->text(__t('button.create')),
                'expandAll',
                'collapseAll',
            ])
            ->data([
                'formData' => $menuForm->getDefaultData(),
                'editingId' => null,
                'submitting' => false,
                'menuTreeOptions' => [],
            ])
            ->methods([
                'loadMenuTree' => [
                    FetchAction::make('/menus?action_type=all')
                        ->get()
                        ->then([
                            SetAction::make('menuTreeOptions', '{{ $response.data || [] }}'),
                        ]),
                ],
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(
                            FetchAction::make('{{ "/menus/" + editingId }}')
                                ->put()
                                ->body('{{ formData }}')
                                ->then([
                                    CallAction::make('$message.success', [__t('crud.updated')]),
                                    SetAction::make('formVisible', false),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}']),
                                ])
                                ->finally([
                                    SetAction::make('submitting', false),
                                ])
                        )
                        ->else(
                            FetchAction::make('/menus')
                                ->post()
                                ->body('{{ formData }}')
                                ->then([
                                    CallAction::make('$message.success', [__t('crud.created')]),
                                    SetAction::make('formVisible', false),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}']),
                                ])
                                ->finally([
                                    SetAction::make('submitting', false),
                                ])
                        ),
                ],
                'handleAddChild' => [
                    SetAction::batch([
                        'editingId' => null,
                        'formData' => array_merge($menuForm->getDefaultData(), ['parent_id' => '{{ $event.id }}']),
                        'formVisible' => true,
                    ]),
                    CallAction::make('loadMenuTree'),
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑菜单" : "新增菜单" }}', $menuForm, ['width' => '600px']);

        return success($schema->build());
    }

    protected function formUi(): array
    {
        return $this->listUi();
    }

    /**
     * 获取表格列配置
     */
    protected function getTableColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID', 'width' => 80],
            ['key' => 'title', 'title' => __t('form.title')],
            ['key' => 'name', 'title' => __t('form.name')],
            ['key' => 'path', 'title' => __t('form.path')],
            ['key' => 'icon', 'title' => __t('form.icon')],
            ['key' => 'order', 'title' => __t('column.sort'), 'width' => 80],
            ['key' => 'hide_in_menu', 'title' => __t('column.hide_in_menu'), 'width' => 80, 'slot' => [
                Tag::make()
                    ->props([
                        'type' => "{{ slotData.row.hide_in_menu ? 'warning' : 'success' }}",
                        'size' => 'small',
                    ])
                    ->children(["{{ slotData.row.hide_in_menu ? __t('tag.yes') : __t('tag.no') }}"]),
            ]],
            ['key' => 'actions', 'title' => __t('column.actions'), 'width' => 200, 'fixed' => 'right', 'slot' => [
                Space::make()->children([
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'primary', 'text' => true])
                        ->on('click', [
                            SetAction::make('editingId', '{{ slotData.row.id }}'),
                            SetAction::make('formData.parent_id', '{{ slotData.row.parent_id }}'),
                            SetAction::make('formData.name', '{{ slotData.row.name }}'),
                            SetAction::make('formData.title', '{{ slotData.row.title || "" }}'),
                            SetAction::make('formData.path', '{{ slotData.row.path }}'),
                            SetAction::make('formData.icon', '{{ slotData.row.icon || "" }}'),
                            SetAction::make('formData.redirect', '{{ slotData.row.redirect || "" }}'),
                            SetAction::make('formData.order', '{{ slotData.row.order || 0 }}'),
                            SetAction::make('formData.layout_type', '{{ slotData.row.layout_type }}'),
                            SetAction::make('formData.open_type', '{{ slotData.row.open_type }}'),
                            SetAction::make('formData.href', '{{ slotData.row.href || "" }}'),
                            SetAction::make('formData.use_json_renderer', '{{ slotData.row.use_json_renderer || false }}'),
                            SetAction::make('formData.schema_source', '{{ slotData.row.schema_source || "" }}'),
                            SetAction::make('formData.hide_in_menu', '{{ slotData.row.hide_in_menu || false }}'),
                            SetAction::make('formData.keep_alive', '{{ slotData.row.keep_alive || false }}'),
                            SetAction::make('formData.requires_auth', '{{ slotData.row.requires_auth !== false }}'),
                            SetAction::make('formData.is_default_after_login', '{{ slotData.row.is_default_after_login || false }}'),
                            SetAction::make('formVisible', true),
                            CallAction::make('loadMenuTree'),
                        ])
                        ->text(__t('button.edit')),
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'success', 'text' => true])
                        ->on('click', ['call' => 'handleAddChild', 'args' => ['{{ slotData.row }}']])
                        ->text(__t('button.add_child_menu')),
                    Popconfirm::make()
                        ->props([
                            'positiveText' => __t('button.confirm'),
                            'negativeText' => __t('button.cancel'),
                        ])
                        ->on('positive-click',
                            FetchAction::make('/menus/{{ slotData.row.id }}')
                                ->delete()
                                ->then([
                                    CallAction::make('$message.success', [__t('crud.deleted')]),
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
                                ->text(__t('button.delete')),
                        ])
                        ->children([__t('confirm.delete_menu')]),
                ]),
            ]],
        ];
    }
}
