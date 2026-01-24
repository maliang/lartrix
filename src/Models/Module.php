<?php

namespace Lartrix\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    /**
     * 表名
     */
    protected $table = 'modules';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'name',
        'title',
        'description',
        'version',
        'author',
        'website',
        'enabled',
        'config',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    /**
     * 查询启用的模块
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * 查询禁用的模块
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }

    /**
     * 检查模块是否启用
     */
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    /**
     * 启用模块
     */
    public function enable(): bool
    {
        $this->enabled = true;
        return $this->save();
    }

    /**
     * 禁用模块
     */
    public function disable(): bool
    {
        $this->enabled = false;
        return $this->save();
    }
}
