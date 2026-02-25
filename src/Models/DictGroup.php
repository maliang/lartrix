<?php

namespace Lartrix\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DictGroup extends Model
{
    /**
     * 表名
     */
    protected $table = 'dict_groups';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_system',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * 关联字典项
     */
    public function items(): HasMany
    {
        return $this->hasMany(DictItem::class, 'group_id')->orderBy('sort');
    }

    /**
     * 根据 code 获取分组
     */
    public static function findByCode(string $code): ?static
    {
        return static::where('code', $code)->first();
    }
}
