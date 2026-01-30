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
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

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
     */
    public function canAccess(array $userPermissions): bool
    {
        // 如果菜单没有设置权限要求，则允许访问
        if (empty($this->permissions)) {
            return true;
        }

        // 检查用户是否拥有菜单要求的任一权限
        return !empty(array_intersect($this->permissions, $userPermissions));
    }
}
