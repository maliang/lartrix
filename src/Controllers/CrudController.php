<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Lartrix\Exports\BaseExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * CrudController - CRUD 控制器基类
 * 
 * 提供标准的 CRUD 操作，子类只需实现配置方法即可
 * 
 * action_type 参数：
 * - index: list(默认), export, batch_destroy, list_ui, form_ui
 * - update: update(默认), status, 及子类自定义的 action
 * - destroy: delete(默认), batch
 */
abstract class CrudController extends Controller
{
    /**
     * 获取模型类名
     */
    abstract protected function getModelClass(): string;

    /**
     * 获取资源名称（用于错误提示）
     */
    protected function getResourceName(): string
    {
        return '记录';
    }

    /**
     * 获取数据表名
     */
    protected function getTable(): string
    {
        return (new ($this->getModelClass()))->getTable();
    }

    /**
     * 获取主键名
     */
    protected function getPrimaryKey(): string
    {
        return 'id';
    }

    /**
     * 获取默认排序
     */
    protected function getDefaultOrder(): array
    {
        return ['id', 'desc'];
    }

    /**
     * 获取默认分页大小
     */
    protected function getDefaultPageSize(): int
    {
        return 15;
    }

    /**
     * 获取列表预加载关联
     */
    protected function getListWith(): array
    {
        return [];
    }

    /**
     * 获取详情预加载关联
     */
    protected function getShowWith(): array
    {
        return $this->getListWith();
    }

    /**
     * 获取导出列配置
     */
    protected function getExportColumns(): array
    {
        return [];
    }

    /**
     * 获取导出文件名前缀
     */
    protected function getExportFilenamePrefix(): string
    {
        return '导出数据';
    }

    // ==================== 路由方法 ====================

    /**
     * 列表入口（支持 action_type 分发）
     */
    public function index(Request $request): mixed
    {
        $actionType = $request->input('action_type', 'list');

        return match ($actionType) {
            'export' => $this->export($request),
            'batch_destroy' => $this->batchDestroy($request),
            'list_ui' => $this->listUi(),
            'form_ui' => $this->formUi(),
            default => $this->list($request),
        };
    }

    /**
     * 创建资源
     */
    public function store(Request $request): array
    {
        $validated = $this->validateStore($request);
        $model = $this->performStore($validated);
        $this->afterStore($model, $validated);

        return success('创建成功', $model->load($this->getShowWith())->toArray());
    }

    /**
     * 显示资源详情
     */
    public function show(int $id): array
    {
        $model = $this->findOrFail($id, $this->getShowWith());
        return success($model->toArray());
    }

    /**
     * 更新入口（支持 action_type 分发）
     */
    public function update(Request $request, int $id): array
    {
        $actionType = $request->input('action_type', 'update');

        // 先检查子类是否有自定义处理
        $customMethod = 'update' . str_replace('_', '', ucwords($actionType, '_'));
        if ($actionType !== 'update' && method_exists($this, $customMethod)) {
            return $this->$customMethod($request, $id);
        }

        return match ($actionType) {
            'status' => $this->updateStatus($request, $id),
            default => $this->updateModel($request, $id),
        };
    }

    /**
     * 删除入口（支持 action_type 分发）
     */
    public function destroy(Request $request, int $id = 0): array
    {
        $actionType = $request->input('action_type', 'delete');

        if ($actionType === 'batch') {
            return $this->batchDestroy($request);
        }

        return $this->deleteModel($id);
    }

    // ==================== 列表相关 ====================

    /**
     * 获取列表数据
     */
    protected function list(Request $request): array
    {
        $query = $this->buildListQuery($request);
        
        // 分页
        $perPage = $request->input('page_size', $this->getDefaultPageSize());
        $paginator = $query->paginate($perPage);

        return success([
            'list' => collect($paginator->items())->map->toArray()->values()->all(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
        ]);
    }

    /**
     * 构建列表查询
     */
    protected function buildListQuery(Request $request): Builder
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        // 应用搜索条件
        $this->applySearch($query, $request);

        // 应用筛选条件
        $this->applyFilters($query, $request);

        // 预加载关联
        if ($with = $this->getListWith()) {
            $query->with($with);
        }

        // 排序
        [$orderColumn, $orderDirection] = $this->getDefaultOrder();
        $query->orderBy($orderColumn, $orderDirection);

        return $query;
    }

    /**
     * 应用搜索条件（子类重写）
     */
    protected function applySearch(Builder $query, Request $request): void
    {
        // 子类实现具体搜索逻辑
    }

    /**
     * 应用筛选条件（子类重写）
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        // 通用状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }
    }

    // ==================== 创建相关 ====================

    /**
     * 验证创建数据（子类重写）
     */
    protected function validateStore(Request $request): array
    {
        return $request->validate($this->getStoreRules());
    }

    /**
     * 获取创建验证规则（子类重写）
     */
    protected function getStoreRules(): array
    {
        return [];
    }

