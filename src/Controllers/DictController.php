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
use Lartrix\Schema\Actions\FetchAction;

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
        $schema = CrudPage::make()
            ->title('字典管理')
            ->api('/dicts/groups')
            ->rowKey('id')
            ->searchForm([
                Input::make()
                    ->model('keyword')
                    ->placeholder('搜索编码/名称')
                    ->clearable(true)
                    ->props(['style' => 'width: 200px']),
            ])
            ->toolbarActions([
                Button::make()
                    ->type('primary')
                    ->children(['新增分组'])
                    ->on('click', [
                        'action' => 'openDialog',
                        'title' => '新增字典分组',
                        'schema' => $this->groupFormSchema(),
                    ]),
            ])
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'code', 'title' => '编码', 'width' => 150],
                ['key' => 'name', 'title' => '名称', 'width' => 150],
                ['key' => 'description', 'title' => '描述'],
                [
                    'key' => 'items_count',
                    'title' => '字典项数',
                    'width' => 100,
                    'render' => Tag::make()
                        ->type('info')
                        ->children(['{{ row.items_count }}'])
                        ->toArray(),
                ],
                [
                    'key' => 'is_system',
                    'title' => '系统内置',
                    'width' => 100,
                    'render' => Tag::make()
                        ->type('{{ row.is_system ? "warning" : "default" }}')
                        ->children(['{{ row.is_system ? "是" : "否" }}'])
                        ->toArray(),
                ],
                ['key' => 'created_at', 'title' => '创建时间', 'width' => 180],
            ])
            ->rowActions([
                Button::make()
                    ->text(true)
                    ->type('primary')
                    ->children(['字典项'])
                    ->on('click', [
                        'action' => 'openDialog',
                        'title' => '{{ row.name }} - 字典项管理',
                        'width' => '800px',
                        'schema' => $this->itemsListSchema(),
                    ]),
                Button::make()
                    ->text(true)
                    ->type('info')
                    ->children(['编辑'])
                    ->on('click', [
                        'action' => 'openDialog',
                        'title' => '编辑字典分组',
                        'schema' => $this->groupFormSchema(true),
                    ]),
                Popconfirm::make()
                    ->content('确定要删除该字典分组吗？删除后其下所有字典项也将被删除。')
                    ->onPositiveClick(
                        FetchAction::make()
                            ->url('/dicts/groups/{{ row.id }}')
                            ->method('DELETE')
                            ->successMessage('删除成功')
                            ->then(['action' => 'refreshTable'])
                    )
                    ->children([
                        Button::make()
                            ->text(true)
                            ->type('error')
                            ->disabled('{{ row.is_system }}')
                            ->children(['删除']),
                    ]),
            ])
            ->toArray();

        return success($schema);
    }

    /**
     * 字典分组表单 Schema
     */
    protected function groupFormSchema(bool $isEdit = false): array
    {
        $form = OptForm::make()
            ->api($isEdit ? '/dicts/groups/{{ row.id }}' : '/dicts/groups')
            ->method($isEdit ? 'PUT' : 'POST')
            ->successMessage($isEdit ? '更新成功' : '创建成功')
            ->afterSuccess(['action' => 'refreshTable', 'closeDialog' => true])
            ->fields([
                [
                    'key' => 'code',
                    'label' => '编码',
                    'required' => true,
                    'component' => Input::make()
                        ->model('formData.code')
                        ->placeholder('请输入编码，如 order_status')
                        ->disabled($isEdit ? '{{ row.is_system }}' : false),
                ],
                [
                    'key' => 'name',
                    'label' => '名称',
                    'required' => true,
                    'component' => Input::make()
                        ->model('formData.name')
                        ->placeholder('请输入名称'),
                ],
                [
                    'key' => 'description',
                    'label' => '描述',
                    'component' => Input::make()
                        ->type('textarea')
                        ->model('formData.description')
                        ->placeholder('请输入描述'),
                ],
            ]);

        if ($isEdit) {
            $form->initApi('/dicts/groups/{{ row.id }}');
        }

        return $form->toArray();
    }

    /**
     * 字典项列表 Schema（弹窗内）
     */
    protected function itemsListSchema(): array
    {
        return CrudPage::make()
            ->api('/dicts/groups/{{ row.id }}/items')
            ->rowKey('id')
            ->pagination(false)
            ->toolbarActions([
                Button::make()
                    ->type('primary')
                    ->size('small')
                    ->children(['新增字典项'])
                    ->on('click', [
                        'action' => 'openDialog',
                        'title' => '新增字典项',
                        'schema' => $this->itemFormSchema(),
                    ]),
            ])
            ->columns([
                ['key' => 'sort', 'title' => '排序', 'width' => 60],
                ['key' => 'code', 'title' => '编码', 'width' => 120],
                ['key' => 'label', 'title' => '显示文本', 'width' => 120],
                ['key' => 'value', 'title' => '存储值', 'width' => 100],
                [
                    'key' => 'is_enabled',
                    'title' => '状态',
                    'width' => 80,
                    'render' => Tag::make()
                        ->type('{{ row.is_enabled ? "success" : "default" }}')
                        ->children(['{{ row.is_enabled ? "启用" : "禁用" }}'])
                        ->toArray(),
                ],
            ])
            ->rowActions([
                Button::make()
                    ->text(true)
                    ->type('info')
                    ->size('small')
                    ->children(['编辑'])
                    ->on('click', [
                        'action' => 'openDialog',
                        'title' => '编辑字典项',
                        'schema' => $this->itemFormSchema(true),
                    ]),
                Popconfirm::make()
                    ->content('确定要删除该字典项吗？')
                    ->onPositiveClick(
                        FetchAction::make()
                            ->url('/dicts/groups/{{ $parent.row.id }}/items/{{ row.id }}')
                            ->method('DELETE')
                            ->successMessage('删除成功')
                            ->then(['action' => 'refreshTable'])
                    )
                    ->children([
                        Button::make()
                            ->text(true)
                            ->type('error')
                            ->size('small')
                            ->children(['删除']),
                    ]),
            ])
            ->toArray();
    }

    /**
     * 字典项表单 Schema
     */
    protected function itemFormSchema(bool $isEdit = false): array
    {
        $form = OptForm::make()
            ->api($isEdit 
                ? '/dicts/groups/{{ $parent.row.id }}/items/{{ row.id }}' 
                : '/dicts/groups/{{ $parent.row.id }}/items')
            ->method($isEdit ? 'PUT' : 'POST')
            ->successMessage($isEdit ? '更新成功' : '创建成功')
            ->afterSuccess(['action' => 'refreshTable', 'closeDialog' => true])
            ->fields([
                [
                    'key' => 'code',
                    'label' => '编码',
                    'required' => true,
                    'component' => Input::make()
                        ->model('formData.code')
                        ->placeholder('请输入编码'),
                ],
                [
                    'key' => 'label',
                    'label' => '显示文本',
                    'required' => true,
                    'component' => Input::make()
                        ->model('formData.label')
                        ->placeholder('请输入显示文本'),
                ],
                [
                    'key' => 'value',
                    'label' => '存储值',
                    'required' => true,
                    'component' => Input::make()
                        ->model('formData.value')
                        ->placeholder('请输入存储值'),
                ],
                [
                    'key' => 'sort',
                    'label' => '排序',
                    'component' => Input::make()
                        ->type('number')
                        ->model('formData.sort')
                        ->placeholder('数字越小越靠前'),
                ],
                [
                    'key' => 'is_enabled',
                    'label' => '启用状态',
                    'component' => SwitchC::make()
                        ->model('formData.is_enabled')
                        ->defaultValue(true),
                ],
            ]);

        if ($isEdit) {
            $form->initApi('/dicts/groups/{{ $parent.row.id }}/items/{{ row.id }}');
        }

        return $form->toArray();
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
