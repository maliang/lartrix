<?php

namespace Lartrix\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'parent_id',
        'name',
        'title',
        'guard_name',
        'module',
        'description',
        'sort',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'sort' => 'integer',
    ];

    /**
     * 序列化日期格式
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 获取父级权限
     */
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * 获取子级权限
     */
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id')->orderBy('sort');
    }

    /**
     * 递归获取所有子级权限
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * 按模块分组获取权限树
     */
    public static function getTreeByModule(): array
    {
        return static::query()
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->orderBy('sort')])
            ->orderBy('module')
            ->orderBy('sort')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    /**
     * 获取完整的权限树
     */
    public static function getTree(): array
    {
        return static::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort')
            ->get()
            ->toArray();
    }
}