    /**
     * 执行创建操作
     */
    protected function performStore(array $validated): mixed
    {
        $modelClass = $this->getModelClass();
        return $modelClass::create($this->prepareStoreData($validated));
    }

    /**
     * 准备创建数据（子类可重写）
     */
    protected function prepareStoreData(array $validated): array
    {
        return $validated;
    }

    /**
     * 创建后回调（子类可重写）
     */
    protected function afterStore(mixed $model, array $validated): void
    {
        // 子类实现
    }

    // ==================== 更新相关 ====================

    /**
     * 更新模型
     */
    protected function updateModel(Request $request, int $id): array
    {
        $model = $this->findOrFail($id);
        $validated = $this->validateUpdate($request, $id);
        
        $model->fill($this->prepareUpdateData($validated));
        $model->save();
        
        $this->afterUpdate($model, $validated);

        return success('更新成功', $model->load($this->getShowWith())->toArray());
    }

    /**
     * 验证更新数据（子类重写）
     */
    protected function validateUpdate(Request $request, int $id): array
    {
        return $request->validate($this->getUpdateRules($id));
    }

    /**
     * 获取更新验证规则（子类重写）
     */
    protected function getUpdateRules(int $id): array
    {
        return [];
    }

    /**
     * 准备更新数据（子类可重写）
     */
    protected function prepareUpdateData(array $validated): array
    {
        return $validated;
    }

    /**
     * 更新后回调（子类可重写）
     */
    protected function afterUpdate(mixed $model, array $validated): void
    {
        // 子类实现
    }

    /**
     * 更新状态
     */
    protected function updateStatus(Request $request, int $id): array
    {
        $model = $this->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $model->status = $validated['status'];
        $model->save();

        $this->afterStatusUpdate($model, $validated['status']);

        return success('状态更新成功', ['status' => $model->status]);
    }

    /**
     * 状态更新后回调（子类可重写）
     */
    protected function afterStatusUpdate(mixed $model, bool $status): void
    {
        // 子类实现
    }

    // ==================== 删除相关 ====================

    /**
     * 删除单个模型
     */
    protected function deleteModel(int $id): array
    {
        $model = $this->findOrFail($id);
        
        $this->beforeDelete($model);
        $model->delete();
        $this->afterDelete($model);

        return success('删除成功');
    }

    /**
     * 批量删除
     */
    protected function batchDestroy(Request $request): array
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $modelClass = $this->getModelClass();
        $models = $modelClass::whereIn($this->getPrimaryKey(), $validated['ids'])->get();

        if ($models->isEmpty()) {
            return error("未找到要删除的{$this->getResourceName()}");
        }

        foreach ($models as $model) {
            $this->beforeDelete($model);
        }

        $deleted = $modelClass::whereIn($this->getPrimaryKey(), $validated['ids'])->delete();

        foreach ($models as $model) {
            $this->afterDelete($model);
        }

        return success('批量删除成功', ['deleted' => $deleted]);
    }

    /**
     * 删除前回调（子类可重写）
     */
    protected function beforeDelete(mixed $model): void
    {
        // 子类实现
    }

    /**
     * 删除后回调（子类可重写）
     */
    protected function afterDelete(mixed $model): void
    {
        // 子类实现
    }

    // ==================== 导出相关 ====================

    /**
     * 导出数据
     */
    protected function export(Request $request)
    {
        $query = $this->buildListQuery($request);

        // 根据导出类型获取数据
        $type = $request->input('type', 'current');
        $prefix = $this->getExportFilenamePrefix();
        
        if ($type === 'current') {
            $page = (int) $request->input('page', 1);
            $pageSize = (int) $request->input('page_size', $this->getDefaultPageSize());
            $data = $query->skip(($page - 1) * $pageSize)->take($pageSize)->get();
            $filename = "{$prefix}_第{$page}页_" . date('YmdHis') . '.xlsx';
        } else {
            $data = $query->get();
            $filename = "{$prefix}_全部_" . date('YmdHis') . '.xlsx';
        }

        $columns = $this->getExportColumns();

        return Excel::download(new BaseExport($data, $columns), $filename);
    }

    // ==================== UI Schema ====================

    /**
     * 列表页 UI Schema（子类重写）
     */
    protected function listUi(): array
    {
        return success([]);
    }

    /**
     * 表单页 UI Schema（子类重写）
     */
    protected function formUi(): array
    {
        return success([]);
    }

    // ==================== 辅助方法 ====================

    /**
     * 查找模型或抛出错误
     */
    protected function findOrFail(int $id, array $with = []): mixed
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();
        
        if ($with) {
            $query->with($with);
        }
        
        $model = $query->find($id);

        if (!$model) {
            throw new \Lartrix\Exceptions\ApiException("{$this->getResourceName()}不存在", 40004);
        }

        return $model;
    }
}
