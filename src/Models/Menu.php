<?php

namespace Lartrix\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /**
     * 表名
     */
    protected $table = 'admin_menus';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'parent_id',
        'name',
        'path',
        'component',
        'redirect',
        'title',
        'icon',
        'order',
        'hide_in_menu',
        'keep_alive',
        'permissions',
        'use_json_renderer',
        'schema_source',
        'layout_type',
        'open_type',
        'href',
        'is_default_after_login',
        'fixed_index_in_tab',
        'requires_auth',
        'active_menu',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'hide_in_menu' => 'boolean',
        'keep_alive' => 'boolean',
        'use_json_renderer' => 'boolean',
        'is_default_after_login' => 'boolean',
        'fixed_index_in_tab' => 'integer',
        'requires_auth' => 'boolean',
        'permissions' => 'array',
        'order' => 'integer',
    ];

    /**
     * 序列化日期格式
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 获取父级菜单
     */
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * 获取子级菜单
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    /**
     * 递归获取所有子级菜单
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * 转换为 MenuRoute 格式（对应 trix 前端类型）
     * 
     * @param array|null $userPermissions 用户权限列表，用于过滤子菜单
     */
    public function toMenuRoute(?array $userPermissions = null): array
    {
        $route = [
            'name' => $this->name,
            'path' => $this->path,
        ];

        if ($this->component) {
            $route['component'] = $this->component;
        }
        if ($this->redirect) {
            $route['redirect'] = $this->redirect;
        }

        // 构建 meta 对象
        $meta = [];
        if ($this->title) $meta['title'] = $this->title;
        if ($this->icon) $meta['icon'] = $this->icon;
        if ($this->order) $meta['order'] = $this->order;
        if ($this->hide_in_menu) $meta['hideInMenu'] = true;
        if ($this->keep_alive) $meta['keepAlive'] = true;
        if ($this->permissions) $meta['permissions'] = $this->permissions;
        if ($this->use_json_renderer) $meta['useJsonRenderer'] = true;
        if ($this->schema_source) $meta['schemaSource'] = $this->schema_source;
        if ($this->layout_type) $meta['layoutType'] = $this->layout_type;
        if ($this->open_type) $meta['openType'] = $this->open_type;
        if ($this->href) $meta['href'] = $this->href;
        if ($this->is_default_after_login) $meta['isDefaultAfterLogin'] = true;
        if ($this->fixed_index_in_tab !== null) $meta['fixedIndexInTab'] = $this->fixed_index_in_tab;
        if ($this->requires_auth) $meta['requiresAuth'] = true;
        if ($this->active_menu) $meta['activeMenu'] = $this->active_menu;

        if (!empty($meta)) {
            $route['meta'] = $meta;
        }

        // 递归处理子菜单（支持 children 和 allChildren 两种关系）
        $childrenRelation = $this->relationLoaded('allChildren') ? 'allChildren' : 'children';
        if ($this->relationLoaded($childrenRelation) && $this->$childrenRelation->isNotEmpty()) {
            // 如果提供了用户权限，则过滤子菜单
            $children = $this->$childrenRelation;
            if ($userPermissions !== null) {
                $children = $children->filter(fn($child) => $child->canAccess($userPermissions));
            }
            if ($children->isNotEmpty()) {
                $route['children'] = $children->map(fn($child) => $child->toMenuRoute($userPermissions))->values()->toArray();
            }
        }

        return $route;
    }

    /**
     * 获取用户可访问的菜单树（MenuRoute 格式）
     */
    public static function getRoutesForUser($user): array
    {
        // 使用 getActivePermissions 方法，超级管理员会自动获得所有权限
        $userPermissions = $user->getActivePermissions()->pluck('name')->toArray();

        return static::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('order')
            ->get()
            ->filter(fn($menu) => $menu->canAccess($userPermissions))
            ->map(fn($menu) => $menu->toMenuRoute($userPermissions))
            ->values()
            ->toArray();
    }

    /**
     * 检查用户是否有权限访问此菜单
     * 
     * 过滤逻辑：
     * 1. 如果菜单设置了 permissions 字段，检查用户是否拥有其中任一权限
     * 2. 如果菜单没有设置 permissions，则检查用户是否拥有与菜单 name 同名的权限
     * 3. 如果都没有匹配，则允许访问（兼容没有权限控制的菜单）
     */
    public function canAccess(array $userPermissions): bool
    {
        // 如果菜单设置了权限要求，检查用户是否拥有其中任一权限
        if (!empty($this->permissions)) {
            return !empty(array_intersect($this->permissions, $userPermissions));
        }

        // 如果菜单名称在权限列表中存在，则需要用户拥有该权限
        // 这样可以通过菜单名称自动关联权限
        if (in_array($this->name, $userPermissions)) {
            return true;
        }

        // 检查是否存在以菜单名称开头的权限（如 user.list, user.create 等）
        // 如果存在，说明这是一个需要权限控制的菜单
        $menuNamePrefix = $this->name . '.';
        foreach ($userPermissions as $permission) {
            if (str_starts_with($permission, $menuNamePrefix)) {
                return true;
            }
        }

        // 检查权限表中是否存在与菜单名称相关的权限
        // 如果存在但用户没有，则不允许访问
        $permissionModel = config('lartrix.models.permission', \Lartrix\Models\Permission::class);
        $relatedPermissionExists = $permissionModel::query()
            ->where('name', $this->name)
            ->orWhere('name', 'like', $this->name . '.%')
            ->exists();

        if ($relatedPermissionExists) {
            return false;
        }

        // 没有相关权限控制，允许访问
        return true;
    }
}
