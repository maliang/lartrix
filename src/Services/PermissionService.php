<?php

namespace Lartrix\Services;

use Lartrix\Models\Permission;
use Lartrix\Models\Role;
use Lartrix\Models\AdminUser;

class PermissionService extends BaseService
{
    /**
     * 获取权限树（按模块分组）
     */
    public function getTreeByModule(): array
    {
        return Permission::getTreeByModule();
    }

    /**
     * 获取完整权限树
     */
    public function getTree(): array
    {
        return Permission::getTree();
    }

    /**
     * 获取用户的有效权限（排除禁用角色的权限）
     */
    public function getUserActivePermissions(AdminUser $user): array
    {
        return $user->getActivePermissions()->pluck('name')->toArray();
    }

    /**
     * 检查用户是否有指定权限（排除禁用角色）
     */
    public function userHasPermission(AdminUser $user, string $permission): bool
    {
        return $user->hasActivePermission($permission);
    }

    /**
     * 检查用户是否有任一指定权限（排除禁用角色）
     */
    public function userHasAnyPermission(AdminUser $user, array $permissions): bool
    {
        $activePermissions = $this->getUserActivePermissions($user);
        return !empty(array_intersect($permissions, $activePermissions));
    }

    /**
     * 检查用户是否有所有指定权限（排除禁用角色）
     */
    public function userHasAllPermissions(AdminUser $user, array $permissions): bool
    {
        $activePermissions = $this->getUserActivePermissions($user);
        return empty(array_diff($permissions, $activePermissions));
    }

    /**
     * 获取角色的权限列表
     */
    public function getRolePermissions(Role $role): array
    {
        return $role->permissions->pluck('name')->toArray();
    }

    /**
     * 同步角色权限
     */
    public function syncRolePermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);
    }
}
