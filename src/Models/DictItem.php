<?php

namespace Lartrix\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictItem extends Model
{
    /**
     * 表名
     */
    protected $table = 'dict_items';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'group_id',
        'code',
        'label',
        'value',
        'sort',
        'is_enabled',
        'extra',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'sort' => 'integer',
        'is_enabled' => 'boolean',
        'extra' => 'array',
    ];

    /**
     * 关联分组
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(DictGroup::class, 'group_id');
    }

    /**
     * 只查询启用的项
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
