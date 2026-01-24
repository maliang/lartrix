<?php

namespace Lartrix\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'name',
        'title',
        'guard_name',
        'description',
        'status',
        'is_system',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'status' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * 序列化日期格式
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 查询启用的角色
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', true);
    }

    /**
     * 查询禁用的角色
     */
    public function scopeDisabled($query)
    {
        return $query->where('status', false);
    }

    /**
     * 查询系统内置角色
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 检查是否为系统内置角色
     */
    public function isSystemRole(): bool
    {
        return $this->is_system === true;
    }

    /**
     * 检查角色是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === true;
    }
}
