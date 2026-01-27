<?php

namespace Lartrix\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Lartrix\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

class AdminUser extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    /**
     * 表名
     */
    protected $table = 'admin_users';

    /**
     * 权限 guard 名称
     */
    protected string $guard_name = 'sanctum';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'name',
        'nick_name',
        'real_name',
        'email',
        'mobile',
        'password',
        'status',
        'avatar',
    ];

    /**
     * 隐藏的属性
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'status' => 'boolean',
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    /**
     * 序列化日期格式
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 检查用户是否为超级管理员
     */
    public function isSuperAdmin(): bool
    {
        $superAdminRole = config('lartrix.super_admin_role', 'super-admin');
        return $this->hasRole($superAdminRole);
    }

    /**
     * 获取用户的有效权限（排除禁用角色的权限）
     * 超级管理员返回所有权限
     */
    public function getActivePermissions(): \Illuminate\Support\Collection
    {
        // 超级管理员拥有所有权限
        if ($this->isSuperAdmin()) {
            return Permission::all();
        }

        return $this->roles()
            ->where('status', true)
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    /**
     * 检查用户是否有指定权限（排除禁用角色）
     * 超级管理员拥有所有权限
     */
    public function hasActivePermission(string $permission): bool
    {
        // 超级管理员拥有所有权限
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getActivePermissions()->contains('name', $permission);
    }

    /**
     * 检查用户状态是否启用
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }
}
