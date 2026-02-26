<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Models\DictGroup;
use Lartrix\Models\DictItem;
use Lartrix\Services\DataDictService;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\NaiveUI\SwitchC;
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Components\Business\OptForm;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use Lartrix\Schema\Actions\IfAction;

class DictController extends Controller
{
    public function __construct(
        protected DataDictService $dictService
    ) {}

    /**
     * 字典分组入口（支持 action_type 分发）
     */
    public function groups(Request $request): array
    {
        $actionType = $request->input('action_type', 'list');

        return match ($actionType) {
            'list_ui' => $this->groupsListUi(),
            default => $this->groupsList($request),
        };
    }

    /**
     * 字典分组列表
     */
    protected function groupsList(Request $request): array
    {
        $query = DictGroup::query();

        // 搜索
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                  ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        $groups = $query->withCount('items')
            ->orderBy('id', 'desc')
            ->paginate($request->input('page_size', 20));

        return success([
            'list' => $groups->items(),
            'total' => $groups->total(),
        ]);
    }

    /**
     * 字典分组列表 UI Schema
     */
    protected function groupsListUi(): array
    {
        // 分组表单
        $groupForm = OptForm::make('formData')
            ->fields([
                ['编码', 'code', Input::make()->props(['placeholder' => '请输入编码，如 order_status', 'disabled' => '{{ !!editingId && editingSystem }}'])],
                ['名称', 'name', Input::make()->props(['placeholder' => '请输入名称'])],
                ['描述', 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => '请输入描述'])],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        // 字典项表单
        $itemForm = OptForm::make('itemFormData')
            ->fields([
                ['编码', 'code', Input::make()->props(['placeholder' => '请输入编码'])],
                ['显示文本', 'label', Input::make()->props(['placeholder' => '请输入显示文本'])],
                ['存储值', 'value', Input::make()->props(['placeholder' => '请输入存储值'])],
                ['排序', 'sort', Input::make()->props(['type' => 'number', 'placeholder' => '数字越小越靠前']), 0],
                ['启用状态', 'is_enabled', SwitchC::make(), true],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('itemFormVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ itemSubmitting }}'])->on('click', ['call' => 'handleItemSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('字典管理')
            ->apiPrefix('/dicts/groups')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'code', 'title' => '编码', 'width' => 150],
                ['key' => 'name', 'title' => '名称', 'width' => 150],
                ['key' => 'description', 'title' => '描述'],
                ['key' => 'items_count', 'title' => '字典项数', 'width' => 100, 'slot' => [
                    Tag::make()
                        ->props(['type' => 'info', 'size' => 'small'])
                        ->children(['{{ slotData.row.items_count }}']),
                ]],
                ['key' => 'is_system', 'title' => '系统内置', 'width' => 100, 'slot' => [
                    Tag::make()
                        ->props([
                            'type' => "{{ slotData.row.is_system ? 'warning' : 'default' }}",
                            'size' => 'small',
                        ])
                        ->children(["{{ slotData.row.is_system ? '是' : '否' }}"]),
                ]],
                ['key' => 'created_at', 'title' => '创建时间', 'width' => 180],
                ['key' => 'actions', 'title' => '操作', 'width' => 200, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()
                            ->size('small')
                            ->props(['type' => 'primary', 'text' => true])
                            ->on('click', [
                                SetAction::make('currentGroupId', '{{ slotData.row.id }}'),
                                SetAction::make('currentGroupName', '{{ slotData.row.name }}'),
                                SetAction::make('itemsVisible', true),
                                CallAction::make('loadItems'),
                            ])
                            ->text('字典项'),
                        Button::make()
                            ->size('small')
                            ->props(['type' => 'info', 'text' => true])
                            ->on('click', [
                                SetAction::make('editingId', '{{ slotData.row.id }}'),
                                SetAction::make('editingSystem', '{{ slotData.row.is_system }}'),
                                SetAction::make('formData.code', '{{ slotData.row.code }}'),
                                SetAction::make('formData.name', '{{ slotData.row.name }}'),
                                SetAction::make('formData.description', '{{ slotData.row.description || "" }}'),
                                SetAction::make('formVisible', true),
                            ])
                            ->text('编辑'),
                        Popconfirm::make()
                            ->if('!slotData.row.is_system')
                            ->props(['positiveText' => '确定', 'negativeText' => '取消'])
                            ->on('positive-click',
                                FetchAction::make('/dicts/groups/{{ slotData.row.id }}')
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
                            ->children(['确定要删除该字典分组吗？删除后其下所有字典项也将被删除。']),
                    ]),
                ]],
            ])
            ->scrollX(1100)
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索编码/名称', 'clearable' => true])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::batch([
                            'editingId' => null,
                            'editingSystem' => false,
                            'formData.code' => '',
                            'formData.name' => '',
                            'formData.description' => '',
                            'formVisible' => true,
                        ]),
                    ])
                    ->text('新增分组'),
            ])
            ->data([
                'formData' => $groupForm->getDefaultData(),
                'editingId' => null,
                'editingSystem' => false,
                'submitting' => false,
                // 字典项相关
                'currentGroupId' => null,
                'currentGroupName' => '',
                'itemsData' => [],
                'itemsLoading' => false,
                'itemFormData' => $itemForm->getDefaultData(),
                'editingItemId' => null,
                'itemSubmitting' => false,
                'itemFormVisible' => false,
            ])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(
                            FetchAction::make('{{ "/dicts/groups/" + editingId }}')
                                ->put()
                                ->body('{{ formData }}')
                                ->then([
                                    CallAction::make('$message.success', ['更新成功']),
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
                            FetchAction::make('/dicts/groups')
                                ->post()
                                ->body('{{ formData }}')
                                ->then([
                                    CallAction::make('$message.success', ['创建成功']),
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
                'loadItems' => [
                    SetAction::make('itemsLoading', true),
                    FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items" }}')
                        ->then([
                            SetAction::make('itemsData', '{{ $response.data.list || [] }}'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "加载字典项失败" }}']),
                        ])
                        ->finally([
                            SetAction::make('itemsLoading', false),
                        ]),
                ],
                'handleItemSubmit' => [
                    SetAction::make('itemSubmitting', true),
                    IfAction::make('editingItemId')
                        ->then(
                            FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items/" + editingItemId }}')
                                ->put()
                                ->body('{{ itemFormData }}')
                                ->then([
                                    CallAction::make('$message.success', ['更新成功']),
                                    SetAction::make('itemFormVisible', false),
                                    CallAction::make('loadItems'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}']),
                                ])
                                ->finally([
                                    SetAction::make('itemSubmitting', false),
                                ])
                        )
                        ->else(
                            FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items" }}')
                                ->post()
                                ->body('{{ itemFormData }}')
                                ->then([
                                    CallAction::make('$message.success', ['创建成功']),
                                    SetAction::make('itemFormVisible', false),
                                    CallAction::make('loadItems'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}']),
                                ])
                                ->finally([
                                    SetAction::make('itemSubmitting', false),
                                ])
                        ),
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑字典分组" : "新增字典分组" }}', $groupForm)
            ->drawer('items', '{{ currentGroupName + " - 字典项管理" }}', $this->buildItemsDrawerContent($itemForm), ['width' => 800]);

        return success($schema->build());
    }

    /**
     * 构建字典项抽屉内容
     */
    protected function buildItemsDrawerContent(OptForm $itemForm): array
    {
        $itemsTable = \Lartrix\Schema\Components\Business\DataTable::make()
            ->props([
                'loading' => '{{ itemsLoading }}',
                'data' => '{{ itemsData }}',
                'columns' => [
                    ['key' => 'sort', 'title' => '排序', 'width' => 60],
                    ['key' => 'code', 'title' => '编码', 'width' => 120],
                    ['key' => 'label', 'title' => '显示文本', 'width' => 120],
                    ['key' => 'value', 'title' => '存储值', 'width' => 100],
                    ['key' => 'is_enabled', 'title' => '状态', 'width' => 80],
                    ['key' => 'actions', 'title' => '操作', 'width' => 120, 'fixed' => 'right'],
                ],
                'rowKey' => '{{ row => row.id }}',
                'scrollX' => 700,
            ])
            ->slot('is_enabled', [
                Tag::make()
                    ->props([
                        'type' => "{{ slotData.row.is_enabled ? 'success' : 'default' }}",
                        'size' => 'small',
                    ])
                    ->children(["{{ slotData.row.is_enabled ? '启用' : '禁用' }}"]),
            ], 'slotData')
            ->slot('actions', [
                Space::make()->children([
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'info', 'text' => true])
                        ->on('click', [
                            SetAction::make('editingItemId', '{{ slotData.row.id }}'),
                            SetAction::make('itemFormData.code', '{{ slotData.row.code }}'),
                            SetAction::make('itemFormData.label', '{{ slotData.row.label }}'),
                            SetAction::make('itemFormData.value', '{{ slotData.row.value }}'),
                            SetAction::make('itemFormData.sort', '{{ slotData.row.sort }}'),
                            SetAction::make('itemFormData.is_enabled', '{{ slotData.row.is_enabled }}'),
                            SetAction::make('itemFormVisible', true),
                        ])
                        ->text('编辑'),
                    Popconfirm::make()
                        ->props(['positiveText' => '确定', 'negativeText' => '取消'])
                        ->on('positive-click',
                            FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items/" + slotData.row.id }}')
                                ->delete()
                                ->then([
                                    CallAction::make('$message.success', ['删除成功']),
                                    CallAction::make('loadItems'),
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
                        ->children(['确定要删除该字典项吗？']),
                ]),
            ], 'slotData');

        return [
            Space::make()
                ->props(['vertical' => true, 'size' => 'large', 'wrapItem' => false])
                ->children([
                    Button::make()
                        ->type('primary')
                        ->size('small')
                        ->on('click', [
                            SetAction::batch([
                                'editingItemId' => null,
                                'itemFormData.code' => '',
                                'itemFormData.label' => '',
                                'itemFormData.value' => '',
                                'itemFormData.sort' => 0,
                                'itemFormData.is_enabled' => true,
                                'itemFormVisible' => true,
                            ]),
                        ])
                        ->text('新增字典项'),
                    $itemsTable,
                ]),
            \Lartrix\Schema\Components\NaiveUI\Modal::make()
                ->props([
                    'show' => '{{ itemFormVisible }}',
                    'title' => '{{ editingItemId ? "编辑字典项" : "新增字典项" }}',
                    'style' => ['width' => '500px'],
                    'preset' => 'card',
                ])
                ->on('update:show', [SetAction::make('itemFormVisible', false)])
                ->children([$itemForm->toArray()]),
        ];
    }

    /**
     * 创建字典分组
     */
    public function createGroup(Request $request): array
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:dict_groups,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $group = DictGroup::create($validated);

        return success($group, '创建成功');
    }

    /**
     * 获取字典分组详情
     */
    public function showGroup(int $id): array
    {
        $group = DictGroup::findOrFail($id);
        return success($group);
    }

    /**
     * 更新字典分组
     */
    public function updateGroup(Request $request, int $id): array
    {
        $group = DictGroup::findOrFail($id);

        // 系统内置分组不允许修改 code
        $rules = [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ];

        if (!$group->is_system) {
            $rules['code'] = "required|string|max:50|unique:dict_groups,code,{$id}";
        }

        $validated = $request->validate($rules);

        $group->update($validated);

        // 清除缓存
        $this->dictService->clearCache($group->code);

        return success($group, '更新成功');
    }

    /**
     * 删除字典分组
     */
    public function deleteGroup(int $id): array
    {
        $group = DictGroup::findOrFail($id);

        if ($group->is_system) {
            return error('系统内置分组不允许删除');
        }

        // 清除缓存
        $this->dictService->clearCache($group->code);

        $group->delete();

        return success(null, '删除成功');
    }

    /**
     * 字典项列表
     */
    public function items(Request $request, int $groupId): array
    {
        $group = DictGroup::findOrFail($groupId);

        $items = $group->items()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return success([
            'group' => $group,
            'list' => $items,
        ]);
    }

    /**
     * 获取字典项详情
     */
    public function showItem(int $groupId, int $id): array
    {
        DictGroup::findOrFail($groupId);
        $item = DictItem::where('group_id', $groupId)->findOrFail($id);
        return success($item);
    }

    /**
     * 创建字典项
     */
    public function createItem(Request $request, int $groupId): array
    {
        $group = DictGroup::findOrFail($groupId);

        $validated = $request->validate([
            'code' => "required|string|max:50|unique:dict_items,code,NULL,id,group_id,{$groupId}",
            'label' => 'required|string|max:100',
            'value' => 'required|string|max:100',
            'sort' => 'nullable|integer',
            'is_enabled' => 'nullable|boolean',
            'extra' => 'nullable|array',
        ]);

        $validated['group_id'] = $groupId;
        $validated['sort'] = $validated['sort'] ?? 0;
        $validated['is_enabled'] = $validated['is_enabled'] ?? true;

        $item = DictItem::create($validated);

        // 清除缓存
        $this->dictService->clearCache($group->code);

        return success($item, '创建成功');
    }

    /**
     * 更新字典项
     */
    public function updateItem(Request $request, int $groupId, int $id): array
    {
        $group = DictGroup::findOrFail($groupId);
        $item = DictItem::where('group_id', $groupId)->findOrFail($id);

        $validated = $request->validate([
            'code' => "required|string|max:50|unique:dict_items,code,{$id},id,group_id,{$groupId}",
            'label' => 'required|string|max:100',
            'value' => 'required|string|max:100',
            'sort' => 'nullable|integer',
            'is_enabled' => 'nullable|boolean',
            'extra' => 'nullable|array',
        ]);

        $item->update($validated);

        // 清除缓存
        $this->dictService->clearCache($group->code);

        return success($item, '更新成功');
    }

    /**
     * 删除字典项
     */
    public function deleteItem(int $groupId, int $id): array
    {
        $group = DictGroup::findOrFail($groupId);
        $item = DictItem::where('group_id', $groupId)->findOrFail($id);

        $item->delete();

        // 清除缓存
        $this->dictService->clearCache($group->code);

        return success(null, '删除成功');
    }

    /**
     * 批量更新字典项排序
     */
    public function sortItems(Request $request, int $groupId): array
    {
        $group = DictGroup::findOrFail($groupId);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:dict_items,id',
            'items.*.sort' => 'required|integer',
        ]);

        foreach ($validated['items'] as $itemData) {
            DictItem::where('id', $itemData['id'])
                ->where('group_id', $groupId)
                ->update(['sort' => $itemData['sort']]);
        }

        // 清除缓存
        $this->dictService->clearCache($group->code);

        return success(null, '排序更新成功');
    }

    /**
     * 获取字典选项（供前端 select 使用）
     */
    public function options(string $code): array
    {
        $options = $this->dictService->selectOptions($code);
        return success($options);
    }

    /**
     * 批量获取多个字典的选项
     */
    public function batchOptions(Request $request): array
    {
        $validated = $request->validate([
            'codes' => 'required|array',
            'codes.*' => 'required|string',
        ]);

        $result = [];
        foreach ($validated['codes'] as $code) {
            $result[$code] = $this->dictService->selectOptions($code);
        }

        return success($result);
    }
}
